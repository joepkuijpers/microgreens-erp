<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/scheduler_engine.php';

try {
    echo json_encode(
        schedulerEvaluate(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
