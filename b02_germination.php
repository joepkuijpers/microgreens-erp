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
        if ((int)$harv->fetchColumn() > 0) { throw new RuntimeException("Kan niet annuleren: deze kiemactie heeft al een (concept-)oogst. Verwijder die oogst-regel in B07 eerst, of voer de batch op in plaats van te annuleren."); }
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

// ── Blackout-override per kiemactie (optioneel; leeg = standaardrecept) ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["blackout_id"])) {
    $blackoutId = (int)$_POST["blackout_id"];
    try {
        $db->beginTransaction();
        $bk = $db->prepare("SELECT id, blackout_days, blackout_light_hours FROM germination_records WHERE id = :id");
        $bk->execute([':id' => $blackoutId]);
        $bkRow = $bk->fetch(PDO::FETCH_ASSOC);
        if (!$bkRow) { throw new RuntimeException(__('err_germ_not_found')); }

        $reset = isset($_POST["reset_blackout"]);
        $rawDays  = trim($_POST["blackout_days"]        ?? '');
        $rawLight = trim($_POST["blackout_light_hours"] ?? '');

        if ($reset) {
            $newDays = null; $newLight = null;
        } else {
            $newDays  = $rawDays  === '' ? null : (int)   $rawDays;
            $newLight = $rawLight === '' ? null : (float) $rawLight;
            if ($newDays !== null && ($newDays < 0 || $newDays > 30)) { throw new RuntimeException(__('b02_blackout_days_invalid')); }
        }

        $db->prepare("UPDATE germination_records SET blackout_days = :d, blackout_light_hours = :l WHERE id = :id")
           ->execute([':d' => $newDays, ':l' => $newLight, ':id' => $blackoutId]);

        auditLog($db, 'B02_BLACKOUT_OVERRIDE', 'germination_records', $blackoutId, 'CORRECTION',
            "Blackout-override aangepast voor kiemactie #{$blackoutId}." . ($reset ? " Teruggezet naar standaardrecept." : ""),
            ['blackout_days' => $bkRow['blackout_days'], 'blackout_light_hours' => $bkRow['blackout_light_hours']],
            ['blackout_days' => $newDays, 'blackout_light_hours' => $newLight, 'operator' => $operator],
            'germination_record', $blackoutId);
        $db->commit();
        $message = __('b02_blackout_saved');
        $messageType = "success";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $message = $e->getMessage(); $messageType = "error";
    }
}

// ── Nieuwe kiemactie ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["cancel_id"]) && !isset($_POST["advance_id"]) && !isset($_POST["blackout_id"])) {
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
// Actieve kiemacties + effectieve blackout (override → lot-standaard → gewas-recept)
$germStmt = $db->prepare("SELECT gr.id, gr.status, gr.quantity_seeds, gr.start_date,
                                 gr.blackout_days, gr.blackout_light_hours,
                                 sl.blackout_days AS lot_days, sl.blackout_light_hours AS lot_light,
                                 cp.blackout_days AS standaard_days, cp.blackout_light_hours AS standaard_light,
                                 t.tray_code, pb.batch_code, pb.crop_type, pb.track_color,
                                 pb.id AS production_batch_id, pb.status AS batch_status
                          FROM germination_records gr
                          LEFT JOIN trays t ON t.id = gr.tray_id
                          LEFT JOIN production_batches pb ON pb.id = gr.production_batch_id
                          LEFT JOIN crop_profiles cp ON cp.id = pb.crop_id
                          LEFT JOIN seed_inventory si ON si.id = gr.seed_inventory_id
                          LEFT JOIN seed_lots sl ON sl.id = si.seed_lot_id
                          WHERE gr.status = 'GERMINATING' ORDER BY gr.id DESC");
$germStmt->execute();
$activeGerminations = $germStmt->fetchAll(PDO::FETCH_ASSOC);

// Terugval op naam wanneer crop_id geen geldige FK bevat
foreach ($activeGerminations as $i => $g) {
    if ($g['standaard_days'] === null && !empty($g['crop_type'])) {
        $cp = $db->prepare("SELECT blackout_days, blackout_light_hours FROM crop_profiles WHERE crop_name = :c LIMIT 1");
        $cp->execute([':c' => $g['crop_type']]);
        if ($cpRow = $cp->fetch(PDO::FETCH_ASSOC)) {
            $activeGerminations[$i]['standaard_days']  = $cpRow['blackout_days'];
            $activeGerminations[$i]['standaard_light'] = $cpRow['blackout_light_hours'];
        }
    }
    // Lot-standaard gaat vóór het gewasrecept: lot wint van profiel
    if ($g['lot_days'] !== null) {
        $activeGerminations[$i]['standaard_days']  = $g['lot_days'];
        $activeGerminations[$i]['standaard_light'] = $g['lot_light'];
    }
}
?>
