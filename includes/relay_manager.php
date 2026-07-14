<?php

function relayRuntimeDir(): string
{
    return __DIR__ . '/../hardware/relay';
}

function relayStateFile(): string
{
    return relayRuntimeDir() . '/state.json';
}

function relayLogFile(): string
{
    return relayRuntimeDir() . '/relay.log';
}

function relayDefaultState(): array
{
    return [
        'outputs' => []
    ];
}

function relayLoadState(): array
{
    $file = relayStateFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return relayDefaultState();
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return relayDefaultState();
    }

    return array_merge(relayDefaultState(), $data);
}

function relaySaveState(array $state): bool
{
    if (!is_dir(relayRuntimeDir())) {
        mkdir(relayRuntimeDir(), 0775, true);
    }

    return file_put_contents(
        relayStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function relayLog(string $message, array $context = []): void
{
    if (!is_dir(relayRuntimeDir())) {
        mkdir(relayRuntimeDir(), 0775, true);
    }

    file_put_contents(
        relayLogFile(),
        json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function relayCanSwitch(string $output, bool $newState, int $minIntervalSeconds = 5): array
{
    $state = relayLoadState();
    $outputs = $state['outputs'] ?? [];

    $current = $outputs[$output] ?? [
        'state' => null,
        'last_changed_ts' => null,
        'last_changed_at' => null,
    ];

    if ($current['state'] === $newState) {
        return [
            'allowed' => true,
            'reason' => 'State unchanged',
            'current' => $current,
        ];
    }

    $lastChangedTs = (int)($current['last_changed_ts'] ?? 0);
    $age = $lastChangedTs > 0 ? time() - $lastChangedTs : null;

    if ($age !== null && $age < $minIntervalSeconds) {
        return [
            'allowed' => false,
            'reason' => 'Relay anti-flapping interval active',
            'age_seconds' => $age,
            'min_interval_seconds' => $minIntervalSeconds,
            'current' => $current,
        ];
    }

    return [
        'allowed' => true,
        'reason' => 'Switch allowed',
        'age_seconds' => $age,
        'min_interval_seconds' => $minIntervalSeconds,
        'current' => $current,
    ];
}

function relayRecordSwitch(string $output, bool $stateValue): array
{
    $state = relayLoadState();

    if (!isset($state['outputs']) || !is_array($state['outputs'])) {
        $state['outputs'] = [];
    }

    $state['outputs'][$output] = [
        'state' => $stateValue,
        'state_text' => $stateValue ? 'AAN' : 'UIT',
        'last_changed_ts' => time(),
        'last_changed_at' => date('Y-m-d H:i:s'),
    ];

    relaySaveState($state);

    return $state['outputs'][$output];
}

function relaySetOutputManaged(string $output, bool $newState, callable $setter, int $minIntervalSeconds = 5): array
{
    $check = relayCanSwitch($output, $newState, $minIntervalSeconds);

    if (!$check['allowed']) {
        relayLog('Relay switch blocked', [
            'output' => $output,
            'requested_state' => $newState,
            'check' => $check,
        ]);

        return [
            'status' => 'blocked',
            'output' => $output,
            'requested_state' => $newState,
            'check' => $check,
        ];
    }

    $result = $setter($output, $newState);
    $record = relayRecordSwitch($output, $newState);

    relayLog('Relay switch executed', [
        'output' => $output,
        'state' => $newState,
        'result' => $result,
        'record' => $record,
    ]);

    return [
        'status' => 'ok',
        'output' => $output,
        'requested_state' => $newState,
        'result' => $result,
        'record' => $record,
        'check' => $check,
    ];
}
