<?php

function gpioSimulationStateFile(): string
{
    return __DIR__ . '/simulation_state.json';
}

function gpioSimulationWrite(string $outputName, bool $state, array $relayConfig): array
{
    $stateFile = gpioSimulationStateFile();

    $currentState = [];

    if (file_exists($stateFile)) {
        $json = file_get_contents($stateFile);
        $currentState = json_decode($json, true) ?: [];
    }

    $currentState[$outputName] = [
        'label' => $relayConfig['label'],
        'gpio_pin' => $relayConfig['gpio_pin'],
        'active_low' => $relayConfig['active_low'],
        'state' => $state,
        'state_text' => $state ? 'AAN' : 'UIT',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    file_put_contents(
        $stateFile,
        json_encode($currentState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    return $currentState[$outputName];
}

function gpioSimulationReadAll(): array
{
    $stateFile = gpioSimulationStateFile();

    if (!file_exists($stateFile)) {
        return [];
    }

    $json = file_get_contents($stateFile);

    return json_decode($json, true) ?: [];
}
