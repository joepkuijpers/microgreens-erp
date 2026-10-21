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
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['contact_email'] ?? '');
        $code = trim($_POST['certificate_code'] ?? '');
        $expiry = trim($_POST['certificate_expiry'] ?? '');
        $ostatus = $_POST['organic_status'] ?? 'UNKNOWN';
        $skal = isset($_POST['is_skal_validated']) ? 1 : 0;
        if ($name === '') { $errors[] = __('b18_name_required'); }
        if (empty($errors)) {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE suppliers SET name=:n, contact_email=:e, certificate_code=:c, certificate_expiry=:x, organic_status=:o, is_skal_validated=:s WHERE id=:id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                } else {
                    $stmt = $db->prepare("INSERT INTO suppliers (name, contact_email, certificate_code, certificate_expiry, organic_status, is_skal_validated) VALUES (:n, :e, :c, :x, :o, :s)");
                }
                $stmt->bindValue(':n', $name, PDO::PARAM_STR);
                $stmt->bindValue(':e', $email, PDO::PARAM_STR);
                $stmt->bindValue(':c', $code, PDO::PARAM_STR);
                $stmt->bindValue(':x', ($expiry !== '') ? $expiry : null, $expiry !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':o', $ostatus, PDO::PARAM_STR);
                $stmt->bindValue(':s', $skal, PDO::PARAM_INT);
                $stmt->execute();
                $rid = $id > 0 ? $id : (int)$db->lastInsertId();
                $reg_ms = (int)round((microtime(true) - $t0) * 1000);
                auditLog($db, 'B18_SUPPLIER_SAVE', 'supplier', $rid, $id > 0 ? 'UPDATE' : 'CREATE', 'Leverancier opgeslagen', null, ['name' => $name, 'expiry' => $expiry, 'skal' => $skal, 'operator' => $operator, 'reg_ms' => $reg_ms]);
                $flash = __('b18_saved');
                $flashClass = 'flash-success';
            } catch (Exception $ex) {
                $flash = __('b18_dup_name');
                $flashClass = 'flash-warning';
            }
        } else {
            $flash = implode(' · ', $errors);
            $flashClass = 'flash-error';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $refs = 0;
        foreach (['seed_lots', 'cleaning_agents', 'inventory'] as $tbl) {
            try { $q = $db->prepare("SELECT COUNT(*) FROM $tbl WHERE supplier_id=:id"); $q->bindValue(':id', $id, PDO::PARAM_INT); $q->execute(); $refs += (int)$q->fetchColumn(); } catch (Exception $e) {}
        }
        if ($refs > 0) {
            $flash = __('b18_has_refs');
            $flashClass = 'flash-error';
        } else {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE id=:id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            auditLog($db, 'B18_SUPPLIER_DELETE', 'supplier', $id, 'DELETE', 'Leverancier verwijderd', null, ['operator' => $operator]);
            $flash = __('b18_deleted');
            $flashClass = 'flash-warning';
        }
    }
}
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY is_skal_validated ASC, certificate_expiry IS NULL, certificate_expiry ASC")->fetchAll(PDO::FETCH_ASSOC);
function b18CertBadge($exp) {
    if ($exp === null || $exp === '') { return '<span style="color:#6b7280">' . htmlspecialchars(__('b18_no_cert')) . '</span>'; }
    $today = strtotime(date('Y-m-d'));
    $d = strtotime($exp);
    if ($d < $today) { return '<span style="background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b18_expired')) . ' · ' . htmlspecialchars($exp) . '</span>'; }
    if ($d < strtotime('+30 days')) { return '<span style="background:#ffedd5;color:#9a3412;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b18_soon')) . ' · ' . htmlspecialchars($exp) . '</span>'; }
    return '<span style="background:#dcfce7;color:#166534;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b18_valid')) . ' · ' . htmlspecialchars($exp) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#7c3aed,#0f766e);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b18_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b18_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="save">
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b18_name')); ?></label><input name="name" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b18_email')); ?></label><input name="contact_email" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b18_code')); ?></label><input name="certificate_code" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b18_expiry')); ?></label><input type="date" name="certificate_expiry" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b18_status')); ?></label><select name="organic_status" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="VALID">VALID</option><option value="PENDING">PENDING</option><option value="UNKNOWN">UNKNOWN</option></select></div>
        <label style="display:flex;align-items:center;gap:6px;padding-bottom:8px"><input type="checkbox" name="is_skal_validated" style="transform:scale(1.25)"><?php echo htmlspecialchars(__('b18_skal')); ?></label>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b18_add')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b18_name')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b18_cert_status')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b18_status')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b18_skal')); ?></th><th style="padding:10px"></th></tr></thead>
            <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="5" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b18_no_rows')); ?></td></tr>
            <?php else: foreach ($suppliers as $s): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($s['name']); ?></strong><?php if (!empty($s['certificate_code'])): ?><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($s['certificate_code']); ?></div><?php endif; ?></td>
                    <td style="padding:10px"><?php echo b18CertBadge($s['certificate_expiry']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($s['organic_status'] ?? '—'); ?></td>
                    <td style="padding:10px"><?php echo $s['is_skal_validated'] ? '✅' : '—'; ?></td>
                    <td style="padding:10px;text-align:right">
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"><button type="submit" onclick="return confirm('<?php echo htmlspecialchars(__('b18_confirm_del')); ?>')" style="background:#dc2626;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b18_delete')); ?></button></form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
