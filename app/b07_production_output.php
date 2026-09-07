<?php
/**
 * B07 - Production Output & Branching Engine
 * 
 * Supports multiple output paths from a single production batch.
 * Integrates with B03 (Audit) and B04 (Entity Links).
 */

require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/b04_entity_linkage.php';

/**
 * Register a new output for a production batch.
 * 
 * @param PDO $db
 * @param int $batchId
 * @param string $outputType (FRESH, FREEZE_DRY_INPUT, WASTE, etc.)
 * @param float $quantity
 * @param string $unit
 * @param string|null $notes
 * @param string|null $status (default: REGISTERED)
 * 
 * @return array ['id' => int, 'audit_id' => int|null]
 * @throws InvalidArgumentException
 */
function b07_register_output(
    PDO $db,
    int $batchId,
    string $outputType,
    float $quantity,
    string $unit,
    ?string $notes = null,
    ?string $status = 'REGISTERED'
): array {
    // Validatie input
    if ($quantity <= 0) throw new InvalidArgumentException("Quantity must be positive.");
    if (trim($unit) === '') throw new InvalidArgumentException("Unit cannot be empty.");
    
    // Haal batch info op
    $stmt = $db->prepare("SELECT * FROM production_batches WHERE id = :id");
    $stmt->execute([':id' => $batchId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) throw new InvalidArgumentException("Batch not found.");
    
    // Check of output_type valid is (database CHECK doet dit ook, maar fail fast is beter)
    $validTypes = ['FRESH', 'FREEZE_DRY_INPUT', 'FREEZE_DRIED_PRODUCT', 'WASTE', 'SAMPLE', 'OTHER'];
    if (!in_array($outputType, $validTypes)) {
        throw new InvalidArgumentException("Invalid output type: $outputType");
    }

    // Bereken totale allocatie
    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM production_outputs WHERE batch_id = :id");
    $stmt->execute([':id' => $batchId]);
    $totalAllocated = (float) $stmt->fetchColumn();
    
    // Validatie: als batch COMPLETED is, mag de som van outputs niet groter zijn dan actual_quantity
    if ($batch['status'] === 'COMPLETED' && $batch['actual_quantity'] !== null) {
        if (($totalAllocated + $quantity) > $batch['actual_quantity']) {
            throw new InvalidArgumentException(
                "Total output quantity (" . ($totalAllocated + $quantity) . " $unit) exceeds batch actual quantity (" . $batch['actual_quantity'] . " " . $batch['quantity_unit'] . ")."
            );
        }
    }

    $effectiveActor = null;
    if (function_exists('auth_current_user')) {
        $user = auth_current_user();
        if ($user && isset($user['id'])) $effectiveActor = (int)$user['id'];
    }

    $db->beginTransaction();
    try {
        // Insert output
        $stmt = $db->prepare("
            INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes, produced_at)
            VALUES (:batch, :type, :qty, :unit, :status, :notes, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':batch' => $batchId,
            ':type' => $outputType,
            ':qty' => $quantity,
            ':unit' => $unit,
            ':status' => $status,
            ':notes' => $notes
        ]);
        
        $outputId = (int) $db->lastInsertId();
        $auditId = null;

        // Audit Event
        if ($effectiveActor !== null) {
            $auditId = auditLog(
                $db,
                'B07_OUTPUT_REGISTERED',
                'production_outputs',
                $outputId,
                'CREATE',
                null,
                null,
                [
                    'batch_id' => $batchId,
                    'type' => $outputType,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'status' => $status
                ],
                'production_batches',
                $batchId
            );
        }

        $db->commit();
        return ['id' => $outputId, 'audit_id' => $auditId];

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Get all outputs for a batch.
 */
function b07_get_batch_outputs(PDO $db, int $batchId): array {
    $stmt = $db->prepare("SELECT * FROM production_outputs WHERE batch_id = :id ORDER BY produced_at DESC");
    $stmt->execute([':id' => $batchId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get allocation summary for a batch.
 */
function b07_get_allocation_summary(PDO $db, int $batchId): array {
    // Haal batch info
    $stmt = $db->prepare("SELECT * FROM production_batches WHERE id = :id");
    $stmt->execute([':id' => $batchId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) return [];

    // Som per type
    $stmt = $db->prepare("
        SELECT output_type, SUM(quantity) as total_qty, unit 
        FROM production_outputs 
        WHERE batch_id = :id 
        GROUP BY output_type, unit
    ");
    $stmt->execute([':id' => $batchId]);
    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAllocated = array_sum(array_column($breakdown, 'total_qty'));
    $remaining = ($batch['actual_quantity'] !== null) ? ($batch['actual_quantity'] - $totalAllocated) : null;

    return [
        'batch_code' => $batch['batch_code'],
        'actual_quantity' => $batch['actual_quantity'],
        'unit' => $batch['quantity_unit'],
        'allocated_total' => $totalAllocated,
        'remaining' => $remaining,
        'breakdown' => $breakdown
    ];
}
