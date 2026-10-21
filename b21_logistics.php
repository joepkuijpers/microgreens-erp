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
    if ($action === 'create_run') {
        $date = trim($_POST['run_date'] ?? '');
        $driver = trim($_POST['driver'] ?? '');
        if ($date === '' || $driver === '') { $errors[] = __('b21_run_required'); }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO delivery_runs (run_date, driver, notes) VALUES (:d, :dr, :n)");
            $stmt->bindValue(':d', $date, PDO::PARAM_STR);
            $stmt->bindValue(':dr', $driver, PDO::PARAM_STR);
            $stmt->bindValue(':n', trim($_POST['notes'] ?? ''), PDO::PARAM_STR);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            auditLog($db, 'B21_RUN_CREATE', 'delivery_run', $nid, 'CREATE', 'Rit aangemaakt', null, ['date' => $date, 'driver' => $driver, 'operator' => $operator]);
            $flash = __('b21_run_created'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'add_delivery') {
        $run_id = (int)($_POST['run_id'] ?? 0);
        $sale_id = (int)($_POST['sale_id'] ?? 0);
        $dest = trim($_POST['destination'] ?? '');
        if ($run_id <= 0) { $errors[] = __('b21_run_required'); }
        if ($sale_id <= 0) { $errors[] = __('b21_sale_required'); }
        if (empty($errors)) {
            $dup = $db->prepare("SELECT COUNT(*) FROM deliveries WHERE sale_id=:s");
            $dup->bindValue(':s', $sale_id, PDO::PARAM_INT); $dup->execute();
            if ((int)$dup->fetchColumn() > 0) {
                $flash = __('b21_sale_dup'); $flashClass = 'flash-warning';
            } else {
                $stmt = $db->prepare("INSERT INTO deliveries (run_id, sale_id, destination) VALUES (:r, :s, :d)");
                $stmt->bindValue(':r', $run_id, PDO::PARAM_INT);
                $stmt->bindValue(':s', $sale_id, PDO::PARAM_INT);
                $stmt->bindValue(':d', $dest, PDO::PARAM_STR);
                $stmt->execute();
                $nid = (int)$db->lastInsertId();
                auditLog($db, 'B21_DELIVERY_ADD', 'delivery', $nid, 'CREATE', 'Levering toegevoegd', null, ['run_id' => $run_id, 'sale_id' => $sale_id, 'destination' => $dest, 'operator' => $operator]);
                $flash = __('b21_delivery_added'); $flashClass = 'flash-success';
            }
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'set_status') {
        $did = (int)($_POST['delivery_id'] ?? 0);
        $st = $_POST['delivery_status'] ?? 'PENDING';
        if (!in_array($st, ['PENDING','IN_TRANSIT','DELIVERED','FAILED'], true)) { $st = 'PENDING'; }
        $stmt = $db->prepare("UPDATE deliveries SET delivery_status=:s, delivered_at=:dt WHERE id=:id");
        $stmt->bindValue(':s', $st, PDO::PARAM_STR);
        $stmt->bindValue(':dt', $st === 'DELIVERED' ? date('Y-m-d H:i:s') : null, $st === 'DELIVERED' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $did, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B21_DELIVERY_STATUS', 'delivery', $did, 'UPDATE', 'Leverstatus: ' . $st, null, ['status' => $st, 'operator' => $operator]);
        $flash = __('b21_status_set'); $flashClass = 'flash-success';
    }
}
$reg_ms = (int)round((microtime(true) - $t0) * 1000);
$runs = $db->query("SELECT * FROM delivery_runs ORDER BY run_date DESC, id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$allDel = $db->query("SELECT d.*, s.batch_id, pb.batch_code, c.name AS customer_name FROM deliveries d LEFT JOIN sales s ON s.id=d.sale_id LEFT JOIN production_batches pb ON pb.id=s.batch_id LEFT JOIN customers c ON c.id=s.customer_id ORDER BY d.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$delByRun = [];
foreach ($allDel as $d) { $delByRun[(int)$d['run_id']][] = $d; }
$sales = $db->query("SELECT s.id, s.product, s.amount, pb.batch_code FROM sales s LEFT JOIN production_batches pb ON pb.id=s.batch_id ORDER BY s.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
function b21StatusBadge($s) {
    $map = ['PENDING' => '#fef3c7:#92400e', 'IN_TRANSIT' => '#dbeafe:#1e40af', 'DELIVERED' => '#dcfce7:#166534', 'FAILED' => '#fee2e2:#991b1b', 'PLANNED' => '#f3f4f6:#374151', 'ACTIVE' => '#dbeafe:#1e40af', 'DONE' => '#dcfce7:#166534'];
    $c = $map[$s] ?? $map['PENDING']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($s)) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#1e40af,#7c3aed);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b21_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b21_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="create_run">
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b21_run_date')); ?></label><input type="date" name="run_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b21_driver')); ?></label><input name="driver" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b21_notes')); ?></label><input name="notes" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b21_new_run')); ?></button>
    </form>
    <?php if (empty($runs)): ?>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b21_no_runs')); ?></div>
    <?php else: foreach ($runs as $r): $rid = (int)$r['id']; ?>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div><strong style="font-size:16px"><?php echo htmlspecialchars($r['run_date']); ?> · <?php echo htmlspecialchars($r['driver']); ?></strong> <?php b21StatusBadge($r['status']); ?></div>
                <span style="color:#6b7280;font-size:13px"><?php echo count($delByRun[$rid] ?? []); ?> <?php echo htmlspecialchars(__('b21_stops')); ?></span>
            </div>
            <table style="width:100%;border-collapse:collapse">
                <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px"><?php echo htmlspecialchars(__('b21_destination')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b21_customer')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b21_batch')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b21_status')); ?></th><th style="padding:8px"></th></tr></thead>
                <tbody>
                <?php foreach ($delByRun[$rid] ?? [] as $d): ?>
                    <tr style="border-top:1px solid #e5e7eb">
                        <td style="padding:8px"><?php echo htmlspecialchars($d['destination'] ?: '—'); ?></td>
                        <td style="padding:8px;color:#6b7280"><?php echo htmlspecialchars($d['customer_name'] ?? '—'); ?></td>
                        <td style="padding:8px;color:#6b7280"><?php echo htmlspecialchars($d['batch_code'] ?? '—'); ?></td>
                        <td style="padding:8px"><?php b21StatusBadge($d['delivery_status']); ?></td>
                        <td style="padding:8px;text-align:right">
                            <form method="post" style="display:inline"><input type="hidden" name="action" value="set_status"><input type="hidden" name="delivery_id" value="<?php echo (int)$d['id']; ?>"><input type="hidden" name="delivery_status" value="IN_TRANSIT"><button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:8px;padding:5px 10px;font-weight:700;cursor:pointer;margin:2px"><?php echo htmlspecialchars(__('b21_transit')); ?></button></form>
                            <form method="post" style="display:inline"><input type="hidden" name="action" value="set_status"><input type="hidden" name="delivery_id" value="<?php echo (int)$d['id']; ?>"><input type="hidden" name="delivery_status" value="DELIVERED"><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:5px 10px;font-weight:700;cursor:pointer;margin:2px"><?php echo htmlspecialchars(__('b21_delivered')); ?></button></form>
                            <form method="post" style="display:inline"><input type="hidden" name="action" value="set_status"><input type="hidden" name="delivery_id" value="<?php echo (int)$d['id']; ?>"><input type="hidden" name="delivery_status" value="FAILED"><button type="submit" style="background:#dc2626;color:#fff;border:none;border-radius:8px;padding:5px 10px;font-weight:700;cursor:pointer;margin:2px"><?php echo htmlspecialchars(__('b21_failed')); ?></button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-top:10px;border-top:1px dashed #e5e7eb;padding-top:10px">
                <input type="hidden" name="action" value="add_delivery"><input type="hidden" name="run_id" value="<?php echo $rid; ?>">
                <div style="flex:2;min-width:180px"><select name="sale_id" style="width:100%;padding:8px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b21_select_sale')); ?></option><?php foreach ($sales as $s): ?><option value="<?php echo (int)$s['id']; ?>">#<?php echo (int)$s['id']; ?> · <?php echo htmlspecialchars($s['product'] ?? ''); ?> · <?php echo htmlspecialchars($s['batch_code'] ?? '—'); ?></option><?php endforeach; ?></select></div>
                <div style="flex:2;min-width:160px"><input name="destination" placeholder="<?php echo htmlspecialchars(__('b21_destination')); ?>" style="width:100%;padding:8px;border-radius:10px;border:1px solid #d1d5db"></div>
                <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:10px;padding:8px 14px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b21_add_stop')); ?></button>
            </form>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
