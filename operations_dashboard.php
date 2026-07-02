<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.ops-page {
    padding: 24px;
}

.ops-header {
    background: linear-gradient(135deg, #111827, #0f766e);
    color: white;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
}

.ops-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
}

.ops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.ops-card {
    background: white;
    border-radius: 18px;
    padding: 18px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.ops-card h3 {
    margin: 0 0 12px 0;
    color: #111827;
}

.ops-status {
    font-size: 22px;
    font-weight: 900;
    margin-bottom: 10px;
}

.ops-ok {
    color: #16a34a;
}

.ops-warning {
    color: #ea580c;
}

.ops-danger {
    color: #dc2626;
}

.ops-muted {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.ops-list {
    margin-top: 10px;
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
}

.ops-small {
    font-size: 13px;
    color: #6b7280;
}

.ops-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 12px;
    margin: 2px;
}

.badge-on {
    background: #dcfce7;
    color: #166534;
}

.badge-off {
    background: #fee2e2;
    color: #991b1b;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}
</style>

<main class="main"><div class="ops-page">
    <div class="ops-header">
        <h1>Operations Dashboard</h1>
        <p>Centrale status van automation, safety, scheduler, watchdog en GPIO.</p>
    </div>

    <div class="ops-grid">
        <div class="ops-card">
            <h3>Hardware Health</h3>
            <div id="healthStatus" class="ops-status ops-muted">Laden...</div>
            <div id="healthDetails" class="ops-list"></div>
        </div>

        <div class="ops-card">
            <h3>Watchdog</h3>
            <div id="watchdogStatus" class="ops-status ops-muted">Laden...</div>
            <div id="watchdogDetails" class="ops-list"></div>
        </div>

        <div class="ops-card">
            <h3>Scheduler</h3>
            <div id="schedulerStatus" class="ops-status ops-muted">Laden...</div>
            <div id="schedulerDetails" class="ops-list"></div>
        </div>

        <div class="ops-card">
            <h3>Manual Override</h3>
            <div id="overrideStatus" class="ops-status ops-muted">Laden...</div>
            <div id="overrideDetails" class="ops-list"></div>
        </div>

        <div class="ops-card">
            <h3>GPIO Outputs</h3>
            <div id="gpioStatus" class="ops-status ops-muted">Laden...</div>
            <div id="gpioDetails" class="ops-list"></div>
        </div>

        <div class="ops-card">
            <h3>Priority Decisions</h3>
            <div id="priorityStatus" class="ops-status ops-muted">Laden...</div>
            <div id="priorityDetails" class="ops-list"></div>
        </div>
    </div>
</div>

<script>
function statusClass(value) {
    if (value === 'ok') return 'ops-status ops-ok';
    if (value === 'warning') return 'ops-status ops-warning';
    if (value === 'timeout' || value === 'error') return 'ops-status ops-danger';
    return 'ops-status ops-muted';
}

async function loadJson(url) {
    const response = await fetch(url);
    return await response.json();
}

async function updateOperationsDashboard() {
    const [health, watchdog, scheduler, override, gpio, automation] = await Promise.all([
        loadJson('api/hardware_health.php'),
        loadJson('api/watchdog.php'),
        loadJson('api/scheduler.php'),
        loadJson('api/override.php'),
        loadJson('api/gpio.php'),
        loadJson('api/automation.php')
    ]);

    const healthOverall = health.health?.overall ?? 'unknown';
    healthStatus.className = statusClass(healthOverall);
    healthStatus.textContent = healthOverall.toUpperCase();
    healthDetails.innerHTML = Object.entries(health.health?.checks ?? {}).map(([key, item]) => {
        return `${key}: <strong>${item.status}</strong><br>`;
    }).join('');

    watchdogStatus.className = statusClass(watchdog.status);
    watchdogStatus.textContent = watchdog.status.toUpperCase();
    watchdogDetails.innerHTML = `
        Age: <strong>${watchdog.age_seconds ?? '-'} sec</strong><br>
        Timeout: <strong>${watchdog.timeout_seconds ?? '-'} sec</strong><br>
        Fail-safe nodig: <strong>${watchdog.fail_safe_required ? 'JA' : 'NEE'}</strong>
    `;

    const activeSchedules = (scheduler.schedules ?? []).filter(s => s.enabled).length;
    schedulerStatus.className = 'ops-status ops-ok';
    schedulerStatus.textContent = activeSchedules + ' actief';
    schedulerDetails.innerHTML = (scheduler.schedules ?? []).map(s => {
        return `${s.name}: <strong>${s.state_text}</strong> (${s.start_time}-${s.end_time})<br>`;
    }).join('');

    const overrideState = override.override ?? {};
    overrideStatus.className = overrideState.active ? 'ops-status ops-warning' : 'ops-status ops-ok';
    overrideStatus.textContent = overrideState.active ? 'ACTIEF' : 'UIT';
    overrideDetails.innerHTML = `
        Output: <strong>${overrideState.output ?? '-'}</strong><br>
        State: <strong>${overrideState.state ? 'AAN' : 'UIT'}</strong><br>
        Expires: <strong>${overrideState.expires_at ?? '-'}</strong>
    `;

    gpioStatus.className = 'ops-status ops-ok';
    gpioStatus.textContent = (gpio.mode ?? 'unknown').toUpperCase();
    gpioDetails.innerHTML = Object.entries(gpio.outputs ?? {}).map(([key, item]) => {
        const badge = item.state ? 'badge-on' : 'badge-off';
        return `<span class="ops-badge ${badge}">${key}: ${item.state_text}</span>`;
    }).join(' ');

    const priority = automation.priority ?? {};
    priorityStatus.className = 'ops-status ops-ok';
    priorityStatus.textContent = 'OK';
    priorityDetails.innerHTML = Object.entries(priority.resolution ?? {}).map(([key, item]) => {
        return `${key}: <strong>${item.final_state ? 'AAN' : 'UIT'}</strong> via ${item.winner}<br>`;
    }).join('');
}

updateOperationsDashboard();
setInterval(updateOperationsDashboard, 5000);
</script>

</div></main>

<?php include 'includes/footer.php'; ?>
