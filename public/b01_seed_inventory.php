<?php
// B01: Seed Inventory & Skal Compliance
// Tijdslimiet: < 20 sec per toevoeging
require_once __DIR__ . '/../includes/db_connection.php';
if (!isset($db)) { $db = getDbConnection(); }

$message = '';
$messageType = '';

// --- HANDLE POST (TOEVOEGEN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $item_name = trim($_POST['item_name'] ?? '');
        $category = trim($_POST['category'] ?? 'Zaad');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'g');
        $lot_number = trim($_POST['lot_number'] ?? '');
        $expiration_date = trim($_POST['expiration_date'] ?? null);
        $organic_certified = isset($_POST['organic_certified']) ? 1 : 0;
        $supplier_id = intval($_POST['supplier_id'] ?? 0);

        if (empty($item_name) || $quantity <= 0) {
            throw new Exception("Naam en hoeveelheid zijn verplicht.");
        }

        $stmt = $db->prepare("
            INSERT INTO inventory (item_name, category, quantity, unit, lot_number, expiration_date, organic_certified, supplier_id)
            VALUES (:name, :cat, :qty, :unit, :lot, :exp, :org, :sup)
        ");
        
        $stmt->execute([
            ':name' => $item_name,
            ':cat' => $category,
            ':qty' => $quantity,
            ':unit' => $unit,
            ':lot' => $lot_number,
            ':exp' => $expiration_date,
            ':org' => $organic_certified,
            ':sup' => $supplier_id ?: null
        ]);

        $message = "✅ Zaadpartij '{$item_name}' (Lot: " . ($lot_number ?: 'N/A') . ") succesvol toegevoegd.";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "⚠️ Fout: " . htmlspecialchars($e->getMessage());
        $messageType = 'error';
    }
}

// --- FETCH DATA FOR VIEW ---
$inventory = $db->query("
    SELECT i.*, s.name as supplier_name 
    FROM inventory i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    WHERE lower(i.category) LIKE '%zaad%' OR lower(i.category) LIKE '%seed%' OR lower(i.item_name) LIKE '%zaad%'
    ORDER BY i.item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- SAD PATH ALERTS ---
$planningAlerts = [];
$totalSeedKg = 0;
foreach ($inventory as $item) {
    $unit = strtolower(trim($item['unit']));
    $qty = (float)$item['quantity'];
    if ($unit === 'kg') $totalSeedKg += $qty;
    elseif (in_array($unit, ['g', 'gram'])) $totalSeedKg += ($qty / 1000);
}

if ($totalSeedKg < 1.0) {
    $planningAlerts[] = "⚠️ Totale zaadvoorraad is laag (< 1kg). Controleer planning.";
}

$expired = array_filter($inventory, fn($i) => $i['expiration_date'] && new DateTime($i['expiration_date']) < new DateTime());
if (!empty($expired)) {
    $planningAlerts[] = "🚫 " . count($expired) . " zaadpartijen zijn verlopen.";
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B01 - Zaadvoorraad & Skal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Liberation Sans', sans-serif; background: #f4f6f8; padding: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { margin-top: 0; color: #2c3e50; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; grid-column: span 2; }
        button:hover { background: #219150; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
        .bg-ok { background: #d4edda; color: #155724; }
        .bg-warn { background: #fff3cd; color: #856404; }
        .bg-err { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="card">
    <h2>🌱 B01: Zaadvoorraad & Skal Beheer</h2>
    <?php foreach ($planningAlerts as $alert): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($alert) ?></div>
    <?php endforeach; ?>
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST">
        <div><label>Naam Zaad *</label><input type="text" name="item_name" required placeholder="Bijv. Rucola Bio"></div>
        <div><label>Lot Nummer (Skal)</label><input type="text" name="lot_number" placeholder="Verplicht voor traceerbaarheid"></div>
        <div><label>Hoeveelheid *</label><input type="number" step="0.01" name="quantity" required value="100"></div>
        <div><label>Eenheid</label><select name="unit"><option value="g" selected>Gram (g)</option><option value="kg">Kilogram (kg)</option></select></div>
        <div><label>Houdbaarheid tot</label><input type="date" name="expiration_date"></div>
        <div><label>Leverancier</label><select name="supplier_id"><option value="0">-- Selecteer --</option><?php $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="full-width"><label style="display:inline-flex; align-items:center; gap:10px; cursor:pointer;"><input type="checkbox" name="organic_certified" checked style="width:auto;"><span>Biologisch Gecertificeerd (Skal Toegestaan)</span></label></div>
        <button type="submit">➕ Voeg Zaadpartij Toe</button>
    </form>
</div>
<div class="card">
    <h3>Huidige Voorraad</h3>
    <table>
        <thead><tr><th>Naam</th><th>Lot Nr</th><th>Hoeveelheid</th><th>Houdbaarheid</th><th>Status</th><th>Leverancier</th></tr></thead>
        <tbody>
            <?php if (empty($inventory)): ?><tr><td colspan="6" style="text-align:center;">Geen zaadvoorraden gevonden.</td></tr>
            <?php else: ?><?php foreach ($inventory as $item): 
                $isExpired = $item['expiration_date'] && new DateTime($item['expiration_date']) < new DateTime();
                $statusClass = $isExpired ? 'bg-err' : ($item['organic_certified'] ? 'bg-ok' : 'bg-warn');
                $statusText = $isExpired ? 'Verlopen' : ($item['organic_certified'] ? 'Bio OK' : 'Conventioneel');
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                <td><?= htmlspecialchars($item['lot_number'] ?? '-') ?></td>
                <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                <td><?= $item['expiration_date'] ?? '-' ?></td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                <td><?= htmlspecialchars($item['supplier_name'] ?? 'Onbekend') ?></td>
            </tr>
            <?php endforeach; ?><?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
