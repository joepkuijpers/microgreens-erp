<?php

function schedulerDefaultSchedules(): array
{
    return [
        [
            'id' => 'light_day_cycle',
            'name' => 'Grow light dagcyclus',
            'output' => 'grow_light',
            'enabled' => true,
            'start_time' => '07:00',
            'end_time' => '21:00',
        ],
        [
            'id' => 'fan_airflow_cycle',
            'name' => 'Ventilator luchtcirculatie',
            'output' => 'fan',
            'enabled' => true,
            'start_time' => '00:00',
            'end_time' => '23:59',
        ],
        [
            'id' => 'water_morning_cycle',
            'name' => 'Waterpomp ochtend',
            'output' => 'water_pump',
            'enabled' => false,
            'start_time' => '08:00',
            'end_time' => '08:02',
        ],
    ];
}

function schedulerRuntimeDir(): string
{
    return __DIR__ . '/../hardware/scheduler';
}

function schedulerSchedulesFile(): string
{
    return schedulerRuntimeDir() . '/schedules.json';
}

function schedulerLogFile(): string
{
    return schedulerRuntimeDir() . '/scheduler.log';
}

function schedulerLoadSchedules(): array
{
    $file = schedulerSchedulesFile();

    if (!file_exists($file) || filesize($file) === 0) {
        return schedulerDefaultSchedules();
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return schedulerDefaultSchedules();
    }

    return $data;
}

function schedulerSaveSchedules(array $schedules): bool
{
    if (!is_dir(schedulerRuntimeDir())) {
        mkdir(schedulerRuntimeDir(), 0775, true);
    }

    return file_put_contents(
        schedulerSchedulesFile(),
        json_encode($schedules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function schedulerIsTimeActive(string $startTime, string $endTime, ?DateTime $now = null): bool
{
    $now = $now ?: new DateTime();

    $today = $now->format('Y-m-d');
    $start = new DateTime($today . ' ' . $startTime);
    $end = new DateTime($today . ' ' . $endTime);

    if ($end >= $start) {
        return $now >= $start && $now <= $end;
    }

    return $now >= $start || $now <= $end;
}

function schedulerEvaluate(?DateTime $now = null): array
{
    $now = $now ?: new DateTime();
    $schedules = schedulerLoadSchedules();
    $decisions = [];

    foreach ($schedules as $schedule) {
        $enabled = (bool)($schedule['enabled'] ?? false);
        $active = false;

        if ($enabled) {
            $active = schedulerIsTimeActive(
                $schedule['start_time'] ?? '00:00',
                $schedule['end_time'] ?? '00:00',
                $now
            );
        }

        $decisions[] = [
            'id' => $schedule['id'] ?? '',
            'name' => $schedule['name'] ?? '',
            'output' => $schedule['output'] ?? '',
            'enabled' => $enabled,
            'start_time' => $schedule['start_time'] ?? '',
            'end_time' => $schedule['end_time'] ?? '',
            'should_be_on' => $active,
            'state_text' => $active ? 'AAN' : 'UIT',
        ];
    }

    return [
        'status' => 'ok',
        'timestamp' => $now->format('Y-m-d H:i:s'),
        'schedules' => $decisions,
    ];
}

function schedulerLog(string $message, array $context = []): void
{
    if (!is_dir(schedulerRuntimeDir())) {
        mkdir(schedulerRuntimeDir(), 0775, true);
    }

    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
    ];

    file_put_contents(
        schedulerLogFile(),
        json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND
    );
}

function schedulerAllowedOutputs(): array
{
    return [
        'grow_light',
        'fan',
        'water_pump',
        'heater',
        'cooler',
        'humidifier',
    ];
}

function schedulerNormalizeTime(string $time): string
{
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return '00:00';
    }

    [$hour, $minute] = explode(':', $time);

    $hour = max(0, min(23, (int)$hour));
    $minute = max(0, min(59, (int)$minute));

    return sprintf('%02d:%02d', $hour, $minute);
}

function schedulerUpdateFromPost(array $post): array
{
    $existing = schedulerLoadSchedules();
    $updated = [];
    $allowedOutputs = schedulerAllowedOutputs();

    foreach ($existing as $schedule) {
        $id = $schedule['id'] ?? '';

        if ($id === '') {
            continue;
        }

        $output = $post['output'][$id] ?? ($schedule['output'] ?? '');

        if (!in_array($output, $allowedOutputs, true)) {
            $output = $schedule['output'] ?? 'grow_light';
        }

        $updated[] = [
            'id' => $id,
            'name' => trim($post['name'][$id] ?? ($schedule['name'] ?? $id)),
            'output' => $output,
            'enabled' => isset($post['enabled'][$id]),
            'start_time' => schedulerNormalizeTime($post['start_time'][$id] ?? ($schedule['start_time'] ?? '00:00')),
            'end_time' => schedulerNormalizeTime($post['end_time'][$id] ?? ($schedule['end_time'] ?? '00:00')),
        ];
    }

    schedulerSaveSchedules($updated);
    schedulerLog('Scheduler configuration updated', ['schedules' => $updated]);

    return $updated;
}
