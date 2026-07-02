<?php
require_once __DIR__ . '/../../hardware/gpio/driver.php';

$apiPrefix = $navPrefix ?? '';
$gpioConfig = gpioConfig();
$gpioOutputs = gpioReadOutputs();
?>

<div class="climate-card hardware-control-card">
    <div class="live-sensor-header">
        <div>
            <h3>Hardware Control Center</h3>
            <p>Modus: <?= htmlspecialchars($gpioConfig['mode'] ?? 'simulation') ?></p>
        </div>

        <span class="sensor-status-badge ok">
            GPIO monitor actief
        </span>
    </div>

    <div class="live-sensor-grid">
        <?php foreach (($gpioConfig['relays'] ?? []) as $name => $relay): ?>
            <?php
            $output = $gpioOutputs[$name] ?? null;
            $state = (bool)($output['state'] ?? false);
            ?>
            <div class="live-sensor-item <?= $state ? 'alarm' : 'ok' ?>">
                <strong><?= htmlspecialchars($relay['label']) ?></strong>
                <span><?= $state ? 'AAN' : 'UIT' ?></span>
                <small>GPIO <?= htmlspecialchars((string)$relay['gpio_pin']) ?> | <?= htmlspecialchars($name) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
async function updateHardwareControlCenter() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/gpio.php');
        const data = await response.json();

        const card = document.querySelector('.hardware-control-card');
        if (!card || !data.outputs) return;

        const items = card.querySelectorAll('.live-sensor-item');

        Object.values(data.outputs).forEach((output, index) => {
            if (!items[index]) return;

            const state = output.state === true;
            items[index].classList.remove('ok', 'alarm');
            items[index].classList.add(state ? 'alarm' : 'ok');

            items[index].querySelector('span').textContent = state ? 'AAN' : 'UIT';
        });

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateHardwareControlCenter, 5000);
</script>
