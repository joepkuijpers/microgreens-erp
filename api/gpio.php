<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../hardware/gpio/driver.php';

try {
    echo json_encode([
        'status' => 'ok',
        'mode' => gpioConfig()['mode'] ?? 'simulation',
        'outputs' => gpioReadOutputs(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
