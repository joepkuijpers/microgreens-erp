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
$uploadDir = __DIR__ . '/app/uploads';
$allowedExt = ['pdf','jpg','jpeg','png','docx'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $code = trim($_POST['doc_code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $cat = $_POST['category'] ?? 'SOP';
        $version = trim($_POST['version'] ?? '1.0');
        $review = trim($_POST['review_due_date'] ?? '');
        $valid = trim($_POST['valid_until'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($code === '') { $errors[] = __('b23_code_required'); }
        if ($title === '') { $errors[] = __('b23_title_required'); }
        $dup = 0;
        if ($code !== '') { $d = $db->prepare("SELECT COUNT(*) FROM documents WHERE doc_code=:c"); $d->bindValue(':c',$code,PDO::PARAM_STR); $d->execute(); $dup = (int)$d->fetchColumn(); }
        if ($dup > 0) { $errors[] = __('b23_code_dup'); }
        if (empty($errors)) {
            $filePath = null; $fileName = null; $uploadNote = '';
            if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $orig = $_FILES['file']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExt, true)) {
                    $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                    $newName = $code . '_' . date('YmdHis') . '_' . $safe . '.' . $ext;
                    $dest = $uploadDir . '/' . $newName;
                    if (is_dir($uploadDir) && is_writable($uploadDir) && move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                        $filePath = 'app/uploads/' . $newName; $fileName = $orig;
                    } else { $uploadNote = __('b23_upload_failed'); }
                } else { $uploadNote = __('b23_upload_ext'); }
            }
            $stmt = $db->prepare("INSERT INTO documents (doc_code, title, category, version, file_path, file_name, review_due_date, valid_until, notes) VALUES (:c, :t, :cat, :v, :fp, :fn, :r, :va, :n)");
            $stmt->bindValue(':c', $code, PDO::PARAM_STR);
            $stmt->bindValue(':t', $title, PDO::PARAM_STR);
            $stmt->bindValue(':cat', $cat, PDO::PARAM_STR);
            $stmt->bindValue(':v', $version, PDO::PARAM_STR);
            $stmt->bindValue(':fp', $filePath, $filePath ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fn', $fileName, $fileName ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':r', ($review !== '') ? $review : null, $review !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':va', ($valid !== '') ? $valid : null, $valid !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':n', $notes, PDO::PARAM_STR);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B23_DOC_SAVE', 'document', $nid, 'CREATE', 'Document geregistreerd', null, ['doc_code' => $code, 'category' => $cat, 'file' => $fileName, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b23_saved') . ($uploadNote ? ' · ' . $uploadNote : '');
            $flashClass = $uploadNote ? 'flash-warning' : 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE documents SET is_active = CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at=datetime('now') WHERE id=:id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT); $stmt->execute();
        auditLog($db, 'B23_DOC_TOGGLE', 'document', $id, 'UPDATE', 'Document actief/inactief', null, ['operator' => $operator]);
        $flash = __('b23_toggled'); $flashClass = 'flash-success';
    }
}
$docs = $db->query("SELECT * FROM documents ORDER BY is_active DESC, category ASC, doc_code ASC")->fetchAll(PDO::FETCH_ASSOC);
function b23ValidBadge($valid, $review) {
    $today = strtotime(date('Y-m-d'));
    if ($valid && strtotime($valid) < $today) { return '<span style="background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b23_expired')) . '</span>'; }
    if ($review && strtotime($review) < $today) { return '<span style="background:#ffedd5;color:#9a3412;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b23_review_due')) . '</span>'; }
    if ($review && strtotime($review) < strtotime('+30 days')) { return '<span style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b23_review_soon')) . '</span>'; }
    return '<span style="background:#dcfce7;color:#166534;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px">' . htmlspecialchars(__('b23_ok')) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#0f766e,#374151);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b23_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b23_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="save">
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b23_code')); ?></label><input name="doc_code" placeholder="SOP-001" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b23_title_field')); ?></label><input name="title" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:150px"><label><?php echo htmlspecialchars(__('b23_category')); ?></label><select name="category" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><?php foreach (['HANDBOEK','RISICOPLAN','SOP','PROCEDURE','CERTIFICAAT','SAMENSTELLING','WATERANALYSE','KALIBRATIE','INCIDENT'] as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select></div>
        <div style="flex:1;min-width:80px"><label><?php echo htmlspecialchars(__('b23_version')); ?></label><input name="version" value="1.0" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b23_review')); ?></label><input type="date" name="review_due_date" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b23_valid')); ?></label><input type="date" name="valid_until" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b23_file')); ?></label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx" style="width:100%;padding:7px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b23_save')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b23_code')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b23_title_field')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b23_category')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b23_version')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b23_status')); ?></th><th style="padding:10px"></th></tr></thead>
            <tbody>
            <?php if (empty($docs)): ?>
                <tr><td colspan="6" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b23_no_rows')); ?></td></tr>
            <?php else: foreach ($docs as $d): ?>
                <tr style="border-top:1px solid #e5e7eb;<?php echo $d['is_active'] ? '' : 'opacity:.5'; ?>">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($d['doc_code']); ?></strong></td>
                    <td style="padding:10px"><?php echo htmlspecialchars($d['title']); ?><?php if (!empty($d['file_name'])): ?><div style="color:#2563eb;font-size:13px">📎 <?php echo htmlspecialchars($d['file_name']); ?></div><?php endif; ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($d['category']); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($d['version']); ?></td>
                    <td style="padding:10px"><?php echo b23ValidBadge($d['valid_until'], $d['review_due_date']); ?></td>
                    <td style="padding:10px;text-align:right">
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>"><button type="submit" style="background:#6b7280;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo $d['is_active'] ? htmlspecialchars(__('b23_deactivate')) : htmlspecialchars(__('b23_activate')); ?></button></form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
