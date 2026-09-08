<?php
/**
 * API Endpoint: Start Freeze-Dry Process
 * Method: POST
 * Body: {"output_id": 123, "machine_id": "FD-01", "operator_id": 1}
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../app/b07_production_output.php';
require_once __DIR__ . '/../app/b08_freeze_dry.php';

// Alleen POST toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Lees JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['output_id']) || !isset($input['machine_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: output_id, machine_id']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../database/MicrogreensERP_Development.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $result = b08_start_process(
        $db, 
        (int)$input['output_id'], 
        $input['machine_id'], 
        $input['operator_id'] ?? null,
        $input['notes'] ?? null
    );

    http_response_code(201);
    echo json_encode(['success' => true, 'data' => $result]);

} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
