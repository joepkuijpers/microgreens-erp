<?php

function getProductionPlanning(PDO $db): array
{
    $sales = $db->query("
        SELECT
            s.id,
            s.customer_name,
            s.sale_date,
            s.product,
            s.quantity,
            s.status,
            s.product_id,
            p.name AS product_name,
            cp.id AS crop_profile_id,
            cp.crop_name,
            cp.grow_days_min,
            cp.grow_days_max,
            cp.seed_grams_per_tray,
            cp.expected_yield_grams_per_tray
        FROM sales s
        LEFT JOIN products p ON p.id = s.product_id
        LEFT JOIN crop_profiles cp
            ON cp.crop_name = COALESCE(p.name, s.product)
        WHERE s.status IS NULL
           OR LOWER(s.status) NOT IN ('cancelled', 'geannuleerd')
        ORDER BY s.sale_date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $planning = [];
    $alerts = [];

    foreach ($sales as $sale) {
        $quantity = (float)($sale['quantity'] ?? 0);
        $yieldPerTray = (float)($sale['expected_yield_grams_per_tray'] ?? 0);
        $growDays = (int)($sale['grow_days_max'] ?? 0);

        if (!$sale['crop_profile_id']) {
            $alerts[] = [
                'type' => 'missing_profile',
                'message' => 'Geen gewasprofiel gevonden voor product: ' . ($sale['product_name'] ?: $sale['product'])
            ];
            continue;
        }

        if ($yieldPerTray <= 0) {
            $alerts[] = [
                'type' => 'missing_yield',
                'message' => 'Geen verwachte opbrengst per tray ingesteld voor: ' . $sale['crop_name']
            ];
            continue;
        }

        $traysNeeded = (int)ceil($quantity / $yieldPerTray);

        $harvestDate = $sale['sale_date'] ?: date('Y-m-d');
        $sowDate = date('Y-m-d', strtotime($harvestDate . ' -' . $growDays . ' days'));

        $planning[] = [
            'sale_id' => $sale['id'],
            'customer_name' => $sale['customer_name'],
            'product' => $sale['crop_name'],
            'quantity' => $quantity,
            'yield_per_tray' => $yieldPerTray,
            'trays_needed' => $traysNeeded,
            'seed_needed_grams' => $traysNeeded * (float)$sale['seed_grams_per_tray'],
            'sow_date' => $sowDate,
            'expected_harvest_date' => $harvestDate,
            'grow_days' => $growDays,
            'status' => $sale['status'] ?: 'planned'
        ];
    }

    return [
        'planning' => $planning,
        'alerts' => $alerts
    ];
}
