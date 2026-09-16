<?php
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/batch_queue_engine.php';

try {
    $db = getDbConnection();
    $data = getBatchQueue($db);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}