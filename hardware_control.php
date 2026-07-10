<?php
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.hardware-page {
    padding: 24px;
}

.hardware-header {
    background: linear-gradient(135deg, #0f766e, #1e3a8a);
    color: white;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
}

.hardware-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
}

.hardware-header p {
    margin: 0;
    opacity: 0.9;
}

.hardware-warning {
    background: #fff7ed;
    border-left: 6px solid #ea580c;
    color: #7c2d12;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-weight: 600;
}

.hardware-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.relay-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
}

.relay-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.relay-title {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
}

.relay-state {
    padding: 7px 12px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 13px;
}

.relay-on {
    background: #dcfce7;
    color: #166534;
}

.relay-off {
    background: #fee2e2;
    color: #991b1b;
}

.relay-meta {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 16px;
    line-height: 1.5;
}

.relay-actions {
    display: flex;
    gap: 10px;
}

.relay-btn {
    border: none;
    border-radius: 12px;
    padding: 11px 16px;
    font-weight: 800;
    cursor: pointer;
    flex: 1;
}

.btn-on {
    background: #16a34a;
    color: white;
}

.btn-off {
    background: #dc2626;
    color: white;
}

.btn-disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.hardware-footer {
    margin-top: 24px;
    color: #6b7280;
    font-size: 14px;
}

.mode-badge {
    display: inline-block;
    background: #e0f2fe;
    color: #075985;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 800;
    margin-top: 10px;
}
</style>

<div class="main">
    <div class="hardware-page">
    <div class="hardware-header">
        <h1>Hardware Control Center</h1>
        <p>Handmatige relaisbediening via GPIO HAL.</p>
        <div class="mode-badge" id="gpioMode">Mode laden...</div>
    </div>

    <div class="hardware-warning">
        Veiligheidsmodus: deze pagina is bedoeld voor simulation mode. Echte relais pas inschakelen na wiring-check.
    </div>

    <div class="hardware-grid" id="relayGrid">
        Laden...
    </div>

    <div class="hardware-footer" id="lastUpdate">
        Laatste update: laden...
    </div>
</div>

<script>
const relayOrder = [
    'heater',
    'cooler',
    'fan',
    'humidifier',
    'grow_light',
    'water_pump'
];

async function loadRelays() {
    const response = await fetch('api/gpio.php');
    const data = await response.json();

    document.getElementById('gpioMode').textContent = 'Mode: ' + data.mode;

    const grid = document.getElementById('relayGrid');
    grid.innerHTML = '';

    relayOrder.forEach((key) => {
        if (!data.outputs[key]) {
            return;
        }

        const relay = data.outputs[key];
        const isOn = relay.state === true;

        const card = document.createElement('div');
        card.className = 'relay-card';

        card.innerHTML = `
            <div class="relay-top">
                <div class="relay-title">${relay.label}</div>
                <div class="relay-state ${isOn ? 'relay-on' : 'relay-off'}">
                    ${relay.state_text}
                </div>
            </div>

            <div class="relay-meta">
                Output: <strong>${key}</strong><br>
                GPIO pin: <strong>${relay.gpio_pin}</strong><br>
                Active low: <strong>${relay.active_low ? 'ja' : 'nee'}</strong><br>
                Bijgewerkt: <strong>${relay.updated_at}</strong>
            </div>

            <div class="relay-actions">
                <button class="relay-btn btn-on ${isOn ? 'btn-disabled' : ''}" onclick="setRelay('${key}', 'on')" ${isOn ? 'disabled' : ''}>
                    AAN
                </button>
                <button class="relay-btn btn-off ${!isOn ? 'btn-disabled' : ''}" onclick="setRelay('${key}', 'off')" ${!isOn ? 'disabled' : ''}>
                    UIT
                </button>
            </div>
        `;

        grid.appendChild(card);
    });

    document.getElementById('lastUpdate').textContent = 'Laatste update: ' + data.timestamp;
}

async function setRelay(output, state) {
    await fetch(`api/relay_test.php?output=${encodeURIComponent(output)}&state=${encodeURIComponent(state)}`);
    await loadRelays();

}

loadRelays();
setInterval(loadRelays, 5000);
</script>

    </div>
</div>

<?php include 'includes/footer.php'; ?>