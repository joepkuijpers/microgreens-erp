<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/sensor_health.php';

try {
    echo json_encode(get_sensor_health($db), JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'overall_status' => 'error',
        'overall_label' => 'Sensor health API fout',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
