<?php

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/scheduler_engine.php';
require_once __DIR__ . '/includes/gpio_controller.php';

try {
    $scheduleEvaluation = schedulerEvaluate();
    $automationResult = applyAutomation($db);

    schedulerLog('Scheduler runner executed with automation controller', [
        'status' => 'ok',
        'schedule_evaluation' => $scheduleEvaluation,
        'automation_result' => [
            'mode' => $automationResult['mode'] ?? null,
            'decisions' => $automationResult['decisions'] ?? [],
            'timestamp' => $automationResult['timestamp'] ?? null,
        ],
    ]);

    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'schedule_evaluation' => $scheduleEvaluation,
        'automation_result' => $automationResult,
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
