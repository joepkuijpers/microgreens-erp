<div class="card">
    <h2>🟢 <?= __('system_status') ?></h2>

    <p>🖥 Raspberry Pi: <strong><?= __('online') ?></strong></p>
    <p>🌐 Apache: <strong><?= __('active') ?></strong></p>
    <p>💾 <?= __('database') ?>: <strong id="databaseStatus">--</strong></p>

    <p>📡 <?= __('sensors') ?>: <span id="sensorStatus">⏳ <?= __('loading') ?></span></p>
    <p>🕒 <?= __('last_measurement') ?>: <span id="statusLastUpdate">--</span></p>
    <p>⏱ <?= __('last_update') ?>: <span id="statusSecondsAgo">--</span></p>
    <p>🔄 <?= __('refresh') ?>: <span id="statusRefresh">--</span></p>
</div>