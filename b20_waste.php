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
        $bid = (int)($_POST['batch_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($bid <= 0) { $errors[] = __('b20_batch_required'); }
        if ($qty <= 0) { $errors[] = __('b20_qty_invalid'); }
        if (empty($errors)) {
            $tc = $db->query("SELECT track_color FROM production_batches WHERE id=$bid")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes, track_color) VALUES (:b, 'WASTE', :q, 'g', 'REGISTERED', :n, :tc)");
            $stmt->bindValue(':b', $bid, PDO::PARAM_INT);
            $stmt->bindValue(':q', $qty, PDO::PARAM_STR);
            $stmt->bindValue(':n', $reason, PDO::PARAM_STR);
            $stmt->bindValue(':tc', ($tc !== false && $tc !== null && $tc !== '') ? $tc : null, ($tc !== false && $tc !== null && $tc !== '') ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $nid = (int)$db->lastInsertId();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B20_WASTE_REGISTER', 'production_output', $nid, 'CREATE', 'Afval geregistreerd', null, ['batch_id' => $bid, 'quantity' => $qty, 'reason' => $reason, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b20_saved');
            $flashClass = 'flash-success';
        } else {
            $flash = implode(' · ', $errors);
            $flashClass = 'flash-error';
        }
    }
}
$batches = $db->query("SELECT id, batch_code, crop_type, track_color FROM production_batches ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$mass = $db->query("
    SELECT pb.id, pb.batch_code, pb.crop_type,
        COALESCE((SELECT SUM(h.weight_grams) FROM harvests h JOIN germination_records gr ON h.germination_record_id=gr.id WHERE gr.production_batch_id=pb.id),0) AS oogst,
        COALESCE((SELECT SUM(quantity) FROM production_outputs WHERE batch_id=pb.id AND output_type='FRESH'),0) AS fresh,
        COALESCE((SELECT SUM(quantity) FROM production_outputs WHERE batch_id=pb.id AND output_type='FREEZE_DRY_INPUT'),0) AS freeze,
        COALESCE((SELECT SUM(quantity) FROM production_outputs WHERE batch_id=pb.id AND output_type='WASTE'),0) AS waste
    FROM production_batches pb
    WHERE EXISTS (SELECT 1 FROM production_outputs WHERE batch_id=pb.id)
       OR EXISTS (SELECT 1 FROM germination_records gr JOIN harvests h ON h.germination_record_id=gr.id WHERE gr.production_batch_id=pb.id)
    ORDER BY pb.id DESC LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#92400e,#7c3aed);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b20_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b20_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <input type="hidden" name="action" value="save">
        <div style="flex:2;min-width:180px"><label><?php echo htmlspecialchars(__('b20_batch')); ?></label><select name="batch_id" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"><option value="0"><?php echo htmlspecialchars(__('b20_select_batch')); ?></option><?php foreach ($batches as $b): ?><option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['batch_code'] . ' · ' . $b['crop_type']); ?></option><?php endforeach; ?></select></div>
        <div style="flex:1;min-width:110px"><label><?php echo htmlspecialchars(__('b20_weight')); ?></label><input type="number" step="1" min="0" name="quantity" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b20_reason')); ?></label><input name="reason" style="width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db"></div>
        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b20_save')); ?></button>
    </form>
    <h3 style="color:#374151"><?php echo htmlspecialchars(__('b20_balance')); ?></h3>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b20_batch')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b20_harvest')); ?></th><th style="padding:10px">Fresh</th><th style="padding:10px">Vriesdroog</th><th style="padding:10px"><?php echo htmlspecialchars(__('b20_waste')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b20_rest')); ?></th></tr></thead>
            <tbody>
            <?php if (empty($mass)): ?>
                <tr><td colspan="6" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b20_no_rows')); ?></td></tr>
            <?php else: foreach ($mass as $m): $out = $m['fresh'] + $m['freeze'] + $m['waste']; $rest = $m['oogst'] - $out; ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($m['batch_code']); ?></strong><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($m['crop_type']); ?></div></td>
                    <td style="padding:10px;font-weight:700"><?php echo number_format($m['oogst'],0); ?> g</td>
                    <td style="padding:10px;color:#16a34a"><?php echo number_format($m['fresh'],0); ?> g</td>
                    <td style="padding:10px;color:#2563eb"><?php echo number_format($m['freeze'],0); ?> g</td>
                    <td style="padding:10px;color:#92400e"><?php echo number_format($m['waste'],0); ?> g</td>
                    <td style="padding:10px;color:<?php echo $rest < 0 ? '#dc2626' : '#6b7280'; ?>"><?php echo number_format($rest,0); ?> g</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <p style="color:#6b7280;font-size:13px;margin-top:10px"><?php echo htmlspecialchars(__('b20_rest_hint')); ?></p>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
