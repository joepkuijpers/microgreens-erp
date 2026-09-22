<?php
require_once __DIR__ . '/../../app/includes/db_connection.php';
$db = getDbConnection();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gpioPin = (int)($_POST['gpio_pin'] ?? 17);
    $lightType = $_POST['light_type'] ?? 'white_led';
    $ppfdMin = (int)($_POST['ppfd_min'] ?? 150);
    $ppfdMax = (int)($_POST['ppfd_max'] ?? 250);
    
    // Bereken Lux grenzen op basis van factor
    $factor = 54.0; 
    if ($lightType === 'blurple_led') $factor = 83.0;
    if ($lightType === 'sunlight') $factor = 50.0;
    
    $luxMin = (int)($ppfdMin * $factor);
    $luxMax = (int)($ppfdMax * $factor);

    $stmt = $db->prepare("UPDATE lighting_configs SET gpio_pin = ?, light_type = ?, target_ppfd_min = ?, target_ppfd_max = ?, light_min_lux = ?, light_max_lux = ?, spectrum_factor = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1");
    $stmt->execute([$gpioPin, $lightType, $ppfdMin, $ppfdMax, $luxMin, $luxMax, $factor]);
    $message = "✅ Instellingen opgeslagen! (PPFD: {$ppfdMin}-{$ppfdMax} µmol ≈ {$luxMin}-{$luxMax} Lux)";
}

$config = $db->query("SELECT * FROM lighting_configs WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$factorDisplay = $config['spectrum_factor'] ?? 54.0;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Licht Configuraties (PPFD)</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f8; padding: 20px; }
        .card { background: white; padding: 20px; max-width: 600px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2e7d32; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #2e7d32; color: white; border: none; padding: 10px 20px; cursor: pointer; width: 100%; margin-top: 5px;}
        .alert { background: #e8f5e9; color: #2e7d32; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c8e6c9; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #ffeeba; font-size: 0.9em; }
        .calc-box { background: #e3f2fd; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 0.9em; border: 1px solid #bbdefb; }
        .btn-test { background: #f57c00; margin-top: 10px; width: auto; display: inline-block; }
        .btn-off { background: #555; margin-top: 10px; width: auto; display: inline-block; }
        .button-group { margin-top: 15px; }
    </style>
</head>
<body>
<div class="card">
    <h1>💡 Licht Instellingen (PPFD)</h1>
    
    <!-- CRUCIALE DISCLAIMER OVER AFGELEIDE WAARDE -->
    <div class="warning">
        ⚠️ <strong>Let op:</strong> De getoonde PPFD-waarden (µmol) zijn <em>schattingen</em> afgeleid van een Lux-sensor (BH1750). 
        De nauwkeurigheid hangt af van het spectrum van je lampen (marge ±15%). 
        Voor wetenschappelijke kalibratie is een echte PAR-meter nodig.
    </div>

    <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <form method="POST" id="lightForm">
        <div class="form-group">
            <label>GPIO Pin (BCM):</label>
            <input type="number" name="gpio_pin" value="<?= htmlspecialchars($config['gpio_pin']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Type Lamp:</label>
            <select name="light_type" id="light_type" onchange="calcLux()">
                <option value="white_led" <?= $config['light_type'] === 'white_led' ? 'selected' : '' ?>>Witte LED</option>
                <option value="blurple_led" <?= $config['light_type'] === 'blurple_led' ? 'selected' : '' ?>>Rood/Blauw (Blurple)</option>
                <option value="sunlight" <?= $config['light_type'] === 'sunlight' ? 'selected' : '' ?>>Zonlicht / HPS</option>
            </select>
        </div>

        <div class="form-group">
            <label>Doel PPFD Min (µmol/m²/s):</label>
            <input type="number" name="ppfd_min" id="ppfd_min" value="<?= htmlspecialchars($config['target_ppfd_min']) ?>" required oninput="calcLux()">
        </div>
        
        <div class="form-group">
            <label>Doel PPFD Max (µmol/m²/s):</label>
            <input type="number" name="ppfd_max" id="ppfd_max" value="<?= htmlspecialchars($config['target_ppfd_max']) ?>" required oninput="calcLux()">
        </div>

        <div class="calc-box">
            <strong>Berekende Lux waarden (voor BH1750 sensor):</strong><br>
            Lamp AAN als <span id="lux_min_display"><?= $config['light_min_lux'] ?></span> Lux<br>
            Lamp UIT als <span id="lux_max_display"><?= $config['light_max_lux'] ?></span> Lux
        </div>

        <button type="submit">💾 Opslaan</button>
    </form>

    <hr>
    <h3>🧪 Handmatige Test</h3>
    <div id="status" style="margin: 10px 0; font-weight: bold; color: #555;">Status: Wachten...</div>
    <div class="button-group">
        <button class="btn-test" onclick="testLight(true)">🔦 Test AAN</button>
        <button class="btn-off" onclick="testLight(false)">⚫ Test UIT</button>
    </div>
</div>

<script>
const factors = { 'white_led': 54, 'blurple_led': 83, 'sunlight': 50 };
function calcLux() {
    const type = document.getElementById('light_type').value;
    const min = parseInt(document.getElementById('ppfd_min').value) || 0;
    const max = parseInt(document.getElementById('ppfd_max').value) || 0;
    const factor = factors[type];
    document.getElementById('lux_min_display').innerText = Math.round(min * factor);
    document.getElementById('lux_max_display').innerText = Math.round(max * factor);
}
function testLight(state) {
    const pin = <?= (int)$config['gpio_pin'] ?>;
    // Let op: pas het pad aan als je api-bestand anders staat
    fetch('/microgreens/PHP/public/api/gpio_test.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pin: pin, state: state})
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('status').innerText = d.message;
        document.getElementById('status').style.color = d.success ? 'green' : 'red';
    })
    .catch(e => {
        document.getElementById('status').innerText = "Fout: " + e;
        document.getElementById('status').style.color = 'red';
    });
}
calcLux();
</script>
</body>
</html>
