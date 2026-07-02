<?php

function watchdogRuntimeDir(): string
{
    return __DIR__ . '/../hardware/watchdog';
}

function watchdogStateFile(): string
{
    return watchdogRuntimeDir() . '/state.json';
}

function watchdogLogFile(): string
{
    return watchdogRuntimeDir() . '/watchdog.log';
}

function watchdogDefaultState(): array
{
    return [
        'last_heartbeat' => null,
        'last_heartbeat_ts' => null,
        'status' => 'unknown',
        'updated_at' => null,
    ];
}

function watchdogLoadState(): array
{
    $file = watchdogStateFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return watchdogDefaultState();
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return watchdogDefaultState();
    }

    return array_merge(watchdogDefaultState(), $data);
}

function watchdogSaveState(array $state): bool
{
    if (!is_dir(watchdogRuntimeDir())) {
        mkdir(watchdogRuntimeDir(), 0775, true);
    }

    $state = array_merge(watchdogDefaultState(), $state);
    $state['updated_at'] = date('Y-m-d H:i:s');

    return file_put_contents(
        watchdogStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function watchdogLog(string $message, array $context = []): void
{
    if (!is_dir(watchdogRuntimeDir())) {
        mkdir(watchdogRuntimeDir(), 0775, true);
    }

    file_put_contents(
        watchdogLogFile(),
        json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function watchdogHeartbeat(): array
{
    $state = [
        'last_heartbeat' => date('Y-m-d H:i:s'),
        'last_heartbeat_ts' => time(),
        'status' => 'ok',
    ];

    watchdogSaveState($state);

    return watchdogLoadState();
}

function watchdogCheck(int $timeoutSeconds = 180): array
{
    $state = watchdogLoadState();
    $lastTs = (int)($state['last_heartbeat_ts'] ?? 0);
    $age = $lastTs > 0 ? time() - $lastTs : null;

    if ($lastTs <= 0) {
        $state['status'] = 'unknown';
        watchdogSaveState($state);

        return [
            'status' => 'unknown',
            'age_seconds' => null,
            'timeout_seconds' => $timeoutSeconds,
            'fail_safe_required' => true,
            'state' => watchdogLoadState(),
        ];
    }

    if ($age > $timeoutSeconds) {
        $state['status'] = 'timeout';
        watchdogSaveState($state);
        watchdogLog('Watchdog timeout detected', [
            'age_seconds' => $age,
            'timeout_seconds' => $timeoutSeconds,
        ]);

        return [
            'status' => 'timeout',
            'age_seconds' => $age,
            'timeout_seconds' => $timeoutSeconds,
            'fail_safe_required' => true,
            'state' => watchdogLoadState(),
        ];
    }

    $state['status'] = 'ok';
    watchdogSaveState($state);

    return [
        'status' => 'ok',
        'age_seconds' => $age,
        'timeout_seconds' => $timeoutSeconds,
        'fail_safe_required' => false,
        'state' => watchdogLoadState(),
    ];
}
