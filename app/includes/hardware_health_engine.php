<?php

require_once __DIR__ . '/../hardware/gpio/driver.php';
require_once __DIR__ . '/watchdog_engine.php';
require_once __DIR__ . '/safety_engine.php';
require_once __DIR__ . '/override_engine.php';

function hardwareHealthRuntimeDir(): string
{
    return __DIR__ . '/../hardware/health';
}

function hardwareHealthStateFile(): string
{
    return hardwareHealthRuntimeDir() . '/state.json';
}

function hardwareHealthLogFile(): string
{
    return hardwareHealthRuntimeDir() . '/health.log';
}

function hardwareHealthStatus(bool $ok, string $label, array $extra = []): array
{
    return array_merge([
        'status' => $ok ? 'ok' : 'warning',
        'label' => $label,
    ], $extra);
}

function getHardwareHealthState(): array
{
    $gpioConfig = gpioConfig();
    $watchdog = watchdogCheck(180);
    $safety = safetyLoadState();
    $override = overrideLoadState();

    $checks = [
        'gpio_mode' => hardwareHealthStatus(
            ($gpioConfig['mode'] ?? '') === 'simulation',
            'GPIO draait veilig in simulation mode',
            ['mode' => $gpioConfig['mode'] ?? 'unknown']
        ),
        'gpio_outputs' => hardwareHealthStatus(
            count($gpioConfig['relays'] ?? []) >= 6,
            'GPIO outputs geconfigureerd',
            ['count' => count($gpioConfig['relays'] ?? [])]
        ),
        'watchdog' => hardwareHealthStatus(
            !($watchdog['fail_safe_required'] ?? true),
            'Watchdog heartbeat actief',
            $watchdog
        ),
        'emergency_stop' => hardwareHealthStatus(
            empty($safety['emergency_stop']),
            'Emergency Stop niet actief',
            ['active' => !empty($safety['emergency_stop'])]
        ),
        'maintenance_mode' => hardwareHealthStatus(
            empty($safety['maintenance_mode']),
            'Maintenance Mode niet actief',
            ['active' => !empty($safety['maintenance_mode'])]
        ),
        'manual_override' => hardwareHealthStatus(
            true,
            !empty($override['active']) ? 'Manual Override actief' : 'Manual Override niet actief',
            ['active' => !empty($override['active']), 'output' => $override['output'] ?? null]
        ),
        'disk_space' => hardwareHealthStatus(
            disk_free_space(__DIR__) > 250 * 1024 * 1024,
            'Voldoende vrije schijfruimte',
            ['free_bytes' => disk_free_space(__DIR__)]
        ),
    ];

    $overall = 'ok';

    foreach ($checks as $check) {
        if (($check['status'] ?? 'warning') !== 'ok') {
            $overall = 'warning';
            break;
        }
    }

    $state = [
        'last_check' => date('Y-m-d H:i:s'),
        'overall' => $overall,
        'checks' => $checks,
    ];

    if (!is_dir(hardwareHealthRuntimeDir())) {
        mkdir(hardwareHealthRuntimeDir(), 0775, true);
    }

    file_put_contents(
        hardwareHealthStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    return $state;
}
