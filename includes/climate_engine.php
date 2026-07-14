<?php

require_once __DIR__ . '/sensor_service.php';

function getClimateState(PDO $db): array
{
    $sensorData = get_live_sensor_data($db);

    $reading = $sensorData['reading'];
    $settings = $sensorData['settings'];

    if (!$reading) {
        return [
            'overall' => 'alarm',
            'label' => 'Geen sensordata beschikbaar',
            'temperature' => [
                'status' => 'unknown',
                'label' => 'Geen data',
                'heater' => false,
                'cooler' => false
            ],
            'humidity' => [
                'status' => 'unknown',
                'label' => 'Geen data',
                'humidifier' => false,
                'ventilation' => false
            ],
            'light' => [
                'status' => 'unknown',
                'label' => 'Geen data',
                'grow_light' => false
            ],
            'timestamp' => null
        ];
    }

    $temperature = $reading['temperature'];
    $humidity = $reading['humidity'];
    $light = $reading['light'];

    $temperatureState = [
        'status' => 'ok',
        'label' => 'Temperatuur OK',
        'heater' => false,
        'cooler' => false
    ];

    if ($temperature < $settings['temp_min']) {
        $temperatureState = [
            'status' => 'low',
            'label' => 'Temperatuur te laag',
            'heater' => true,
            'cooler' => false
        ];
    }

    if ($temperature > $settings['temp_max']) {
        $temperatureState = [
            'status' => 'high',
            'label' => 'Temperatuur te hoog',
            'heater' => false,
            'cooler' => true
        ];
    }

    $humidityState = [
        'status' => 'ok',
        'label' => 'Luchtvochtigheid OK',
        'humidifier' => false,
        'ventilation' => false
    ];

    if ($humidity < $settings['humidity_min']) {
        $humidityState = [
            'status' => 'low',
            'label' => 'Luchtvochtigheid te laag',
            'humidifier' => true,
            'ventilation' => false
        ];
    }

    if ($humidity > $settings['humidity_max']) {
        $humidityState = [
            'status' => 'high',
            'label' => 'Luchtvochtigheid te hoog',
            'humidifier' => false,
            'ventilation' => true
        ];
    }

    $lightState = [
        'status' => 'ok',
        'label' => 'Lichtniveau OK',
        'grow_light' => false
    ];

    if ($light < $settings['light_min']) {
        $lightState = [
            'status' => 'low',
            'label' => 'Lichtniveau te laag',
            'grow_light' => true
        ];
    }

    if ($light > $settings['light_max']) {
        $lightState = [
            'status' => 'high',
            'label' => 'Lichtniveau te hoog',
            'grow_light' => false
        ];
    }

    $overall = 'ok';

    if (
        $temperatureState['status'] !== 'ok' ||
        $humidityState['status'] !== 'ok' ||
        $lightState['status'] !== 'ok'
    ) {
        $overall = 'warning';
    }

    return [
        'overall' => $overall,
        'label' => $overall === 'ok' ? 'Klimaat stabiel' : 'Klimaatcorrectie nodig',
        'temperature' => $temperatureState,
        'humidity' => $humidityState,
        'light' => $lightState,
        'timestamp' => $reading['timestamp']
    ];
}
