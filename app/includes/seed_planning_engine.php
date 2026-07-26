<?php

function getSeedPlanning(PDO $db, int $forecastDays = 14): array
{
    $defaultSeedGramsPerTray = [
        'alfalfa' => 12,
        'broccoli' => 10,
        'fenegriek' => 25,
        'mosterdzaad' => 12,
        'mungbonen' => 80,
        'radijs' => 18,
        'rucola' => 10,
        'tuinkers' => 8,
    ];

    $profiles = $db->query("
        SELECT id, crop_name
        FROM crop_profiles
        ORDER BY crop_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $seedInventory = $db->query("
        SELECT
            id,
            item_name,
            category,
            quantity,
            unit,
            unit_cost
        FROM inventory
        WHERE lower(COALESCE(category, '')) LIKE '%zaad%'
           OR lower(COALESCE(category, '')) LIKE '%seed%'
           OR lower(COALESCE(item_name, '')) LIKE '%zaad%'
           OR lower(COALESCE(item_name, '')) LIKE '%seed%'
        ORDER BY item_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $totalSeedKg = 0.0;
    foreach ($seedInventory as $item) {
        $unit = strtolower(trim((string)$item['unit']));
        $quantity = (float)$item['quantity'];

        if ($unit === 'kg') {
            $totalSeedKg += $quantity;
        } elseif (in_array($unit, ['g', 'gram', 'grams'], true)) {
            $totalSeedKg += $quantity / 1000;
        }
    }

    $plans = [];
    $totalRequiredGrams = 0.0;

    foreach ($profiles as $profile) {
        $cropKey = strtolower(trim((string)$profile['crop_name']));
        $seedPerTray = $defaultSeedGramsPerTray[$cropKey] ?? 15;

        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(tray_count), 0) AS active_planned_trays
            FROM grow_batches
            WHERE crop_profile_id = :profile_id
              AND harvest_date IS NULL
              AND lower(COALESCE(status, '')) NOT IN ('geoogst', 'harvested', 'klaar', 'afgerond')
        ");
        $stmt->execute([':profile_id' => (int)$profile['id']]);
        $activePlannedTrays = (int)$stmt->fetchColumn();

        $recommendedTrays = $activePlannedTrays > 0 ? 0 : 1;
        $requiredGrams = $recommendedTrays * $seedPerTray;
        $totalRequiredGrams += $requiredGrams;

        if ($requiredGrams <= 0) {
            $priority = 'ok';
            $advice = 'Geen extra zaad nodig';
        } elseif ($totalSeedKg * 1000 >= $totalRequiredGrams) {
            $priority = 'warning';
            $advice = 'Zaad reserveren voor volgende batch';
        } else {
            $priority = 'alarm';
            $advice = 'Zaadvoorraad onvoldoende';
        }

        $plans[] = [
            'crop_profile_id' => (int)$profile['id'],
            'crop_name' => $profile['crop_name'],
            'seed_grams_per_tray' => $seedPerTray,
            'active_planned_trays' => $activePlannedTrays,
            'recommended_new_trays' => $recommendedTrays,
            'required_seed_grams' => $requiredGrams,
            'priority' => $priority,
            'advice' => $advice,
        ];
    }

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'forecast_days' => $forecastDays,
        'summary' => [
            'seed_inventory_items' => count($seedInventory),
            'available_seed_kg' => round($totalSeedKg, 3),
            'required_seed_grams' => round($totalRequiredGrams, 1),
            'required_seed_kg' => round($totalRequiredGrams / 1000, 3),
            'shortage_grams' => max(0, round($totalRequiredGrams - ($totalSeedKg * 1000), 1)),
            'crops_need_seed' => count(array_filter($plans, fn($plan) => $plan['recommended_new_trays'] > 0)),
        ],
        'inventory' => $seedInventory,
        'plans' => $plans,
    ];
}
