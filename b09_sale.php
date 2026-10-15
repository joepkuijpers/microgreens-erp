<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
$pageTitle = __('module_b09');

$u = auth_current_user(); $operator = $u['display_name'] ?? 'system';
$error = null; $message = null;

if (($_POST['action'] ?? '') === 'register_sale') {
    $t0 = microtime(true);
    $activeInteraction = isset($_POST['active_interaction_ms']) ? intval($_POST['active_interaction_ms']) : null;
    $screenAvailability = isset($_POST['screen_availability_ms']) ? intval($_POST['screen_availability_ms']) : null;
    $customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    $unit_id = filter_input(INPUT_POST, 'packaging_unit_id', FILTER_VALIDATE_INT);
    $sale_date = trim($_POST['sale_date'] ?? '') ?: date('Y-m-d');
    $confirmedConventional = !empty($_POST['confirmed_conventional']);

    try {
        if (!$customer_id) throw new Exception(__('b09_err_no_customer'));
        if (!$unit_id) throw new Exception(__('b09_err_no_unit'));

        $db->beginTransaction();

        $cs = $db->prepare("SELECT id, name FROM customers WHERE id = ?");
        $cs->execute([$customer_id]);
        $customer = $cs->fetch(PDO::FETCH_ASSOC);
        if (!$customer) throw new Exception(__('b09_err_no_customer'));

        $us = $db->prepare("SELECT id, finished_inventory_id, package_code, total_weight_g, sale_price_eur, status FROM packaging_units WHERE id = ?");
        $us->execute([$unit_id]);
        $unit = $us->fetch(PDO::FETCH_ASSOC);
        if (!$unit) throw new Exception(__('b09_err_no_unit'));
        if ($unit['status'] !== 'AVAILABLE') throw new Exception(__('b09_err_unit_sold'));

        $fs = $db->prepare("SELECT id, harvest_id, product_name FROM finished_inventory WHERE id = ?");
        $fs->execute([$unit['finished_inventory_id']]);
        $finv = $fs->fetch(PDO::FETCH_ASSOC);
        if (!$finv) throw new Exception(__('b09_err_no_trace'));

        $hs = $db->prepare("SELECT id, germination_record_id FROM harvests WHERE id = ?");
        $hs->execute([$finv['harvest_id']]);
        $harvest = $hs->fetch(PDO::FETCH_ASSOC);
        if (!$harvest) throw new Exception(__('b09_err_no_trace'));

        $gs = $db->prepare("SELECT production_batch_id FROM germination_records WHERE id = ?");
        $gs->execute([$harvest['germination_record_id']]);
        $germ = $gs->fetch(PDO::FETCH_ASSOC);
        if (!$germ || !$germ['production_batch_id']) throw new Exception(__('b09_err_no_trace'));
        $batch_id = (int)$germ['production_batch_id'];

        $seedStmt = $db->prepare("
            SELECT sl.organic_status, sl.certificate_valid_until
            FROM batch_materials bm
            JOIN seed_inventory si ON si.id = bm.inventory_id
            JOIN seed_lots sl ON sl.id = si.seed_lot_id
            WHERE bm.batch_id = ? LIMIT 1
        ");
        $seedStmt->execute([$batch_id]);
        $seed = $seedStmt->fetch(PDO::FETCH_ASSOC);
        if (!$seed) throw new Exception(__('b07_err_no_input'));
        $gate = organicGateEvaluate($seed, $confirmedConventional);
        if (empty($gate['allowed'])) throw new Exception($gate['reason']);
        $organicSnapshot = $gate['conventional'] ? 'conventional' : 'organic';

        $amount = (float)$unit['sale_price_eur'];
        $qty = (float)$unit['total_weight_g'];

        $ins = $db->prepare("INSERT INTO sales (customer_name, sale_date, product, quantity, amount, status, customer_id, product_id, batch_id, harvest_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)");
        $ins->execute([$customer['name'], $sale_date, $finv['product_name'], $qty, $amount, 'VERKOCHT', $customer_id, $batch_id, $finv['harvest_id']]);
        $saleId = (int)$db->lastInsertId();

        $upd = $db->prepare("UPDATE packaging_units SET status = 'SOLD' WHERE id = ? AND status = 'AVAILABLE'");
        $upd->execute([$unit_id]);

        $regTime = round(microtime(true) - $t0, 3);
        auditLog($db, 'B09_SALE', 'sales', $saleId, 'REGISTER_SALE', 'Verkoop geregistreerd: ' . $finv['product_name'], null, ['operator' => $operator, 'package_code' => $unit['package_code'], 'amount_eur' => $amount, 'organic_status_snapshot' => $organicSnapshot, 'reg_time' => $regTime, 'active_interaction_ms' => $activeInteraction, 'screen_availability_ms' => $screenAvailability]);

        $db->commit();
        $message = __('b09_sale_success');
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = __('error_general') . ': ' . $e->getMessage();
    }
}

$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$units = $db->query("
    SELECT pu.id, pu.package_code, pu.total_weight_g, pu.sale_price_eur, fi.product_name, pb.batch_code
    FROM packaging_units pu
    JOIN finished_inventory fi ON fi.id = pu.finished_inventory_id
    JOIN harvests h ON h.id = fi.harvest_id
    JOIN germination_records gr ON gr.id = h.germination_record_id
    JOIN production_batches pb ON pb.id = gr.production_batch_id
    WHERE pu.status = 'AVAILABLE'
    ORDER BY pu.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="max-width: 900px; margin: 0 auto;">
    <?php if ($message): ?><div style="background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin-bottom:20px;">✅ <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin-bottom:20px;">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div style="background:#e8f4fd;padding:12px 15px;border-radius:5px;margin-bottom:20px;border-left:4px solid #3498db;">
        💡 <?php echo __('b09_quick_add'); ?>
        <a href="b09_customers.php" style="margin-left:10px;color:#2980b9;font-weight:bold;">➕ <?php echo __('b09_link_customer'); ?></a>
        <a href="b09_packaging.php" style="margin-left:10px;color:#2980b9;font-weight:bold;">➕ <?php echo __('b09_link_package'); ?></a>
    </div>

    <div class="card" style="background:#fff;padding:25px;border-radius:8px;margin-bottom:30px;">
        <h2 style="margin-top:0;color:#2c3e50;">🧾 <?php echo __('b09_register_sale'); ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="register_sale">
            <div style="margin-bottom:15px;">
                <label style="display:block;font-weight:bold;margin-bottom:5px;">1. <?php echo __('b09_customer'); ?></label>
                <select name="customer_id" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:4px;">
                    <option value="">-- <?php echo __('b09_choose_customer'); ?> --</option>
                    <?php foreach ($customers as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;font-weight:bold;margin-bottom:5px;">2. <?php echo __('b09_package'); ?></label>
                <select name="packaging_unit_id" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:4px;">
                    <option value="">-- <?php echo __('b09_choose_unit'); ?> --</option>
                    <?php foreach ($units as $un): ?><option value="<?php echo (int)$un['id']; ?>"><?php echo htmlspecialchars($un['package_code']); ?> — <?php echo htmlspecialchars($un['product_name']); ?> (<?php echo htmlspecialchars($un['batch_code']); ?>) · <?php echo number_format((float)$un['total_weight_g'],0,',','.'); ?> g · €<?php echo number_format((float)$un['sale_price_eur'],2,',','.'); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;font-weight:bold;margin-bottom:5px;">3. <?php echo __('b09_sale_date'); ?></label>
                <input type="date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" style="padding:10px;border:1px solid #ddd;border-radius:4px;">
            </div>
            <label style="display:block;margin-bottom:15px;padding:10px;background:#fff8e1;border-radius:5px;border:1px solid #ffe082;">
                <input type="checkbox" name="confirmed_conventional" value="1"> <?php echo __('b01_confirm_conventional'); ?>
            </label>
            <button type="submit" style="background:#27ae60;color:#fff;border:none;padding:15px 30px;font-size:18px;border-radius:5px;cursor:pointer;width:100%;font-weight:bold;">✅ <?php echo __('b09_register_sale'); ?></button>
        </form>
    </div>
</div>