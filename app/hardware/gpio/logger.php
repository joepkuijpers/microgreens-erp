<?php

function gpioLogFile(): string
{
    return __DIR__ . '/gpio_actions.log';
}

function gpioLogAction(string $outputName, bool $state, array $result): void
{
    $line = json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'output' => $outputName,
        'state' => $state,
        'state_text' => $state ? 'AAN' : 'UIT',
        'result' => $result
    ], JSON_UNESCAPED_UNICODE);

    file_put_contents(gpioLogFile(), $line . PHP_EOL, FILE_APPEND);
}
