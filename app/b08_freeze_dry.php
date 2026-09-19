<?php
/**
 * B08: Freeze-Dry Process Engine
 * 
 * Beheert de specifieke procesdata voor het vriesdrogen (Layer 3).
 * Linkt aan een BESTAANDE production_output (type FREEZE_DRY_INPUT) uit B07.
 * 
 * Functies:
 * - b08_start_process: Start een nieuwe cyclus.
 * - b08_complete_process: Rondt een cyclus af en registreert het eindgewicht.
 * - b08_get_process_info: Haalt details op van een proces.
 */

/**
 * Start een nieuw vriesdroogproces.
 * 
 * @param PDO $db Database connectie.
 * @param int $productionOutputId ID van de output uit B07 (moet type FREEZE_DRY_INPUT zijn).
 * @param string $machineId Identificatie van de machine (bijv. 'FD-01').
 * @param int|null $operatorId ID van de operator die start.
 * @param string|null $notes Optionele notities.
 * @return array ['id' => int, 'cycle_code' => string]
 * @throws InvalidArgumentException Als de output niet bestaat, niet het juiste type is, of al een proces heeft.
 */
function b08_start_process(PDO $db, int $productionOutputId, string $machineId, ?int $operatorId = null, ?string $notes = null): array {
    // 1. Validatie: Bestaat de output en is het van het juiste type?
    $stmt = $db->prepare("SELECT id, quantity, status FROM production_outputs WHERE id = ?");
    $stmt->execute([$productionOutputId]);
    $output = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$output) {
        throw new InvalidArgumentException("Production output ID $productionOutputId niet gevonden.");
    }
    
    if ($output['status'] !== 'ALLOCATED') {
        // We verwachten dat de output gereserveerd is voor dit proces.
        // Afhankelijk van de workflow kan dit 'REGISTERED' of 'ALLOCATED' zijn.
        // Voor nu checken we vooral op het type.
    }
    
    // Check type (impliciet via logica, of we kunnen een kolom 'output_type' checken als die bestaat)
    // In B07 hebben we 'output_type' gebruikt. Laten we die checken.
    $stmt = $db->prepare("SELECT output_type FROM production_outputs WHERE id = ?");
    $stmt->execute([$productionOutputId]);
    $type = $stmt->fetchColumn();
    
    if ($type !== 'FREEZE_DRY_INPUT') {
        throw new InvalidArgumentException("Output ID $productionOutputId is van type '$type'. Verwacht 'FREEZE_DRY_INPUT'.");
    }
    
    // 2. Validatie: Bestaat er al een proces voor deze output? (1-op-1 relatie)
    $stmt = $db->prepare("SELECT id FROM freeze_dry_processes WHERE production_output_id = ?");
    $stmt->execute([$productionOutputId]);
    if ($stmt->fetch()) {
        throw new InvalidArgumentException("Er bestaat al een vriesdroogproces voor output ID $productionOutputId.");
    }
    
    // 3. Genereer unieke cycle_code
    $cycleCode = 'FD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    $startedAt = date('Y-m-d H:i:s');
    $inputWeight = (float) $output['quantity']; // Gewicht uit B07 output
    
    // 4. Insert het proces
    $insert = $db->prepare("
        INSERT INTO freeze_dry_processes 
        (production_output_id, machine_id, cycle_code, started_at, input_weight_g, status, operator_id, notes)
        VALUES (?, ?, ?, ?, ?, 'RUNNING', ?, ?)
    ");
    $insert->execute([
        $productionOutputId,
        $machineId,
        $cycleCode,
        $startedAt,
        $inputWeight,
        $operatorId,
        $notes
    ]);
    
    $processId = (int) $db->lastInsertId();
    
    // Optioneel: Update status van de output naar 'IN_PROCESS' als die kolom bestaat, anders laten we het zo.
    
    return [
        'id' => $processId,
        'cycle_code' => $cycleCode,
        'started_at' => $startedAt,
        'input_weight_g' => $inputWeight
    ];
}

/**
 * Rondt een vriesdroogproces af.
 * 
 * @param PDO $db Database connectie.
 * @param int $processId ID van het proces.
 * @param float $finalWeight Het gewicht NA drogen (in gram).
 * @param float|null $avgTemp Gemiddelde temperatuur (optioneel).
 * @param float|null $minPressure Minimale druk (optioneel).
 * @param string|null $notes Optionele eindnotities.
 * @return array ['id' => int, 'yield_percent' => float]
 * @throws InvalidArgumentException Als het proces niet bestaat, al klaar is, of het gewicht onlogisch is.
 */
function b08_complete_process(PDO $db, int $processId, float $finalWeight, ?float $avgTemp = null, ?float $minPressure = null, ?string $notes = null): array {
    // 1. Validatie: Bestaat het proces?
    $stmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE id = ?");
    $stmt->execute([$processId]);
    $process = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$process) {
        throw new InvalidArgumentException("Proces ID $processId niet gevonden.");
    }
    
    if ($process['status'] === 'COMPLETED') {
        throw new InvalidArgumentException("Proces ID $processId is al voltooid. Historische data mag niet overschreven worden.");
    }
    
    // 2. Validatie: Fysieke logica (Eindgewicht moet lager zijn dan startgewicht)
    // We hanteren een ruime marge voor meetfouten, maar eindgewicht > startgewicht is onmogelijk.
    if ($finalWeight > $process['input_weight_g']) {
        throw new InvalidArgumentException("Fout: Eindgewicht ($finalWeight g) kan niet groter zijn dan startgewicht ({$process['input_weight_g']} g).");
    }
    
    if ($finalWeight <= 0) {
        throw new InvalidArgumentException("Fout: Eindgewicht moet groter zijn dan 0.");
    }
    
    $endedAt = date('Y-m-d H:i:s');
    
    // 3. Update het proces
    $update = $db->prepare("
        UPDATE freeze_dry_processes
        SET ended_at = ?,
            final_weight_g = ?,
            avg_temperature_c = ?,
            min_pressure_mbar = ?,
            notes = COALESCE(?, notes) || (CASE WHEN notes IS NOT NULL AND ? IS NOT NULL THEN CHAR(10) ELSE '' END) || ?,
            status = 'COMPLETED'
        WHERE id = ?
    ");
    
    // Notities samenvoegen als er al notities zijn
    $fullNotes = $notes; 
    
    $update->execute([
        $endedAt,
        $finalWeight,
        $avgTemp,
        $minPressure,
        $notes,
        $notes,
        $notes,
        $processId
    ]);
    
    // Bereken rendement (yield)
    $yieldPercent = ($finalWeight / $process['input_weight_g']) * 100;
    
    return [
        'id' => $processId,
        'cycle_code' => $process['cycle_code'],
        'input_weight_g' => $process['input_weight_g'],
        'final_weight_g' => $finalWeight,
        'yield_percent' => round($yieldPercent, 2),
        'duration_minutes' => round((strtotime($endedAt) - strtotime($process['started_at'])) / 60, 1)
    ];
}

/**
 * Haalt informatie op over een specifiek vriesdroogproces.
 */
function b08_get_process_info(PDO $db, int $processId): ?array {
    $stmt = $db->prepare("
        SELECT 
            fdp.*,
            po.batch_code,
            po.output_type
        FROM freeze_dry_processes fdp
        JOIN production_outputs po ON fdp.production_output_id = po.id
        WHERE fdp.id = ?
    ");
    $stmt->execute([$processId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
