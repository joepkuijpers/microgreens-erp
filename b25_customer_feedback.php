<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
$t0 = microtime(true);
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$flash = '';
$flashClass = 'flash-info';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $cust = (int)($_POST['customer_id'] ?? 0);
        $type = $_POST['type'] ?? 'COMPLAINT';
        $sev = $_POST['severity'] ?? 'MEDIUM';
        $msg = trim($_POST['message'] ?? '');
        $sale = (int)($_POST['sale_id'] ?? 0);
        if ($cust <= 0) { $errors[] = __('b25_cust_required'); }
        if ($msg === '') { $errors[] = __('b25_msg_required'); }
        if (!in_array($type, ['COMPLAINT','PRAISE','QUESTION','SUGGESTION'], true)) { $type = 'COMPLAINT'; }
        if (!in_array($sev, ['LOW','MEDIUM','HIGH'], true)) { $sev = 'MEDIUM'; }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO customer_feedback (customer_id, type, severity, message, sale_id) VALUES (:c, :t, :s, :m, :sa)");
            $stmt->bindValue(':c', $cust, PDO::PARAM_INT);
            $stmt->bindValue(':t', $type, PDO::PARAM_STR);
            $stmt->bindValue(':s', $sev, PDO::PARAM_STR);
            $stmt->bindValue(':m', $msg, PDO::PARAM_STR);
            $stmt->bindValue(':sa', $sale > 0 ? $sale : null, $sale > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B25_FEEDBACK_CREATE', 'customer_feedback', $nid, 'CREATE', 'Feedback geregistreerd', null, ['customer_id' => $cust, 'type' => $type, 'severity' => $sev, 'sale_id' => $sale, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b25_saved'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'resolve') {
        $id = (int)($_POST['id'] ?? 0);
        $res = trim($_POST['resolution'] ?? '');
        $stmt = $db->prepare("UPDATE customer_feedback SET status='RESOLVED', resolution=:r, resolved_at=datetime('now'), resolved_by=:op WHERE id=:id AND status != 'RESOLVED'");
        $stmt->bindValue(':r', $res, PDO::PARAM_STR);
        $stmt->bindValue(':op', $operator, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B25_FEEDBACK_RESOLVE', 'customer_feedback', $id, 'UPDATE', 'Feedback afgehandeld', null, ['resolution' => $res, 'operator' => $operator]);
        $flash = __('b25_resolved'); $flashClass = 'flash-success';
    }
}
$openCount = (int)$db->query("SELECT COUNT(*) FROM customer_feedback WHERE status='OPEN'")->fetchColumn();
$fb = $db->query("SELECT f.*, c.name AS customer_name, pb.batch_code FROM customer_feedback f LEFT JOIN customers c ON c.id=f.customer_id LEFT JOIN sales s ON s.id=f.sale_id LEFT JOIN production_batches pb ON pb.id=s.batch_id ORDER BY CASE f.status WHEN 'OPEN' THEN 0 ELSE 1 END, CASE f.severity WHEN 'HIGH' THEN 0 WHEN 'MEDIUM' THEN 1 ELSE 2 END, f.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$customers = $db->query("SELECT id, name FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sales = $db->query("SELECT s.id, s.product, pb.batch_code FROM sales s LEFT JOIN production_batches pb ON pb.id=s.batch_id ORDER BY s.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
function b25SevBadge($s) {
    $map = ['HIGH' => '#fee2e2:#991b1b', 'MEDIUM' => '#ffedd5:#9a3412', 'LOW' => '#f3f4f6:#374151'];
    $c = $map[$s] ?? $map['MEDIUM']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($s)) . '</span>';
}
function b25StatusBadge($s) {
    $map = ['OPEN' => '#fef3c7:#92400e', 'IN_PROGRESS' => '#dbeafe:#1e40af', 'RESOLVED' => '#dcfce7:#166534'];
    $c = $map[$s] ?? $map['OPEN']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($s)) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#7c3aed,#0f766e);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b25_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b25_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <div style="display:flex;gap:12px;margin-bottom:20px">
        <div style="flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800;color:#92400e"><?php echo $openCount; ?></div><div><?php echo htmlspecialchars(__('b25_open')); ?></div></div>
    </div>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="save">
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b25_customer')); ?></label><select name="customer_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b25_select_cust')); ?></option><?php foreach ($customers as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select></div>
        <div style="flex:1;min-width:130px"><label><?php echo htmlspecialchars(__('b25_type')); ?></label><select name="type" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="COMPLAINT"><?php echo htmlspecialchars(__('COMPLAINT')); ?></option><option value="PRAISE"><?php echo htmlspecialchars(__('PRAISE')); ?></option><option value="QUESTION"><?php echo htmlspecialchars(__('QUESTION')); ?></option><option value="SUGGESTION"><?php echo htmlspecialchars(__('SUGGESTION')); ?></option></select></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b25_severity')); ?></label><select name="severity" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="LOW"><?php echo htmlspecialchars(__('LOW')); ?></option><option value="MEDIUM"><?php echo htmlspecialchars(__('MEDIUM')); ?></option><option value="HIGH"><?php echo htmlspecialchars(__('HIGH')); ?></option></select></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b25_sale')); ?></label><select name="sale_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b25_no_sale')); ?></option><?php foreach ($sales as $s): ?><option value="<?php echo (int)$s['id']; ?>">#<?php echo (int)$s['id']; ?> · <?php echo htmlspecialchars($s['product'] ?? ''); ?> · <?php echo htmlspecialchars($s['batch_code'] ?? '—'); ?></option><?php endforeach; ?></select></div>
        <div style="flex:3;min-width:200px"><label><?php echo htmlspecialchars(__('b25_message')); ?></label><input name="message" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b25_save')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b25_customer')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b25_type')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b25_severity')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b25_batch')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b25_status')); ?></th><th style="padding:10px"></th></tr></thead>
            <tbody>
            <?php if (empty($fb)): ?>
                <tr><td colspan="6" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b25_no_rows')); ?></td></tr>
            <?php else: foreach ($fb as $r): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($r['customer_name'] ?? '—'); ?></strong><div style="color:#374151;font-size:13px"><?php echo htmlspecialchars($r['message']); ?></div><?php if (!empty($r['resolution'])): ?><div style="color:#16a34a;font-size:13px">✓ <?php echo htmlspecialchars($r['resolution']); ?></div><?php endif; ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars(__($r['type'])); ?></td>
                    <td style="padding:10px"><?php b25SevBadge($r['severity']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($r['batch_code'] ?? '—'); ?></td>
                    <td style="padding:10px"><?php b25StatusBadge($r['status']); ?></td>
                    <td style="padding:10px;text-align:right">
                        <?php if ($r['status'] !== 'RESOLVED'): ?>
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="resolve"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><input type="text" name="resolution" placeholder="<?php echo htmlspecialchars(__('b25_res_ph')); ?>" style="width:140px;padding:6px;border-radius:8px;border:1px solid #d1d5db"><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b25_resolve')); ?></button></form>
                        <?php else: ?><span style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($r['resolved_by'] ?? ''); ?></span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
