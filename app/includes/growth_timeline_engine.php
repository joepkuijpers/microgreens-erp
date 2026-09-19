<?php

require_once __DIR__ . '/growth_stage_engine.php';

function getGrowthTimeline(PDO $db): array
{
    $stage = getGrowthStage($db);
    $profile = $stage['profile'];

    if (!$stage['active'] || empty($profile['sow_date'])) {
        return [
            'active' => false,
            'label' => 'Geen actieve teeltlijn',
            'items' => []
        ];
    }

    $sowDate = new DateTimeImmutable($profile['sow_date']);
    $today = new DateTimeImmutable('today');

    $blackoutEnd = $sowDate->modify('+' . (int)$stage['blackout_days'] . ' days');
    $harvestStart = $sowDate->modify('+' . (int)$stage['grow_days_min'] . ' days');
    $harvestEnd = $sowDate->modify('+' . (int)$stage['grow_days_max'] . ' days');

    $daysUntilHarvest = (int)$today->diff($harvestStart)->format('%r%a');
    $daysOverdue = max(0, (int)$today->diff($harvestEnd)->format('%r%a') * -1);

    return [
        'active' => true,
        'label' => $stage['label'],
        'batch_id' => $profile['batch_id'],
        'crop_name' => $profile['crop_name'],
        'current_stage' => $stage['stage'],
        'day_number' => $stage['day_number'],
        'sow_date' => $sowDate->format('Y-m-d'),
        'blackout_end_date' => $blackoutEnd->format('Y-m-d'),
        'harvest_start_date' => $harvestStart->format('Y-m-d'),
        'harvest_end_date' => $harvestEnd->format('Y-m-d'),
        'days_until_harvest' => max(0, $daysUntilHarvest),
        'days_overdue' => $daysOverdue,
        'items' => [
            [
                'key' => 'sow',
                'label' => 'Zaaien',
                'date' => $sowDate->format('Y-m-d'),
                'done' => true
            ],
            [
                'key' => 'blackout',
                'label' => 'Blackout einde',
                'date' => $blackoutEnd->format('Y-m-d'),
                'done' => $today >= $blackoutEnd
            ],
            [
                'key' => 'harvest_start',
                'label' => 'Oogstvenster start',
                'date' => $harvestStart->format('Y-m-d'),
                'done' => $today >= $harvestStart
            ],
            [
                'key' => 'harvest_end',
                'label' => 'Oogstvenster einde',
                'date' => $harvestEnd->format('Y-m-d'),
                'done' => $today > $harvestEnd
            ]
        ]
    ];
}
