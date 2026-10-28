<?php

function getBatchQueue(PDO $db): array
{
    $stmt = $db->query("
        SELECT
            gb.id,
            gb.crop,
            gb.sow_date,
            gb.expected_harvest_date,
            gb.harvest_date,
            gb.status,
            gb.tray_count,
            gb.crop_profile_id,
            cp.crop_name,
            cp.blackout_days,
            cp.grow_days_min,
            cp.grow_days_max
        FROM grow_batches gb
        LEFT JOIN crop_profiles cp ON cp.id = gb.crop_profile_id
        ORDER BY
            CASE
                WHEN lower(COALESCE(gb.status, '')) IN ('gezaaid','groei','blackout','actief') THEN 0
                WHEN gb.harvest_date IS NULL THEN 1
                ELSE 2
            END,
            COALESCE(gb.expected_harvest_date, gb.sow_date),
            gb.id
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $today = new DateTimeImmutable('today');

    $active = null;
    $next = null;
    $upcomingHarvests = [];
    $overdue = [];
    $completed = [];

    foreach ($rows as $row) {
        $status = strtolower((string)($row['status'] ?? ''));
        $isCompleted = in_array($status, ['geoogst', 'harvested', 'klaar', 'afgerond'], true) || !empty($row['harvest_date']);

        $sowDate = !empty($row['sow_date']) ? new DateTimeImmutable($row['sow_date']) : null;
        $harvestStart = null;
        $harvestEnd = null;

        if ($sowDate && !empty($row['grow_days_min']) && !empty($row['grow_days_max'])) {
            $harvestStart = $sowDate->modify('+' . (int)$row['grow_days_min'] . ' days');
            $harvestEnd = $sowDate->modify('+' . (int)$row['grow_days_max'] . ' days');
        } elseif (!empty($row['expected_harvest_date'])) {
            $harvestStart = new DateTimeImmutable($row['expected_harvest_date']);
            $harvestEnd = new DateTimeImmutable($row['expected_harvest_date']);
        }

        $item = [
            'id' => (int)$row['id'],
            'crop' => $row['crop'],
            'crop_name' => $row['crop_name'] ?? $row['crop'],
            'sow_date' => $row['sow_date'],
            'expected_harvest_date' => $row['expected_harvest_date'],
            'harvest_date' => $row['harvest_date'],
            'status' => $row['status'],
            'tray_count' => (int)($row['tray_count'] ?? 0),
            'crop_profile_id' => $row['crop_profile_id'] !== null ? (int)$row['crop_profile_id'] : null,
            'harvest_start_date' => $harvestStart ? $harvestStart->format('Y-m-d') : null,
            'harvest_end_date' => $harvestEnd ? $harvestEnd->format('Y-m-d') : null,
            'is_completed' => $isCompleted
        ];

        if ($isCompleted) {
            $completed[] = $item;
            continue;
        }

        if ($active === null) {
            $active = $item;
            continue;
        }

        if ($next === null) {
            $next = $item;
        }

        if ($harvestEnd && $today > $harvestEnd) {
            $overdue[] = $item;
        } elseif ($harvestStart && $today >= $harvestStart) {
            $upcomingHarvests[] = $item;
        }
    }

    if ($active && !empty($active['harvest_end_date'])) {
        $activeHarvestEnd = new DateTimeImmutable($active['harvest_end_date']);
        if ($today > $activeHarvestEnd) {
            $overdue[] = $active;
        } elseif (!empty($active['harvest_start_date']) && $today >= new DateTimeImmutable($active['harvest_start_date'])) {
            $upcomingHarvests[] = $active;
        }
    }

    return [
        'active' => $active,
        'next' => $next,
        'upcoming_harvests' => $upcomingHarvests,
        'overdue' => $overdue,
        'completed' => $completed,
        'counts' => [
            'active' => $active ? 1 : 0,
            'next' => $next ? 1 : 0,
            'upcoming_harvests' => count($upcomingHarvests),
            'overdue' => count($overdue),
            'completed' => count($completed)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
