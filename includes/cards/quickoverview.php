<div class="card">
    <h2>📋 <?= htmlspecialchars(t('quick_overview')) ?></h2>

    <div class="grid">
        <div class="tile">
            <h2>🌱 <?= htmlspecialchars(t('active_batches')) ?></h2>
            <p><?= $teelten ?></p>
        </div>

        <div class="tile">
            <h2>📦 <?= htmlspecialchars(t('products')) ?></h2>
            <p><?= $producten ?></p>
        </div>

        <div class="tile">
            <h2>⚠ <?= htmlspecialchars(t('low_stock')) ?></h2>
            <p><?= $lage_voorraad ?></p>
        </div>

        <div class="tile">
            <h2>💰 <?= htmlspecialchars(t('revenue')) ?></h2>
            <p>€<?= number_format($omzet, 2) ?></p>
        </div>
    </div>
</div>