<?php

function raspberryPiGpioExport(int $pin): void
{
    if (!is_dir('/sys/class/gpio/gpio' . $pin)) {
        file_put_contents('/sys/class/gpio/export', (string)$pin);
        usleep(150000);
    }
}

function raspberryPiGpioSetDirection(int $pin, string $direction = 'out'): void
{
    file_put_contents('/sys/class/gpio/gpio' . $pin . '/direction', $direction);
}

function raspberryPiGpioWrite(int $pin, bool $state, bool $activeLow = true): array
{
    raspberryPiGpioExport($pin);
    raspberryPiGpioSetDirection($pin, 'out');

    $gpioValue = $activeLow
        ? ($state ? '0' : '1')
        : ($state ? '1' : '0');

    file_put_contents('/sys/class/gpio/gpio' . $pin . '/value', $gpioValue);

    return [
        'gpio_pin' => $pin,
        'active_low' => $activeLow,
        'state' => $state,
        'state_text' => $state ? 'AAN' : 'UIT',
        'gpio_value' => $gpioValue,
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

function raspberryPiGpioRead(int $pin): ?string
{
    $path = '/sys/class/gpio/gpio' . $pin . '/value';

    if (!file_exists($path)) {
        return null;
    }

    return trim(file_get_contents($path));
}
