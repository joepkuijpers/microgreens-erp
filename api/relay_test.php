<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../hardware/gpio/driver.php';

$config = gpioConfig();

if (($config['mode'] ?? 'simulation') !== 'simulation') {
    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'message' => 'Relay test is alleen toegestaan in simulation mode.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}

$output = $_GET['output'] ?? 'grow_light';
$state = ($_GET['state'] ?? 'on') === 'on';

try {
    $result = gpioSetOutput($output, $state);

    echo json_encode([
        'status' => 'ok',
        'output' => $output,
        'requested_state' => $state,
        'result' => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
