<?php

require_once __DIR__ . '/sensor_health.php';

function get_live_sensor_data(PDO $db): array
{
    $health = get_sensor_health($db);

    return [
        'timestamp' => $health['timestamp'],
        'overall_status' => $health['overall_status'],
        'overall_label' => $health['overall_label'],
        'reading' => $health['reading'],
        'checks' => $health['checks'],
        'settings' => $health['settings']
    ];
}
