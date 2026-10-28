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
$showResolved = isset($_GET['resolved']) && $_GET['resolved'] === '1';
$target = $showResolved ? 1 : 0;
$moduleMap = [
    'EXPIRING_CERT' => ['b18_suppliers.php', 'b28_goto_b18'],
    'SENSOR_FAULT' => ['b10_sensor_sync.php', 'b28_goto_b10'],
    'LIGHT_FAULT' => ['b04_lighting.php', 'b28_goto_b04'],
    'MAINTENANCE_OVERDUE' => ['b22_maintenance.php', 'b28_goto_b22'],
    'LOW_STOCK' => ['b01_batch.php', 'b28_goto_b01'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'resolve') {
        $id = (int)($_POST['id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        $stmt = $db->prepare("UPDATE system_alerts SET resolved=1 WHERE id=:id AND resolved=0");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B28_ALERT_RESOLVE', 'system_alerts', $id, 'UPDATE', 'Alert verklaard', null, ['note' => $note, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b28_resolved_msg'); $flashClass = 'flash-success';
        } else { $flash = __('b28_already_resolved'); $flashClass = 'flash-warning'; }
    } elseif ($action === 'reopen') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE system_alerts SET resolved=0 WHERE id=:id AND resolved=1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            auditLog($db, 'B28_ALERT_REOPEN', 'system_alerts', $id, 'UPDATE', 'Alert heropend', null, ['operator' => $operator]);
            $flash = __('b28_reopened_msg'); $flashClass = 'flash-success';
        } else { $flash = __('b28_no_change'); $flashClass = 'flash-warning'; }
    }
}
$alerts = $db->query("SELECT id, alert_type, severity, message, related_id, created_at FROM system_alerts WHERE resolved=" . $target . " ORDER BY CASE severity WHEN 'CRITICAL' THEN 0 ELSE 1 END, id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
$openCount = (int)$db->query("SELECT COUNT(*) FROM system_alerts WHERE resolved=0")->fetchColumn();
$critCount = (int)$db->query("SELECT COUNT(*) FROM system_alerts WHERE resolved=0 AND severity='CRITICAL'")->fetchColumn();
$sysOk = false; $sysMsg = __('b28_no_live'); $sysLast = '—';
if (is_file('/var/www/html/microgreens/app/state/watchdog_state.json')) {
    $sysOk = true; $sysMsg = __('b28_sys_ok');
    $sysLast = date('Y-m-d H:i', (int)@filemtime('/var/www/html/microgreens/app/state/watchdog_state.json'));
}
$card = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:20px';
function b28SevBadge($s) {
    $map = ['CRITICAL' => '#fee2e2:#991b1b', 'WARNING' => '#ffedd5:#9a3412', 'INFO' => '#dbeafe:#1e40af'];
    $c = $map[$s] ?? $map['WARNING']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($s)) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#991b1b,#0f766e);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b28_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b28_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800;color:#92400e"><?php echo $openCount; ?></div><div><?php echo htmlspecialchars(__('b28_open')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800;color:#991b1b"><?php echo $critCount; ?></div><div><?php echo htmlspecialchars(__('b28_critical')); ?></div></div>
        <div style="flex:2;min-width:200px;background:<?php echo $sysOk ? '#f0fdf4' : '#f8fafc'; ?>;border:1px solid <?php echo $sysOk ? '#bbf7d0' : '#e5e7eb'; ?>;border-radius:14px;padding:16px"><div style="font-weight:800;color:<?php echo $sysOk ? '#166534' : '#6b7280'; ?>"><?php echo htmlspecialchars(__('b28_system')); ?>: <?php echo htmlspecialchars($sysMsg); ?></div><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars(__('b28_last_seen')); ?> <?php echo htmlspecialchars($sysLast); ?></div></div>
    </div>

    <div style="margin-bottom:14px">
        <a href="?resolved=0" style="text-decoration:none;font-weight:800;color:<?php echo !$showResolved ? '#0f766e' : '#6b7280'; ?>;margin-right:16px"><?php echo htmlspecialchars(__('b28_tab_open')); ?></a>
        <a href="?resolved=1" style="text-decoration:none;font-weight:800;color:<?php echo $showResolved ? '#0f766e' : '#6b7280'; ?>"><?php echo htmlspecialchars(__('b28_tab_resolved')); ?></a>
    </div>

    <div style="<?php echo $card; ?>">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px"><?php echo htmlspecialchars(__('b28_severity')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b28_type')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b28_message')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b28_ref')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b28_time')); ?></th><th style="padding:8px"></th></tr></thead>
            <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="6" style="padding:16px;color:#6b7280"><?php echo $showResolved ? htmlspecialchars(__('b28_none_resolved')) : htmlspecialchars(__('b28_none_open')); ?></td></tr>
            <?php else: foreach ($alerts as $a): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:8px"><?php b28SevBadge($a['severity']); ?></td>
                    <td style="padding:8px;color:#6b7280"><code><?php echo htmlspecialchars($a['alert_type']); ?></code></td>
                    <td style="padding:8px"><?php echo htmlspecialchars($a['message']); ?></td>
                    <td style="padding:8px;color:#6b7280"><?php echo $a['related_id'] ? '#' . (int)$a['related_id'] : '—'; ?></td>
                    <td style="padding:8px;color:#6b7280;font-size:13px"><?php echo htmlspecialchars($a['created_at']); ?></td>
                    <td style="padding:8px;text-align:right;white-space:nowrap">
                        <?php if (!$showResolved): ?>
                            <?php if (isset($moduleMap[$a['alert_type']])): $m = $moduleMap[$a['alert_type']]; ?><a href="<?php echo $m[0]; ?>" style="text-decoration:none;background:#0f766e;color:#fff;border-radius:8px;padding:6px 10px;font-weight:700;font-size:13px;margin-right:6px"><?php echo htmlspecialchars(__($m[1])); ?></a><?php endif; ?>
                            <form method="post" style="display:inline"><input type="hidden" name="action" value="resolve"><input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>"><input type="text" name="note" placeholder="<?php echo htmlspecialchars(__('b28_note_ph')); ?>" style="width:120px;padding:6px;border-radius:8px;border:1px solid #d1d5db"><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer;margin-left:4px"><?php echo htmlspecialchars(__('b28_resolve')); ?></button></form>
                        <?php else: ?>
                            <form method="post" style="display:inline"><input type="hidden" name="action" value="reopen"><input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>"><button type="submit" style="background:#6b7280;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b28_reopen')); ?></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
