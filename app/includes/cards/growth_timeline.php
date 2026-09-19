<?php
require_once __DIR__ . '/../growth_timeline_engine.php';

$timeline = getGrowthTimeline($db);
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📅 <?= htmlspecialchars(__('growth_timeline')) ?></h3>
            <p>
                <?= htmlspecialchars($timeline['crop_name'] ?? __('no_active_batch')) ?>
                <?= isset($timeline['batch_id']) ? ' | ' . htmlspecialchars(__('batch')) . ' #' . htmlspecialchars((string)$timeline['batch_id']) : '' ?>
            </p>
        </div>

        <span class="sensor-status-badge <?= ($timeline['current_stage'] ?? '') === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($timeline['label'] ?? __('no_data')) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <?php foreach (($timeline['items'] ?? []) as $item): ?>
            <div class="live-sensor-item <?= $item['done'] ? 'ok' : 'alarm' ?>">
                <strong><?= htmlspecialchars($item['label']) ?></strong>
                <span><?= htmlspecialchars($item['date']) ?></span>
                <small><?= $item['done'] ? htmlspecialchars(__('completed')) : htmlspecialchars(__('pending')) ?></small>
            </div>
        <?php endforeach; ?>

        <div class="live-sensor-item <?= ($timeline['days_overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('harvest_status')) ?></strong>
            <span>
                <?= ($timeline['days_overdue'] ?? 0) > 0
                    ? htmlspecialchars((string)$timeline['days_overdue']) . ' ' . htmlspecialchars(__('days_overdue'))
                    : htmlspecialchars((string)($timeline['days_until_harvest'] ?? 0)) . ' ' . htmlspecialchars(__('days_until_harvest'))
                ?>
            </span>
            <small><?= htmlspecialchars(__('current_day')) ?>: <?= htmlspecialchars((string)($timeline['day_number'] ?? '-')) ?></small>
        </div>
    </div>
</div>