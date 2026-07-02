<?php

function safetyRuntimeDir(): string
{
    return __DIR__ . '/../hardware/safety';
}

function safetyStateFile(): string
{
    return safetyRuntimeDir() . '/state.json';
}

function safetyLogFile(): string
{
    return safetyRuntimeDir() . '/safety.log';
}

function safetyDefaultState(): array
{
    return [
        'emergency_stop' => false,
        'maintenance_mode' => false,
        'updated_at' => null,
    ];
}

function safetyLoadState(): array
{
    $file = safetyStateFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return safetyDefaultState();
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return safetyDefaultState();
    }

    return array_merge(safetyDefaultState(), $data);
}

function safetySaveState(array $state): bool
{
    if (!is_dir(safetyRuntimeDir())) {
        mkdir(safetyRuntimeDir(), 0775, true);
    }

    $state = array_merge(safetyDefaultState(), $state);
    $state['updated_at'] = date('Y-m-d H:i:s');

    return file_put_contents(
        safetyStateFile(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function safetyLog(string $message, array $context = []): void
{
    if (!is_dir(safetyRuntimeDir())) {
        mkdir(safetyRuntimeDir(), 0775, true);
    }

    file_put_contents(
        safetyLogFile(),
        json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function safetyApplyRules(array $decisions, array $context = []): array
{
    $rules = [];
    $safe = $decisions;
    $state = safetyLoadState();

    if (!empty($state['emergency_stop'])) {
        foreach ($safe as $output => $value) {
            $safe[$output] = false;
        }

        $rules[] = 'Emergency Stop actief: alle uitgangen uitgeschakeld.';
    }

    if (!empty($state['maintenance_mode'])) {
        foreach ($safe as $output => $value) {
            $safe[$output] = false;
        }

        $rules[] = 'Maintenance Mode actief: automatisering gepauzeerd.';
    }

    if (($safe['heater'] ?? false) && ($safe['cooler'] ?? false)) {
        $safe['heater'] = false;
        $safe['cooler'] = false;
        $rules[] = 'Heater en cooler conflict: beide uitgeschakeld.';
    }

    if (($safe['water_pump'] ?? false) && empty($context['water']['water_tank_sensor_available'])) {
        $safe['water_pump'] = false;
        $rules[] = 'Waterpomp geblokkeerd: geen tankniveau-sensor beschikbaar.';
    }

    if (($safe['humidifier'] ?? false) && ($safe['fan'] ?? false)) {
        $safe['fan'] = false;
        $rules[] = 'Ventilator tijdelijk uitgeschakeld tijdens bevochtigen.';
    }

    if (!empty($rules)) {
        safetyLog('Safety rules applied', [
            'rules' => $rules,
            'input' => $decisions,
            'output' => $safe,
            'state' => $state,
        ]);
    }

    return [
        'decisions' => $safe,
        'rules_applied' => $rules,
        'safe' => empty($rules),
        'state' => $state,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
