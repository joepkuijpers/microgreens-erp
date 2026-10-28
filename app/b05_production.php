<?php

declare(strict_types=1);

/**
 * B05 Production Engine
 *
 * Creates production batches.
 */

function b05_create_production_batch(
    PDO $db,
    string $batchCode,
    string $status,
    ?float $plannedQuantity = null,
    ?string $quantityUnit = null,
    ?string $startedAt = null,
    ?string $notes = null
): int {
    $batchCode = trim($batchCode);
    $status = trim($status);

    if ($batchCode === '') {
        throw new InvalidArgumentException(
            'batch_code is required'
        );
    }

    if ($status === '') {
        throw new InvalidArgumentException(
            'status is required'
        );
    }

    if ($status !== 'PLANNED') {
        throw new InvalidArgumentException(
            'production batch must be created with status PLANNED'
        );
    }

    if ($startedAt !== null && trim($startedAt) !== '') {
        throw new InvalidArgumentException(
            'started_at must be null when creating a PLANNED production batch'
        );
    }

    if (
        $plannedQuantity !== null &&
        $plannedQuantity < 0
    ) {
        throw new InvalidArgumentException(
            'planned_quantity cannot be negative'
        );
    }

    if (
        $plannedQuantity !== null &&
        ($quantityUnit === null || trim($quantityUnit) === '')
    ) {
        throw new InvalidArgumentException(
            'quantity_unit is required when planned_quantity is supplied'
        );
    }

    $stmt = $db->prepare('
        INSERT INTO production_batches (
            batch_code,
            status,
            planned_quantity,
            quantity_unit,
            started_at,
            notes
        )
        VALUES (
            :batch_code,
            :status,
            :planned_quantity,
            :quantity_unit,
            :started_at,
            :notes
        )
    ');

    $stmt->execute([
        ':batch_code' => $batchCode,
        ':status' => $status,
        ':planned_quantity' => $plannedQuantity,
        ':quantity_unit' => $quantityUnit,
        ':started_at' => $startedAt,
        ':notes' => $notes
    ]);

    return (int)$db->lastInsertId();
}

function b05_start_production_batch(PDO $db, int $batchId): void
{
    if ($batchId <= 0) {
        throw new InvalidArgumentException(
            'batch_id must be positive'
        );
    }

    $stmt = $db->prepare("
        UPDATE production_batches
        SET status = 'STARTED',
            started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
        WHERE id = ?
          AND status = 'PLANNED'
    ");

    $stmt->execute([$batchId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'production batch cannot be started'
        );
    }
}

function b05_complete_production_batch(
    PDO $db,
    int $batchId,
    float $actualQuantity
): void {
    if ($batchId <= 0) {
        throw new InvalidArgumentException(
            'batch_id must be positive'
        );
    }

    if ($actualQuantity < 0) {
        throw new InvalidArgumentException(
            'actual_quantity cannot be negative'
        );
    }

    $unitStmt = $db->prepare("
        SELECT quantity_unit
        FROM production_batches
        WHERE id = ?
    ");

    $unitStmt->execute([$batchId]);

    $quantityUnit = $unitStmt->fetchColumn();

    if ($quantityUnit === false) {
        throw new RuntimeException(
            'production batch does not exist'
        );
    }

    if ($quantityUnit === null || trim((string)$quantityUnit) === '') {
        throw new RuntimeException(
            'quantity_unit is required before completion'
        );
    }

    $stmt = $db->prepare("
        UPDATE production_batches
        SET status = 'COMPLETED',
            actual_quantity = ?,
            completed_at = CURRENT_TIMESTAMP
        WHERE id = ?
          AND status = 'STARTED'
    ");

    $stmt->execute([
        $actualQuantity,
        $batchId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'production batch cannot be completed'
        );
    }
}

function b05_link_production_batch(
    PDO $db,
    int $productionBatchId,
    string $targetEntityType,
    int $targetEntityId,
    string $relationshipType
): int {
    if ($productionBatchId <= 0) {
        throw new InvalidArgumentException(
            'production_batch_id must be positive'
        );
    }

    require_once __DIR__ . '/b04_entity_linkage.php';

    return b04_create_entity_link(
        $db,
        'production_batch',
        $productionBatchId,
        $relationshipType,
        $targetEntityType,
        $targetEntityId
    );
}

function b05_get_production_batch(PDO $db, int $batchId): array
{
    if ($batchId <= 0) {
        throw new InvalidArgumentException(
            'batch_id must be positive'
        );
    }

    $stmt = $db->prepare("
        SELECT
            id,
            batch_code,
            status,
            planned_quantity,
            actual_quantity,
            quantity_unit,
            started_at,
            completed_at,
            notes,
            created_at
        FROM production_batches
        WHERE id = ?
    ");

    $stmt->execute([$batchId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        throw new RuntimeException(
            'production batch does not exist'
        );
    }

    return $row;
}

function b05_get_production_batch_by_code(
    PDO $db,
    string $batchCode
): array {
    $batchCode = trim($batchCode);

    if ($batchCode === '') {
        throw new InvalidArgumentException(
            'batch_code is required'
        );
    }

    $stmt = $db->prepare("
        SELECT
            id,
            batch_code,
            status,
            planned_quantity,
            actual_quantity,
            quantity_unit,
            started_at,
            completed_at,
            notes,
            created_at
        FROM production_batches
        WHERE batch_code = ?
    ");

    $stmt->execute([$batchCode]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        throw new RuntimeException(
            'production batch does not exist'
        );
    }

    return $row;
}
