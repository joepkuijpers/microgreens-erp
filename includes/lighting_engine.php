<?php

require_once __DIR__ . '/sensor_service.php';

function getLightingState(PDO $db): array
{
    $sensorData = get_live_sensor_data($db);
    $reading = $sensorData['reading'];
    $settings = $sensorData['settings'];

    if (!$reading) {
        return [
            'mode' => 'automatic',
            'schedule_active' => false,
            'light_level_ok' => false,
            'lights_should_be_on' => false,
            'relay_output' => false,
            'reason' => 'Geen sensordata beschikbaar',
            'timestamp' => null
        ];
    }

    $light = (float)$reading['light'];
    $lightMin = (float)$settings['light_min'];
    $lightMax = (float)$settings['light_max'];

    $lightLevelOk = $light >= $lightMin && $light <= $lightMax;
    $lightsShouldBeOn = $light < $lightMin;

    if ($light < $lightMin) {
        $reason = 'Lichtniveau te laag';
    } elseif ($light > $lightMax) {
        $reason = 'Lichtniveau te hoog';
    } else {
        $reason = 'Lichtniveau binnen ingestelde grenzen';
    }

    return [
        'mode' => 'automatic',
        'schedule_active' => true,
        'light_level_ok' => $lightLevelOk,
        'lights_should_be_on' => $lightsShouldBeOn,
        'relay_output' => $lightsShouldBeOn,
        'reason' => $reason,
        'current_lux' => $light,
        'min_lux' => $lightMin,
        'max_lux' => $lightMax,
        'timestamp' => $reading['timestamp']
    ];
}
