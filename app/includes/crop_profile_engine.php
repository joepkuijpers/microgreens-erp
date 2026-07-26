<?php

function getActiveCropProfile(PDO $db): array
{
    $stmt = $db->query("
        SELECT
            gb.id AS batch_id,
            gb.crop,
            gb.sow_date,
            gb.expected_harvest_date,
            gb.harvest_date,
            gb.status,
            gb.crop_profile_id,
            cp.id AS profile_id,
            cp.crop_name,
            cp.blackout_days,
            cp.grow_days_min,
            cp.grow_days_max,
            cp.light_hours_per_day,
            cp.temp_min,
            cp.temp_max,
            cp.humidity_min,
            cp.humidity_max,
            cp.irrigation_notes,
            cp.notes
        FROM grow_batches gb
        LEFT JOIN crop_profiles cp ON cp.id = gb.crop_profile_id
        WHERE lower(COALESCE(gb.status, '')) NOT IN ('geoogst', 'harvested', 'klaar', 'afgerond')
        ORDER BY gb.sow_date ASC, gb.id ASC
        LIMIT 1
    ");

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile || !$profile['profile_id']) {
        return [
            'active' => false,
            'batch_id' => null,
            'crop' => null,
            'sow_date' => null,
            'expected_harvest_date' => null,
            'harvest_date' => null,
            'status' => null,
            'profile_id' => null,
            'crop_name' => 'Standaard profiel',
            'blackout_days' => 0,
            'grow_days_min' => 7,
            'grow_days_max' => 14,
            'light_hours_per_day' => 12,
            'temp_min' => 18,
            'temp_max' => 24,
            'humidity_min' => 45,
            'humidity_max' => 75,
            'irrigation_notes' => 'Standaard waterbeheer.',
            'notes' => 'Geen actief gekoppeld teeltprofiel gevonden.'
        ];
    }

    return [
        'active' => true,
        'batch_id' => (int)$profile['batch_id'],
        'crop' => $profile['crop'],
        'sow_date' => $profile['sow_date'],
        'expected_harvest_date' => $profile['expected_harvest_date'],
        'harvest_date' => $profile['harvest_date'],
        'status' => $profile['status'],
        'profile_id' => (int)$profile['profile_id'],
        'crop_name' => $profile['crop_name'],
        'blackout_days' => (int)$profile['blackout_days'],
        'grow_days_min' => (int)$profile['grow_days_min'],
        'grow_days_max' => (int)$profile['grow_days_max'],
        'light_hours_per_day' => (int)$profile['light_hours_per_day'],
        'temp_min' => (float)$profile['temp_min'],
        'temp_max' => (float)$profile['temp_max'],
        'humidity_min' => (float)$profile['humidity_min'],
        'humidity_max' => (float)$profile['humidity_max'],
        'irrigation_notes' => $profile['irrigation_notes'],
        'notes' => $profile['notes']
    ];
}
