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
        $type = $_POST['transaction_type'] ?? 'EXPENSE';
        $cat = trim($_POST['category'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $date = trim($_POST['transaction_date'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $bid = (int)($_POST['related_entity_id'] ?? 0);
        $skal = isset($_POST['is_skal_relevant']) ? 1 : 0;
        if ($cat === '') { $errors[] = __('b19_cat_required'); }
        if ($amount <= 0) { $errors[] = __('b19_amount_invalid'); }
        if (!in_array($type, ['INCOME','EXPENSE'], true)) { $type = 'EXPENSE'; }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO financial_transactions (transaction_type, category, amount, description, transaction_date, related_entity_id, related_entity_type, created_by, is_skal_relevant) VALUES (:t, :c, :a, :d, :dt, :ri, :rt, :cb, :sk)");
            $stmt->bindValue(':t', $type, PDO::PARAM_STR);
            $stmt->bindValue(':c', $cat, PDO::PARAM_STR);
            $stmt->bindValue(':a', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':d', $desc, PDO::PARAM_STR);
            $stmt->bindValue(':dt', ($date !== '') ? $date : date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':ri', $bid > 0 ? $bid : null, $bid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':rt', $bid > 0 ? 'batch' : null, $bid > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':cb', $operator, PDO::PARAM_STR);
            $stmt->bindValue(':sk', $skal, PDO::PARAM_INT);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B19_TRANS_SAVE', 'financial_transaction', $nid, 'CREATE', 'Financiële transactie', null, ['type' => $type, 'category' => $cat, 'amount' => $amount, 'batch_id' => $bid, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b19_saved');
            $flashClass = 'flash-success';
        } else {
            $flash = implode(' · ', $errors);
            $flashClass = 'flash-error';
        }
    }
}
$income = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='INCOME'")->fetchColumn();
$expense = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='EXPENSE'")->fetchColumn();
$result = $income - $expense;
$rows = $db->query("SELECT ft.*, pb.batch_code FROM financial_transactions ft LEFT JOIN production_batches pb ON ft.related_entity_type='batch' AND pb.id=ft.related_entity_id ORDER BY ft.transaction_date DESC, ft.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$batches = $db->query("SELECT id, batch_code FROM production_batches ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#0f766e,#7c3aed);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b19_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b19_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800;color:#16a34a">€ <?php echo number_format($income,2); ?></div><div><?php echo htmlspecialchars(__('b19_income')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800;color:#dc2626">€ <?php echo number_format($expense,2); ?></div><div><?php echo htmlspecialchars(__('b19_expense')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800;color:<?php echo $result>=0?'#0f766e':'#dc2626'; ?>">€ <?php echo number_format($result,2); ?></div><div><?php echo htmlspecialchars(__('b19_result')); ?></div></div>
    </div>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="save">
        <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b19_type')); ?></label><select name="transaction_type" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="EXPENSE"><?php echo htmlspecialchars(__('b19_expense')); ?></option><option value="INCOME"><?php echo htmlspecialchars(__('b19_income')); ?></option></select></div>
        <div style="flex:2;min-width:140px"><label><?php echo htmlspecialchars(__('b19_category')); ?></label><input name="category" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:110px"><label><?php echo htmlspecialchars(__('b19_amount')); ?></label><input type="number" step="0.01" min="0" name="amount" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:1;min-width:140px"><label><?php echo htmlspecialchars(__('b19_date')); ?></label><input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b19_batch')); ?></label><select name="related_entity_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b19_no_batch')); ?></option><?php foreach ($batches as $b): ?><option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['batch_code']); ?></option><?php endforeach; ?></select></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b19_desc')); ?></label><input name="description" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <label style="display:flex;align-items:center;gap:6px;padding-bottom:8px"><input type="checkbox" name="is_skal_relevant" checked style="transform:scale(1.25)"><?php echo htmlspecialchars(__('b19_skal')); ?></label>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b19_save')); ?></button>
    </form>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b19_date')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b19_category')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b19_amount')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b19_batch')); ?></th><th style="padding:10px">Skal</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b19_no_rows')); ?></td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><?php echo htmlspecialchars(substr($r['transaction_date'],0,10)); ?></td>
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($r['category']); ?></strong><?php if (!empty($r['description'])): ?><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($r['description']); ?></div><?php endif; ?></td>
                    <td style="padding:10px;font-weight:700;color:<?php echo $r['transaction_type']==='INCOME'?'#16a34a':'#dc2626'; ?>"><?php echo ($r['transaction_type']==='INCOME'?'+':'-'); ?> € <?php echo number_format((float)$r['amount'],2); ?></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($r['batch_code'] ?? '—'); ?></td>
                    <td style="padding:10px"><?php echo $r['is_skal_relevant'] ? '✅' : '—'; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
