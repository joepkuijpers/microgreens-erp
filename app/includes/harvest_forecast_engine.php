<?php

function getHarvestForecast(PDO $db, int $daysAhead = 14): array
{
    $today = new DateTimeImmutable('today');
    $endDate = $today->modify('+' . max(1, $daysAhead) . ' days');

    $stmt = $db->query("
        SELECT
            gb.id,
            gb.crop,
            gb.sow_date,
            gb.expected_harvest_date,
            gb.harvest_date,
            gb.tray_count,
            gb.status,
            gb.crop_profile_id,
            cp.crop_name,
            cp.grow_days_min,
            cp.grow_days_max
        FROM grow_batches gb
        LEFT JOIN crop_profiles cp ON cp.id = gb.crop_profile_id
        WHERE gb.harvest_date IS NULL
          AND lower(COALESCE(gb.status, '')) NOT IN ('geoogst', 'harvested', 'klaar', 'afgerond')
        ORDER BY gb.sow_date ASC, gb.id ASC
    ");

    $items = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $batch) {
        $sowDate = !empty($batch['sow_date'])
            ? new DateTimeImmutable($batch['sow_date'])
            : $today;

        if (!empty($batch['expected_harvest_date'])) {
            $harvestDate = new DateTimeImmutable($batch['expected_harvest_date']);
        } else {
            $growMin = (int)($batch['grow_days_min'] ?? 7);
            $harvestDate = $sowDate->modify('+' . max(1, $growMin) . ' days');
        }

        if ($harvestDate > $endDate) {
            continue;
        }

        $daysUntil = (int)$today->diff($harvestDate)->format('%r%a');

        if ($daysUntil < 0) {
            $urgency = 'overdue';
            $priority = 'alarm';
            $label = abs($daysUntil) . ' dagen te laat';
        } elseif ($daysUntil === 0) {
            $urgency = 'today';
            $priority = 'alarm';
            $label = 'Vandaag oogsten';
        } elseif ($daysUntil === 1) {
            $urgency = 'tomorrow';
            $priority = 'warning';
            $label = 'Morgen oogsten';
        } elseif ($daysUntil <= 7) {
            $urgency = 'week';
            $priority = 'warning';
            $label = 'Deze week';
        } else {
            $urgency = 'later';
            $priority = 'ok';
            $label = 'Later';
        }

        $items[] = [
            'batch_id' => (int)$batch['id'],
            'crop' => $batch['crop_name'] ?: $batch['crop'],
            'sow_date' => $batch['sow_date'],
            'harvest_date' => $harvestDate->format('Y-m-d'),
            'days_until_harvest' => $daysUntil,
            'tray_count' => (int)($batch['tray_count'] ?? 0),
            'status' => $batch['status'],
            'urgency' => $urgency,
            'priority' => $priority,
            'label' => $label,
        ];
    }

    usort($items, fn($a, $b) =>
        strcmp($a['harvest_date'], $b['harvest_date'])
        ?: $a['batch_id'] <=> $b['batch_id']
    );

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'period_days' => $daysAhead,
        'summary' => [
            'total' => count($items),
            'overdue' => count(array_filter($items, fn($item) => $item['urgency'] === 'overdue')),
            'today' => count(array_filter($items, fn($item) => $item['urgency'] === 'today')),
            'this_week' => count(array_filter($items, fn($item) => in_array($item['urgency'], ['today', 'tomorrow', 'week'], true))),
            'total_trays' => array_sum(array_column($items, 'tray_count')),
        ],
        'items' => $items,
    ];
}
