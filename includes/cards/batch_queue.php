<?php
require_once __DIR__ . '/../batch_queue_engine.php';

$queue = getBatchQueue($db);
$active = $queue['active'];
$next = $queue['next'];
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>📦 Batch Queue</h3>
            <p>Actieve teelt, volgende batch en oogstprioriteit</p>
        </div>
        <span class="sensor-status-badge <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'Achterstallig' : 'Planning OK' ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $active ? 'alarm' : 'ok' ?>">
            <strong>Actieve batch</strong>
            <span><?= $active ? htmlspecialchars($active['crop_name']) : 'Geen' ?></span>
            <small><?= $active ? 'Batch #' . htmlspecialchars((string)$active['id']) . ' | ' . htmlspecialchars($active['status']) : 'Geen actieve teelt' ?></small>
        </div>

        <div class="live-sensor-item <?= $next ? 'ok' : 'alarm' ?>">
            <strong>Volgende batch</strong>
            <span><?= $next ? htmlspecialchars($next['crop_name']) : 'Geen wachtrij' ?></span>
            <small><?= $next ? 'Batch #' . htmlspecialchars((string)$next['id']) : 'Nieuwe batch nodig' ?></small>
        </div>

        <div class="live-sensor-item <?= ($queue['counts']['overdue'] ?? 0) > 0 ? 'alarm' : 'ok' ?>">
            <strong>Achterstallig</strong>
            <span><?= htmlspecialchars((string)($queue['counts']['overdue'] ?? 0)) ?></span>
            <small>Moet geoogst of bijgewerkt worden</small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Afgerond</strong>
            <span><?= htmlspecialchars((string)($queue['counts']['completed'] ?? 0)) ?></span>
            <small>Historische batches</small>
        </div>
    </div>
</div>
