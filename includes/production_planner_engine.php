<?php

function getProductionPlanner(PDO $db): array
{
    $profiles = $db->query("
        SELECT *
        FROM crop_profiles
        ORDER BY crop_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $plans = [];
    $today = new DateTimeImmutable('today');

    foreach ($profiles as $profile) {
        $profileId = (int)$profile['id'];
        $growMin = (int)($profile['grow_days_min'] ?? 7);
        $growMax = (int)($profile['grow_days_max'] ?? 14);
        $cycleDays = max(1, (int)ceil(($growMin + $growMax) / 2));

        $stmt = $db->prepare("
            SELECT
                count(*) AS total_batches,
                COALESCE(SUM(CASE WHEN lower(COALESCE(status, '')) IN ('active', 'actief') THEN tray_count ELSE 0 END), 0) AS active_trays,
                COALESCE(SUM(CASE WHEN lower(COALESCE(status, '')) IN ('planned', 'gepland', 'gezaaid', 'sown') THEN tray_count ELSE 0 END), 0) AS planned_trays,
                COALESCE(SUM(CASE WHEN lower(COALESCE(status, '')) IN ('harvested', 'geoogst', 'klaar', 'afgerond') THEN 1 ELSE 0 END), 0) AS harvested_batches,
                MAX(sow_date) AS last_sow_date
            FROM grow_batches
            WHERE crop_profile_id = :profile_id
        ");
        $stmt->execute([':profile_id' => $profileId]);
        $batchStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastSowDate = $batchStats['last_sow_date'] ?? null;

        if ($lastSowDate) {
            $nextSow = (new DateTimeImmutable($lastSowDate))->modify('+' . $cycleDays . ' days');
            if ($nextSow < $today) {
                $nextSow = $today;
            }
        } else {
            $nextSow = $today;
        }

        $expectedHarvest = $nextSow->modify('+' . $growMin . ' days');

        $activeTrays = (int)($batchStats['active_trays'] ?? 0);
        $plannedTrays = (int)($batchStats['planned_trays'] ?? 0);

        if ($activeTrays > 0) {
            $advice = 'Productie actief';
            $priority = 'ok';
        } elseif ($plannedTrays > 0) {
            $advice = 'Gepland, wacht op activatie';
            $priority = 'warning';
        } else {
            $advice = 'Nieuwe batch plannen';
            $priority = 'alarm';
        }

        $plans[] = [
            'crop_profile_id' => $profileId,
            'crop_name' => $profile['crop_name'],
            'cycle_days' => $cycleDays,
            'active_trays' => $activeTrays,
            'planned_trays' => $plannedTrays,
            'harvested_batches' => (int)($batchStats['harvested_batches'] ?? 0),
            'last_sow_date' => $lastSowDate,
            'next_sow_date' => $nextSow->format('Y-m-d'),
            'expected_harvest_date' => $expectedHarvest->format('Y-m-d'),
            'advice' => $advice,
            'priority' => $priority,
        ];
    }

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'plans' => $plans,
        'summary' => [
            'profiles' => count($profiles),
            'needs_planning' => count(array_filter($plans, fn($plan) => $plan['priority'] === 'alarm')),
            'active_crops' => count(array_filter($plans, fn($plan) => $plan['active_trays'] > 0)),
        ],
    ];
}
