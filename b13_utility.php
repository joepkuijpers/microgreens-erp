<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);

// Taal
if (isset($_GET['lang'])) $_SESSION['lang'] = $_GET['lang'];
$lang = $_SESSION['lang'] ?? 'nl';
$T = [
    'nl' => ['title' => 'Utility Beheer (B13)', 'meter' => 'Kies Meter', 'type' => 'Type', 'val' => 'Stand (bijv. 1234,56)', 'unit' => 'Eenheid', 'submit' => 'Opslaan', 'success' => 'Opgeslagen!', 'history' => 'Laatste 5 standen', 'back' => 'Terug naar Dashboard', 'select' => '-- Selecteer --'],
    'en' => ['title' => 'Utility Management (B13)', 'meter' => 'Select Meter', 'type' => 'Type', 'val' => 'Reading (e.g. 1234.56)', 'unit' => 'Unit', 'submit' => 'Save', 'success' => 'Saved!', 'history' => 'Last 5 Readings', 'back' => 'Back to Dashboard', 'select' => '-- Select --']
];
$t = $T[$lang];
$msg = "";

// Verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meter_id'])) {
    $meter_id = intval($_POST['meter_id']);
    $type = $_POST['utility_type'];
    
    // BELANGRIJK: Vervang komma door punt voor opslag
    $raw_input = str_replace(',', '.', $_POST['reading_value']);
    $value = floatval($raw_input);
    
    $unit = $_POST['unit'];
    $date = date('Y-m-d');
    $time = date('H:i:s');

    if ($meter_id > 0 && $value > 0) {
        $stmt = $db->prepare("INSERT INTO utility_logs (meter_id, utility_type, reading_value, unit, log_date, log_time) VALUES (:mid, :type, :val, :unit, :date, :time)");
        $stmt->bindValue(':mid', $meter_id, SQLITE3_INTEGER);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':val', $value, SQLITE3_FLOAT);
        $stmt->bindValue(':unit', $unit, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':time', $time, SQLITE3_TEXT);

        if ($stmt->execute()) {
            $msg = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin-bottom:20px;border-left:5px solid #28a745;'>
                    <strong>✅ " . $t['success'] . "</strong><br>
                    " . $type . ": <strong>" . number_format($value, 2, ',', '.') . " " . $unit . "</strong>
                    </div>";
        } else {
            $msg = "<div style='background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;'>❌ Fout: " . $db->lastErrorMsg() . "</div>";
        }
    } else {
        $msg = "<div style='background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;'>❌ Vul een geldige meter en waarde in.</div>";
    }
}

// Ophalen meters
$meters = [];
$res = $db->query("SELECT id, meter_type, location, unit FROM meters ORDER BY meter_type");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $meters[] = $row; }
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f4f6f8;margin:0;padding:20px;}
        .container{max-width:600px;margin:0 auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
        h2{margin-top:0;color:#333;border-bottom:2px solid #8b5cf6;padding-bottom:10px;display:inline-block;}
        label{display:block;margin-top:20px;font-weight:600;color:#444;font-size:0.95rem;}
        input, select{width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;font-size:1rem;box-sizing:border-box;transition:border 0.3s;}
        input:focus, select:focus{border-color:#8b5cf6;outline:none;ring:2px solid #8b5cf6;}
        button{background:#8b5cf6;color:white;border:none;padding:15px;margin-top:25px;border-radius:6px;cursor:pointer;font-size:1.1rem;font-weight:bold;width:100%;transition:background 0.3s;}
        button:hover{background:#7c3aed;}
        table{width:100%;margin-top:30px;border-collapse:collapse;font-size:0.9rem;}
        th, td{padding:12px;text-align:left;border-bottom:1px solid #eee;}
        th{background:#f8f9fa;color:#666;font-weight:600;}
        .back{display:inline-block;margin-top:20px;color:#666;text-decoration:none;font-weight:500;}
        .back:hover{color:#8b5cf6;}
        .hint{font-size:0.85rem;color:#888;margin-top:5px;}
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard_full.php" class="back">← <?= $t['back'] ?></a>
    <h2 style="color:#8b5cf6;"><?= $t['title'] ?></h2>
    
    <?= $msg ?>

    <form method="POST">
        <label><?= $t['meter'] ?></label>
        <select name="meter_id" id="meter_id" required onchange="updateForm()">
            <option value="0"><?= $t['select'] ?></option>
            <?php foreach($meters as $m): ?>
            <option value="<?= $m['id'] ?>" data-type="<?= $m['meter_type'] ?>" data-unit="<?= $m['unit'] ?>">
                <?= $m['meter_type'] ?> (<?= $m['location'] ?>)
            </option>
            <?php endforeach; ?>
        </select>

        <label><?= $t['type'] ?></label>
        <select id="utility_type" name="utility_type" required>
            <option value="Electricity">Electricity</option>
            <option value="Water">Water</option>
            <option value="Gas">Gas</option>
            <option value="Heat">Heat</option>
        </select>

        <label><?= $t['val'] ?></label>
        <!-- step="any" accepteert zowel punt als komma invoer in moderne browsers -->
        <input type="number" step="any" name="reading_value" id="reading_value" required placeholder="1234,56">
        <div class="hint">Tip: Je mag zowel een punt (.) als komma (,) gebruiken.</div>

        <label><?= $t['unit'] ?></label>
        <input type="text" id="unit" name="unit" value="kWh" required readonly style="background:#f0f0f0;color:#555;font-weight:bold;">

        <button type="submit"><?= $t['submit'] ?></button>
    </form>

    <h3 style="margin-top:40px;color:#555;"><?= $t['history'] ?></h3>
    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Type</th>
                <th>Stand</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $db->query("SELECT log_date, utility_type, reading_value, unit FROM utility_logs ORDER BY log_date DESC, log_time DESC LIMIT 5");
            $found = false;
            while($row = $res->fetchArray(SQLITE3_ASSOC)):
                $found = true;
            ?>
            <tr>
                <td><?= date('d-m', strtotime($row['log_date'])) ?></td>
                <td><span style="color:#8b5cf6;font-weight:bold;"><?= $row['utility_type'] ?></span></td>
                <td><?= number_format($row['reading_value'], 2, ',', '.') ?> <small style="color:#888;"><?= $row['unit'] ?></small></td>
            </tr>
            <?php endwhile; ?>
            <?php if(!$found): ?>
            <tr><td colspan="3" style="text-align:center;color:#999;">Nog geen gegevens</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    function updateForm() {
        var select = document.getElementById('meter_id');
        var option = select.options[select.selectedIndex];
        if (option.value > 0) {
            document.getElementById('utility_type').value = option.getAttribute('data-type');
            document.getElementById('unit').value = option.getAttribute('data-unit');
        }
    }
    // Initieel aanroepen voor zekerheid
    window.onload = function() { updateForm(); };
</script>
</body>
</html>
