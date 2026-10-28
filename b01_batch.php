<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
require_once __DIR__ . '/app/includes/track_color.php';

$flash = "";
$flashType = "";
$u = auth_current_user(); $operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$startTime = microtime(true);
$mode = ($_GET["mode"] ?? "now") === "planned" ? "planned" : "now";

$racks = $db->query("SELECT name FROM racks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$seedStmt = $db->prepare("
    SELECT si.id AS inventory_id, sl.variety, sl.organic_status,
           sl.certificate_valid_until, s.name AS supplier_name,
           si.stock_grams
    FROM seed_inventory si
    JOIN seed_lots sl ON sl.id = si.seed_lot_id
    JOIN suppliers s   ON s.id = si.supplier_id
    WHERE si.stock_grams > 0
    ORDER BY sl.variety ASC
");
$seedStmt->execute();
$seedOptions = $seedStmt->fetchAll(PDO::FETCH_ASSOC);

function daysBetween(?string $a, ?string $b): ?int {
    if (!$a || !$b) return null;
    $d1 = strtotime($a); $d2 = strtotime($b);
    if ($d1 === false || $d2 === false) return null;
    return (int)round(($d2 - $d1) / 86400);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $post_mode    = ($_POST["mode"] ?? "now") === "planned" ? "planned" : "now";
    $inventory_id = (int)($_POST["inventory_id"] ?? 0);
    $rack_id      = trim($_POST["rack_id"] ?? "");
    $tray_count   = (int)($_POST["tray_count"] ?? 0);
    $tray_type    = trim($_POST["tray_type"] ?? "1020");
    $seed_grams   = (float)($_POST["seed_grams"] ?? 0);
    $expected     = trim($_POST["expected_harvest_date"] ?? "");
    $planned_sow  = trim($_POST["planned_sow_date"] ?? "");

    $need_sow = ($post_mode === "planned") ? ($planned_sow !== "") : true;

    $errors = [];
    if ($inventory_id <= 0) $errors[] = __('err_choose_seed');
    if ($rack_id === "")    $errors[] = __('err_choose_rack');
    if ($tray_count <= 0)   $errors[] = __('err_tray_min');
    if ($seed_grams <= 0)   $errors[] = __('err_grams_min');
    if (!$need_sow)         $errors[] = __('err_choose_sow');

    $today = date("Y-m-d");
    if ($expected !== "" && $expected < $today) {
        $errors[] = __('err_harvest_past');
    }
    if ($post_mode === "planned" && $planned_sow !== "" && $planned_sow < $today) {
        $errors[] = __('err_sow_past');
    }
    if ($post_mode === "planned" && $planned_sow !== "" && $expected !== "" && $expected <= $planned_sow) {
        $errors[] = __('err_harvest_after_sow');
    }

    if (!empty($errors)) {
        $flash = implode(" ", $errors);
        $flashType = "error";
    } else {
        try {
            $db->beginTransaction();

            $gate = $db->prepare("
                SELECT sl.id AS lot_id, sl.variety, sl.organic_status,
                       sl.certificate_valid_until, s.name AS supplier_name,
                       s.is_skal_validated, si.stock_grams
                FROM seed_inventory si
                JOIN seed_lots sl ON sl.id = si.seed_lot_id
                JOIN suppliers s  ON s.id = si.supplier_id
                WHERE si.id = :id
            ");
            $gate->execute([':id' => $inventory_id]);
            $seed = $gate->fetch(PDO::FETCH_ASSOC);

            $gateResult = ['allowed'=>false, 'conventional'=>false, 'reason'=>''];
            if (!$seed) {
                $errors[] = __('err_seed_not_found');
            } else {
                $confirmed = isset($_POST["confirm_conventional"]);
                $gateResult = organicGateEvaluate($seed, $confirmed);
                if (!$gateResult["allowed"]) {
                    $errors[] = $gateResult["reason"];
                }
                if ($seed["stock_grams"] < $seed_grams) {
                    $errors[] = sprintf(__('err_no_stock'), $seed['stock_grams'], $seed_grams);
                }
            }

            if (!empty($errors)) {
                $db->rollBack();
                $flash = implode(" ", $errors);
                $flashType = "error";
            } else {
                $crop_type = $seed["variety"];
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $crop_type), 0, 3));
                if ($prefix === "") $prefix = "MG";
                $date_str = date("Ymd");
                $codeStmt = $db->prepare("SELECT COUNT(*) FROM production_batches WHERE batch_code LIKE :p");
                $codeStmt->execute([':p' => $prefix . '-' . $date_str . '-%']);
                $seq = (int)$codeStmt->fetchColumn() + 1;
                $batch_code = sprintf("%s-%s-%02d", $prefix, $date_str, $seq);

                $status    = ($post_mode === "planned") ? "PLANNED" : "SOWN";
                $started   = ($post_mode === "planned") ? null : date("Y-m-d H:i:s");
                $planned   = ($post_mode === "planned") ? $planned_sow : null;
                $exp_val   = $expected !== "" ? $expected : null;

                $notes = "";
                if (!empty($gateResult["conventional"])) {
                    $notes = "⚠ CONVENTIONEEL ZAAD GEBRUIKT: " . $gateResult["reason"];
                }

                $track = (isset($_POST['track_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['track_color'])) ? $_POST['track_color'] : null;
                $ins = $db->prepare("
                    INSERT INTO production_batches
                        (batch_code, status, crop_type, rack_id, tray_count, tray_type,
                         expected_harvest_date, planned_sow_date, started_at, notes, track_color)
                    VALUES
                        (:code, :status, :crop, :rack, :tray_count, :tray_type,
                         :expected, :planned, :started, :notes, :track)
                ");
                $ins->execute([
                    ':track'      => $track,
                    ':code'       => $batch_code,
                    ':status'     => $status,
                    ':crop'       => $crop_type,
                    ':rack'       => $rack_id,
                    ':tray_count' => $tray_count,
                    ':tray_type'  => $tray_type,
                    ':expected'   => $exp_val,
                    ':planned'    => $planned,
                    ':started'    => $started,
                    ':notes'      => $notes !== "" ? $notes : null,
                ]);
                $batch_id = (int)$db->lastInsertId();

                $bm = $db->prepare("INSERT INTO batch_materials (batch_id, inventory_id, quantity_used) VALUES (:b, :i, :q)");
                $bm->execute([':b' => $batch_id, ':i' => $inventory_id, ':q' => $seed_grams]);

                if ($post_mode === "now") {
                    $dec = $db->prepare("UPDATE seed_inventory SET stock_grams = stock_grams - :q WHERE id = :id");
                    $dec->execute([':q' => $seed_grams, ':id' => $inventory_id]);
                }

                $regTime = round(microtime(true) - $startTime, 2);

                $dSowToHarvest = ($exp_val && ($started || $planned))
                    ? daysBetween($started ? substr($started,0,10) : $planned, $exp_val) : null;
                $dNowToHarvest = $exp_val ? daysBetween(date("Y-m-d"), $exp_val) : null;
                $dNowToSow     = $planned ? daysBetween(date("Y-m-d"), $planned) : null;

                $desc = "Batch {$batch_code} (" . ($post_mode === "planned" ? "gepland" : "gestart") . "): {$crop_type}, rack {$rack_id}, {$seed_grams}g zaad uit lot '{$seed['variety']}' ({$seed['supplier_name']}).";
                if (!empty($gateResult["conventional"])) $desc .= " CONVENTIONEEL ZAAD: {$gateResult['reason']}";
                if ($dNowToHarvest !== null) $desc .= " Over {$dNowToHarvest} dagen oogst.";
                if ($dNowToSow !== null)     $desc .= " Gepland zaaien over {$dNowToSow} dagen.";
                $desc .= " Registratietijd {$regTime}s.";

                auditLog(
                    $db, 'B01_BATCH_CREATED', 'production_batches', $batch_id, 'CREATE',
                    $desc,
                    null,
                    [
                        'batch_code' => $batch_code, 'rack_id' => $rack_id, 'seed_grams' => $seed_grams,
                        'organic_status' => $seed['organic_status'], 'conventional' => !empty($gateResult["conventional"]),
                        'mode' => $post_mode, 'planned_sow_date' => $planned, 'expected_harvest_date' => $exp_val,
                        'days_now_to_harvest' => $dNowToHarvest, 'days_now_to_sow' => $dNowToSow,
                        'days_sow_to_harvest' => $dSowToHarvest, 'registration_time_seconds' => $regTime, 'operator' => $operator,
                    ],
                    'seed_lot', (int)$seed["lot_id"]
                );

                $db->commit();
                $word = ($post_mode === "planned") ? __('word_planned') : __('word_started');
                $flash = sprintf(__('msg_batch_started'), $batch_code, $word, $rack_id, $regTime) .
                         (!empty($gateResult["conventional"]) ? __('msg_conventional') : "");
                $flashType = "ok";
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $flash = $e->getMessage();
            $flashType = "error";
        }
    }
}

?>

<div style="max-width:640px;margin:20px auto;font-family:sans-serif;">
    <h2><?= htmlspecialchars(__('b01_title')) ?></h2>
    <div style="margin:0 0 16px;"><a href="germination_test.php" style="display:inline-block;margin-left:8px;background:#0e7490;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">+ <?= __('germ_register') ?></a></div>
    <div style="margin:0 0 16px;"><a href="add_seed_inventory_form.php" style="display:inline-block;background:#2e7d32;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">+ <?= __('seed_register_new') ?></a></div>

    <div style="display:flex;gap:8px;margin-bottom:18px;">
        <a href="?mode=now" style="flex:1;text-align:center;padding:12px;border-radius:8px;text-decoration:none;font-weight:bold;<?= $mode==='now' ? 'background:#2e7d32;color:#fff;' : 'background:#eee;color:#333;' ?>"><?= htmlspecialchars(__('b01_tab_now')) ?></a>
        <a href="?mode=planned" style="flex:1;text-align:center;padding:12px;border-radius:8px;text-decoration:none;font-weight:bold;<?= $mode==='planned' ? 'background:#1565c0;color:#fff;' : 'background:#eee;color:#333;' ?>"><?= htmlspecialchars(__('b01_tab_planned')) ?></a>
    </div>

    <?php if ($flash): ?>
        <div style="padding:15px;border-radius:8px;margin-bottom:20px;<?= $flashType === 'ok' ? 'background:#e8f5e9;color:#2e7d32;' : 'background:#ffebee;color:#c62828;' ?>">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <input type="hidden" name="mode" value="<?= $mode ?>">

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_seed_label')) ?></label>
            <select name="inventory_id" required style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
                <option value=""><?= htmlspecialchars(__('b01_seed_placeholder')) ?></option>
                <?php foreach ($seedOptions as $s): ?>
                    <option value="<?= (int)$s['inventory_id'] ?>">
                        <?= htmlspecialchars($s['variety']) ?> - <?= htmlspecialchars($s['supplier_name']) ?>
                        (<?= htmlspecialchars($s['organic_status']) ?>, <?= (float)$s['stock_grams'] ?>g)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div style="flex:1;">
                <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:bold;margin-bottom:6px;">Volgkleur (optioneel)</label>
                <div style="display:flex;align-items:center;gap:10px;"><span id="colorBox" style="width:26px;height:26px;border-radius:5px;border:1px solid #ccc;background:#fff;flex-shrink:0;"></span><select name="track_color" style="width:100%;padding:10px;font-size:16px;border:1px solid #ddd;border-radius:6px;"><option value="">Geen kleur</option><option value="#1e3a8a">Donkerblauw</option><option value="#38bdf8">Lichtblauw</option><option value="#166534">Donkergroen</option><option value="#4ade80">Lichtgroen</option><option value="#ffdd00">Geel</option><option value="#ff8000">Oranje</option><option value="#78350f">Bruin</option><option value="#7c3aed">Paars</option><option value="#ff0000">Rood</option><option value="#6b7280">Grijs</option><option value="#111827">Zwart</option><option value="#ffffff">Wit</option><option value="#ec4899">Roze</option></select></div><script>(function(){var s=document.querySelector('select[name=track_color]');var b=document.getElementById("colorBox");function u(){b.style.background=s.value||"#fff";}s.addEventListener("change",u);u();})();</script>
                <span style="font-size:12px;color:#888;">kleur die bij deze batch blijft</span>
            </div>
            <div>
            <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_rack')) ?></label>
                <select name="rack_id" required style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
                    <option value=""><?= htmlspecialchars(__('b01_rack_placeholder')) ?></option>
                    <?php foreach ($racks as $r): ?>
                        <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_tray_count')) ?></label>
                <input type="number" name="tray_count" value="1" min="1" required style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_seed_grams')) ?></label>
            <input type="number" name="seed_grams" step="0.01" min="0.01" required style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
        </div>

        <?php if ($mode === 'planned'): ?>
        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_planned_sow')) ?></label>
            <input type="date" name="planned_sow_date" id="plannedSow" required style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
        </div>
        <?php endif; ?>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-weight:bold;margin-bottom:6px;"><?= htmlspecialchars(__('b01_expected_harvest')) ?> <span style="font-weight:normal;color:#888;"><?= htmlspecialchars(__('b01_harvest_hint')) ?></span></label>
            <input type="date" name="expected_harvest_date" id="harvestDate" style="width:100%;padding:12px;font-size:16px;border:1px solid #ddd;border-radius:6px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:flex;align-items:flex-start;gap:8px;font-weight:normal;">
                <input type="checkbox" name="confirm_conventional" value="1" style="margin-top:4px;width:auto;">
                <span><?= htmlspecialchars(__('b01_confirm_conventional')) ?></span>
            </label>
        </div>

        <div id="dayInfo" style="padding:12px;background:#f0f4f8;border-radius:8px;margin-bottom:16px;font-weight:bold;color:#2c3e50;display:none;"></div>

        <button type="submit" style="width:100%;padding:16px;font-size:18px;font-weight:bold;color:#fff;background:<?= $mode==='planned' ? '#1565c0' : '#2e7d32' ?>;border:none;border-radius:8px;cursor:pointer;">
            <?= htmlspecialchars($mode==='planned' ? __('b01_btn_planned') : __('b01_btn_now')) ?>
        </button>
    </form>
</div>

<script>
(function(){
    var mode = "<?= $mode ?>";
    var harvest = document.getElementById("harvestDate");
    var sow = document.getElementById("plannedSow");
    var info = document.getElementById("dayInfo");
    var L = {
        hint: <?= json_encode(__('b01_day_hint')) ?>,
        toSow: <?= json_encode(__('b01_days_to_sow')) ?>,
        sowHarvest: <?= json_encode(__('b01_days_sow_to_harvest')) ?>,
        toHarvest: <?= json_encode(__('b01_days_to_harvest')) ?>
    };
    function fmt(tpl, n){ return tpl.replace(/%d/, n); }
    function todayStr(){ var d=new Date(); return d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0"); }
    function days(a,b){ if(!a||!b) return null; var d1=new Date(a+"T00:00:00"), d2=new Date(b+"T00:00:00"); if(isNaN(d1)||isNaN(d2)) return null; return Math.round((d2-d1)/86400000); }
    function render(){
        var today=todayStr(); var parts=[];
        if(mode==="planned" && sow && sow.value){
            var ns=days(today, sow.value);
            if(ns!==null && ns>=0) parts.push(fmt(L.toSow, ns));
        }
        if(harvest && harvest.value){
            var anchor = (mode==="planned" && sow && sow.value) ? sow.value : today;
            var nh=days(anchor, harvest.value);
            if(nh!==null) parts.push(fmt(L.sowHarvest, nh));
            var nnow=days(today, harvest.value);
            if(nnow!==null && nnow>=0) parts.push(fmt(L.toHarvest, nnow));
        }
        if(parts.length){ info.style.display="block"; info.textContent=parts.join("   |   "); }
        else if(harvest && !harvest.value){ info.style.display="block"; info.textContent=L.hint; }
        else { info.style.display="none"; }
    }
    if(harvest) harvest.addEventListener("change", render);
    if(sow) sow.addEventListener("change", render);
    render();
})();
</script>

<div class="next-step-bar">
    <div><a href="dashboard.php">Dashboard</a></div>
    <div><a href="b02_germination.php">Volgende: B02</a></div>
</div>

<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>