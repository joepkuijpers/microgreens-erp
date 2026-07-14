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
    require_once __DIR__ . '/override_engine.php';
    require_once __DIR__ . '/relay_manager.php';
    require_once __DIR__ . '/priority_manager.php';

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

    $requestsByOutput = [];

    foreach ($decisions as $output => $state) {
        $requestsByOutput[$output][] = [
            'source' => 'automation_engine',
            'state' => $state,
            'reason' => 'Automation Engine decision'
        ];
    }

    $override = overrideApply($decisions);

    if (!empty($override['applied']) && !empty($override['override']['output'])) {
        $requestsByOutput[$override['override']['output']][] = [
            'source' => 'manual_override',
            'state' => (bool)$override['override']['state'],
            'reason' => 'Manual override active'
        ];
    }

    $safety = safetyApplyRules($override['decisions'], [
        'climate' => $climate,
        'lighting' => $lighting,
        'water' => $water,
        'override' => $override,
    ]);

    foreach ($safety['decisions'] as $output => $state) {
        if (($override['decisions'][$output] ?? null) !== $state) {
            $requestsByOutput[$output][] = [
                'source' => 'safety_engine',
                'state' => $state,
                'reason' => implode(' | ', $safety['rules_applied'] ?? [])
            ];
        }
    }

    $priority = priorityResolve($requestsByOutput);
    $decisions = $priority['decisions'];

    $results = [];

    foreach ($decisions as $output => $state) {
        $results[$output] = relaySetOutputManaged(
            $output,
            $state,
            function (string $outputName, bool $outputState): array {
                return gpioSetOutput($outputName, $outputState);
            },
            5
        );
    }

    return [
        'status' => 'ok',
        'mode' => gpioConfig()['mode'] ?? 'simulation',
        'decisions' => $decisions,
        'results' => $results,
        'safety' => $safety,
        'override' => $override,
        'priority' => $priority,
        'context' => [
            'climate' => $climate,
            'lighting' => $lighting,
            'water' => $water,
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
