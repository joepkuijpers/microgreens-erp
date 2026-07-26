<?php

function priorityRuntimeDir(): string
{
    return __DIR__ . '/../hardware/priority';
}

function priorityStateFile(): string
{
    return priorityRuntimeDir() . '/state.json';
}

function priorityLogFile(): string
{
    return priorityRuntimeDir() . '/priority.log';
}

function priorityDefaultState(): array
{
    return [
        'last_resolution' => null,
        'outputs' => []
    ];
}

function priorityLoadState(): array
{
    $file = priorityStateFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return priorityDefaultState();
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return priorityDefaultState();
    }

    return array_merge(priorityDefaultState(), $data);
}

function prioritySaveState(array $state): bool
{
    if (!is_dir(priorityRuntimeDir())) {
        mkdir(priorityRuntimeDir(), 0775, true);
    }

    return file_put_contents(
        priorityStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function priorityLog(string $message, array $context = []): void
{
    if (!is_dir(priorityRuntimeDir())) {
        mkdir(priorityRuntimeDir(), 0775, true);
    }

    file_put_contents(
        priorityLogFile(),
        json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function priorityRank(string $source): int
{
    $priorities = [
        'emergency_stop' => 1,
        'maintenance_mode' => 2,
        'manual_override' => 3,
        'safety_engine' => 4,
        'automation_engine' => 5,
        'scheduler' => 6,
        'default' => 99,
    ];

    return $priorities[$source] ?? $priorities['default'];
}

function priorityResolveOutput(string $output, array $requests): array
{
    usort($requests, function (array $a, array $b): int {
        return priorityRank($a['source'] ?? 'default') <=> priorityRank($b['source'] ?? 'default');
    });

    $winner = $requests[0] ?? [
        'source' => 'default',
        'state' => false,
        'reason' => 'Geen request beschikbaar',
    ];

    return [
        'output' => $output,
        'final_state' => (bool)($winner['state'] ?? false),
        'winner' => $winner['source'] ?? 'default',
        'priority' => priorityRank($winner['source'] ?? 'default'),
        'reason' => $winner['reason'] ?? '',
        'requests' => $requests,
    ];
}

function priorityResolve(array $requestsByOutput): array
{
    $resolved = [];
    $finalDecisions = [];

    foreach ($requestsByOutput as $output => $requests) {
        $resolution = priorityResolveOutput($output, $requests);
        $resolved[$output] = $resolution;
        $finalDecisions[$output] = $resolution['final_state'];
    }

    $state = [
        'last_resolution' => date('Y-m-d H:i:s'),
        'outputs' => $resolved,
    ];

    prioritySaveState($state);
    priorityLog('Priority resolution completed', $state);

    return [
        'decisions' => $finalDecisions,
        'resolution' => $resolved,
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}
