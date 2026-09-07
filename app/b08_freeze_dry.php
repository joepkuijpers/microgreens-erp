<?php
/**
 * B08 - Freeze-Dry Process Engine
 * 
 * Manages specific freeze-drying cycles linked to production outputs.
 */

require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * Start a new freeze-dry cycle for a specific output.
 * 
 * @param PDO $db
 * @param int $outputId ID of the production_output (must be FREEZE_DRY_INPUT)
 * @param string $machineIdentifier
 * @param string|null $cycleCode
 * @param float $inputWeight Weight of the input material
 * @param float|null $targetPressure
 * @param float|null $targetTempC
 * @param string|null $notes
 * 
 * @return int The new process ID
 * @throws InvalidArgumentException
 */
function b08_start_freeze_dry_cycle(
    PDO $db,
    int $outputId,
    string $machineIdentifier,
    ?string $cycleCode = null,
    ?float $inputWeight = null,
    ?float $targetPressure = null,
    ?float $targetTempC = null,
    ?string $notes = null
): int {
    // Validatie
    if (trim($machineIdentifier) === '') throw new InvalidArgumentException("Machine ID required.");
    
    // Haal output info op
    $stmt = $db->prepare("SELECT * FROM production_outputs WHERE id = :id");
    $stmt->execute([':id' => $outputId]);
    $output = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$output) throw new InvalidArgumentException("Output not found.");
    if ($output['output_type'] !== 'FREEZE_DRY_INPUT') {
        throw new InvalidArgumentException("Output must be of type FREEZE_DRY_INPUT, got: " . $output['output_type']);
    }
    
    // Check of er niet al een proces aan deze output hangt (UNIQUE constraint)
    $stmt = $db->prepare("SELECT id FROM freeze_dry_processes WHERE output_id = :id");
    $stmt->execute([':id' => $outputId]);
    if ($stmt->fetch()) {
        throw new InvalidArgumentException("A freeze-dry process already exists for this output.");
    }

    // Gebruik output quantity als inputWeight als die niet is meegegeven
    $weight = $inputWeight ?? $output['quantity'];
    if ($weight <= 0) throw new InvalidArgumentException("Input weight must be positive.");

    $effectiveActor = null;
    if (function_exists('auth_current_user')) {
        $user = auth_current_user();
        if ($user && isset($user['id'])) $effectiveActor = (int)$user['id'];
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO freeze_dry_processes 
            (output_id, machine_identifier, cycle_code, started_at, input_weight, target_pressure, target_temp_c, notes)
            VALUES (:output, :machine, :cycle, datetime('now'), :weight, :pressure, :temp, :notes)
        ");
        
        $stmt->execute([
            ':output' => $outputId,
            ':machine' => $machineIdentifier,
            ':cycle' => $cycleCode,
            ':weight' => $weight,
            ':pressure' => $targetPressure,
            ':temp' => $targetTempC,
            ':notes' => $notes
        ]);
        
        $processId = (int) $db->lastInsertId();

        // Update output status naar 'PROCESSED'
        $upd = $db->prepare("UPDATE production_outputs SET status = 'PROCESSED' WHERE id = :id");
        $upd->execute([':id' => $outputId]);

        // Audit
        if ($effectiveActor) {
            auditLog($db, 'B08_FREEZE_DRY_START', 'freeze_dry_processes', $processId, 'CREATE', null, null,
                ['output_id' => $outputId, 'machine' => $machineIdentifier, 'input_weight' => $weight],
                'production_outputs', $outputId);
        }

        $db->commit();
        return $processId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Complete a freeze-dry cycle and record results.
 * 
 * @param PDO $db
 * @param int $processId
 * @param float $outputWeight Final dried weight
 * @param string|null $notes
 * 
 * @return array ['id' => int, 'yield_percent' => float]
 */
function b08_complete_freeze_dry_cycle(
    PDO $db,
    int $processId,
    float $outputWeight,
    ?string $notes = null
): array {
    if ($outputWeight <= 0) throw new InvalidArgumentException("Output weight must be positive.");
    
    $db->beginTransaction();
    try {
        // Haal proces op
        $stmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE id = :id");
        $stmt->execute([':id' => $processId]);
        $process = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$process) throw new InvalidArgumentException("Process not found.");
        if ($process['completed_at'] !== null) {
            throw new InvalidArgumentException("Process is already completed.");
        }

        // Bereken yield
        $yield = ($outputWeight / $process['input_weight']) * 100;

        // Update proces
        $stmt = $db->prepare("
            UPDATE freeze_dry_processes 
            SET completed_at = datetime('now'), output_weight = :weight, yield_percent = :yield, notes = CASE WHEN :notes IS NOT NULL THEN notes || CHAR(10) || :notes ELSE notes END
            WHERE id = :id
        ");
        $stmt->execute([
            ':weight' => $outputWeight,
            ':yield' => $yield,
            ':notes' => $notes,
            ':id' => $processId
        ]);

        // Update output naar 'STORED' of 'COMPLETED' (laten we 'STORED' doen voor gedroogd product)
        $upd = $db->prepare("UPDATE production_outputs SET status = 'STORED' WHERE id = :id");
        $upd->execute([':id' => $process['output_id']]);

        // Audit
        $effectiveActor = null;
        if (function_exists('auth_current_user')) {
            $user = auth_current_user();
            if ($user && isset($user['id'])) $effectiveActor = (int)$user['id'];
        }
        
        if ($effectiveActor) {
            auditLog($db, 'B08_FREEZE_DRY_COMPLETE', 'freeze_dry_processes', $processId, 'UPDATE', null,
                ['status' => 'running'], ['status' => 'completed', 'output_weight' => $outputWeight, 'yield' => $yield]);
        }

        $db->commit();
        return ['id' => $processId, 'yield_percent' => $yield];

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Get process details.
 */
function b08_get_process(PDO $db, int $processId): ?array {
    $stmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE id = :id");
    $stmt->execute([':id' => $processId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
