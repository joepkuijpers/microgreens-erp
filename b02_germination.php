<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b02');
$message = ""; $messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $seedInvId = $_POST["seed_inventory_id"] ?? null;
    $trayId = $_POST["tray_id"] ?? null;
    $quantity = $_POST["quantity_seeds"] ?? 0;
    $operator = $_SESSION['user_name'] ?? "Joep";

    if (!$seedInvId || !$trayId || $quantity <= 0) {
        $message = __('seed_error'); $messageType = "error";
    } else {
        $stmt = $db->prepare("SELECT stock_grams FROM seed_inventory WHERE id = ?");
        $stmt->execute([$seedInvId]);
        $currentStock = $stmt->fetchColumn();
        if ($currentStock === false || $currentStock < $quantity) {
            $message = __('seed_no_stock') . ($currentStock ?: 0) . "g";
            $messageType = "error";
        } else {
            $harvestDate = date("Y-m-d", strtotime("+7 days"));
            $now = date("Y-m-d H:i:s");
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE seed_inventory SET stock_grams = stock_grams - ? WHERE id = ?")->execute([$quantity, $seedInvId]);
                $db->prepare("UPDATE trays SET status = ? WHERE id = ?")->execute(["GERMINATING", $trayId]);
                $db->prepare("INSERT INTO germination_records (seed_inventory_id, tray_id, quantity_seeds, start_date, expected_harvest_date, operator) VALUES (?, ?, ?, ?, ?, ?)")->execute([$seedInvId, $trayId, $quantity, $now, $harvestDate, $operator]);
                $db->commit();
                $message = __('seed_success') . $harvestDate;
                $messageType = "success";
            } catch (Exception $e) {
                $db->rollBack();
                $message = $e->getMessage(); $messageType = "error";
            }
        }
    }
}

$racks = []; $emptyTrays = []; $seedInventory = [];
try {
    $racks = $db->query("SELECT id, name FROM racks ORDER BY name")->fetchAll();
    $allTrays = $db->query("SELECT id, tray_code, rack_id, status FROM trays ORDER BY tray_code")->fetchAll();
    foreach ($allTrays as $tray) {
        if ($tray["status"] === "EMPTY") {
            $rackName = "Onbekend";
            foreach ($racks as $r) { if ($r["id"] == $tray["rack_id"]) { $rackName = $r["name"]; break; } }
            $tray["rack_name"] = $rackName;
            $emptyTrays[] = $tray;
        }
    }
    $seedInventory = $db->query("SELECT si.id, sl.variety, si.stock_grams, s.name as supplier FROM seed_inventory si JOIN seed_lots sl ON si.seed_lot_id = sl.id JOIN suppliers s ON si.supplier_id = s.id WHERE si.stock_grams > 0 ORDER BY sl.variety")->fetchAll();
} catch (Exception $e) { $message = $e->getMessage(); $messageType = "error"; }
?>

<div style="max-width:1000px; margin:0 auto;">
    <?php if($message): ?>
        <div style="background:<?php echo $messageType==='error'?'#f8d7da':'#d4edda'; ?>; color:<?php echo $messageType==='error'?'#721c24':'#155724'; ?>; padding:15px; border-radius:5px; margin-bottom:20px;"><?php echo $message; ?></div>
    <?php endif; ?>

    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2 style="margin-top:0;">🌱 <?php echo __('start_germination'); ?></h2>
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo __('seed_stock'); ?></label>
                <select name="seed_inventory_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">-- Kies --</option>
                    <?php foreach($seedInventory as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['variety']); ?> (<?php echo number_format($s['stock_grams'],1); ?>g)</option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($seedInventory)): ?><small style="color:red;"><?php echo __('no_seeds'); ?></small><?php endif; ?>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo __('tray_empty'); ?></label>
                <select name="tray_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">-- Kies --</option>
                    <?php foreach($emptyTrays as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['tray_code']); ?> (<?php echo htmlspecialchars($t['rack_name']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($emptyTrays)): ?><small style="color:red;"><?php echo __('no_empty_trays'); ?></small><?php endif; ?>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php echo __('quantity_grams'); ?></label>
                <input type="number" step="0.01" name="quantity_seeds" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:12px 20px; border-radius:4px; cursor:pointer; font-size:16px;">🚀 <?php echo __('start_germination_btn'); ?></button>
        </form>
    </div>

    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2><?php echo __('tray_overview'); ?></h2>
        <p style="color:#666;"><?php echo __('tray_overview_desc'); ?></p>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(80px, 1fr)); gap:10px;">
            <?php foreach($allTrays as $t): 
                $color = '#eee';
                if($t['status']=='EMPTY') $color='#d4edda';
                elseif($t['status']=='GERMINATING') $color='#fff3cd';
                elseif($t['status']=='GROWING') $color='#cce5ff';
            ?>
            <div style="background:<?php echo $color; ?>; padding:10px; text-align:center; border-radius:4px; border:1px solid #ccc;">
                <strong><?php echo htmlspecialchars($t['tray_code']); ?></strong>
                <div style="font-size:0.7em; color:#666;"><?php echo $t['status']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="next-step-bar">
    <a href="b01_seed_inventory.php">← <?php echo __('module_b01'); ?></a>
    <a href="b03_growth_stage.php"><?php echo __('module_b03'); ?> →</a>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
