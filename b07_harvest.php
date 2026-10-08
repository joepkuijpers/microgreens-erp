<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/auth.php';

// Initialiseer variabelen
$message = '';
$error = '';
$available_batches = [];

// --- VERWERKING FORMULIER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "harvest") {
    $batch_id = intval($_POST["batch_id"] ?? 0);
    $weight = floatval($_POST["weight"] ?? 0);
    $output_type = $_POST['output_type'] ?? 'FRESH'; // NIEUW: Vers of Vriesdrogen
    $notes = trim($_POST["notes"] ?? '');
    
    if ($batch_id > 0 && $weight > 0) {
        try {
            // 1. Check batch status
            $stmt = $db->prepare("SELECT status, crop_type FROM production_batches WHERE id = ?");
            $stmt->execute([$batch_id]);
            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($batch) {
                if ($batch['status'] === 'HARVESTED') {
                    $error = "Deze batch is al geoogst.";
                } else {
                    // 2. Bepaal status voor voorraad
                    // FRESH -> AVAILABLE (voor B09)
                    // FREEZE_DRY -> PENDING_FREEZE (moet eerst naar B08)
                    $output_status = ($output_type === 'FRESH') ? 'AVAILABLE' : 'PENDING_FREEZE';
                    
                    // 3. Update batch status
                    $stmt = $db->prepare("UPDATE production_batches SET status = 'HARVESTED', actual_quantity = ?, completed_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$weight, $batch_id]);
                    
                    // 4. Maak record in production_outputs (Traceability)
                    $stmt = $db->prepare("INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes) VALUES (?, ?, ?, 'g', ?, ?)");
                    $stmt->execute([$batch_id, $output_type, $weight, $output_status, $notes]);
                    
                    // 5. Maak record in finished_inventory (Voorraad)
                    // We gebruiken crop_type als productnaam voor nu
                    $stmt = $db->prepare("INSERT INTO finished_inventory (product_name, weight_grams, status, output_type, production_date) VALUES (?, ?, ?, ?, date('now'))");
                    $stmt->execute([$batch['crop_type'], $weight, $inventory_status, $output_type]);
                    
                    $type_label = ($output_type === 'FRESH') ? 'Vers' : 'Vriesdroog';
                    $message = "✅ Oogst geregistreerd! ($type_label) - $weight gram.";
                }
            } else {
                $error = "Batch niet gevonden.";
            }
        } catch (Exception $e) {
            $error = "Fout bij opslaan: " . $e->getMessage();
        }
    } else {
        $error = "Vul batch en gewicht in.";
    }
}

// --- OPHALEN ACTIEVE BATCHES ---
try {
    // Alleen batches die klaar zijn om te oogsten (GROWING of READY_TO_HARVEST)
    $stmt = $db->query("SELECT id, batch_code, crop_type, started_at, rack_position FROM production_batches WHERE status IN ('GROWING', 'READY_TO_HARVEST') ORDER BY started_at DESC");
    $available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Fout bij laden batches: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['module_b07'] ?? 'Oogst'; ?> - Microgreens ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Liberation Sans', sans-serif; background: #f4f4f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        select, input[type="number"], textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .radio-group { display: flex; gap: 20px; margin-top: 10px; }
        .radio-option { display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; flex: 1; transition: background 0.2s; }
        .radio-option:hover { background: #f0f8ff; }
        .radio-option input { margin: 0; }
        .btn { padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-success { background: #2ecc71; color: white; width: 100%; }
        .btn-secondary { background: #95a5a6; color: white; margin-bottom: 20px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 4px; border-left: 4px solid #2196f3; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="btn btn-secondary">&larr; <?php echo $lang['back'] ?? 'Terug'; ?></a>
    
    <h1>🌱 <?php echo $lang['module_b07'] ?? 'Oogst Registratie'; ?></h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="info-box">
        <strong>ℹ️ Workflow:</strong><br>
        Kies <b>Vers</b> voor directe verpakking (B09).<br>
        Kies <b>Vriesdrogen</b> als de batch eerst naar de vriesdroger (B08) moet.
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="harvest">
        
        <div class="form-group">
            <label><?php echo $lang['choose_batch'] ?? 'Selecteer Batch'; ?></label>
            <select name="batch_id" required>
                <option value="">-- Kies een actieve batch --</option>
                <?php foreach ($available_batches as $batch): ?>
                    <option value="<?php echo $batch['id']; ?>">
                        <?php echo htmlspecialchars($batch['batch_code']); ?> - <?php echo htmlspecialchars($batch['crop_type']); ?> 
                        (Rack: <?php echo $batch['rack_position'] ?? '?'; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><?php echo $lang['weight_grams'] ?? 'Oogst Gewicht (gram)'; ?></label>
            <input type="number" name="weight" step="0.1" min="1" required placeholder="Bijv. 450">
        </div>

        <!-- NIEUW: TYPE OUTPUT KEUZE -->
        <div class="form-group">
            <label><?php echo $lang['output_type'] ?? 'Type Output'; ?></label>
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="output_type" value="FRESH" checked>
                    <div>
                        <strong>🥬 Vers</strong><br>
                        <small>Direct naar verpakking (B09)</small>
                    </div>
                </label>
                <label class="radio-option">
                    <input type="radio" name="output_type" value="FREEZE_DRY">
                    <div>
                        <strong>❄️ Vriesdrogen</strong><br>
                        <small>Naar vriesdroger proces (B08)</small>
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label><?php echo $lang['notes'] ?? 'Notities (Optioneel)'; ?></label>
            <textarea name="notes" rows="3" placeholder="Bijv. kwaliteit, bijzonderheden..."></textarea>
        </div>

        <button type="submit" class="btn btn-success">✅ <?php echo $lang['register_harvest'] ?? 'Registreer Oogst'; ?></button>
    </form>
</div>

</body>
</html>
