<?php

require_once __DIR__ . '/sensor_health.php';

function get_live_sensor_data(PDO $db): array
{
    $health = get_sensor_health($db);

    return [
        'timestamp' => $health['timestamp'] ?? null,
        'overall_status' => $health['overall_status'] ?? 'alarm',
        'overall_label' => $health['overall_label'] ?? 'No data',
        'reading' => $health['reading'] ?? [
            'temperature' => null,
            'humidity' => null,
            'light' => null
        ],
        'checks' => $health['checks'] ?? [
            'temperature' => [
                'status' => 'alarm',
                'label' => 'No data'
            ],
            'humidity' => [
                'status' => 'alarm',
                'label' => 'No data'
            ],
            'light' => [
                'status' => 'alarm',
                'label' => 'No data'
            ]
        ],
        'settings' => $health['settings'] ?? []
    ];
}

function getLiveSensorStatus(PDO $db): array
{
    return get_live_sensor_data($db);
}