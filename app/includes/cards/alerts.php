<?php

require_once __DIR__ . '/../sensor_service.php';

$sensorStatus = getLiveSensorStatus($db);
$sensorsAreHealthy = $sensorStatus['overall_status'] === 'ok';
?>

<div class="card">

<h2>🚨 <?= __('alarms') ?></h2>

<ul id="alerts" style="list-style:none;padding:0;">
    <?php if ($sensorsAreHealthy): ?>
        <li>🟢 <?= __('all_systems_normal') ?></li>
    <?php else: ?>
        <li>
            🔴 <?= htmlspecialchars(
                (string)($sensorStatus['overall_label'] ?? '--'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </li>
    <?php endif; ?>
</ul>

</div>
