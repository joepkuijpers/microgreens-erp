<?php
require_once __DIR__ . '/../growth_timeline_engine.php';

$timeline = getGrowthTimeline($db);
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📅 Growth Timeline</h3>
            <p>
                <?= htmlspecialchars($timeline['crop_name'] ?? 'Geen actieve teelt') ?>
                <?= isset($timeline['batch_id']) ? ' | Batch #' . htmlspecialchars((string)$timeline['batch_id']) : '' ?>
            </p>
        </div>

        <span class="sensor-status-badge <?= ($timeline['current_stage'] ?? '') === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($timeline['label'] ?? 'Geen data') ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <?php foreach (($timeline['items'] ?? []) as $item): ?>
            <div class="live-sensor-item <?= $item['done'] ? 'ok' : 'alarm' ?>">
                <strong><?= htmlspecialchars($item['label']) ?></strong>
                <span><?= htmlspecialchars($item['date']) ?></span>
                <small><?= $item['done'] ? 'Voltooid' : 'Nog open' ?></small>
            </div>
        <?php endforeach; ?>

        <div class="live-sensor-item <?= ($timeline['days_overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong>Oogststatus</strong>
            <span>
                <?= ($timeline['days_overdue'] ?? 0) > 0
                    ? htmlspecialchars((string)$timeline['days_overdue']) . ' dagen te laat'
                    : htmlspecialchars((string)($timeline['days_until_harvest'] ?? 0)) . ' dagen tot oogst'
                ?>
            </span>
            <small>Huidige dag: <?= htmlspecialchars((string)($timeline['day_number'] ?? '-')) ?></small>
        </div>
    </div>
</div>
