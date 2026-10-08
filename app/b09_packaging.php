<?php
/**
 * B09: Packaging Engine
 * Creëert verpakkingen vanuit een Fresh Output of een Freeze-Dry Process.
 */

/**
 * Creëert een nieuwe verpakkingseenheid.
 * 
 * @param PDO $db Database connectie.
 * @param string $sourceType 'OUTPUT' (voor Fresh) of 'PROCESS' (voor Freeze-Dry).
 * @param int $sourceId ID van de output of het proces.
 * @param string $packageType Type verpakking (bijv. 'CLAMSHELL').
 * @param float $weightPerUnit Gewicht per stuk (gram).
 * @param int $quantity Aantal stuks.
 * @param string $labelCode Unieke labelcode/barcode.
 * @param string|null $bestBeforeDate Houdbaarheidsdatum (Y-m-d).
 * @param int|null $operatorId ID van de operator.
 * @return array ['id' => int, 'total_weight' => float]
 * @throws InvalidArgumentException Als bron niet bestaat of gewicht niet beschikbaar is.
 */
function b09_create_packaging(PDO $db, string $sourceType, int $sourceId, string $packageType, float $weightPerUnit, int $quantity, string $labelCode, ?string $bestBeforeDate = null, ?int $operatorId = null): array {
    
    if (!in_array($sourceType, ['OUTPUT', 'PROCESS'])) {
        throw new InvalidArgumentException("Ongeldig source_type. Gebruik 'OUTPUT' of 'PROCESS'.");
    }

    $totalWeight = $weightPerUnit * $quantity;
    $availableWeight = 0;
    $sourceExists = false;

    // 1. Controleer beschikbaar gewicht op basis van de bron
    if ($sourceType === 'OUTPUT') {
        // Check production_outputs
        $stmt = $db->prepare("SELECT quantity, unit FROM production_outputs WHERE id = ?");
        $stmt->execute([$sourceId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $sourceExists = true;
            $availableWeight = (float) $res['quantity'];
        }
    } elseif ($sourceType === 'PROCESS') {
        // Check freeze_dry_processes (gebruik final_weight_g)
        $stmt = $db->prepare("SELECT final_weight_g FROM freeze_dry_processes WHERE id = ? AND status = 'COMPLETED'");
        $stmt->execute([$sourceId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $sourceExists = true;
            $availableWeight = (float) $res['final_weight_g'];
        } elseif ($stmt->fetchColumn() === null) {
             // Check of het proces bestaat maar nog niet COMPLETED is
             $stmt2 = $db->prepare("SELECT id FROM freeze_dry_processes WHERE id = ?");
             $stmt2->execute([$sourceId]);
             if ($stmt2->fetch()) {
                 throw new InvalidArgumentException("Proces ID $sourceId is nog niet voltooid (status niet COMPLETED). Kan nog niet verpakt worden.");
             }
        }
    }

    if (!$sourceExists) {
        throw new InvalidArgumentException("Bron (Type: $sourceType, ID: $sourceId) niet gevonden.");
    }

    // 2. Validatie: Is er genoeg gewicht?
    // Note: In een volledige implementatie zouden we bijhouden hoeveel er al verpakt is van deze bron.
    // Voor nu doen we een simpele check of het totaalgewicht niet groter is dan de bron (versimpeld).
    // Een betere check zou zijn: SUM(verpakt) + nieuw <= bron_gewicht.
    
    $stmt = $db->prepare("SELECT SUM(total_weight_g) FROM packaging_units WHERE source_type = ? AND source_id = ?");
    $stmt->execute([$sourceType, $sourceId]);
    $alreadyPacked = (float) ($stmt->fetchColumn() ?: 0);

    if (($alreadyPacked + $totalWeight) > $availableWeight) {
        throw new InvalidArgumentException("Niet genoeg gewicht beschikbaar. Beschikbaar: {$availableWeight}g, Al verpakt: {$alreadyPacked}g, Gevraagd: {$totalWeight}g.");
    }

    // 3. Insert de verpakking
    $insert = $db->prepare("
        INSERT INTO packaging_units 
        (source_type, source_id, package_type, weight_per_unit_g, quantity_units, total_weight_g, label_code, best_before_date, operator_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insert->execute([
        $sourceType,
        $sourceId,
        $packageType,
        $weightPerUnit,
        $quantity,
        $totalWeight,
        $labelCode,
        $bestBeforeDate,
        $operatorId
    ]);

    return [
        'id' => (int) $db->lastInsertId(),
        'label_code' => $labelCode,
        'total_weight_g' => $totalWeight,
        'units_created' => $quantity
    ];
}

/**
 * Haalt verpakkingsgeschiedenis op voor een bron.
 */
function b09_get_packaging_history(PDO $db, string $sourceType, int $sourceId): array {
    $stmt = $db->prepare("SELECT * FROM packaging_units WHERE source_type = ? AND source_id = ? ORDER BY packed_at DESC");
    $stmt->execute([$sourceType, $sourceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
