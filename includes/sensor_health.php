<?php

function get_sensor_settings(PDO $db): array
{
    $stmt = $db->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        return [
            'light_min' => 500,
            'light_max' => 30000,
            'temp_min' => 18,
            'temp_max' => 24,
            'humidity_min' => 45,
            'humidity_max' => 75,
            'refresh_seconds' => 5
        ];
    }

    return [
        'light_min' => (float)($settings['light_min'] ?? 500),
        'light_max' => (float)($settings['light_max'] ?? 30000),
        'temp_min' => (float)($settings['temp_min'] ?? 18),
        'temp_max' => (float)($settings['temp_max'] ?? 24),
        'humidity_min' => (float)($settings['humidity_min'] ?? 45),
        'humidity_max' => (float)($settings['humidity_max'] ?? 75),
        'refresh_seconds' => (int)($settings['refresh_seconds'] ?? 5)
    ];
}

function get_latest_sensor_reading(PDO $db): ?array
{
    $stmt = $db->query("
        SELECT id, timestamp, temperature, humidity, pressure, light
        FROM sensor_log
        ORDER BY timestamp DESC, id DESC
        LIMIT 1
    ");

    $reading = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reading) {
        return null;
    }

    return [
        'id' => (int)$reading['id'],
        'timestamp' => $reading['timestamp'],
        'temperature' => $reading['temperature'] !== null ? (float)$reading['temperature'] : null,
        'humidity' => $reading['humidity'] !== null ? (float)$reading['humidity'] : null,
        'pressure' => $reading['pressure'] !== null ? (float)$reading['pressure'] : null,
        'light' => $reading['light'] !== null ? (float)$reading['light'] : null
    ];
}

function check_sensor_value(?float $value, float $min, float $max, string $unit): array
{
    if ($value === null) {
        return [
            'status' => 'alarm',
            'label' => 'Geen data',
            'message' => 'Geen sensorwaarde beschikbaar',
            'value' => null,
            'unit' => $unit
        ];
    }

    if ($value < $min) {
        return [
            'status' => 'alarm',
            'label' => 'Te laag',
            'message' => 'Waarde is lager dan de ingestelde ondergrens',
            'value' => $value,
            'unit' => $unit
        ];
    }

    if ($value > $max) {
        return [
            'status' => 'alarm',
            'label' => 'Te hoog',
            'message' => 'Waarde is hoger dan de ingestelde bovengrens',
            'value' => $value,
            'unit' => $unit
        ];
    }

    return [
        'status' => 'ok',
        'label' => 'OK',
        'message' => 'Waarde binnen ingestelde grenzen',
        'value' => $value,
        'unit' => $unit
    ];
}

function get_sensor_health(PDO $db): array
{
    $settings = get_sensor_settings($db);
    $reading = get_latest_sensor_reading($db);

    if (!$reading) {
        return [
            'overall_status' => 'alarm',
            'overall_label' => 'Geen sensordata',
            'timestamp' => null,
            'settings' => $settings,
            'reading' => null,
            'checks' => [
                'temperature' => check_sensor_value(null, $settings['temp_min'], $settings['temp_max'], '°C'),
                'humidity' => check_sensor_value(null, $settings['humidity_min'], $settings['humidity_max'], '%'),
                'light' => check_sensor_value(null, $settings['light_min'], $settings['light_max'], 'lux')
            ]
        ];
    }

    $checks = [
        'temperature' => check_sensor_value($reading['temperature'], $settings['temp_min'], $settings['temp_max'], '°C'),
        'humidity' => check_sensor_value($reading['humidity'], $settings['humidity_min'], $settings['humidity_max'], '%'),
        'light' => check_sensor_value($reading['light'], $settings['light_min'], $settings['light_max'], 'lux')
    ];

    $overallStatus = 'ok';

    foreach ($checks as $check) {
        if ($check['status'] !== 'ok') {
            $overallStatus = 'alarm';
            break;
        }
    }

    return [
        'overall_status' => $overallStatus,
        'overall_label' => $overallStatus === 'ok' ? 'Alle sensoren OK' : 'Sensor alarm actief',
        'timestamp' => $reading['timestamp'],
        'settings' => $settings,
        'reading' => $reading,
        'checks' => $checks
    ];
}
