<?php

require_once __DIR__ . '/simulation.php';
require_once __DIR__ . '/raspberrypi.php';
require_once __DIR__ . '/logger.php';

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
    $mode = $config['mode'] ?? 'simulation';

    if ($mode === 'simulation') {
        $result = gpioSimulationWrite($outputName, $state, $relayConfig);
        gpioLogAction($outputName, $state, $result);
        return $result;
    }

    if ($mode === 'raspberrypi') {
        $result = raspberryPiGpioWrite(
            (int)$relayConfig['gpio_pin'],
            $state,
            (bool)$relayConfig['active_low']
        );

        $result = array_merge([
            'label' => $relayConfig['label']
        ], $result);

        gpioLogAction($outputName, $state, $result);

        return $result;
    }

    throw new RuntimeException('Onbekende GPIO-modus: ' . $mode);
}

function gpioReadOutputs(): array
{
    $config = gpioConfig();
    $mode = $config['mode'] ?? 'simulation';

    if ($mode === 'simulation') {
        return gpioSimulationReadAll();
    }

    if ($mode === 'raspberrypi') {
        $outputs = [];

        foreach ($config['relays'] as $name => $relayConfig) {
            $outputs[$name] = [
                'label' => $relayConfig['label'],
                'gpio_pin' => $relayConfig['gpio_pin'],
                'active_low' => $relayConfig['active_low'],
                'gpio_value' => raspberryPiGpioRead((int)$relayConfig['gpio_pin'])
            ];
        }

        return $outputs;
    }

    return [];
}
