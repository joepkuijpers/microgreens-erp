<?php
/**
 * B10: Quality Lab Engine
 */

/**
 * Registreert een lab-uitslag en bepaalt automatisch Pass/Fail.
 */
function b10_register_lab_result(PDO $db, string $sourceType, int $sourceId, string $testType, float $value, string $unit, ?float $limitMin = null, ?float $limitMax = null, ?int $technicianId = null): array {
    
    // Bepaal status
    $status = 'PASS';
    if ($limitMin !== null && $value < $limitMin) $status = 'FAIL';
    if ($limitMax !== null && $value > $limitMax) $status = 'FAIL';

    $insert = $db->prepare("
        INSERT INTO lab_results 
        (source_type, source_id, test_type, test_value, unit, limit_min, limit_max, result_status, lab_technician_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insert->execute([
        $sourceType, $sourceId, $testType, $value, $unit, $limitMin, $limitMax, $status, $technicianId
    ]);

    return [
        'id' => (int) $db->lastInsertId(),
        'status' => $status,
        'value' => $value,
        'limits' => "[$limitMin - $limitMax]"
    ];
}

/**
 * Checkt of een bron (proces/verpakking) volledig goedgekeurd is.
 */
function b10_is_quality_approved(PDO $db, string $sourceType, int $sourceId): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lab_results WHERE source_type = ? AND source_id = ? AND result_status = 'FAIL'");
    $stmt->execute([$sourceType, $sourceId]);
    return ($stmt->fetchColumn() == 0);
}
