<div class="card">
    <h2>📋 Quick Overview</h2>

    <div class="grid">
        <div class="tile">
            <h2>🌱 Actieve teelten</h2>
            <p><?= $teelten ?></p>
        </div>

        <div class="tile">
            <h2>📦 Producten</h2>
            <p><?= $producten ?></p>
        </div>

        <div class="tile">
            <h2>⚠ Lage voorraad</h2>
            <p><?= $lage_voorraad ?></p>
        </div>

        <div class="tile">
            <h2>💰 Omzet</h2>
            <p>€<?= number_format($omzet, 2) ?></p>
        </div>
    </div>
</div>
