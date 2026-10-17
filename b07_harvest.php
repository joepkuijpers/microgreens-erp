<?php
require_once __DIR__ . '/app/includes/auth.php';
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';

$message = '';
$errors = [];
$registrationTime = null;

// --- OUTPUT-TAKEN (centraal, hergebruikt) ---
$branchLabels = [
    'FRESH'        => __('b07_branch_fresh'),
    'FREEZE_DRY'   => __('b07_branch_freeze'),
];
$branchOutputType = [
    'FRESH'      => 'FRESH',
    'FREEZE_DRY' => 'FREEZE_DRY_INPUT',
];
$branchInvStatus = [
    'FRESH'      => 'AVAILABLE',
    'FREEZE_DRY' => 'PENDING_FREEZE',
];

// --- VERWERKING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'harvest') {
    $t0 = microtime(true);
    $batch_id    = intval($_POST['batch_id'] ?? 0);
    $branch      = $_POST['branch'] ?? 'FRESH';
    $w_fresh     = floatval($_POST['weight_fresh'] ?? 0);
    $w_freeze    = floatval($_POST['weight_freeze'] ?? 0);
    $notes       = trim($_POST['notes'] ?? '');
    $confirmedConventional = isset($_POST['confirm_conventional']);
    $activeInteraction = isset($_POST['active_interaction_ms']) ? intval($_POST['active_interaction_ms']) : null;
    $screenAvailability = isset($_POST['screen_availability_ms']) ? intval($_POST['screen_availability_ms']) : null;

    if ($batch_id <= 0) { $errors[] = __('b07_err_no_batch'); }
    if (!isset($branchLabels[$branch]) && $branch !== 'BOTH') { $errors[] = __('b07_err_branch'); }

    // Gewichten per tak samenstellen
    $alloc = [];
    if ($branch === 'FRESH')        { $alloc['FRESH'] = $w_fresh; }
    elseif ($branch === 'FREEZE_DRY'){ $alloc['FREEZE_DRY'] = $w_freeze; }
    elseif ($branch === 'BOTH')     { $alloc['FRESH'] = $w_fresh; $alloc['FREEZE_DRY'] = $w_freeze; }

    $total = array_sum($alloc);
    foreach ($alloc as $k => $v) {
        if ($v <= 0) { $errors[] = $branchLabels[$k] . ': ' . __('b07_err_weight'); }
    }
    if ($total <= 0) { $errors[] = __('b07_err_total'); }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Batch laden + statuscheck
            $stmt = $db->prepare("SELECT id, batch_code, crop_type, status, rack_id FROM production_batches WHERE id = ?");
            $stmt->execute([$batch_id]);
            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$batch) { throw new Exception(__('b07_err_not_found')); }
            if ($batch['status'] === 'HARVESTED') { throw new Exception(__('b07_err_already')); }

            // 2. Organic-gate: gevalideerde seed-input vereist voor deze batch
            $seedStmt = $db->prepare("
                SELECT sl.organic_status, sl.certificate_valid_until
                FROM batch_materials bm
                JOIN seed_inventory si ON si.id = bm.inventory_id
                JOIN seed_lots sl ON sl.id = si.seed_lot_id
                WHERE bm.batch_id = ?
                LIMIT 1
            ");
            $seedStmt->execute([$batch_id]);
            $seed = $seedStmt->fetch(PDO::FETCH_ASSOC);
            if (!$seed) { throw new Exception(__('b07_err_no_input')); }
            $gate = organicGateEvaluate($seed, $confirmedConventional);
            if (empty($gate['allowed'])) { throw new Exception(__('b07_err_gate')); }

            // 3. Kiemrecord afleiden (traceability-keten intact houden)
            $germStmt = $db->prepare("SELECT id FROM germination_records WHERE production_batch_id = ? ORDER BY id DESC LIMIT 1");
            $germStmt->execute([$batch_id]);
            $germId = $germStmt->fetchColumn();
            if (!$germId) { throw new Exception(__('b07_err_no_germination')); }

            $u = auth_current_user(); $operator = $u['display_name'] ?? 'system';

            // 4. Eén oogstrecord (totaal)
            $hStmt = $db->prepare("INSERT INTO harvests (germination_record_id, harvest_date, weight_grams, quality_status, operator, notes) VALUES (?, datetime('now'), ?, 'GOOD', ?, ?)");
            $hStmt->execute([$germId, $total, $operator, $notes]);
            $harvestId = (int)$db->lastInsertId();

            // 5. Per tak: production_output + finished_inventory
            foreach ($alloc as $key => $grams) {
                $otype = $branchOutputType[$key];
                $istat = $branchInvStatus[$key];

                $po = $db->prepare("INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes) VALUES (?, ?, ?, 'g', 'REGISTERED', ?)");
                $po->execute([$batch_id, $otype, $grams, $notes]);

                $fi = $db->prepare("INSERT INTO finished_inventory (harvest_id, product_name, weight_grams, harvest_date, status, output_type) VALUES (?, ?, ?, date('now'), ?, ?)");
                $fi->execute([$harvestId, $batch['crop_type'], $grams, $istat, $otype]);
            }

            // 6. Batchstatus
            $db->prepare("UPDATE production_batches SET status = 'HARVESTED', actual_quantity = ?, completed_at = datetime('now') WHERE id = ?")
               ->execute([$total, $batch_id]);

            $registrationTime = round(microtime(true) - $t0, 3);

            // 7. Audit (bewijs in 'reason'-kolom)
            auditLog($db, 'B07_HARVEST', 'harvests', $harvestId, 'REGISTER_HARVEST',
                sprintf(__('b07_audit'), $batch['batch_code'], $total, implode(' + ', array_keys($alloc))),
                null, ['operator' => $operator, 'reg_time' => $registrationTime, 'active_interaction_ms' => $activeInteraction, 'screen_availability_ms' => $screenAvailability]);

            $db->commit();
            $message = sprintf(__('b07_success'), $batch['batch_code'], $total, $registrationTime);

        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// --- ACTIEVE BATCHES ---
$available_batches = [];
try {
    $stmt = $db->query("SELECT id, batch_code, crop_type, started_at, rack_id FROM production_batches WHERE status IN ('GROWING','READY_TO_HARVEST') ORDER BY started_at DESC");
    $available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* leeg */ }
?>

<div style="max-width:800px; margin:0 auto;">
    <h2>🌾 <?php echo __('b07_title'); ?></h2>

    <?php if ($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border-left:5px solid #28a745;">✅ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border-left:5px solid #dc3545;">❌ <?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if (empty($available_batches)): ?>
        <div style="background:#e3f2fd; padding:15px; border-radius:5px; border-left:4px solid #2196f3;">ℹ️ <?php echo __('b07_no_batches'); ?></div>
    <?php else: ?>
    <form method="POST" style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <input type="hidden" name="action" value="harvest">
        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:bold; margin-bottom:6px;"><?php echo __('b07_batch'); ?></label>
            <select name="batch_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                <option value="">-- <?php echo __('b07_choose_batch'); ?> --</option>
                <?php foreach ($available_batches as $b): ?>
                    <option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['batch_code']); ?> — <?php echo htmlspecialchars($b['crop_type']); ?> (<?php echo htmlspecialchars($b['rack_id']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:bold; margin-bottom:6px;"><?php echo __('b07_output_type'); ?></label>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <label style="flex:1; min-width:140px; border:1px solid #ddd; border-radius:6px; padding:12px; cursor:pointer;"><input type="radio" name="branch" value="FRESH" checked onchange="toggleBranch()"> 🥬 <?php echo __('b07_branch_fresh'); ?></label>
                <label style="flex:1; min-width:140px; border:1px solid #ddd; border-radius:6px; padding:12px; cursor:pointer;"><input type="radio" name="branch" value="FREEZE_DRY" onchange="toggleBranch()"> ❄️ <?php echo __('b07_branch_freeze'); ?></label>
                <label style="flex:1; min-width:140px; border:1px solid #ddd; border-radius:6px; padding:12px; cursor:pointer;"><input type="radio" name="branch" value="BOTH" onchange="toggleBranch()"> 🔀 <?php echo __('b07_branch_both'); ?></label>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
            <div id="wrap_fresh">
                <label style="display:block; font-weight:bold; margin-bottom:6px;"><?php echo __('b07_weight_fresh'); ?></label>
                <div style="font-size:0.8em; color:#888; margin-bottom:4px;"><?= __("b07_weight_invoer") ?></div>
                <input type="number" step="0.1" min="0" name="weight_fresh" placeholder="0" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div id="wrap_freeze" style="display:none;">
                <label style="display:block; font-weight:bold; margin-bottom:6px;"><?php echo __('b07_weight_freeze'); ?></label>
                <div style="font-size:0.8em; color:#888; margin-bottom:4px;"><?= __("b07_weight_invoer") ?></div>
                <input type="number" step="0.1" min="0" name="weight_freeze" placeholder="0" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
        </div>
        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:bold; margin-bottom:6px;"><?php echo __('b07_notes'); ?></label>
            <textarea name="notes" rows="2" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            <label style="display:block; margin-top:12px; font-weight:normal;"><input type="checkbox" name="confirm_conventional" value="1"> 🌿 <?php echo __('b07_confirm_conv'); ?></label>
        </div>
        <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:14px 24px; font-size:16px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;">✅ <?php echo __('b07_submit'); ?></button>
    </form>
    <?php endif; ?>
</div>
<script>
function toggleBranch() {
    var b = document.querySelector('input[name=branch]:checked').value;
    document.getElementById('wrap_fresh').style.display  = (b === 'FREEZE_DRY') ? 'none' : 'block';
    document.getElementById('wrap_freeze').style.display = (b === 'FRESH')      ? 'none' : 'block';
}
</script>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
