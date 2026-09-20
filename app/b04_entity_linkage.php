<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/audit.php';
/*
 * B03 AUDIT INTEGRATION
 *
 * B04 creates historical traceability links.
 * Every successfully persisted B04 link must therefore also
 * produce a B03 audit event.
 */
/**
 * B04 Entity Linkage Engine
 *
 * Purpose:
 * - register traceability relationships between existing ERP entities
 * - preserve historical links
 * - attribute manually created links to an ERP actor
 * - validate linkage data before persistence
 * - never delete historical linkage
 */
function b04_create_entity_link(
    PDO $db,
    string $sourceEntityType,
    int $sourceEntityId,
    string $relationshipType,
    string $targetEntityType,
    int $targetEntityId,
    ?float $quantity = null,
    ?string $quantityUnit = null,
    ?int $actorUserId = null,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $notes = null,
    ?string $eventTime = null
): int {
    $sourceEntityType = trim($sourceEntityType);
    $relationshipType = trim($relationshipType);
    $targetEntityType = trim($targetEntityType);
    if ($sourceEntityType === '') {
        throw new InvalidArgumentException(
            'source_entity_type is required'
        );
    }
    if ($sourceEntityId <= 0) {
        throw new InvalidArgumentException(
            'source_entity_id must be positive'
        );
    }
    if ($relationshipType === '') {
        throw new InvalidArgumentException(
            'relationship_type is required'
        );
    }
    if ($targetEntityType === '') {
        throw new InvalidArgumentException(
            'target_entity_type is required'
        );
    }
    if ($targetEntityId <= 0) {
        throw new InvalidArgumentException(
            'target_entity_id must be positive'
        );
    }
    if ($quantity !== null && $quantity < 0) {
        throw new InvalidArgumentException(
            'quantity cannot be negative'
        );
    }
    if ($quantity !== null && trim((string)$quantityUnit) === '') {
        throw new InvalidArgumentException(
            'quantity_unit is required when quantity is supplied'
        );
    }
    if ($referenceId !== null) {
        if ($referenceId <= 0) {
            throw new InvalidArgumentException(
                'reference_id must be positive'
            );
        }
        if ($referenceType === null || trim($referenceType) === '') {
            throw new InvalidArgumentException(
                'reference_type is required when reference_id is supplied'
            );
        }
    }
    $eventTime = $eventTime !== null
        ? trim($eventTime)
        : null;
    if ($eventTime === '') {
        $eventTime = null;
    }
    /*
     * Event time is deliberately supplied separately from created_at.
     *
     * event_time = when the relationship actually occurred
     * created_at = when ERP registered the linkage
     *
     * Historical reconstruction therefore remains possible.
     */
    if ($eventTime === null) {
        $sql = "
            INSERT INTO entity_links (
                source_entity_type,
                source_entity_id,
                relationship_type,
                target_entity_type,
                target_entity_id,
                quantity,
                quantity_unit,
                actor_user_id,
                reference_type,
                reference_id,
                notes
            )
            VALUES (
                :source_entity_type,
                :source_entity_id,
                :relationship_type,
                :target_entity_type,
                :target_entity_id,
                :quantity,
                :quantity_unit,
                :actor_user_id,
                :reference_type,
                :reference_id,
                :notes
            )
        ";
    } else {
        $sql = "
            INSERT INTO entity_links (
                source_entity_type,
                source_entity_id,
                relationship_type,
                target_entity_type,
                target_entity_id,
                quantity,
                quantity_unit,
                event_time,
                actor_user_id,
                reference_type,
                reference_id,
                notes
            )
            VALUES (
                :source_entity_type,
                :source_entity_id,
                :relationship_type,
                :target_entity_type,
                :target_entity_id,
                :quantity,
                :quantity_unit,
                :event_time,
                :actor_user_id,
                :reference_type,
                :reference_id,
                :notes
            )
        ";
    }
    $startedTransaction = false;
    try {
        /*
         * B04 -> B03 must be atomic.
         *
         * If B04 persistence succeeds but B03 audit persistence fails,
         * the B04 entity link must NOT remain in the database.
         *
         * Respect an already-active outer transaction.
         */
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $startedTransaction = true;
        }
        $stmt = $db->prepare($sql);
        $params = [
            ':source_entity_type' => $sourceEntityType,
            ':source_entity_id' => $sourceEntityId,
            ':relationship_type' => $relationshipType,
            ':target_entity_type' => $targetEntityType,
            ':target_entity_id' => $targetEntityId,
            ':quantity' => $quantity,
            ':quantity_unit' => $quantityUnit,
            ':actor_user_id' => $actorUserId,
            ':reference_type' => $referenceType,
            ':reference_id' => $referenceId,
            ':notes' => $notes
        ];
        if ($eventTime !== null) {
            $params[':event_time'] = $eventTime;
        }
        $stmt->execute($params);
        $entityLinkId = (int)$db->lastInsertId();
        /*
         * B03 integration:
         * The entity link itself is immutable historical data.
         * B03 receives a separate immutable audit event describing
         * the registration of that link.
         *
         * The audit event references the newly created entity_link.
         */
        auditLog(
            $db,
            'entity_link',
            'entity_link',
            $entityLinkId,
            'create',
            null,
            null,
            [
                'source_entity_type' => $sourceEntityType,
                'source_entity_id' => $sourceEntityId,
                'relationship_type' => $relationshipType,
                'target_entity_type' => $targetEntityType,
                'target_entity_id' => $targetEntityId,
                'quantity' => $quantity,
                'quantity_unit' => $quantityUnit,
                'event_time' => $eventTime,
                'actor_user_id' => $actorUserId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes
            ],
            'entity_link',
            $entityLinkId
        );
        if ($startedTransaction) {
            $db->commit();
        }
        return $entityLinkId;
    } catch (Throwable $e) {
        if ($startedTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}
/**
 * Return all links originating from an entity.
 */
function b04_get_outgoing_links(
    PDO $db,
    string $entityType,
    int $entityId
): array {
    $stmt = $db->prepare("
        SELECT *
        FROM entity_links
        WHERE source_entity_type = ?
          AND source_entity_id = ?
        ORDER BY event_time, id
    ");
    $stmt->execute([
        trim($entityType),
        $entityId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Return all links terminating at an entity.
 */
function b04_get_incoming_links(
    PDO $db,
    string $entityType,
    int $entityId
): array {
    $stmt = $db->prepare("
        SELECT *
        FROM entity_links
        WHERE target_entity_type = ?
          AND target_entity_id = ?
        ORDER BY event_time, id
    ");
    $stmt->execute([
        trim($entityType),
        $entityId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Return both directions of the direct entity relationship.
 */
function b04_get_entity_links(
    PDO $db,
    string $entityType,
    int $entityId
): array {
    return [
        'outgoing' => b04_get_outgoing_links(
            $db,
            $entityType,
            $entityId
        ),
        'incoming' => b04_get_incoming_links(
            $db,
            $entityType,
            $entityId
        )
    ];
}
/**
 * Historical linkage is immutable.
 *
 * There is intentionally no update/delete function here.
 * A changed relationship must be represented by a new event/link,
 * never by rewriting history.
 */
function b04_count_entity_links(PDO $db): int
{
    return (int)$db
        ->query("SELECT COUNT(*) FROM entity_links")
        ->fetchColumn();
}
