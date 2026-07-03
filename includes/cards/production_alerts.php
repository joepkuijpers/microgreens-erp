<?php
$alerts = $productionData['alerts'] ?? [];
?>

<div class="card">
    <h2>⚠️ Productiewaarschuwingen</h2>

    <?php if (empty($alerts)): ?>
        <p>Geen productiewaarschuwingen.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($alerts as $alert): ?>
                <li><?= htmlspecialchars($alert['message']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
