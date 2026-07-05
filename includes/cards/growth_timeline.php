<?php
require_once __DIR__ . '/../growth_timeline_engine.php';

$timeline = getGrowthTimeline($db);
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📅 <?= htmlspecialchars(t('growth_timeline')) ?></h3>
            <p>
                <?= htmlspecialchars($timeline['crop_name'] ?? t('no_active_batch')) ?>
                <?= isset($timeline['batch_id']) ? ' | ' . htmlspecialchars(t('batch')) . ' #' . htmlspecialchars((string)$timeline['batch_id']) : '' ?>
            </p>
        </div>

        <span class="sensor-status-badge <?= ($timeline['current_stage'] ?? '') === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($timeline['label'] ?? t('no_data')) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <?php foreach (($timeline['items'] ?? []) as $item): ?>
            <div class="live-sensor-item <?= $item['done'] ? 'ok' : 'alarm' ?>">
                <strong><?= htmlspecialchars($item['label']) ?></strong>
                <span><?= htmlspecialchars($item['date']) ?></span>
                <small><?= $item['done'] ? htmlspecialchars(t('completed')) : htmlspecialchars(t('pending')) ?></small>
            </div>
        <?php endforeach; ?>

        <div class="live-sensor-item <?= ($timeline['days_overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('harvest_status')) ?></strong>
            <span>
                <?= ($timeline['days_overdue'] ?? 0) > 0
                    ? htmlspecialchars((string)$timeline['days_overdue']) . ' ' . htmlspecialchars(t('days_overdue'))
                    : htmlspecialchars((string)($timeline['days_until_harvest'] ?? 0)) . ' ' . htmlspecialchars(t('days_until_harvest'))
                ?>
            </span>
            <small><?= htmlspecialchars(t('current_day')) ?>: <?= htmlspecialchars((string)($timeline['day_number'] ?? '-')) ?></small>
        </div>
    </div>
</div>