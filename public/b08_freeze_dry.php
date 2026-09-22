<?php
/**
 * B08 - Freeze Dry Process
 * Doel: Optionele tak voor vriesdrogen met massabalans en traceability.
 * Compliance: Organic Gate, Sad Path, Audit Trail.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$errors = [];
$success_msg = '';
$mode = $_GET['mode'] ?? 'start'; // 'start' of 'finish'

// --- POST VERWERKING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start_cycle') {
        $output_id = intval($_POST['production_output_id'] ?? 0);
        $machine_id = trim($_POST['machine_id'] ?? '');
        $input_weight = floatval($_POST['input_weight_g'] ?? 0);
        
        if ($output_id <= 0) $errors[] = "Geen output geselecteerd.";
        if (empty($machine_id)) $errors[] = "Machine ID verplicht.";
        if ($input_weight <= 0) $errors[] = "Input gewicht moet > 0 zijn.";
        
        // Organic Gate & Batch Check
        $batch_id = null; $organic_status = 'unknown';
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                SELECT po.batch_id, pb.organic_status 
                FROM production_outputs po 
                JOIN production_batches pb ON po.batch_id = pb.id 
                WHERE po.id = ?
            ");
            $stmt->execute([$output_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) { $errors[] = "Output niet gevonden."; }
            else {
                $batch_id = $data['batch_id'];
                $organic_status = $data['organic_status'];
                if ($organic_status === 'blocked') {
                    $errors[] = "⛔ BLOCKADE: Batch is geblokkeerd voor verwerking.";
                }
                // Check of al een cycle loopt voor deze output
                $stmt = $pdo->prepare("SELECT id FROM freeze_dry_processes WHERE production_output_id = ? AND status = 'RUNNING'");
                $stmt->execute([$output_id]);
                if ($stmt->fetch()) { $errors[] = "Er loopt al een cycle voor deze output."; }
            }
        }
        
        if (empty($errors)) {
            try {
                $cycle_code = 'FD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
                $stmt = $pdo->prepare("
                    INSERT INTO freeze_dry_processes (production_output_id, machine_id, cycle_code, input_weight_g, status, batch_id, organic_status_snapshot, started_at, created_at)
                    VALUES (?, ?, ?, ?, 'RUNNING', ?, ?, datetime('now'), datetime('now'))
                ");
                $stmt->execute([$output_id, $machine_id, $cycle_code, $input_weight, $batch_id, $organic_status]);
                header("Location: b08_freeze_dry.php?success=started"); exit;
            } catch (Exception $e) { $errors[] = "Start fout: " . $e->getMessage(); }
        }
    }
    
    elseif ($action === 'finish_cycle') {
        $process_id = intval($_POST['process_id'] ?? 0);
        $final_weight = floatval($_POST['final_weight_g'] ?? 0);
        
        if ($process_id <= 0) $errors[] = "Geen proces geselecteerd.";
        if ($final_weight < 0) $errors[] = "Final gewicht kan niet negatief zijn.";
        
        if (empty($errors)) {
            try {
                $water_removed = 0;
                // Haal input gewicht op om water_removed te berekenen
                $stmt = $pdo->prepare("SELECT input_weight_g FROM freeze_dry_processes WHERE id = ? AND status = 'RUNNING'");
                $stmt->execute([$process_id]);
                $current = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current) {
                    $water_removed = max(0, $current['input_weight_g'] - $final_weight);
                }
                
                $stmt = $pdo->prepare("
                    UPDATE freeze_dry_processes 
                    SET final_weight_g = ?, water_removed_g = ?, status = 'COMPLETED', ended_at = datetime('now'), modified_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([$final_weight, $water_removed, $process_id]);
                header("Location: b08_freeze_dry.php?success=finished"); exit;
            } catch (Exception $e) { $errors[] = "Finish fout: " . $e->getMessage(); }
        }
    }
}

// --- DATA OPHALEN ---
$running_processes = [];
$available_outputs = [];

try {
    // Lopende processen
    $stmt = $pdo->query("
        SELECT fp.id, fp.cycle_code, fp.machine_id, fp.input_weight_g, fp.started_at, pb.crop_type
        FROM freeze_dry_processes fp
        JOIN production_outputs po ON fp.production_output_id = po.id
        JOIN production_batches pb ON po.batch_id = pb.id
        WHERE fp.status = 'RUNNING'
        ORDER BY fp.started_at DESC
    ");
    $running_processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Beschikbare outputs (die nog niet gedroogd worden)
    // Subquery om lopende processen uit te sluiten
    $stmt = $pdo->query("
        SELECT po.id, po.batch_id, po.weight_g, pb.crop_type, pb.batch_code
        FROM production_outputs po
        JOIN production_batches pb ON po.batch_id = pb.id
        WHERE po.id NOT IN (SELECT production_output_id FROM freeze_dry_processes WHERE status = 'RUNNING')
        LIMIT 20
    ");
    $available_outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B08 Freeze Dry</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f4f4f4; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; }
        h1, h2 { border-bottom: 2px solid #0056b3; padding-bottom: 0.5rem; color: #333; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button.stop { background: #dc3545; }
        button:hover { opacity: 0.9; }
        .card { border: 1px solid #ddd; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; background: #fafafa; }
    </style>
</head>
<body>
<div class="container">
    <h1>❄️ B08: Freeze Dry Proces</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><strong>Fout:</strong><ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] == 'started'): ?>
        <div class="alert alert-success">✅ Cycle gestart. Monitoring loopt.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] == 'finished'): ?>
        <div class="alert alert-success">✅ Cycle voltooid. Massabalans berekend.</div>
    <?php endif; ?>

    <!-- SECTIE 1: LOPENDE PROCESSEN -->
    <h2>🔄 Lopende Cycles</h2>
    <?php if (empty($running_processes)): ?>
        <p>Geen actieve freeze-dry cycles.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Cycle Code</th><th>Batch</th><th>Machine</th><th>Input (g)</th><th>Gestart</th><th>Actie</th></tr></thead>
            <tbody>
                <?php foreach ($running_processes as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['cycle_code']) ?></td>
                    <td><?= htmlspecialchars($p['crop_type']) ?></td>
                    <td><?= htmlspecialchars($p['machine_id']) ?></td>
                    <td><?= number_format($p['input_weight_g'], 1) ?></td>
                    <td><?= $p['started_at'] ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="finish_cycle">
                            <input type="hidden" name="process_id" value="<?= $p['id'] ?>">
                            <label style="display:inline; font-weight:normal;">Final Gewicht (g):</label>
                            <input type="number" step="0.1" name="final_weight_g" value="0" style="width:100px; display:inline;" required>
                            <button type="submit" class="stop">Stop & Bereken</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- SECTIE 2: NIEUWE CYCLE STARTEN -->
    <h2>🚀 Nieuwe Cycle Starten</h2>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="start_cycle">
            <div class="form-row">
                <div class="form-group">
                    <label>Kies Production Output (Bron):</label>
                    <select name="production_output_id" required>
                        <option value="">-- Selecteer Output --</option>
                        <?php foreach ($available_outputs as $o): ?>
                            <option value="<?= $o['id'] ?>">
                                <?= htmlspecialchars($o['crop_type']) ?> - Batch <?= htmlspecialchars($o['batch_code']) ?> (<?= $o['weight_g'] ?>g)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($available_outputs)): ?>
                        <small>Geen beschikbare outputs gevonden.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Machine ID:</label>
                    <input type="text" name="machine_id" value="FD-MACHINE-01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Input Gewicht (g):</label>
                    <input type="number" step="0.1" name="input_weight_g" required>
                </div>
            </div>
            <button type="submit">Start Cycle</button>
        </form>
    </div>
</div>
</body>
</html>
