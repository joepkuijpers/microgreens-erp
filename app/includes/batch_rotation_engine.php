<?php

function normalizeBatchStatus(?string $status): string
{
    $status = strtolower(trim((string)$status));

    return match ($status) {
        'geoogst', 'harvested', 'klaar', 'afgerond' => 'harvested',
        'active', 'actief' => 'active',
        'planned', 'gepland', 'gezaaid', 'sown' => 'planned',
        default => $status !== '' ? $status : 'planned',
    };
}

function rotateActiveBatch(PDO $db): array
{
    $db->beginTransaction();

    try {
        $finished = $db->prepare("
            UPDATE grow_batches
            SET status = 'harvested'
            WHERE harvest_date IS NOT NULL
              AND lower(COALESCE(status, '')) NOT IN ('harvested', 'geoogst', 'klaar', 'afgerond')
        ");
        $finished->execute();

        $activeStmt = $db->query("
            SELECT id
            FROM grow_batches
            WHERE lower(COALESCE(status, '')) IN ('active', 'actief')
              AND harvest_date IS NULL
            ORDER BY sow_date ASC, id ASC
            LIMIT 1
        ");
        $active = $activeStmt->fetch(PDO::FETCH_ASSOC);

        if ($active) {
            $db->commit();

            return [
                'changed' => false,
                'active_batch_id' => (int)$active['id'],
                'message' => 'Actieve batch blijft actief.'
            ];
        }

        $nextStmt = $db->query("
            SELECT id
            FROM grow_batches
            WHERE harvest_date IS NULL
              AND lower(COALESCE(status, '')) IN ('planned', 'gepland', 'gezaaid', 'sown', '')
            ORDER BY sow_date ASC, id ASC
            LIMIT 1
        ");
        $next = $nextStmt->fetch(PDO::FETCH_ASSOC);

        if (!$next) {
            $db->commit();

            return [
                'changed' => false,
                'active_batch_id' => null,
                'message' => 'Geen wachtende batch gevonden.'
            ];
        }

        $activate = $db->prepare("
            UPDATE grow_batches
            SET status = 'active'
            WHERE id = :id
        ");
        $activate->execute([':id' => $next['id']]);

        $db->commit();

        return [
            'changed' => true,
            'active_batch_id' => (int)$next['id'],
            'message' => 'Volgende batch automatisch actief gemaakt.'
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        return [
            'changed' => false,
            'active_batch_id' => null,
            'message' => 'Batch rotation fout: ' . $e->getMessage()
        ];
    }
}
