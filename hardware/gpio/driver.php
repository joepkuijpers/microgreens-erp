<?php

require_once __DIR__ . '/simulation.php';

function gpioConfig(): array
{
    return include __DIR__ . '/config.php';
}

function gpioSetOutput(string $outputName, bool $state): array
{
    $config = gpioConfig();

    if (!isset($config['relays'][$outputName])) {
        throw new InvalidArgumentException('Onbekende GPIO-output: ' . $outputName);
    }

    $relayConfig = $config['relays'][$outputName];

    if (($config['mode'] ?? 'simulation') === 'simulation') {
        return gpioSimulationWrite($outputName, $state, $relayConfig);
    }

    throw new RuntimeException('Echte GPIO-modus is nog niet geactiveerd.');
}

function gpioReadOutputs(): array
{
    $config = gpioConfig();

    if (($config['mode'] ?? 'simulation') === 'simulation') {
        return gpioSimulationReadAll();
    }

    return [];
}
