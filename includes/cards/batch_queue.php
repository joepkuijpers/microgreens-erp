<?php
require_once __DIR__ . '/../batch_queue_engine.php';

$queue = getBatchQueue($db);
$active = $queue['active'];
$next = $queue['next'];
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📦 <?= htmlspecialchars(t('batch_queue')) ?></h3>
            <p><?= htmlspecialchars(t('active_crop_next_batch_harvest_priority')) ?></p>
        </div>

        <span class="sensor-status-badge <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <?= ($queue['counts']['overdue'] ?? 0) > 0
                ? htmlspecialchars(t('overdue'))
                : htmlspecialchars(t('planning_ok')) ?>
        </span>
    </div>

    <div class="live-sensor-grid">

        <div class="live-sensor-item <?= $active ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('active_batch')) ?></strong>

            <span><?= $active ? htmlspecialchars($active['crop_name']) : htmlspecialchars(t('none')) ?></span>

            <small>
                <?= $active
                    ? htmlspecialchars(t('batch')) . ' #' . htmlspecialchars((string)$active['id']) . ' | ' . htmlspecialchars($active['status'])
                    : htmlspecialchars(t('no_active_batch'))
                ?>
            </small>
        </div>

        <div class="live-sensor-item <?= $next ? 'ok' : 'alarm' ?>">
            <strong><?= htmlspecialchars(t('next_batch')) ?></strong>

            <span><?= $next ? htmlspecialchars($next['crop_name']) : htmlspecialchars(t('no_queue')) ?></span>

            <small>
                <?= $next
                    ? htmlspecialchars(t('batch')) . ' #' . htmlspecialchars((string)$next['id'])
                    : htmlspecialchars(t('new_batch_needed'))
                ?>
            </small>
        </div>

        <div class="live-sensor-item <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('overdue')) ?></strong>

            <span><?= htmlspecialchars((string)($queue['counts']['overdue'] ?? 0)) ?></span>

            <small><?= htmlspecialchars(t('must_be_harvested_or_updated')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('completed')) ?></strong>

            <span><?= htmlspecialchars((string)($queue['counts']['completed'] ?? 0)) ?></span>

            <small><?= htmlspecialchars(t('historical_batches')) ?></small>
        </div>

    </div>
</div>