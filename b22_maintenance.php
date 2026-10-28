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
    if ($action === 'create_item') {
        $name = trim($_POST['name'] ?? '');
        $cat = $_POST['category'] ?? 'OTHER';
        $crit = isset($_POST['is_critical']) ? 1 : 0;
        if ($name === '') { $errors[] = __('b22_name_required'); }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO equipment_items (name, category, is_critical) VALUES (:n, :c, :i)");
            $stmt->bindValue(':n', $name, PDO::PARAM_STR);
            $stmt->bindValue(':c', $cat, PDO::PARAM_STR);
            $stmt->bindValue(':i', $crit, PDO::PARAM_INT);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            auditLog($db, 'B22_ITEM_CREATE', 'equipment_item', $nid, 'CREATE', 'Apparaat aangemaakt', null, ['name' => $name, 'category' => $cat, 'critical' => $crit, 'operator' => $operator]);
            $flash = __('b22_item_created'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'add_maintenance') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $mdate = trim($_POST['maintenance_date'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $next = trim($_POST['next_due_date'] ?? '');
        if ($item_id <= 0) { $errors[] = __('b22_item_required'); }
        if ($mdate === '') { $errors[] = __('b22_date_required'); }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO maintenance_logs (item_id, maintenance_date, type, description, performed_by, next_due_date, notes) VALUES (:i, :d, :t, :de, :pb, :nd, :n)");
            $stmt->bindValue(':i', $item_id, PDO::PARAM_INT);
            $stmt->bindValue(':d', $mdate, PDO::PARAM_STR);
            $stmt->bindValue(':t', $type, PDO::PARAM_STR);
            $stmt->bindValue(':de', trim($_POST['description'] ?? ''), PDO::PARAM_STR);
            $stmt->bindValue(':pb', $operator, PDO::PARAM_STR);
            $stmt->bindValue(':nd', ($next !== '') ? $next : null, $next !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':n', trim($_POST['notes'] ?? ''), PDO::PARAM_STR);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B22_MAINT_ADD', 'maintenance_log', $nid, 'CREATE', 'Onderhoud geregistreerd', null, ['item_id' => $item_id, 'date' => $mdate, 'next_due' => $next, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b22_maint_added'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    }
}
$items = $db->query("SELECT * FROM equipment_items ORDER BY is_critical DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$logs = $db->query("SELECT m.*, ei.name AS item_name, ei.is_critical FROM maintenance_logs m JOIN equipment_items ei ON ei.id=m.item_id ORDER BY m.maintenance_date DESC, m.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$today = strtotime(date('Y-m-d'));
$overdueCritical = 0;
foreach ($logs as $l) {
    if ($l['is_critical'] && !empty($l['next_due_date']) && strtotime($l['next_due_date']) < $today) { $overdueCritical++; }
}
if ($overdueCritical > 0) {
    $aex = $db->query("SELECT COUNT(*) FROM system_alerts WHERE alert_type='MAINTENANCE_OVERDUE' AND resolved=0")->fetchColumn();
    if ((int)$aex == 0) {
        $db->prepare("INSERT INTO system_alerts (alert_type, severity, message) VALUES ('MAINTENANCE_OVERDUE', 'WARNING', :m)")->execute([':m' => $overdueCritical . ' kritiek apparaat heeft verlopen onderhoud']);
    }
}
function b22DueBadge($next) {
    if ($next === null || $next === '') { return '<span style="color:#6b7280">—</span>'; }
    $today = strtotime(date('Y-m-d')); $d = strtotime($next);
    if ($d < $today) { return '<span style="background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b22_overdue')) . ' · ' . htmlspecialchars($next) . '</span>'; }
    if ($d < strtotime('+14 days')) { return '<span style="background:#ffedd5;color:#9a3412;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b22_soon')) . ' · ' . htmlspecialchars($next) . '</span>'; }
    return '<span style="background:#dcfce7;color:#166534;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b22_ok')) . ' · ' . htmlspecialchars($next) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#374151,#7c3aed);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b22_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b22_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <?php if ($overdueCritical > 0): ?><div class="flash-warning"><?php echo htmlspecialchars(__('b22_overdue_alert')); ?>: <?php echo $overdueCritical; ?></div><?php endif; ?>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="create_item">
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b22_item_name')); ?></label><input name="name" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b22_category')); ?></label><select name="category" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="FREEZE_DRY">FREEZE_DRY</option><option value="CLIMATE">CLIMATE</option><option value="IRRIGATION">IRRIGATION</option><option value="OTHER">OTHER</option></select></div>
        <label style="display:flex;align-items:center;gap:6px;padding-bottom:8px"><input type="checkbox" name="is_critical" style="transform:scale(1.25)"><?php echo htmlspecialchars(__('b22_critical')); ?></label>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b22_add_item')); ?></button>
    </form>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="add_maintenance">
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b22_item')); ?></label><select name="item_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b22_select_item')); ?></option><?php foreach ($items as $it): ?><option value="<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['name'] . ($it['is_critical'] ? ' ★' : '')); ?></option><?php endforeach; ?></select></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b22_maint_date')); ?></label><input type="date" name="maintenance_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b22_type')); ?></label><input name="type" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b22_next_due')); ?></label><input type="date" name="next_due_date" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b22_desc')); ?></label><input name="description" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b22_save')); ?></button>
    </form>
    <h3 style="color:#374151"><?php echo htmlspecialchars(__('b22_overview')); ?></h3>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b22_item')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b22_maint_date')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b22_type')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b22_next_due')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b22_by')); ?></th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b22_no_rows')); ?></td></tr>
            <?php else: foreach ($logs as $l): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($l['item_name']); ?></strong><?php if ($l['is_critical']): ?> <span title="kritiek">★</span><?php endif; ?><?php if (!empty($l['description'])): ?><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($l['description']); ?></div><?php endif; ?></td>
                    <td style="padding:10px"><?php echo htmlspecialchars($l['maintenance_date']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($l['type'] ?: '—'); ?></td>
                    <td style="padding:10px"><?php echo b22DueBadge($l['next_due_date']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($l['performed_by'] ?? '—'); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
