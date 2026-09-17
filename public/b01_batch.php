<?php
// B01: Batch Initialisatie (Happy Path: < 30 sec | Sad Path: Foutafhandeling)
$db_path = '/var/www/html/database/MicrogreensERP_Live.sqlite';
$message = '';
$error = '';

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_batch') {
        $crop = trim($_POST['crop'] ?? '');
        $planned_qty = floatval($_POST['planned_quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (empty($crop)) {
            $error = "Fout: Kies een gewas uit de lijst.";
        } elseif ($planned_qty <= 0) {
            $error = "Fout: Voer een geldige verwachte opbrengst in (groter dan 0).";
        } else {
            $prefix = strtoupper(substr($crop, 0, 3));
            $date_str = date('Ymd');
            
            // Check bestaande batches voor unieke code
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM production_batches WHERE batch_code LIKE :pattern");
            $stmt->execute([':pattern' => $prefix . '-' . $date_str . '-%']);
            $count = $stmt->fetchColumn() + 1;
            
            $batch_code = sprintf("%s-%s-%02d", $prefix, $date_str, $count);
            
            $insert = $pdo->prepare("INSERT INTO production_batches 
                (batch_code, status, planned_quantity, quantity_unit, started_at, notes, crop) 
                VALUES (:code, 'SOWN', :qty, 'g', datetime('now'), :notes, :crop)");
            
            $insert->execute([
                ':code' => $batch_code,
                ':qty' => $planned_qty,
                ':notes' => $notes,
                ':crop' => $crop
            ]);

            $message = "✅ Batch <strong>{$batch_code}</strong> succesvol gestart!";
        }
    }
} catch (Exception $e) {
    // Specifieke check voor database lock
    if (strpos($e->getMessage(), 'locked') !== false) {
        $error = "Systeem is bezig met een andere taak. Wacht 5 seconden en probeer opnieuw.";
    } else {
        $error = "Er ging iets mis: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B01 - Batch Initialisatie</title>
    <style>
        body { font-family: 'Liberation Sans', sans-serif; background: #f4f6f8; margin: 20px; color: #333; }
        .card { background: white; padding: 25px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #2c3e50; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        select:focus, input:focus, textarea:focus { border-color: #2e7d32; outline: none; ring: 2px solid #2e7d32; }
        button { background: #2e7d32; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-size: 18px; width: 100%; font-weight: bold; transition: background 0.2s; }
        button:hover { background: #1b5e20; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #c62828; }
        h2 { margin-top: 0; color: #2c3e50; }
    </style>
</head>
<body>

<!-- FIX: Absoluut pad voor menu -->
<?php include '/var/www/html/menu.php'; ?>

<div class="card">
    <h2>🌿 B01: Nieuwe Batch Starten</h2>
    
    <?php if ($message): ?><div class="alert-success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="create_batch">
        
        <div class="form-group">
            <label for="crop">Gewas (Crop)</label>
            <select name="crop" id="crop" required onchange="updateDefaults()">
                <option value="">-- Selecteer Gewas --</option>
                <option value="China Rose Radish" data-qty="300">China Rose Radish</option>
                <option value="Dun Pea" data-qty="500">Dun Pea</option>
                <option value="Black Oil Sunflower" data-qty="400">Black Oil Sunflower</option>
                <option value="Waltham 29 Broccoli" data-qty="150">Waltham 29 Broccoli</option>
                <option value="Rocket Arugula" data-qty="120">Rocket Arugula</option>
            </select>
            <small style="color:#666; display:block; margin-top:5px;">Kies een gewas om de standaard hoeveelheid automatisch in te vullen.</small>
        </div>

        <div class="form-group">
            <label for="planned_quantity">Verwachte Opbrengst (gram)</label>
            <input type="number" step="1" name="planned_quantity" id="planned_quantity" placeholder="bijv. 300" required>
        </div>

        <div class="form-group">
            <label for="notes">Opmerkingen (Optioneel)</label>
            <textarea name="notes" id="notes" rows="2" placeholder="Bijv. Zaad lot #2026-A, specifieke leverancier..."></textarea>
        </div>

        <button type="submit">Aanmaken & Starten</button>
    </form>
</div>

<script>
function updateDefaults() {
    const select = document.getElementById('crop');
    const selectedOption = select.options[select.selectedIndex];
    const defaultQty = selectedOption.getAttribute('data-qty');
    if (defaultQty) {
        document.getElementById('planned_quantity').value = defaultQty;
        // Focus verplaatsen naar volgende veld voor snelheid
        document.getElementById('planned_quantity').focus();
    }
}
</script>

</body>
</html>