<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/lighting_engine.php';

try {
    echo json_encode(getLightingState($db), JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
