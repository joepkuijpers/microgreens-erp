<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/override_engine.php';

try {
    $action = $_GET['action'] ?? 'status';

    if ($action === 'clear') {
        echo json_encode([
            'status' => 'ok',
            'override' => overrideClear('Override cleared from API')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'set') {
        $output = $_GET['output'] ?? '';
        $state = ($_GET['state'] ?? 'off') === 'on';
        $minutes = (int)($_GET['minutes'] ?? 5);

        echo json_encode([
            'status' => 'ok',
            'override' => overrideCreate($output, $state, $minutes)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status' => 'ok',
        'override' => overrideLoadState()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
