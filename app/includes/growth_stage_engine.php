<?php

require_once __DIR__ . '/crop_profile_engine.php';

function getGrowthStage(PDO $db): array
{
    $profile = getActiveCropProfile($db);

    if (!$profile['active'] || empty($profile['sow_date'])) {
        return [
            'active' => false,
            'stage' => 'unknown',
            'label' => 'Geen actieve teeltfase',
            'day_number' => null,
            'profile' => $profile
        ];
    }

    $today = new DateTimeImmutable('today');
    $sowDate = new DateTimeImmutable($profile['sow_date']);
    $dayNumber = (int)$sowDate->diff($today)->format('%r%a');

    $blackoutDays = (int)$profile['blackout_days'];
    $growDaysMin = (int)$profile['grow_days_min'];
    $growDaysMax = (int)$profile['grow_days_max'];

    if ($dayNumber < 0) {
        $stage = 'planned';
        $label = 'Gepland';
    } elseif ($dayNumber < $blackoutDays) {
        $stage = 'blackout';
        $label = 'Blackout fase';
    } elseif ($dayNumber < $growDaysMin) {
        $stage = 'growth';
        $label = 'Groeifase';
    } elseif ($dayNumber <= $growDaysMax) {
        $stage = 'harvest_ready';
        $label = 'Oogst gereed';
    } else {
        $stage = 'overdue';
        $label = 'Oogst te laat';
    }

    return [
        'active' => true,
        'stage' => $stage,
        'label' => $label,
        'day_number' => $dayNumber,
        'blackout_days' => $blackoutDays,
        'grow_days_min' => $growDaysMin,
        'grow_days_max' => $growDaysMax,
        'profile' => $profile
    ];
}
