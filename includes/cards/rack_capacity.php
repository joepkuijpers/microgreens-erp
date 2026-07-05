<?php
require_once __DIR__ . '/../rack_capacity_engine.php';

$rackCapacity = getRackCapacity($db);
?>

<div class="dashboard-section">
    <h2>🧱 <?= htmlspecialchars(t('rack_tray_capacity')) ?></h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🧱 <?= htmlspecialchars(t('rack_capacity')) ?></h3>
        <span class="sensor-status-badge <?= $rackCapacity['summary']['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
            <?= (int)$rackCapacity['summary']['free_positions'] ?> <?= htmlspecialchars(t('free')) ?>
        </span>
    </div>

    <div class="live-sensor-item <?= $rackCapacity['summary']['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
        <span><?= htmlspecialchars(t('total_capacity')) ?></span>
        <small>
            <?= htmlspecialchars(t('occupied')) ?>: <?= (int)$rackCapacity['summary']['occupied_positions'] ?> /
            <?= (int)$rackCapacity['summary']['total_positions'] ?> <?= htmlspecialchars(t('positions')) ?> |
            <?= htmlspecialchars((string)$rackCapacity['summary']['occupancy_percent']) ?>%
        </small>
    </div>

    <?php foreach ($rackCapacity['racks'] as $rack): ?>
        <div class="live-sensor-item <?= $rack['free_positions'] > 0 ? 'ok' : 'alarm' ?>">
            <span><?= htmlspecialchars($rack['rack_code']) ?></span>
            <small>
                <?= htmlspecialchars(t('free')) ?>: <?= (int)$rack['free_positions'] ?> |
                <?= htmlspecialchars(t('occupied')) ?>: <?= (int)$rack['occupied_positions'] ?> |
                <?= htmlspecialchars(t('total')) ?>: <?= (int)$rack['total_positions'] ?>
            </small>
        </div>

        <?php foreach ($rack['shelves'] as $shelfNumber => $positions): ?>
            <div class="live-sensor-item ok">
                <span><?= htmlspecialchars(t('shelf')) ?> <?= (int)$shelfNumber ?></span>
                <small>
                    <?php foreach ($positions as $position): ?>
                        <?= $position['occupied'] ? '🟥' : '🟩' ?><?= (int)$position['position'] ?>
                    <?php endforeach; ?>
                </small>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>