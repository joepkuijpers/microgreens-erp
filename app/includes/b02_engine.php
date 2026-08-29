<?php

require_once __DIR__ . '/audit.php';

function b02AllowedTransitions(): array
{
    return [
        'DETECTED' => ['CONTEXT_LOADED'],
        'CONTEXT_LOADED' => ['REVIEWED'],
        'REVIEWED' => ['CORRECTED'],
        'CORRECTED' => ['VERIFIED'],
        'VERIFIED' => ['RESOLVED'],
        'RESOLVED' => [],
    ];
}

function b02CanTransition(
    string $from,
    string $to
): bool {
    $rules = b02AllowedTransitions();

    return in_array(
        $to,
        $rules[$from] ?? [],
        true
    );
}

function b02Transition(
    PDO $db,
    int $findingId,
    string $to,
    ?string $reason = null
): void {
    if ($findingId <= 0) {
        throw new InvalidArgumentException(
            'B02 finding ID must be greater than zero.'
        );
    }

    $to = trim($to);

    if ($to === '') {
        throw new InvalidArgumentException(
            'B02 target status must not be blank.'
        );
    }

    /*
     * B02 lifecycle transition + B03 audit must be atomic.
     *
     * If the finding status is changed but the audit event cannot
     * be persisted, the complete transition must be rolled back.
     *
     * Respect an already-active outer transaction.
     */
    $startedTransaction = false;

    try {
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $startedTransaction = true;
        }

        $stmt = $db->prepare("
            SELECT
                id,
                entity_type,
                entity_id,
                status,
                description
            FROM b02_findings
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $findingId,
        ]);

        $finding = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$finding) {
            throw new RuntimeException(
                'B02 finding not found.'
            );
        }

        $from = $finding['status'];

        if (!b02CanTransition($from, $to)) {
            throw new RuntimeException(
                "Invalid B02 lifecycle transition: $from -> $to"
            );
        }

        $beforeData = [
            'id' => (int)$finding['id'],
            'entity_type' => $finding['entity_type'],
            'entity_id' => (int) $finding['entity_id'],
            'status' => $finding['status'],
            'description' => $finding['description'],
        ];

        $update = $db->prepare("
            UPDATE b02_findings
            SET
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND status = :current_status
        ");

        $update->execute([
            ':status' => $to,
            ':id' => $findingId,
            ':current_status' => $from,
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException(
                'B02 lifecycle transition was not persisted.'
            );
        }

        $afterData = $beforeData;
        $afterData['status'] = $to;

        auditLog(
            $db,
            'B02_LIFECYCLE',
            'b02_finding',
            $findingId,
            'STATUS_TRANSITION',
            $reason,
            $beforeData,
            $afterData
        );

        if ($startedTransaction) {
            $db->commit();
        }

    } catch (Throwable $e) {

        if ($startedTransaction && $db->inTransaction()) {
            $db->rollBack();
        }

        throw $e;
    }
}
