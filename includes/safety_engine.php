<?php

function safetyApplyRules(array $decisions, array $context = []): array
{
    $rules = [];
    $safe = $decisions;

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

    return [
        'decisions' => $safe,
        'rules_applied' => $rules,
        'safe' => empty($rules),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
