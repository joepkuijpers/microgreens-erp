<?php

require_once __DIR__ . '/includes/scheduler_engine.php';
require_once __DIR__ . '/hardware/gpio/driver.php';

try {
    $evaluation = schedulerEvaluate();
    $results = [];

    foreach ($evaluation['schedules'] as $schedule) {
        if (empty($schedule['output'])) {
            continue;
        }

        $output = $schedule['output'];
        $state = (bool)$schedule['should_be_on'];

        $result = gpioSetOutput($output, $state);

        $results[] = [
            'schedule_id' => $schedule['id'],
            'name' => $schedule['name'],
            'output' => $output,
            'state' => $state,
            'state_text' => $state ? 'AAN' : 'UIT',
            'result' => $result,
        ];
    }

    schedulerLog('Scheduler runner executed', [
        'status' => 'ok',
        'results' => $results,
    ]);

    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

} catch (Throwable $e) {
    schedulerLog('Scheduler runner error', [
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

    exit(1);
}
