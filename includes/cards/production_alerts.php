<?php
$alerts = $productionData['alerts'] ?? [];
?>

<div class="card">
    <h2>⚠️ <?= htmlspecialchars(t('production_alerts')) ?></h2>

    <?php if (empty($alerts)): ?>
        <p><?= htmlspecialchars(t('no_production_alerts')) ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($alerts as $alert): ?>
                <li><?= htmlspecialchars($alert['message']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>