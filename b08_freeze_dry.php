<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b08');
$message = ''; $error = ''; $watchdogWarning = '';

// --- ACTIES VERWERKEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ACTIE: Nieuwe Machine Toevoegen
    if ($action === 'add_machine') {
        try {
            $name = trim($_POST['machine_name'] ?? '');
            $power = filter_input(INPUT_POST, 'machine_power_w', FILTER_VALIDATE_FLOAT) ?: 1500;
            if (!$name) throw new Exception(__('machine_name') . ' ?');
            $stmt = $db->prepare("INSERT INTO freeze_dry_machines (name, power_w) VALUES (?, ?)");
            $stmt->execute([$name, $power]);
            $message = __('machine_added');
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
    
    // ACTIE: Machine Verwijderen
    if ($action === 'delete_machine') {
        try {
            $id = filter_input(INPUT_POST, 'machine_id_del', FILTER_VALIDATE_INT);
            $stmt = $db->prepare("UPDATE freeze_dry_machines SET active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = __('machine_deleted');
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
    
    // ACTIE: Vriesdroog Cyclus Starten
    if ($action === 'start_cycle') {
        try {
            $db->beginTransaction();
            $output_id = filter_input(INPUT_POST, 'output_id', FILTER_VALIDATE_INT);
            $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
            $machine_id = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
            $input_weight = filter_input(INPUT_POST, 'input_weight', FILTER_VALIDATE_FLOAT);
            $final_weight = filter_input(INPUT_POST, 'final_weight', FILTER_VALIDATE_FLOAT);
            $energy_kwh = filter_input(INPUT_POST, 'energy_kwh', FILTER_VALIDATE_FLOAT) ?: 0;
            $energy_price = filter_input(INPUT_POST, 'energy_price', FILTER_VALIDATE_FLOAT) ?: 0;
            $notes = trim($_POST['notes'] ?? '');

            if (!$output_id) throw new Exception(__('error_select_batch'));
            if (!$machine_id) throw new Exception(__('error_machine_id_required'));
            if (!$input_weight || $input_weight <= 0) throw new Exception(__('error_invalid_input_weight'));
            if ($final_weight && $final_weight > $input_weight) throw new Exception(__('error_final_weight_exceeds_input'));

            $machineStmt = $db->prepare("SELECT name FROM freeze_dry_machines WHERE id = ?");
            $machineStmt->execute([$machine_id]);
            $machineName = $machineStmt->fetchColumn() ?: 'Unknown';

            $cycle_code = 'FD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            $water_removed = ($final_weight && $input_weight) ? ($input_weight - $final_weight) : 0;
            $operator_id = $_SESSION['user_id'] ?? 1;

            $stmt = $db->prepare("
                INSERT INTO freeze_dry_processes 
                (production_output_id, batch_id, machine_id, cycle_code, started_at, input_weight_g, final_weight_g, water_removed_g, notes, operator_id, organic_status_snapshot, created_at)
                VALUES (?, ?, ?, ?, datetime('now'), ?, ?, ?, ?, ?, 'organic', datetime('now'))
            ");
            $stmt->execute([$output_id, $batch_id, $machineName, $cycle_code, $input_weight, $final_weight, $water_removed, $notes, $operator_id]);

            $upd = $db->prepare("UPDATE production_outputs SET status = 'FREEZE_DRY_PROCESSING' WHERE id = ?");
            $upd->execute([$output_id]);

            $db->commit();
            $message = sprintf(__('success_cycle_started'), $cycle_code);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = __('error_general') . ': ' . $e->getMessage();
        }
    }
}

    // Include complete_cycle actie

    // Include complete_cycle actie

// --- DATA OPHALEN ---
try {
    $defaultEnergyPrice = 0.30;
    $stmt = $db->query("SELECT value FROM settings WHERE key = 'energy_price_kwh' LIMIT 1");
    $val = $stmt->fetchColumn();
    if ($val !== false) $defaultEnergyPrice = (float)$val;
} catch (Exception $e) {}

try {
    $stmt = $db->query("SELECT COUNT(*) FROM production_outputs WHERE output_type = 'FREEZE_DRY_INPUT' AND id NOT IN (SELECT production_output_id FROM freeze_dry_processes WHERE production_output_id IS NOT NULL)");
    if ((int)$stmt->fetchColumn() === 0) $watchdogWarning = __('warning_no_fresh_output_available');
} catch (Exception $e) {}

try {
    $batchesStmt = $db->query("
        SELECT po.id, po.batch_id, pb.crop_type as product_name, po.quantity, po.produced_at
        FROM production_outputs po JOIN production_batches pb ON po.batch_id = pb.id
        WHERE po.output_type = 'FREEZE_DRY_INPUT' AND po.id NOT IN (SELECT production_output_id FROM freeze_dry_processes WHERE production_output_id IS NOT NULL)
        ORDER BY po.produced_at DESC LIMIT 50
    ");
    $availableBatches = $batchesStmt->fetchAll();
} catch (Exception $e) { $availableBatches = []; }

try {
    $machinesStmt = $db->query("SELECT * FROM freeze_dry_machines WHERE active = 1 ORDER BY name");
    $machines = $machinesStmt->fetchAll();

// Haal lopende processen op
try {
    $runningStmt = $db->query("SELECT fd.*, po.batch_id, pb.crop_type as product_name, fd.input_weight_g, fd.started_at FROM freeze_dry_processes fd JOIN production_outputs po ON fd.production_output_id = po.id JOIN production_batches pb ON po.batch_id = pb.id WHERE fd.status = 'RUNNING' OR fd.status = 'FREEZE_DRY_PROCESSING' ORDER BY fd.started_at DESC");
    $runningProcesses = $runningStmt->fetchAll();
} catch (Exception $e) { $runningProcesses = []; }
} catch (Exception $e) { $machines = []; }
?>

<div style="max-width: 1000px; margin: 0 auto;">
    
    <?php if($watchdogWarning): ?>
        <div style="background:#fff3cd; color:#856404; padding:15px; border-radius:5px; margin-bottom:20px; border-left:5px solid #ffc107;">
            ⚠️ <strong><?php echo __('watchdog_warning'); ?>:</strong> <?php echo $watchdogWarning; ?>
        </div>
    <?php endif; ?>
    <?php if($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border-left:5px solid #28a745;">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border-left:5px solid #dc3545;">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Hoofdformulier -->
    <div class="card" style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2 style="margin-top:0; color:#2c3e50;">🧊 <?php echo __('freeze_dry_process'); ?></h2>
        <p style="color:#666; margin-bottom:20px;"><?php echo __('freeze_dry_description'); ?></p>

        <form method="POST" id="freezeDryForm" autocomplete="off">
            <input type="hidden" name="action" value="start_cycle">
            <input type="hidden" name="batch_id" id="batch_id">
            
            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">1. <?php echo __('select_fresh_batch'); ?></label>
                <select name="output_id" id="output_id" required onchange="updateBatchId()" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:4px; font-size:16px;">
                    <option value="">-- <?php echo __('choose_batch'); ?> --</option>
                    <?php foreach($availableBatches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" data-batch="<?php echo $b['batch_id']; ?>">
                            <?php echo htmlspecialchars($b['product_name']); ?> - <?php echo number_format($b['quantity'], 1); ?>g (Batch #<?php echo $b['batch_id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:20px;">
                <div>
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">2. <?php echo __('machine_id'); ?></label>
                    <select name="machine_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- <?php echo __('choose_batch'); ?> --</option>
                        <?php foreach($machines as $m): ?>
                            <option value="<?php echo $m['id']; ?>" data-power="<?php echo $m['power_w']; ?>">
                                <?php echo htmlspecialchars($m['name']); ?> (<?php echo $m['power_w']; ?>W)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">3. <?php echo __('input_weight_g'); ?></label>
                    <input type="number" step="1" name="input_weight" id="input_weight" required placeholder="0.00" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">4. <?php echo __('final_weight_g'); ?></label>
                    <input type="number" step="1" name="final_weight" id="final_weight" placeholder="0.00" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <small style="color:#666;"><?php echo __('leave_empty_if_running'); ?></small>
                </div>
            </div>

            <div style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #e9ecef;">
                <h4 style="margin:0 0 10px 0; color:#495057;">⚡ <?php echo __('energy_consumption'); ?></h4>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                    <div>
                        <label style="font-size:0.9em; font-weight:bold;"><?php echo __('energy_kwh'); ?></label>
                        <input type="number" step="1" name="energy_kwh" id="energy_kwh" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:0.9em; font-weight:bold;"><?php echo __('price_per_kwh'); ?></label>
                        <input type="number" step="0.001" name="energy_price" id="energy_price" value="<?php echo $defaultEnergyPrice; ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:0.9em; font-weight:bold;"><?php echo __('estimated_cost'); ?></label>
                        <div id="cost_display" style="padding:8px; background:#fff; border:1px solid #ddd; border-radius:4px; font-weight:bold; color:#27ae60;">€ 0.00</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo __('notes'); ?></label>
                <textarea name="notes" rows="2" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>

            <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:15px 30px; font-size:18px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;">
                🚀 <?php echo __('start_cycle'); ?>
            </button>
        </form>
    </div>

    <?php require_once __DIR__ . "/app/includes/b08_running_ui.php"; ?>
    <!-- Machine Beheer -->
    <div class="card" style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0; color:#2c3e50;">⚙️ <?php echo __('machine_management'); ?></h2>
        
        <!-- Machine Lijst -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
            <thead>
                <tr style="background:#f4f4f9; text-align:left;">
                    <th style="padding:10px; border-bottom:2px solid #ddd;"><?php echo __('machine_name'); ?></th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;"><?php echo __('machine_power_w'); ?></th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;"><?php echo __('active'); ?></th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($machines)): ?>
                    <tr><td colspan="4" style="padding:15px; text-align:center; color:#999;"><?php echo __('no_machines'); ?></td></tr>
                <?php else: ?>
                    <?php foreach($machines as $m): ?>
                    <tr>
                        <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($m['name']); ?></td>
                        <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $m['power_w']; ?> W</td>
                        <td style="padding:10px; border-bottom:1px solid #eee;">✓</td>
                        <td style="padding:10px; border-bottom:1px solid #eee; text-align:right;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo addslashes(__('confirm_delete')); ?>');">
                                <input type="hidden" name="action" value="delete_machine">
                                <input type="hidden" name="machine_id_del" value="<?php echo $m['id']; ?>">
                                <button type="submit" style="background:#dc3545; color:#fff; border:none; padding:5px 12px; border-radius:3px; cursor:pointer; font-size:12px;">🗑 <?php echo __('delete'); ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Nieuwe Machine Toevoegen -->
        <div style="background:#f8f9fa; padding:15px; border-radius:5px; border:1px dashed #ccc;">
            <h4 style="margin:0 0 10px 0;">➕ <?php echo __('add_machine'); ?></h4>
            <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
                <input type="hidden" name="action" value="add_machine">
                <div style="flex:1; min-width:200px;">
                    <label style="font-size:0.9em; font-weight:bold;"><?php echo __('machine_name'); ?></label>
                    <input type="text" name="machine_name" required placeholder="FD-Machine-02" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div style="flex:1; min-width:150px;">
                    <label style="font-size:0.9em; font-weight:bold;"><?php echo __('machine_power_w'); ?></label>
                    <input type="number" name="machine_power_w" value="1500" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">➕ <?php echo __('add_machine'); ?></button>
            </form>
        </div>
    </div>
</div>

<script>
function updateBatchId() {
    const s = document.getElementById('output_id');
    document.getElementById('batch_id').value = (s.selectedIndex > 0) ? s.options[s.selectedIndex].getAttribute('data-batch') : '';
}

    // Include complete_cycle actie
const eI = document.getElementById('energy_kwh');
const pI = document.getElementById('energy_price');
const cD = document.getElementById('cost_display');
function calcCost() { cD.textContent = '€ ' + ((parseFloat(eI.value)||0) * (parseFloat(pI.value)||0)).toFixed(2); }
eI.addEventListener('input', calcCost);
pI.addEventListener('input', calcCost);
</script>

<div class="next-step-bar">
    <div><a href="b07_harvest.php" style="color:#666; text-decoration:none;">← Oogst (B07)</a></div>
    <div><a href="b09_packaging.php">Volgende: Verpakking (B09) →</a></div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
