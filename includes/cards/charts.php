<div class="card">
    <h2>📈 <?= htmlspecialchars(__('sensor_charts')) ?></h2>

    <div style="margin-bottom:15px;">
        <button onclick="loadCharts('1h')"><?= htmlspecialchars(__('one_hour')) ?></button>
        <button onclick="loadCharts('24h')"><?= htmlspecialchars(__('twenty_four_hours')) ?></button>
        <button onclick="loadCharts('7d')"><?= htmlspecialchars(__('seven_days')) ?></button>
        <button onclick="loadCharts('30d')"><?= htmlspecialchars(__('thirty_days')) ?></button>
    </div>

    <div style="height:350px;">
        <canvas id="temperatureChart"></canvas>
    </div>
</div>