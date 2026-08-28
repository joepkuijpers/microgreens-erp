<?php

/**
 * Write a business audit event to the ERP audit trail.
 *
 * Audit events are immutable historical records.
 * The helper captures the currently authenticated ERP user
 * automatically when available.
 *
 * @param PDO         $db
 * @param string      $eventType
 * @param string      $entityType
 * @param int         $entityId
 * @param string      $action
 * @param string|null $reason
 * @param array|null  $beforeData
 * @param array|null  $afterData
 * @param string|null $referenceType
 * @param int|null    $referenceId
 *
 * @return int Inserted audit event ID.
 */
function auditLog(
    PDO $db,
    string $eventType,
    string $entityType,
    int $entityId,
    string $action,
    ?string $reason = null,
    ?array $beforeData = null,
    ?array $afterData = null,
    ?string $referenceType = null,
    ?int $referenceId = null
): int {
    if ($entityId <= 0) {
        throw new InvalidArgumentException(
            'Audit entity ID must be greater than zero.'
        );
    }

    $eventType = trim($eventType);
    $entityType = trim($entityType);
    $action = trim($action);

    if ($eventType === '') {
        throw new InvalidArgumentException(
            'Audit event type must not be blank.'
        );
    }

    if ($entityType === '') {
        throw new InvalidArgumentException(
            'Audit entity type must not be blank.'
        );
    }

    if ($action === '') {
        throw new InvalidArgumentException(
            'Audit action must not be blank.'
        );
    }

    if ($reason !== null) {
        $reason = trim($reason);

        if ($reason === '') {
            $reason = null;
        }
    }

    $referenceType = $referenceType !== null
        ? trim($referenceType)
        : null;

    if ($referenceType === '') {
        $referenceType = null;
    }

    if ($referenceId !== null && $referenceId <= 0) {
        throw new InvalidArgumentException(
            'Audit reference ID must be greater than zero.'
        );
    }

    if ($referenceId !== null && $referenceType === null) {
        throw new InvalidArgumentException(
            'Audit reference type is required when reference ID is provided.'
        );
    }

    $beforeJson = $beforeData !== null
        ? json_encode(
            $beforeData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )
        : null;

    $afterJson = $afterData !== null
        ? json_encode(
            $afterData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )
        : null;

    $actorUserId = null;

    if (function_exists('auth_current_user')) {
        $currentUser = auth_current_user();

        if ($currentUser !== null && isset($currentUser['id'])) {
            $actorUserId = (int)$currentUser['id'];

            if ($actorUserId <= 0) {
                $actorUserId = null;
            }
        }
    }

    $stmt = $db->prepare("
        INSERT INTO audit_events (
            actor_user_id,
            event_type,
            entity_type,
            entity_id,
            action,
            reason,
            before_data,
            after_data,
            reference_type,
            reference_id
        )
        VALUES (
            :actor_user_id,
            :event_type,
            :entity_type,
            :entity_id,
            :action,
            :reason,
            :before_data,
            :after_data,
            :reference_type,
            :reference_id
        )
    ");

    $stmt->execute([
        ':actor_user_id' => $actorUserId,
        ':event_type' => $eventType,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':action' => $action,
        ':reason' => $reason,
        ':before_data' => $beforeJson,
        ':after_data' => $afterJson,
        ':reference_type' => $referenceType,
        ':reference_id' => $referenceId,
    ]);

    return (int)$db->lastInsertId();
}
