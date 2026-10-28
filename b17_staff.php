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
    if ($action === 'create') {
        $date = trim($_POST['work_date'] ?? '');
        $activity = trim($_POST['activity'] ?? '');
        $hours = (float)($_POST['hours'] ?? 0);
        $rate = (float)($_POST['rate'] ?? 0);
        $bid = (int)($_POST['production_batch_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($date === '') { $errors[] = __('b17_date_required'); }
        if ($activity === '') { $errors[] = __('b17_activity_required'); }
        if ($hours <= 0) { $errors[] = __('b17_hours_invalid'); }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO labor_entries (work_date, hours_worked, activity, batch_id, gross_hourly_rate, notes, operator_name, production_batch_id) VALUES (:d, :h, :a, NULL, :r, :n, :op, :pb)");
            $stmt->bindValue(':d', $date, PDO::PARAM_STR);
            $stmt->bindValue(':h', $hours, PDO::PARAM_STR);
            $stmt->bindValue(':a', $activity, PDO::PARAM_STR);
            $stmt->bindValue(':r', $rate, PDO::PARAM_STR);
            $stmt->bindValue(':n', $notes, PDO::PARAM_STR);
            $stmt->bindValue(':op', $operator, PDO::PARAM_STR);
            $stmt->bindValue(':pb', $bid > 0 ? $bid : null, $bid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B17_LABOR_CREATE', 'labor_entry', $nid, 'CREATE', 'Uren geregistreerd', null, ['date' => $date, 'hours' => $hours, 'activity' => $activity, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b17_created');
            $flashClass = 'flash-success';
        } else {
            $flash = implode(' · ', $errors);
            $flashClass = 'flash-error';
        }
    }
}
$batches = $db->query("SELECT id, batch_code, crop_type FROM production_batches ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$rows = $db->query("SELECT le.*, pb.batch_code FROM labor_entries le LEFT JOIN production_batches pb ON pb.id = le.production_batch_id ORDER BY le.work_date DESC, le.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$totHours = 0.0;
foreach ($rows as $r) { $totHours += (float)$r['hours_worked']; }
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#0f766e,#7c3aed);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b17_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b17_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:12px;margin-bottom:20px">
        <div style="flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800"><?php echo number_format($totHours, 1); ?></div><div><?php echo htmlspecialchars(__('b17_total_hours')); ?></div></div>
        <div style="flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800;color:#0f766e"><?php echo htmlspecialchars($operator); ?></div><div><?php echo htmlspecialchars(__('b17_current_user')); ?></div></div>
    </div>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="create">
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b17_date')); ?></label><input type="date" name="work_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b17_activity')); ?></label><input name="activity" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:90px"><label><?php echo htmlspecialchars(__('b17_hours')); ?></label><input type="number" step="0.1" min="0" name="hours" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:110px"><label><?php echo htmlspecialchars(__('b17_rate')); ?></label><input type="number" step="0.01" min="0" name="rate" value="0" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b17_batch')); ?></label><select name="production_batch_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b17_no_batch')); ?></option><?php foreach ($batches as $b): ?><option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['batch_code'] . ' · ' . $b['crop_type']); ?></option><?php endforeach; ?></select></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b17_save')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b17_date')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b17_activity')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b17_hours')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b17_batch')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b17_person')); ?></th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b17_no_rows')); ?></td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><?php echo htmlspecialchars($r['work_date']); ?></td>
                    <td style="padding:10px"><?php echo htmlspecialchars($r['activity']); ?></td>
                    <td style="padding:10px;font-weight:700"><?php echo htmlspecialchars($r['hours_worked']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($r['batch_code'] ?? '—'); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($r['operator_name'] ?? '—'); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
