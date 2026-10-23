<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/auth.php';
$pageTitle = __('b09_customers_title');

$u = auth_current_user(); $operator = $u['display_name'] ?? 'system';
$error = null; $message = null;

if (($_POST['action'] ?? '') === 'add_customer') {
    $t0 = microtime(true);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    try {
        if ($name === '') throw new Exception(__('b09_err_customer_name'));
        $db->beginTransaction();
        $ins = $db->prepare("INSERT INTO customers (name, email, phone, notes) VALUES (?, ?, ?, ?)");
        $ins->execute([$name, $email, $phone, $notes]);
        $cid = (int)$db->lastInsertId();
        auditLog($db, 'B09_CUSTOMER', 'customers', $cid, 'CREATE', 'Klant aangemaakt: ' . $name, null, ['operator' => $operator, 'name' => $name, 'reg_time' => round(microtime(true) - $t0, 3)]);
        $db->commit();
        $message = __('b09_customer_added');
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = __('error_general') . ': ' . $e->getMessage();
    }
}

if (($_POST['action'] ?? '') === 'delete_customer') {
    $cid = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    try {
        if (!$cid) throw new Exception(__('b09_err_no_customer'));
        $db->beginTransaction();
        $cnt = $db->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
        $cnt->execute([$cid]);
        if ((int)$cnt->fetchColumn() > 0) throw new Exception(__('b09_err_customer_has_sales'));
        $del = $db->prepare("DELETE FROM customers WHERE id = ?");
        $del->execute([$cid]);
        auditLog($db, 'B09_CUSTOMER', 'customers', $cid, 'DELETE', 'Klant verwijderd (geen verkoopgeschiedenis)', null, ['operator' => $operator]);
        $db->commit();
        $message = __('b09_customer_deleted');
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = __('error_general') . ': ' . $e->getMessage();
    }
}

$customers = $db->query("
    SELECT c.id, c.name, c.email, c.phone,
        (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.id) AS sales_count
    FROM customers c ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="max-width: 900px; margin: 0 auto;">
    <a href="b09_sale.php" style="display:inline-block;margin-bottom:16px;text-decoration:none;background:#e5e7eb;color:#374151;border-radius:10px;padding:8px 14px;font-weight:800">&larr; Terug naar verkoop</a>
    <?php if ($message): ?><div style="background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin-bottom:20px;">✅ <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin-bottom:20px;">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="card" style="background:#fff;padding:25px;border-radius:8px;margin-bottom:30px;">
        <h2 style="margin-top:0;color:#2c3e50;">➕ <?php echo __('b09_add_customer'); ?></h2>
        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <input type="hidden" name="action" value="add_customer">
            <div style="flex:2;min-width:200px;"><label style="font-size:0.9em;font-weight:bold;"><?php echo __('b09_customer_name'); ?></label><input type="text" name="name" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;"></div>
            <div style="flex:1;min-width:150px;"><label style="font-size:0.9em;font-weight:bold;"><?php echo __('b09_email'); ?></label><input type="email" name="email" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;"></div>
            <div style="flex:1;min-width:120px;"><label style="font-size:0.9em;font-weight:bold;"><?php echo __('b09_phone'); ?></label><input type="text" name="phone" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;"></div>
            <button type="submit" style="background:#27ae60;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;">➕ <?php echo __('b09_add_customer'); ?></button>
        </form>
    </div>

    <div class="card" style="background:#fff;padding:25px;border-radius:8px;">
        <h2 style="margin-top:0;color:#2c3e50;">👥 <?php echo __('b09_customers_title'); ?></h2>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr style="background:#f4f4f9;text-align:left;">
                <th style="padding:10px;border-bottom:2px solid #ddd;"><?php echo __('b09_customer_name'); ?></th>
                <th style="padding:10px;border-bottom:2px solid #ddd;"><?php echo __('b09_email'); ?></th>
                <th style="padding:10px;border-bottom:2px solid #ddd;"><?php echo __('b09_phone'); ?></th>
                <th style="padding:10px;border-bottom:2px solid #ddd;"><?php echo __('b09_sales_count'); ?></th>
                <th style="padding:10px;border-bottom:2px solid #ddd;"></th>
            </tr></thead>
            <tbody>
            <?php if (empty($customers)): ?><tr><td colspan="5" style="padding:15px;text-align:center;color:#999;"><?php echo __('b09_no_customers'); ?></td></tr><?php endif; ?>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($c['name']); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo (int)$c['sales_count']; ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee;text-align:right;">
                    <?php if ((int)$c['sales_count'] === 0): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo addslashes(__('b09_confirm_delete')); ?>');">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" value="<?php echo (int)$c['id']; ?>">
                        <button type="submit" style="background:#dc3545;color:#fff;border:none;padding:5px 12px;border-radius:3px;cursor:pointer;font-size:12px;">🗑 <?php echo __('delete'); ?></button>
                    </form>
                    <?php else: ?><span style="color:#999;font-size:0.85em;">🔒 <?php echo __('b09_locked_history'); ?></span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>