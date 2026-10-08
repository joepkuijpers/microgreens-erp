<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b04');
$message = ""; $messageType = "";

// --- ACTIES VERWERKEN ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    
    // ACTIE: NIEUWE TRAY TOEVOEGEN
    if ($action === "add_tray") {
        $code = trim($_POST["tray_code"] ?? "");
        $rack_id = filter_input(INPUT_POST, "rack_id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "EMPTY";
        
        if (!$code || !$rack_id) {
            $message = __('error_fill_fields'); $messageType = "error";
        } else {
            try {
                $db->prepare("INSERT INTO trays (tray_code, rack_id, status) VALUES (?, ?, ?)")->execute([$code, $rack_id, $status]);
                $message = __('tray_added') . " ($code)"; $messageType = "success";
            } catch (Exception $e) {
                $message = __('error_tray_exists') . ": " . $e->getMessage(); $messageType = "error";
            }
        }
    }
    
    // ACTIE: TRAY STATUS WIJZIGEN
    if ($action === "edit_status") {
        $id = filter_input(INPUT_POST, "tray_id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "EMPTY";
        try {
            $db->prepare("UPDATE trays SET status = ? WHERE id = ?")->execute([$status, $id]);
            $message = __('tray_updated'); $messageType = "success";
        } catch (Exception $e) { $message = $e->getMessage(); $messageType = "error"; }
    }
    
    // ACTIE: TRAY VERWIJDEREN (VEILIG: alleen als GEEN productiegeschiedenis)
    if ($action === "delete_tray") {
        $id = filter_input(INPUT_POST, "tray_id", FILTER_VALIDATE_INT);
        try {
            // Check of tray in gebruik is (kieming, productie, oogst)
            $check = $db->prepare("SELECT COUNT(*) FROM germination_records WHERE tray_id = ?");
            $check->execute([$id]);
            $used = (int)$check->fetchColumn();
            
            if ($used > 0) {
                // Veilig: deactiveren i.p.v. verwijderen
                $db->prepare("UPDATE trays SET status = 'DECOMMISSIONED' WHERE id = ?")->execute([$id]);
                $message = __('tray_decommissioned'); $messageType = "warning";
            } else {
                // Veilig: hard verwijderen
                $db->prepare("DELETE FROM trays WHERE id = ?")->execute([$id]);
                $message = __('tray_deleted'); $messageType = "success";
            }
        } catch (Exception $e) { $message = $e->getMessage(); $messageType = "error"; }
    }
}

// --- DATA OPHALEN ---
$racks = []; $trays = [];
try {
    $racks = $db->query("SELECT id, name FROM racks ORDER BY name")->fetchAll();
    $trays = $db->query("
        SELECT t.*, r.name as rack_name,
        (SELECT COUNT(*) FROM germination_records WHERE tray_id = t.id) as usage_count
        FROM trays t LEFT JOIN racks r ON t.rack_id = r.id
        ORDER BY t.tray_code
    ")->fetchAll();
} catch (Exception $e) { $message = $e->getMessage(); $messageType = "error"; }
?>

<div style="max-width:1100px; margin:0 auto;">
    <?php if($message): ?>
        <div style="background:<?php echo $messageType==='error'?'#f8d7da':($messageType==='warning'?'#fff3cd':'#d4edda'); ?>; 
                    color:<?php echo $messageType==='error'?'#721c24':($messageType==='warning'?'#856404':'#155724'); ?>; 
                    padding:15px; border-radius:5px; margin-bottom:20px;"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- TRAY LIJST -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2 style="margin-top:0;">📦 <?php echo __('tray_list'); ?> (<?php echo count($trays); ?>)</h2>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f4f4f9; text-align:left;">
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Code</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Rack</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Status</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Gebruik</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd; text-align:right;">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($trays as $t): 
                    $color = '#eee';
                    if($t['status']=='EMPTY') $color='#d4edda';
                    elseif($t['status']=='GERMINATING') $color='#fff3cd';
                    elseif($t['status']=='GROWING') $color='#cce5ff';
                    elseif($t['status']=='DECOMMISSIONED') $color='#e2e3e5';
                ?>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong><?php echo htmlspecialchars($t['tray_code']); ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($t['rack_name'] ?? '-'); ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;">
                        <span style="background:<?php echo $color; ?>; padding:3px 8px; border-radius:3px; font-size:0.85em;"><?php echo $t['status']; ?></span>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid #eee;">
                        <?php echo $t['usage_count']; ?>x
                        <?php if($t['usage_count'] > 0): ?><span title="Historisch gebruikt - alleen deactiveren">🔒</span><?php endif; ?>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid #eee; text-align:right;">
                        <!-- Status wijzigen -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="edit_status">
                            <input type="hidden" name="tray_id" value="<?php echo $t['id']; ?>">
                            <select name="status" onchange="this.form.submit()" style="padding:4px; border:1px solid #ddd; border-radius:3px;">
                                <?php foreach(['EMPTY','GERMINATING','GROWING','DECOMMISSIONED'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $t['status']===$s?'selected':''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <!-- Verwijderen / Deactiveren -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo addslashes(__('confirm_delete')); ?>');">
                            <input type="hidden" name="action" value="delete_tray">
                            <input type="hidden" name="tray_id" value="<?php echo $t['id']; ?>">
                            <button type="submit" style="background:<?php echo $t['usage_count']>0?'#ffc107':'#dc3545'; ?>; color:#fff; border:none; padding:5px 10px; border-radius:3px; cursor:pointer;">
                                <?php echo $t['usage_count']>0?'⏸':'🗑'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- NIEUWE TRAY TOEVOEGEN -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0;">➕ <?php echo __('add_tray'); ?></h2>
        <form method="POST" style="display:flex; gap:15px; flex-wrap:wrap; align-items:end;">
            <input type="hidden" name="action" value="add_tray">
            <div style="flex:1; min-width:180px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Tray Code</label>
                <input type="text" name="tray_code" required placeholder="TRAY-C01" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="flex:1; min-width:180px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Rack</label>
                <select name="rack_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">-- Kies rack --</option>
                    <?php foreach($racks as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1; min-width:150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Status</label>
                <select name="status" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="EMPTY">EMPTY</option>
                </select>
            </div>
            <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:11px 25px; border-radius:4px; cursor:pointer; font-weight:bold;">➕ <?php echo __('add_tray'); ?></button>
        </form>
    </div>
</div>

<div class="next-step-bar">
    <a href="b03_growth_stage.php">← <?php echo __('module_b03'); ?></a>
    <a href="b05_seed_planning.php"><?php echo __('module_b05'); ?> →</a>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
