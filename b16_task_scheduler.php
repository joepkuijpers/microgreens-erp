<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
require_once __DIR__ . '/app/includes/task_engine.php';
$t0 = microtime(true);
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$flash = '';
$flashClass = 'flash-info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'generate') {
        $r = taskGenerateAll($db);
        $flash = __('b16_generated') . ' ' . $r['total'];
        $flashClass = 'flash-success';
        if ($r['total'] > 0) {
            $anchor = (int)$db->query('SELECT MAX(id) FROM tasks')->fetchColumn();
            if ($anchor < 1) { $anchor = 1; }
            auditLog($db, 'B16_TASK_GENERATE', 'task', $anchor, 'GENERATE', 'Taakgeneratie', null, ['certificates' => $r['certificates'], 'cleaning' => $r['cleaning'], 'operator' => $operator]);
        }
    } elseif ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $flash = __('b16_title_required');
            $flashClass = 'flash-error';
        } else {
            $prio = $_POST['priority'] ?? 'NORMAL';
            $due = $_POST['due_date'] ?? null;
            $stmt = $db->prepare("INSERT INTO tasks (title, description, category, priority, status, due_date, source_type) VALUES (:t, :d, 'GENERAL', :p, 'OPEN', :due, 'MANUAL')");
            $stmt->bindValue(':t', $title, PDO::PARAM_STR);
            $stmt->bindValue(':d', trim($_POST['description'] ?? ''), PDO::PARAM_STR);
            $stmt->bindValue(':p', $prio, PDO::PARAM_STR);
            $stmt->bindValue(':due', ($due && $due !== '') ? $due : null, PDO::PARAM_STR);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            auditLog($db, 'B16_TASK_CREATE', 'task', $nid, 'CREATE', 'Taak aangemaakt', null, ['title' => $title, 'operator' => $operator]);
            $flash = __('b16_created');
            $flashClass = 'flash-success';
        }
    } elseif ($action === 'complete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE tasks SET status='DONE', completed_at=datetime('now'), completed_by=:op WHERE id=:id AND status != 'DONE'");
        $stmt->bindValue(':op', $operator, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B16_TASK_COMPLETE', 'task', $id, 'COMPLETE', 'Taak voltooid', null, ['operator' => $operator]);
        $flash = __('b16_completed');
        $flashClass = 'flash-success';
    } elseif ($action === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? __('b16_no_reason'));
        $stmt = $db->prepare("UPDATE tasks SET status='CANCELLED', cancelled_reason=:r WHERE id=:id AND status IN ('OPEN','IN_PROGRESS')");
        $stmt->bindValue(':r', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B16_TASK_CANCEL', 'task', $id, 'CANCEL', 'Taak geannuleerd', null, ['reason' => $reason, 'operator' => $operator]);
        $flash = __('b16_cancelled');
        $flashClass = 'flash-warning';
    }
}
$openCount = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='OPEN'")->fetchColumn();
$doneCount = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='DONE'")->fetchColumn();
$tasks = $db->query("SELECT * FROM tasks WHERE status IN ('OPEN','IN_PROGRESS') ORDER BY CASE priority WHEN 'CRITICAL' THEN 0 WHEN 'HIGH' THEN 1 WHEN 'NORMAL' THEN 2 ELSE 3 END, due_date IS NULL, due_date, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$doneTasks = $db->query("SELECT * FROM tasks WHERE status='DONE' ORDER BY completed_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
function b16PrioBadge($p) {
    $map = ['CRITICAL' => '#fee2e2:#991b1b', 'HIGH' => '#ffedd5:#9a3412', 'NORMAL' => '#dbeafe:#1e40af', 'LOW' => '#f3f4f6:#374151'];
    $c = $map[$p] ?? $map['NORMAL'];
    list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($p)) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#7c3aed,#0f766e);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b16_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b16_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:12px;margin-bottom:20px">
        <div style="flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800"><?php echo $openCount; ?></div><div><?php echo htmlspecialchars(__('b16_open')); ?></div></div>
        <div style="flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:28px;font-weight:800;color:#16a34a"><?php echo $doneCount; ?></div><div><?php echo htmlspecialchars(__('b16_done')); ?></div></div>
    </div>
    <form method="post" style="display:inline;margin-right:8px">
        <input type="hidden" name="action" value="generate">
        <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:12px;padding:12px 18px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b16_generate')); ?></button>
    </form>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin:16px 0;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="create">
        <div style="flex:2;min-width:200px"><label><?php echo htmlspecialchars(__('b16_title_field')); ?></label><input name="title" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b16_priority')); ?></label><select name="priority" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="NORMAL"><?php echo htmlspecialchars(__('NORMAL')); ?></option><option value="HIGH"><?php echo htmlspecialchars(__('HIGH')); ?></option><option value="CRITICAL"><?php echo htmlspecialchars(__('CRITICAL')); ?></option><option value="LOW"><?php echo htmlspecialchars(__('LOW')); ?></option></select></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b16_due')); ?></label><input type="date" name="due_date" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b16_add')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px;margin-bottom:20px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b16_task')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b16_priority')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b16_due')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b16_source')); ?></th><th style="padding:10px"></th></tr></thead>
            <tbody>
            <?php if (empty($tasks)): ?>
                <tr><td colspan="5" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b16_no_tasks')); ?></td></tr>
            <?php else: foreach ($tasks as $t): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($t['title']); ?></strong><?php if (!empty($t['description'])): ?><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($t['description']); ?></div><?php endif; ?></td>
                    <td style="padding:10px"><?php b16PrioBadge($t['priority']); ?></td>
                    <td style="padding:10px"><?php $od = !empty($t['due_date']) && strtotime($t['due_date']) < strtotime(date('Y-m-d')); ?><span style="<?php echo $od ? 'color:#dc2626;font-weight:800' : ''; ?>"><?php echo htmlspecialchars($t['due_date'] ?? '—'); ?></span></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars(__($t['source_type'])); ?></td>
                    <td style="padding:10px;text-align:right">
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>"><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b16_complete')); ?></button></form>
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>"><input type="text" name="reason" placeholder="<?php echo htmlspecialchars(__('b16_reason_ph')); ?>" style="width:120px;padding:6px;border-radius:8px;border:1px solid #d1d5db"><button type="submit" style="background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b16_cancel')); ?></button></form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($doneTasks)): ?>
        <h3 style="color:#374151"><?php echo htmlspecialchars(__('b16_recent_done')); ?></h3>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
            <table style="width:100%;border-collapse:collapse">
            <?php foreach ($doneTasks as $t): ?>
                <tr style="border-top:1px solid #e5e7eb"><td style="padding:8px;color:#16a34a">✓ <?php echo htmlspecialchars($t['title']); ?></td><td style="padding:8px;color:#6b7280;text-align:right"><?php echo htmlspecialchars($t['completed_by'] ?? ''); ?> · <?php echo htmlspecialchars($t['completed_at'] ?? ''); ?></td></tr>
            <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
