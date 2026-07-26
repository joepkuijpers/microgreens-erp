<?php
$planning = $productionData['planning'] ?? [];

$totalTrays = 0;
$totalSeed = 0;

foreach ($planning as $item) {
    $totalTrays += (int)$item['trays_needed'];
    $totalSeed += (float)$item['seed_needed_grams'];
}
?>

<div class="card">
    <h2>🟢 Capaciteitsoverzicht</h2>

    <p><strong>Geplande trays:</strong> <?= (int)$totalTrays ?></p>
    <p><strong>Benodigd zaad:</strong> <?= number_format($totalSeed, 1, ',', '.') ?> g</p>
    <p><strong>Actieve planningregels:</strong> <?= count($planning) ?></p>
</div>