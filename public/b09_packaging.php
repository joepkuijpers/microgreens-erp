<?php
/**
 * B09 - Packaging (FIXED: source_type = 'OUTPUT')
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$errors = [];
$organic_blocked = false;
$default_tht = date('Y-m-d', strtotime('+14 days'));
$package_types = ['Clamshell 250g', 'Doos 500g', 'Zak 1kg'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_id = intval($_POST['source_id'] ?? 0);
    $package_type = trim($_POST['package_type'] ?? '');
    $weight_per_unit = floatval($_POST['weight_per_unit_g'] ?? 0);
    $quantity = intval($_POST['quantity_units'] ?? 0);
    $best_before = trim($_POST['best_before_date'] ?? '');
    
    if ($source_id <= 0) $errors[] = "Geen batch geselecteerd.";
    if (empty($package_type)) $errors[] = "Verpakkingstype verplicht.";
    if ($weight_per_unit <= 0) $errors[] = "Gewicht > 0.";
    if ($quantity <= 0) $errors[] = "Aantal > 0.";

    $batch_id = null; $organic_status = 'unknown';
    if (empty($errors) && $source_id > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT h.batch_id, pb.status as batch_status, pb.crop_type 
                FROM harvests h 
                JOIN production_batches pb ON h.batch_id = pb.id 
                WHERE h.id = ?
            ");
            $stmt->execute([$source_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) { $errors[] = "Oogst niet gevonden."; }
            else {
                $batch_id = $data['batch_id'];
                if ($data['batch_status'] === 'BLOCKED') {
                    $organic_blocked = true;
                    $errors[] = "⛔ BLOCKADE: Batch status is BLOCKED.";
                }
                $organic_status = $data['batch_status'] ?? 'unknown';
            }
        } catch (Exception $e) { $errors[] = "Query fout: " . $e->getMessage(); }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $label = 'PKG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // FIX: Gebruik 'OUTPUT' in plaats van 'HARVEST' vanwege CHECK constraint
            $stmt = $pdo->prepare("
                INSERT INTO packaging_units (source_type, source_id, package_type, weight_per_unit_g, quantity_units, total_weight_g, label_code, best_before_date, harvest_id, batch_id, organic_status_snapshot, created_at) 
                VALUES ('OUTPUT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([$source_id, $package_type, $weight_per_unit, $quantity, $weight_per_unit*$quantity, $label, $best_before, $source_id, $batch_id, $organic_status]);
            $pdo->commit();
            header("Location: b09_packaging.php?success=1"); exit;
        } catch (Exception $e) { $pdo->rollBack(); $errors[] = "Fout: " . $e->getMessage(); }
    }
}

$harvests = [];
try {
    $stmt = $pdo->query("
        SELECT h.id, h.harvest_date, h.weight_grams, pb.crop_type, pb.batch_code 
        FROM harvests h 
        JOIN production_batches pb ON h.batch_id = pb.id 
        ORDER BY h.harvest_date DESC LIMIT 50
    ");
    $harvests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B09 Verpakking</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; }
        h1 { border-bottom: 2px solid #0056b3; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 1rem; width: 100%; font-size: 1.1rem; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h1>📦 B09: Verpakking</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><strong>Fout:</strong><ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Succes! Label gegenereerd.</div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Kies Oogst:</label>
            <select name="source_id" required>
                <option value="">-- Selecteer --</option>
                <?php foreach ($harvests as $h): ?>
                    <option value="<?= $h['id'] ?>">
                        <?= htmlspecialchars($h['crop_type']) ?> - Batch <?= htmlspecialchars($h['batch_code']) ?> (<?= $h['weight_grams'] ?>g)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Verpakkingstype:</label>
            <select name="package_type" required>
                <?php foreach ($package_types as $pt): ?><option value="<?=htmlspecialchars($pt)?>"><?=htmlspecialchars($pt)?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="display:flex; gap:1rem;">
            <div style="flex:1"><label>Gewicht (g):</label><input type="number" step="0.1" name="weight_per_unit_g" value="250" required></div>
            <div style="flex:1"><label>Aantal:</label><input type="number" name="quantity_units" value="1" required></div>
        </div>
        <div class="form-group">
            <label>THT Datum:</label>
            <input type="date" name="best_before_date" id="best_before_date" value="<?=$default_tht?>">
        </div>
        <button type="submit">✅ Registreer</button>
    </form>
</div>
<script>
    document.getElementById('best_before_date').addEventListener('click', function(){ this.showPicker(); });
</script>
</body>
</html>
