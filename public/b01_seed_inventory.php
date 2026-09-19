<?php
// B01: Seed Inventory & Skal Compliance (Met Sad Path)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/includes/db_connection.php';
if (!isset($db)) { $db = getDbConnection(); }

$message = '';
$messageType = '';
// Sad Path: Behoud invoer bij fout
$formData = ['item_name' => '', 'lot_number' => '', 'quantity' => '100', 'expiration_date' => '', 'organic_certified' => 1, 'supplier_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verzamel data (voor behoud bij fout)
    $formData['item_name'] = trim($_POST['item_name'] ?? '');
    $formData['lot_number'] = trim($_POST['lot_number'] ?? '');
    $formData['quantity'] = floatval($_POST['quantity'] ?? 0);
    $formData['expiration_date'] = trim($_POST['expiration_date'] ?? null);
    $formData['organic_certified'] = isset($_POST['organic_certified']) ? 1 : 0;
    $formData['supplier_id'] = intval($_POST['supplier_id'] ?? 0);

    try {
        // 2. Sad Path Validaties
        if (empty($formData['item_name'])) {
            throw new Exception("⚠️ Naam is verplicht.");
        }
        if ($formData['quantity'] <= 0) {
            throw new Exception("⚠️ Hoeveelheid moet groter zijn dan 0.");
        }
        
        // Check op verlopen datum (Preventie)
        if (!empty($formData['expiration_date'])) {
            $expDate = new DateTime($formData['expiration_date']);
            $today = new DateTime();
            if ($expDate < $today) {
                // Waarschuwing, maar sta toe (soms moet je verlopen zaad registreren voor administratie)
                // We gooien een warning, maar blokkeren niet tenzij het 'Bio' is en streng beleid geldt.
                // Voor nu: alleen melding.
                $message = "⚠️ Let op: Deze houdbaarheidsdatum is verlopen. Controleer of dit zaad nog gebruikt mag worden.";
                $messageType = 'warning';
                // Als je wilt blokkeren, uncomment de regel hieronder:
                // throw new Exception("Geen verlopen zaad toegestaan voor bio-teelt.");
            }
        }

        // Check op dubbel Lot-nummer (Unieke traceerbaarheid)
        if (!empty($formData['lot_number'])) {
            $stmt = $db->prepare("SELECT id FROM inventory WHERE lot_number = ? AND item_name = ?");
            $stmt->execute([$formData['lot_number'], $formData['item_name']]);
            if ($stmt->fetchColumn()) {
                throw new Exception("⚠️ Dit Lot-nummer bestaat al voor '{$formData['item_name']}'. Traceerbaarheid vereist unieke lot-nummers per partij.");
            }
        }

        // 3. Insert
        $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, lot_number, expiration_date, organic_certified, supplier_id) VALUES (:name, 'Zaad', :qty, 'g', :lot, :exp, :org, :sup)");
        $stmt->execute([
            ':name' => $formData['item_name'],
            ':qty' => $formData['quantity'],
            ':lot' => $formData['lot_number'],
            ':exp' => $formData['expiration_date'],
            ':org' => $formData['organic_certified'],
            ':sup' => $formData['supplier_id'] ?: null
        ]);
        
        if ($messageType !== 'warning') {
            $message = "✅ Zaadpartij '{$formData['item_name']}' succesvol toegevoegd!";
            $messageType = 'success';
            // Reset form bij succes
            $formData = ['item_name' => '', 'lot_number' => '', 'quantity' => '100', 'expiration_date' => '', 'organic_certified' => 1, 'supplier_id' => 0];
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
        // formData blijft gevuld voor correctie
    }
}

// Fetch data
try {
    $inventory = $db->query("SELECT i.*, s.name as supplier_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id WHERE lower(i.category) LIKE '%zaad%' OR lower(i.item_name) LIKE '%zaad%' ORDER BY i.item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database fout: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B01 - Zaadvoorraad (Sad Path)</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f8; padding: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; max-width: 1000px; margin-left: auto; margin-right: auto; }
        h2 { color: #2c3e50; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid transparent; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border-color: #ffeeba; }
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:focus { border-color: #27ae60; outline: none; }
        button { background: #27ae60; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 1em; grid-column: span 2; }
        button:hover { background: #219150; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
        .bg-bio { background: #d4edda; color: #155724; }
        .bg-conv { background: #e2e3e5; color: #383d41; }
        .bg-expired { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="card">
    <h2>🌱 B01: Zaadvoorraad & Skal</h2>
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div><label>Naam Zaad *</label><input type="text" name="item_name" value="<?= htmlspecialchars($formData['item_name']) ?>" required placeholder="Bijv. Rucola"></div>
        <div><label>Lot Nummer (Uniek)</label><input type="text" name="lot_number" value="<?= htmlspecialchars($formData['lot_number']) ?>" placeholder="Skal Lot"></div>
        <div><label>Hoeveelheid (g) *</label><input type="number" step="0.01" name="quantity" value="<?= $formData['quantity'] ?>" required></div>
        <div><label>Houdbaarheid</label><input type="date" name="expiration_date" value="<?= htmlspecialchars($formData['expiration_date']) ?>"></div>
        <div class="full-width">
            <label style="display:inline-flex; align-items:center; gap:10px;">
                <input type="checkbox" name="organic_certified" <?= $formData['organic_certified'] ? 'checked' : '' ?> style="width:auto;">
                <span>Biologisch Gecertificeerd</span>
            </label>
        </div>
        <div class="full-width"><label>Leverancier</label>
            <select name="supplier_id">
                <option value="0">-- Geen --</option>
                <?php 
                try {
                    $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($suppliers as $s) echo "<option value='{$s['id']}' ".($formData['supplier_id']==$s['id']?'selected':'').">".htmlspecialchars($s['name'])."</option>";
                } catch(Exception $e) {} 
                ?>
            </select>
        </div>
        <button type="submit">➕ Toevoegen</button>
    </form>
</div>

<div class="card">
    <h3>Huidige Voorraad</h3>
    <table>
        <thead><tr><th>Naam</th><th>Lot</th><th>Hoeveelheid</th><th>Houdbaarheid</th><th>Status</th></tr></thead>
        <tbody>
            <?php if (empty($inventory)): ?>
                <tr><td colspan="5" style="text-align:center;">Geen zaad gevonden.</td></tr>
            <?php else: ?>
                <?php 
                $today = new DateTime();
                foreach ($inventory as $item): 
                    $isExpired = !empty($item['expiration_date']) && new DateTime($item['expiration_date']) < $today;
                    $statusClass = $isExpired ? 'bg-expired' : ($item['organic_certified'] ? 'bg-bio' : 'bg-conv');
                    $statusText = $isExpired ? 'Verlopen' : ($item['organic_certified'] ? 'Bio' : 'Conv');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                    <td><?= htmlspecialchars($item['lot_number'] ?? '-') ?></td>
                    <td><?= $item['quantity'] ?> g</td>
                    <td style="color: <?= $isExpired ? 'red' : 'inherit' ?>"><?= $item['expiration_date'] ?? '-' ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
