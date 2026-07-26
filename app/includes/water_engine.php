<?php

require_once __DIR__ . '/sensor_service.php';
require_once __DIR__ . '/growth_stage_engine.php';

function getWaterState(PDO $db): array
{
    $sensorData = get_live_sensor_data($db);
    $stage = getGrowthStage($db);

    $status = 'standby';
    $reason = 'Geen bodemvochtsensor geconfigureerd';

    if ($stage['stage'] === 'planned') {
        $status = 'planned';
        $reason = 'Teelt nog niet gestart: water uit';
    } elseif ($stage['stage'] === 'blackout') {
        $status = 'blackout';
        $reason = 'Blackout fase: minimaal waterbeheer';
    } elseif ($stage['stage'] === 'growth') {
        $status = 'growth';
        $reason = 'Groeifase: normaal waterbeheer, sensor nog niet geconfigureerd';
    } elseif ($stage['stage'] === 'harvest_ready') {
        $status = 'harvest_ready';
        $reason = 'Oogst gereed: minder water geven';
    } elseif ($stage['stage'] === 'overdue') {
        $status = 'overdue';
        $reason = 'Oogst te laat: water minimaal houden';
    }

    return [
        'mode' => 'automatic',

        'soil_moisture_available' => false,
        'water_tank_sensor_available' => false,

        'pump_required' => false,
        'relay_output' => false,

        'soil_moisture' => null,
        'tank_level' => null,

        'status' => $status,
        'reason' => $reason,
        'growth_stage' => $stage,

        'timestamp' => $sensorData['timestamp']
    ];
}
