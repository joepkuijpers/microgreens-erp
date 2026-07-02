<?php

require_once __DIR__ . '/climate_engine.php';
require_once __DIR__ . '/lighting_engine.php';
require_once __DIR__ . '/water_engine.php';
require_once __DIR__ . '/../hardware/gpio/driver.php';

function applyAutomation(PDO $db): array
{
    $climate = getClimateState($db);
    $lighting = getLightingState($db);
    $water = getWaterState($db);

    $outputs = [
        'heater' => $climate['temperature']['heater'],
        'cooler' => $climate['temperature']['cooler'],
        'fan' => $climate['humidity']['ventilation'],
        'humidifier' => $climate['humidity']['humidifier'],
        'grow_light' => $lighting['relay_output'],
        'water_pump' => $water['relay_output']
    ];

    $appliedOutputs = [];

    foreach ($outputs as $outputName => $state) {
        $appliedOutputs[$outputName] = gpioSetOutput($outputName, (bool)$state);
    }

    return [
        'mode' => gpioConfig()['mode'] ?? 'simulation',
        'outputs' => $outputs,
        'applied_outputs' => $appliedOutputs,
        'sources' => [
            'climate' => $climate['label'],
            'lighting' => $lighting['reason'],
            'water' => $water['reason']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}