<?php

function overrideRuntimeDir(): string
{
    return __DIR__ . '/../hardware/override';
}

function overrideStateFile(): string
{
    return overrideRuntimeDir() . '/state.json';
}

function overrideLogFile(): string
{
    return overrideRuntimeDir() . '/override.log';
}

function overrideDefaultState(): array
{
    return [
        'active' => false,
        'output' => null,
        'state' => false,
        'expires_at' => null,
        'expires_ts' => null,
        'updated_at' => null,
    ];
}

function overrideLoadState(): array
{
    $file = overrideStateFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return overrideDefaultState();
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return overrideDefaultState();
    }

    return array_merge(overrideDefaultState(), $data);
}

function overrideSaveState(array $state): bool
{
    if (!is_dir(overrideRuntimeDir())) {
        mkdir(overrideRuntimeDir(), 0775, true);
    }

    $state = array_merge(overrideDefaultState(), $state);
    $state['updated_at'] = date('Y-m-d H:i:s');

    return file_put_contents(
        overrideStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function overrideLog(string $message, array $context = []): void
{
    if (!is_dir(overrideRuntimeDir())) {
        mkdir(overrideRuntimeDir(), 0775, true);
    }

    file_put_contents(
        overrideLogFile(),
        json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function overrideClear(string $reason = 'Override cleared'): array
{
    $state = overrideDefaultState();
    overrideSaveState($state);
    overrideLog($reason, []);

    return $state;
}

function overrideCreate(string $output, bool $state, int $minutes = 5): array
{
    $minutes = max(1, min(60, $minutes));

    $expiresTs = time() + ($minutes * 60);

    $override = [
        'active' => true,
        'output' => $output,
        'state' => $state,
        'expires_at' => date('Y-m-d H:i:s', $expiresTs),
        'expires_ts' => $expiresTs,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    overrideSaveState($override);
    overrideLog('Override created', $override);

    return $override;
}

function overrideApply(array $decisions): array
{
    $override = overrideLoadState();

    if (empty($override['active'])) {
        return [
            'decisions' => $decisions,
            'override' => $override,
            'applied' => false,
            'expired' => false,
        ];
    }

    $expiresTs = (int)($override['expires_ts'] ?? 0);

    if ($expiresTs > 0 && time() > $expiresTs) {
        $cleared = overrideClear('Override expired');

        return [
            'decisions' => $decisions,
            'override' => $cleared,
            'applied' => false,
            'expired' => true,
        ];
    }

    $output = $override['output'] ?? null;

    if ($output !== null && array_key_exists($output, $decisions)) {
        $decisions[$output] = (bool)$override['state'];
    }

    return [
        'decisions' => $decisions,
        'override' => $override,
        'applied' => true,
        'expired' => false,
    ];
}
