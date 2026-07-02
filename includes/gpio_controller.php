<?php

require_once __DIR__ . '/../hardware/gpio/driver.php';

function getGpioControllerState(): array
{
    return [
        'status' => 'ok',
        'mode' => gpioConfig()['mode'] ?? 'simulation',
        'outputs' => gpioReadOutputs(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function setGpioControllerOutput(string $output, bool $state): array
{
    return gpioSetOutput($output, $state);
}

function applyAutomation(PDO $db): array
{
    require_once __DIR__ . '/climate_engine.php';
    require_once __DIR__ . '/lighting_engine.php';
    require_once __DIR__ . '/water_engine.php';
    require_once __DIR__ . '/safety_engine.php';

    $climate = getClimateState($db);
    $lighting = getLightingState($db);
    $water = getWaterState($db);

    $decisions = [
        'heater' => (bool)($climate['temperature']['heater'] ?? false),
        'cooler' => (bool)($climate['temperature']['cooler'] ?? false),
        'fan' => (bool)($climate['humidity']['ventilation'] ?? false),
        'humidifier' => (bool)($climate['humidity']['humidifier'] ?? false),
        'grow_light' => (bool)($lighting['relay_output'] ?? false),
        'water_pump' => (bool)($water['relay_output'] ?? false),
    ];

    $safety = safetyApplyRules($decisions, [
        'climate' => $climate,
        'lighting' => $lighting,
        'water' => $water,
    ]);

    $decisions = $safety['decisions'];

    $results = [];

    foreach ($decisions as $output => $state) {
        $results[$output] = gpioSetOutput($output, $state);
    }

    return [
        'status' => 'ok',
        'mode' => gpioConfig()['mode'] ?? 'simulation',
        'decisions' => $decisions,
        'results' => $results,
        'safety' => $safety,
        'context' => [
            'climate' => $climate,
            'lighting' => $lighting,
            'water' => $water,
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
