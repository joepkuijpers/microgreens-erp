<?php

require_once __DIR__ . '/sensor_service.php';

function getWaterState(PDO $db): array
{
    $sensorData = get_live_sensor_data($db);

    return [
        'mode' => 'automatic',

        // Voorbereiding voor toekomstige sensoren
        'soil_moisture_available' => false,
        'water_tank_sensor_available' => false,

        // Huidige status
        'pump_required' => false,
        'relay_output' => false,

        // Uitbreidbare gegevens
        'soil_moisture' => null,
        'tank_level' => null,

        // Status
        'status' => 'standby',
        'reason' => 'Geen bodemvochtsensor geconfigureerd',

        // Tijdstip
        'timestamp' => $sensorData['timestamp']
    ];
}
