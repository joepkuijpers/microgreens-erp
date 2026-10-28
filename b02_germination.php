<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
require_once __DIR__ . '/app/includes/track_color.php';
$message = ""; $messageType = "";
$u = auth_current_user(); $operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$startTime = microtime(true);
$batchStmt = $db->prepare("SELECT id, batch_code, crop_type, rack_id, status, track_color FROM production_batches WHERE status IN ('PLANNED','SOWN','GERMINATING') ORDER BY id DESC");
$batchStmt->execute();
$openBatches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);
$allTrays = $db->query("SELECT id, tray_code, rack_id, status FROM trays ORDER BY tray_code")->fetchAll(PDO::FETCH_ASSOC);
$emptyTrays = array_values(array_filter($allTrays, fn($t) => $t["status"] === "EMPTY"));
$seedStmt = $db->prepare("SELECT si.id, sl.variety, sl.organic_status, sl.certificate_valid_until, s.name AS supplier, si.stock_grams FROM seed_inventory si JOIN seed_lots sl ON sl.id = si.seed_lot_id JOIN suppliers s ON s.id = si.supplier_id WHERE si.stock_grams > 0 ORDER BY sl.variety ASC");
$seedStmt->execute();
$seedInventory = $seedStmt->fetchAll(PDO::FETCH_ASSOC);

$trayStatusLabels = [
    'EMPTY'       => __('tray_empty_short'),
    'GERMINATING' => __('tray_germinating'),
    'GROWING'     => __('tray_growing'),
    'HARVESTED'   => __('tray_harvested'),
];
function trayStatusLabel($s, $map) { return $map[$s] ?? $s; }
// ── Annuleren ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_id"])) {
    $cancelId = (int)$_POST["cancel_id"];
    try {
        $db->beginTransaction();
        $rec = $db->prepare("SELECT id, tray_id, production_batch_id, status FROM germination_records WHERE id = :id");
        $rec->execute([':id' => $cancelId]);
        $row = $rec->fetch(PDO::FETCH_ASSOC);
        if (!$row) { throw new RuntimeException(__('err_germ_not_found')); }
        if ($row["status"] === "GEANNULEERD") { throw new RuntimeException(__('err_germ_already_cancelled')); }
        $harv = $db->prepare("SELECT COUNT(*) FROM harvests WHERE germination_record_id = :id");
        $harv->execute([':id' => $cancelId]);
        if ((int)$harv->fetchColumn() > 0) { throw new RuntimeException(__('err_germ_has_harvest')); }
        $db->prepare("UPDATE germination_records SET status = 'GEANNULEERD', notes = COALESCE(notes,'') || ' | GEANNULEERD ' || datetime('now','localtime') WHERE id = :id")->execute([':id' => $cancelId]);
        if (!empty($row["tray_id"])) { $db->prepare("UPDATE trays SET status = 'EMPTY' WHERE id = :id")->execute([':id' => $row["tray_id"]]); }
        if (!empty($row["production_batch_id"])) { $db->prepare("UPDATE production_batches SET status = 'SOWN' WHERE id = :id AND status = 'GERMINATING'")->execute([':id' => $row["production_batch_id"]]); }
        auditLog($db, 'B02_GERMINATION_CANCELLED', 'germination_records', $cancelId, 'CORRECTION', "Kiemactie #{$cancelId} geannuleerd. Tray teruggezet naar EMPTY.", ['status' => $row["status"]], ['status' => 'GEANNULEERD', 'operator' => $operator], 'production_batch', $row["production_batch_id"] ? (int)$row["production_batch_id"] : null);
        $db->commit();
        $message = sprintf(__('msg_germ_cancelled'), $cancelId);
        $messageType = "success";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $message = $e->getMessage(); $messageType = "error";
    }
}

// ── Status opvoeren (kiemfase voltooid / klaar voor oogst) ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["advance_id"])) {
    $advId = (int)$_POST["advance_id"];
    $advTo = $_POST["advance_to"] ?? '';
    $allowed = ['GERMINATING'=>'GROWING', 'GROWING'=>'READY_TO_HARVEST'];
    try {
        $db->beginTransaction();
        $st = $db->prepare("SELECT status FROM production_batches WHERE id = ?");
        $st->execute([$advId]);
        $cur = $st->fetchColumn();
        if ($cur === false || ($allowed[$cur] ?? '') !== $advTo) { throw new Exception(__('b02_advance_invalid')); }
        $db->prepare("UPDATE production_batches SET status = ? WHERE id = ?")->execute([$advTo, $advId]);
        auditLog($db, 'B02_BATCH_ADVANCED', 'production_batches', $advId, 'CORRECTION', "Batchstatus: {$cur} -> {$advTo}", ['status'=>$cur], ['status'=>$advTo, 'operator' => $operator]);
        $db->commit();
        $message = __('b02_advance_ok');
        $messageType = "success";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $message = $e->getMessage(); $messageType = "error";
    }
}

// ── Nieuwe kiemactie ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["cancel_id"]) && !isset($_POST["advance_id"])) {
    $seedInvId = 0; // afgeleid uit batch_materials
    $trayId    = (int)($_POST["tray_id"] ?? 0);
    $batchId   = (int)($_POST["production_batch_id"] ?? 0);
    $errors = [];
    // zaad uit batch afgeleid
    if ($trayId <= 0)    $errors[] = __('err_choose_tray');
    if ($batchId <= 0)   $errors[] = __('err_choose_batch');
    if (!empty($errors)) {
        $message = implode(" ", $errors); $messageType = "error";
    } else {
        try {
            $db->beginTransaction();
            $gate = $db->prepare("SELECT sl.variety, sl.organic_status, sl.certificate_valid_until, s.name AS supplier FROM seed_inventory si JOIN seed_lots sl ON sl.id = si.seed_lot_id JOIN suppliers s ON s.id = si.supplier_id WHERE si.id = :id");
            if ($seedInvId <= 0) {
                $bm = $db->prepare("SELECT inventory_id FROM batch_materials WHERE batch_id = :b LIMIT 1");
                $bm->execute([':b' => $batchId]);
                $seedInvId = (int)$bm->fetchColumn();
            }
            $gate->execute([':id' => $seedInvId]);
            $seed = $gate->fetch(PDO::FETCH_ASSOC);
            $gateResult = ['allowed'=>false, 'conventional'=>false, 'reason'=>''];
            if (!$seed) {
                $errors[] = __('err_seed_not_found');
            } else {
                $confirmed = isset($_POST["confirm_conventional"]);
                $gateResult = organicGateEvaluate($seed, $confirmed);
                if (!$gateResult["allowed"]) { $errors[] = $gateResult["reason"]; }
            }
            $trayStmt = $db->prepare("SELECT tray_code, status FROM trays WHERE id = :id");
            $trayStmt->execute([':id' => $trayId]);
            $tray = $trayStmt->fetch(PDO::FETCH_ASSOC);
            if (!$tray) { $errors[] = __('err_tray_not_found'); }
            elseif ($tray["status"] !== "EMPTY") { $errors[] = sprintf(__('err_tray_not_empty'), $tray['tray_code'], $tray['status']); }
            $batchGate = $db->prepare("SELECT batch_code, status FROM production_batches WHERE id = :id");
            $batchGate->execute([':id' => $batchId]);
            $batch = $batchGate->fetch(PDO::FETCH_ASSOC);
            if (!$batch) { $errors[] = __('err_batch_not_found'); }
            elseif (!in_array($batch["status"], ["PLANNED","SOWN","GERMINATING"], true)) { $errors[] = sprintf(__('err_batch_not_open'), $batch['batch_code'], $batch['status']); }
            $quantity = 0.0;
            $matStmt = $db->prepare("SELECT COALESCE(SUM(quantity_used),0) FROM batch_materials WHERE batch_id = :b");
            $matStmt->execute([':b' => $batchId]);
            $quantity = (float)$matStmt->fetchColumn();
            if ($quantity <= 0) { $errors[] = __('err_no_seed_assigned'); }
            if (!empty($errors)) {
                $db->rollBack();
                $message = implode(" ", $errors); $messageType = "error";
            } else {
                $now = date("Y-m-d H:i:s");
                $harvestDate = date("Y-m-d", strtotime("+7 days"));
                $germNotes = null;
                if (!empty($gateResult["conventional"])) { $germNotes = "CONVENTIONEEL ZAAD GEBRUIKT: " . $gateResult["reason"]; }
                $ins = $db->prepare("INSERT INTO germination_records (seed_inventory_id, tray_id, production_batch_id, quantity_seeds, start_date, expected_harvest_date, status, operator, notes) VALUES (:si, :tr, :pb, :q, :start, :harv, 'GERMINATING', :op, :notes)");
                $ins->execute([':si' => $seedInvId, ':tr' => $trayId, ':pb' => $batchId, ':q' => $quantity, ':start' => $now, ':harv' => $harvestDate, ':op' => 'auth', ':notes' => $germNotes]);
                $recId = (int)$db->lastInsertId();
                $db->prepare("UPDATE trays SET status = 'GERMINATING' WHERE id = :id")->execute([':id' => $trayId]);
                $db->prepare("UPDATE production_batches SET status = 'GERMINATING' WHERE id = :id")->execute([':id' => $batchId]);
                $regTime = round(microtime(true) - $startTime, 2);
                auditLog($db, 'B02_GERMINATION_STARTED', 'germination_records', $recId, 'CREATE', "Kiemactie: tray {$tray['tray_code']} voor batch {$batch['batch_code']} ({$seed['variety']}, {$quantity}g). Oogst {$harvestDate}." . (!empty($gateResult["conventional"]) ? " CONVENTIONEEL ZAAD: {$gateResult['reason']}" : "") . " Registratietijd {$regTime}s.", null, ['tray_code' => $tray['tray_code'], 'batch_code' => $batch['batch_code'], 'variety' => $seed['variety'], 'quantity_seeds' => $quantity, 'organic_status' => $seed['organic_status'], 'conventional' => !empty($gateResult["conventional"]), 'expected_harvest_date' => $harvestDate, 'registration_time_seconds' => $regTime, 'operator' => $operator], 'production_batch', $batchId);
                $db->commit();
                $message = sprintf(__('msg_germ_saved'), $tray['tray_code'], $batch['batch_code'], $regTime) . (!empty($gateResult["conventional"]) ? __('msg_conventional') : "");
                $messageType = "success";
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $message = $e->getMessage(); $messageType = "error";
        }
    }
}
$germStmt = $db->prepare("SELECT gr.id, gr.status, gr.quantity_seeds, gr.start_date, t.tray_code, pb.batch_code, pb.track_color, pb.id AS production_batch_id, pb.status AS batch_status FROM germination_records gr LEFT JOIN trays t ON t.id = gr.tray_id LEFT JOIN production_batches pb ON pb.id = gr.production_batch_id WHERE gr.status = 'GERMINATING' ORDER BY gr.id DESC");
$germStmt->execute();
$activeGerminations = $germStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="max-width:1000px; margin:0 auto;">
    <?php if($message): ?>
        <div style="background:<?php echo $messageType==='error'?'#ffebee':'#e8f5e9'; ?>; color:<?php echo $messageType==='error'?'#c62828':'#2e7d32'; ?>; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2 style="margin-top:0;"><?php echo htmlspecialchars(__('b02_title')); ?></h2>
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo htmlspecialchars(__('b02_batch_label')); ?></label>
                <select name="production_batch_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value=""><?php echo htmlspecialchars(__('b02_batch_placeholder')); ?></option>
                    <?php foreach($openBatches as $b): ?>
                        <option value="<?php echo (int)$b['id']; ?>"><?php echo trackEmoji($b['track_color'] ?? null); ?><?php echo htmlspecialchars($b['batch_code']); ?> - <?php echo htmlspecialchars($b['crop_type']); ?> (<?php echo htmlspecialchars($b['rack_id']); ?>, <?php echo htmlspecialchars($b['status']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($openBatches)): ?><small style="color:red;"><?php echo htmlspecialchars(__('b02_no_batches')); ?></small><?php endif; ?>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo htmlspecialchars(__('b02_seed_label')); ?></label>
                <div style="padding:8px 10px;margin-bottom:6px;background:#eef6ff;border:1px solid #cfe3ff;border-radius:4px;color:#345;font-size:13px;">Zaad wordt automatisch uit de gekozen batch gehaald — hier niets kiezen.</div>
                <select name="seed_inventory_id" disabled style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; opacity:.55;">
                    <option value=""><?php echo htmlspecialchars(__('b02_seed_placeholder')); ?></option>
                    <?php foreach($seedInventory as $s): ?>
                        <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['variety']); ?> - <?php echo htmlspecialchars($s['supplier']); ?> (<?php echo htmlspecialchars($s['organic_status']); ?>, <?php echo number_format($s['stock_grams'],1); ?>g)</option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($seedInventory)): ?><small style="color:red;"><?php echo htmlspecialchars(__('b02_no_seeds')); ?></small><?php endif; ?>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo htmlspecialchars(__('b02_tray_label')); ?></label>
                <select name="tray_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value=""><?php echo htmlspecialchars(__('b02_tray_placeholder')); ?></option>
                    <?php foreach($emptyTrays as $t): ?>
                        <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['tray_code']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($emptyTrays)): ?><small style="color:red;"><?php echo htmlspecialchars(__('b02_no_trays')); ?></small><?php endif; ?>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:flex;align-items:flex-start;gap:8px;font-weight:normal;">
                    <input type="checkbox" name="confirm_conventional" value="1" style="margin-top:4px;width:auto;">
                    <span><?php echo htmlspecialchars(__('b01_confirm_conventional')); ?></span>
                </label>
            </div>
            <button type="submit" style="background:#2e7d32; color:#fff; border:none; padding:14px 20px; border-radius:6px; cursor:pointer; font-size:16px; font-weight:bold;"><?php echo htmlspecialchars(__('b02_btn')); ?></button>
        </form>
    </div>
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2><?php echo htmlspecialchars(__('b02_active_title')); ?></h2>
        <?php if (empty($activeGerminations)): ?>
            <p style="color:#666;"><?php echo htmlspecialchars(__('b02_active_none')); ?></p>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr style="text-align:left; border-bottom:2px solid #ddd;">
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b02_th_tray')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b02_th_batch')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b02_th_grams')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b02_th_started')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b02_th_action')); ?></th>
                <th style="padding:8px;">Statusactie</th>
            </tr></thead>
            <tbody>
            <?php foreach ($activeGerminations as $g): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:8px;"><?php echo htmlspecialchars($g['tray_code'] ?? '-'); ?></td>
                    <td style="padding:8px;"><?php echo trackSwatch($g['track_color'] ?? null); ?><?php echo htmlspecialchars($g['batch_code'] ?? '-'); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars((string)$g['quantity_seeds']); ?>g</td>
                    <td style="padding:8px;"><?php echo htmlspecialchars((string)$g['start_date']); ?></td>
                    <td style="padding:8px;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__('b02_cancel_confirm')); ?>');">
                            <input type="hidden" name="cancel_id" value="<?php echo (int)$g['id']; ?>">
                            <button type="submit" style="background:#c62828;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;"><?php echo htmlspecialchars(__('b02_cancel')); ?></button>
                        </form>
                    </td>
                    <td style="padding:8px;">
                        <?php if(($g['batch_status'] ?? '') === 'GERMINATING'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__('b02_advance_germinating')); ?>');">
                            <input type="hidden" name="advance_id" value="<?php echo (int)$g['production_batch_id']; ?>">
                            <input type="hidden" name="advance_to" value="GROWING">
                            <button type="submit" style="background:#1976d2;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;"><?php echo htmlspecialchars(__('b02_advance_germinating')); ?></button>
                        </form>
                        <?php elseif(($g['batch_status'] ?? '') === 'GROWING'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__('b02_advance_ready')); ?>');">
                            <input type="hidden" name="advance_id" value="<?php echo (int)$g['production_batch_id']; ?>">
                            <input type="hidden" name="advance_to" value="READY_TO_HARVEST">
                            <button type="submit" style="background:#f57c00;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;"><?php echo htmlspecialchars(__('b02_advance_ready')); ?></button>
                        </form>
                        <?php else: ?>
                        <span style="color:#999;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2><?php echo htmlspecialchars(__('b02_tray_overview')); ?></h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(80px, 1fr)); gap:10px;">
            <?php foreach($allTrays as $t):
                $color = '#eee';
                if($t['status']=='EMPTY') $color='#d4edda';
                elseif($t['status']=='GERMINATING') $color='#fff3cd';
                elseif($t['status']=='GROWING') $color='#cce5ff';
            ?>
            <div style="background:<?php echo $color; ?>; padding:10px; text-align:center; border-radius:4px; border:1px solid #ccc;">
                <strong><?php echo htmlspecialchars($t['tray_code']); ?></strong>
                <div style="font-size:0.7em; color:#666;"><?php echo htmlspecialchars(trayStatusLabel($t["status"], $trayStatusLabels)); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="next-step-bar">
    <div><a href="b01_batch.php">B01</a></div>
    <div><a href="b03_growth_chamber.php">B03</a></div>
</div>
<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>
<?php require_once __DIR__ . '/app/includes/workflow_nav.php'; ?>
</main></div></body></html>
