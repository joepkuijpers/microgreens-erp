<?php
require_once __DIR__ . '/../seed_planning_engine.php';

$seedPlanning = getSeedPlanning($db, 14);
$seedStatus = $seedPlanning['summary']['shortage_grams'] > 0 ? 'alarm' : 'ok';
?>

<div class="dashboard-section">
    <h2>🌱 Seed Planning</h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🌱 Zaadplanning</h3>
        <span class="sensor-status-badge <?= $seedStatus ?>">
            <?= htmlspecialchars((string)$seedPlanning['summary']['available_seed_kg']) ?> kg beschikbaar
        </span>
    </div>

    <div class="live-sensor-item <?= $seedStatus ?>">
        <span>Zaadbehoefte 14 dagen</span>
        <small>
            Nodig: <?= htmlspecialchars((string)$seedPlanning['summary']['required_seed_kg']) ?> kg |
            Tekort: <?= htmlspecialchars((string)round($seedPlanning['summary']['shortage_grams'] / 1000, 3)) ?> kg |
            Gewassen te plannen: <?= (int)$seedPlanning['summary']['crops_need_seed'] ?>
        </small>
    </div>

    <?php foreach ($seedPlanning['plans'] as $plan): ?>
        <div class="live-sensor-item <?= htmlspecialchars($plan['priority']) ?>">
            <span><?= htmlspecialchars($plan['crop_name']) ?></span>
            <small>
                Actief/gepland: <?= (int)$plan['active_planned_trays'] ?> trays |
                Advies nieuwe trays: <?= (int)$plan['recommended_new_trays'] ?><br>
                Zaadnorm: <?= htmlspecialchars((string)$plan['seed_grams_per_tray']) ?> g/tray |
                Nodig: <?= htmlspecialchars((string)$plan['required_seed_grams']) ?> g<br>
                Advies: <?= htmlspecialchars($plan['advice']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>
