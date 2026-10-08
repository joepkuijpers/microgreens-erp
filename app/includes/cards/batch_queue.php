<?php
require_once __DIR__ . '/../batch_queue_engine.php';

$queue = getBatchQueue($db);
$active = $queue['active'];
$next = $queue['next'];
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📦 <?= htmlspecialchars(__('batch_queue')) ?></h3>
            <p><?= htmlspecialchars(__('active_crop_next_batch_harvest_priority')) ?></p>
        </div>

        <span class="sensor-status-badge <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <?= ($queue['counts']['overdue'] ?? 0) > 0
                ? htmlspecialchars(__('overdue'))
                : htmlspecialchars(__('planning_ok')) ?>
        </span>
    </div>

    <div class="live-sensor-grid">

        <div class="live-sensor-item <?= $active ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('active_batch')) ?></strong>

            <span><?= $active ? htmlspecialchars($active['crop_name']) : htmlspecialchars(__('none')) ?></span>

            <small>
                <?= $active
                    ? htmlspecialchars(__('batch')) . ' #' . htmlspecialchars((string)$active['id']) . ' | ' . htmlspecialchars($active['status'])
                    : htmlspecialchars(__('no_active_batch'))
                ?>
            </small>
        </div>

        <div class="live-sensor-item <?= $next ? 'ok' : 'alarm' ?>">
            <strong><?= htmlspecialchars(__('next_batch')) ?></strong>

            <span><?= $next ? htmlspecialchars($next['crop_name']) : htmlspecialchars(__('no_queue')) ?></span>

            <small>
                <?= $next
                    ? htmlspecialchars(__('batch')) . ' #' . htmlspecialchars((string)$next['id'])
                    : htmlspecialchars(__('new_batch_needed'))
                ?>
            </small>
        </div>

        <div class="live-sensor-item <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('overdue')) ?></strong>

            <span><?= htmlspecialchars((string)($queue['counts']['overdue'] ?? 0)) ?></span>

            <small><?= htmlspecialchars(__('must_be_harvested_or_updated')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('completed')) ?></strong>

            <span><?= htmlspecialchars((string)($queue['counts']['completed'] ?? 0)) ?></span>

            <small><?= htmlspecialchars(__('historical_batches')) ?></small>
        </div>

    </div>
</div>