<?php
require_once __DIR__ . '/../rack_capacity_engine.php';

$rackCapacity = getRackCapacity($db);
?>

<div class="dashboard-section">
    <h2>🧱 Rack / Tray Capaciteit</h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🧱 Rackcapaciteit</h3>
        <span class="sensor-status-badge <?= $rackCapacity['summary']['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
            <?= (int)$rackCapacity['summary']['free_positions'] ?> vrij
        </span>
    </div>

    <div class="live-sensor-item <?= $rackCapacity['summary']['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
        <span>Totale capaciteit</span>
        <small>
            Bezet: <?= (int)$rackCapacity['summary']['occupied_positions'] ?> /
            <?= (int)$rackCapacity['summary']['total_positions'] ?> plaatsen |
            <?= htmlspecialchars((string)$rackCapacity['summary']['occupancy_percent']) ?>%
        </small>
    </div>

    <?php foreach ($rackCapacity['racks'] as $rack): ?>
        <div class="live-sensor-item <?= $rack['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
            <span><?= htmlspecialchars($rack['rack_code']) ?></span>
            <small>
                Vrij: <?= (int)$rack['free_positions'] ?> |
                Bezet: <?= (int)$rack['occupied_positions'] ?> |
                Totaal: <?= (int)$rack['total_positions'] ?>
            </small>
        </div>

        <?php foreach ($rack['shelves'] as $shelfNumber => $positions): ?>
            <div class="live-sensor-item ok">
                <span>Plank <?= (int)$shelfNumber ?></span>
                <small>
                    <?php foreach ($positions as $position): ?>
                        <?= $position['occupied'] ? '🟥' : '🟩' ?><?= (int)$position['position'] ?>
                    <?php endforeach; ?>
                </small>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
