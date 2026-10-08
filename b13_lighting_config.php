<?php
require_once 'app/includes/header.php';
require_once 'app/includes/auth.php';

// --- HANDLE POST ACTIONS ---
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name']);
        $rack_name = trim($_POST['rack_name']);
        $wattage = floatval($_POST['wattage']);
        $num_lamps = intval($_POST['num_lamps']);
        $hours = floatval($_POST['hours']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || $wattage <= 0 || $num_lamps <= 0) {
            $message = "Ongeldige invoer. Controleer de velden.";
            $messageType = 'error';
        } else {
            try {
                if ($id) {
                    // Update bestaand
                    $stmt = $db->prepare("UPDATE equipment SET name=?, rack_name=?, wattage=?, num_lamps=?, hours_per_day=?, is_active=? WHERE id=?");
                    $stmt->execute([$name, $rack_name, $wattage, $num_lamps, $hours, $is_active, $id]);
                } else {
                    // Nieuwe toevoegen
                    $stmt = $db->prepare("INSERT INTO equipment (name, rack_name, wattage, num_lamps, hours_per_day, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $rack_name, $wattage, $num_lamps, $hours, $is_active]);
                }
                $message = $lang['lighting_saved'] ?? "Configuratie opgeslagen.";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Fout bij opslaan: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Soft delete: alleen is_active op 0 zetten
            $stmt = $db->prepare("UPDATE equipment SET is_active=0 WHERE id=?");
            $stmt->execute([$id]);
            $message = $lang['lighting_deactivated'] ?? "Verlichting gedeactiveerd.";
            $messageType = 'success';
        }
    }
    // Refresh na POST om form resubmission te voorkomen
    header("Location: b13_lighting_config.php?msg=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// --- GET PARAMETERS ---
$editId = $_GET['edit'] ?? null;
$feedbackMsg = $_GET['msg'] ?? '';
$feedbackType = $_GET['type'] ?? '';

// --- FETCH DATA ---
$stmt = $db->query("SELECT * FROM equipment ORDER BY rack_name, name");
$equipmentList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Haal unieke rack namen voor de dropdown
$racksStmt = $db->query("SELECT DISTINCT name FROM racks ORDER BY name");
$rackOptions = $racksStmt->fetchAll(PDO::FETCH_COLUMN);

// Haal huidige energieprijs (uit utility_rates of default)
$pricePerKwh = 0.30; // Default
try {
    $rateStmt = $db->query("SELECT rate_per_unit FROM utility_rates WHERE utility_type='ELECTRICITY' AND is_active=1 LIMIT 1");
    $rate = $rateStmt->fetchColumn();
    if ($rate) $pricePerKwh = floatval($rate);
} catch (Exception $e) { /* ignore */ }

// Edit mode data
$editData = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['lighting_config'] ?? 'Lighting Config'; ?> - Microgreens ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Liberation Sans', sans-serif; background: #f4f4f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .calc-box { background: #e8f6f3; padding: 15px; border-radius: 5px; margin-top: 10px; border: 1px solid #a2d9ce; }
        .calc-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .hidden { display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1><?php echo $lang['lighting_config'] ?? 'Lighting Configuration'; ?></h1>

    <?php if ($feedbackMsg): ?>
        <div class="alert alert-<?php echo $feedbackType === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($feedbackMsg); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULIER -->
    <div style="background:#f9f9f9; padding:20px; border-radius:8px; margin-bottom:20px; border:1px solid #eee;">
        <h2><?php echo $editData ? ($lang['edit_lighting'] ?? 'Edit') : ($lang['add_lighting'] ?? 'Add'); ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
            
            <div class="form-group">
                <label><?php echo $lang['lamp_type'] ?? 'Lamp Type / Name'; ?></label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($editData['name'] ?? 'Nurser 3 LED'); ?>" placeholder="Bijv. Nurser 3 LED">
            </div>

            <div class="form-group">
                <label>Rack</label>
                <select name="rack_name" required>
                    <option value="">-- Selecteer Rack --</option>
                    <?php foreach ($rackOptions as $rack): ?>
                        <option value="<?php echo htmlspecialchars($rack); ?>" <?php echo ($editData['rack_name'] ?? '') === $rack ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rack); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:15px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label><?php echo $lang['wattage_per_lamp'] ?? 'Wattage per Lamp'; ?></label>
                    <input type="number" step="0.1" name="wattage" id="wattage" required value="<?php echo $editData['wattage'] ?? 18; ?>" oninput="calculate()">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label><?php echo $lang['num_lamps'] ?? 'Number of Lamps'; ?></label>
                    <input type="number" name="num_lamps" id="num_lamps" required value="<?php echo $editData['num_lamps'] ?? 4; ?>" oninput="calculate()">
                </div>
            </div>

            <div style="display:flex; gap:15px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label><?php echo $lang['hours_per_day'] ?? 'Hours per Day'; ?></label>
                    <input type="number" step="0.5" name="hours" id="hours" required value="<?php echo $editData['hours_per_day'] ?? 16; ?>" oninput="calculate()">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label><?php echo $lang['price_per_kwh'] ?? 'Price per kWh'; ?></label>
                    <input type="number" step="0.01" name="price" id="price" required value="<?php echo $pricePerKwh; ?>" oninput="calculate()">
                    <small>Default uit systeem</small>
                </div>
            </div>

            <!-- LIVE CALCULATIE -->
            <div class="calc-box">
                <h3 style="margin-top:0;"><?php echo $lang['live_calculation'] ?? 'Live Calculation'; ?></h3>
                <div class="calc-row"><span><?php echo $lang['total_wattage'] ?? 'Total Wattage'; ?>:</span> <strong id="res_watt">0</strong> W</div>
                <div class="calc-row"><span><?php echo $lang['kwh_per_day'] ?? 'kWh per Day'; ?>:</span> <strong id="res_kwh">0</strong> kWh</div>
                <div class="calc-row"><span><?php echo $lang['est_cost_per_day'] ?? 'Est. Cost/Day'; ?>:</span> <strong id="res_cost">€0.00</strong></div>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label style="display:inline-flex; align-items:center;">
                    <input type="checkbox" name="is_active" <?php echo (!$editData || $editData['is_active']) ? 'checked' : ''; ?>>
                    <span style="margin-left:10px;"><?php echo $lang['active'] ?? 'Active'; ?></span>
                </label>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-success"><?php echo $lang['save'] ?? 'Save'; ?></button>
                <?php if ($editData): ?>
                    <a href="b13_lighting_config.php" class="btn btn-secondary"><?php echo $lang['cancel'] ?? 'Cancel'; ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- OVERZICHT TABEL -->
    <h2><?php echo $lang['module_b13'] ?? 'Energy'; ?> - Overzicht</h2>
    <?php if (empty($equipmentList)): ?>
        <p><?php echo $lang['no_lighting_config'] ?? 'No configurations found.'; ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?php echo $lang['lamp_type'] ?? 'Type'; ?></th>
                    <th>Rack</th>
                    <th>Watt</th>
                    <th><?php echo $lang['num_lamps'] ?? 'Count'; ?></th>
                    <th><?php echo $lang['hours_per_day'] ?? 'Hours/day'; ?></th>
                    <th>kWh/dag</th>
                    <th>Kosten/dag</th>
                    <th>Status</th>
                    <th><?php echo $lang['actions'] ?? 'Actions'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipmentList as $item): 
                    $w = floatval($item['wattage']);
                    $n = intval($item['num_lamps'] ?? 1);
                    $h = floatval($item['hours_per_day']);
                    $totalW = $w * $n;
                    $kwhDay = ($totalW / 1000) * $h;
                    $costDay = $kwhDay * $pricePerKwh;
                ?>
                <tr style="<?php echo !$item['is_active'] ? 'color:#999; background:#f9f9f9;' : ''; ?>">
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['rack_name']); ?></td>
                    <td><?php echo $w; ?> W</td>
                    <td><?php echo $n; ?></td>
                    <td><?php echo $h; ?> u</td>
                    <td><?php echo number_format($kwhDay, 3); ?></td>
                    <td>€ <?php echo number_format($costDay, 2); ?></td>
                    <td>
                        <?php if ($item['is_active']): ?>
                            <span class="status-active"><?php echo $lang['active'] ?? 'Active'; ?></span>
                        <?php else: ?>
                            <span class="status-inactive"><?php echo $lang['inactive'] ?? 'Inactive'; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-primary" style="padding:5px 10px; font-size:12px;"><?php echo $lang['edit'] ?? 'Edit'; ?></a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo $lang['confirm_delete_lighting'] ?? 'Are you sure?'; ?>');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding:5px 10px; font-size:12px;"><?php echo $lang['delete'] ?? 'Delete'; ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div style="margin-top:20px;">
        <a href="b13_energy.php" class="btn btn-secondary">&larr; <?php echo $lang['back_to_energy'] ?? 'Back to Energy'; ?></a>
    </div>
</div>

<script>
function calculate() {
    const watt = parseFloat(document.getElementById('wattage').value) || 0;
    const num = parseFloat(document.getElementById('num_lamps').value) || 0;
    const hours = parseFloat(document.getElementById('hours').value) || 0;
    const price = parseFloat(document.getElementById('price').value) || 0;

    const totalW = watt * num;
    const kwhDay = (totalW / 1000) * hours;
    const cost = kwhDay * price;

    document.getElementById('res_watt').textContent = totalW.toFixed(1);
    document.getElementById('res_kwh').textContent = kwhDay.toFixed(3);
    document.getElementById('res_cost').textContent = '€ ' + cost.toFixed(2);
}

// Initieel berekenen bij laden
window.onload = calculate;
</script>

</body>
</html>
