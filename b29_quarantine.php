<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';

$t0 = microtime(true);
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$message = '';
$msg_type = 'info';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? '';
    $unitId = (int)($_POST["unit_id"] ?? 0);
    $reason = trim($_POST["quarantine_reason"] ?? "");
    try {
        $db->beginTransaction();
        $st = $db->prepare("SELECT id, package_code, finished_inventory_id, status, quarantine_reason FROM packaging_units WHERE id = ?");
        $st->execute([$unitId]);
        $unit = $st->fetch(PDO::FETCH_ASSOC);
        if (!$unit) { throw new RuntimeException(__('b29_err_not_found')); }

        if ($action === 'quarantine') {
            if ($reason === '') { throw new RuntimeException(__('b29_err_reason_required')); }
            if ($unit['status'] === 'SOLD') { throw new RuntimeException(__('b29_err_sold')); }
            if ($unit['status'] === 'QUARANTINE') { throw new RuntimeException(__('b29_err_already_quarantine')); }
            $db->prepare("UPDATE packaging_units SET status='QUARANTINE', quarantine_reason=? WHERE id=?")->execute([$reason, $unitId]);
            $db->prepare("INSERT INTO system_alerts (alert_type, severity, message, related_id, created_at) VALUES ('QUARANTINE','WARNING',?,?,datetime('now'))")
               ->execute(['Quarantaine: ' . $unit['package_code'] . ' — ' . $reason, $unitId]);
            auditLog($db, 'B29_QUARANTINE', 'packaging_units', $unitId, 'CORRECTION', 'Bakje in quarantaine: ' . $unit['package_code'], ['status' => $unit['status']], ['status' => 'QUARANTINE', 'reason' => $reason, 'operator' => $operator], 'packaging_unit', $unitId);
            $message = sprintf(__('b29_msg_quarantine'), $unit['package_code']); $msg_type = 'warning';
        } elseif ($action === 'release') {
            if ($unit['status'] !== 'QUARANTINE') { throw new RuntimeException(__('b29_err_not_quarantine')); }
            if ($reason === '') { throw new RuntimeException(__('b29_err_reason_required')); }
            $db->prepare("UPDATE packaging_units SET status='AVAILABLE', quarantine_reason=NULL WHERE id=?")->execute([$unitId]);
            auditLog($db, 'B29_QUARANTINE', 'packaging_units', $unitId, 'CORRECTION', 'Bakje vrijgegeven: ' . $unit['package_code'], ['status' => 'QUARANTINE'], ['status' => 'AVAILABLE', 'reason' => $reason, 'operator' => $operator], 'packaging_unit', $unitId);
            $message = sprintf(__('b29_msg_release'), $unit['package_code']); $msg_type = 'success';
        } elseif ($action === 'reject') {
            if ($unit['status'] === 'SOLD') { throw new RuntimeException(__('b29_err_sold')); }
            if ($reason === '') { throw new RuntimeException(__('b29_err_reason_required')); }
            $db->prepare("UPDATE packaging_units SET status='REJECTED', quarantine_reason=? WHERE id=?")->execute([$reason, $unitId]);
            $fi = $db->prepare("SELECT fi.harvest_id, gr.production_batch_id FROM finished_inventory fi JOIN harvests h ON h.id=fi.harvest_id JOIN germination_records gr ON gr.id=h.germination_record_id WHERE fi.id=?");
            $fi->execute([$unit['finished_inventory_id']]);
            $frow = $fi->fetch(PDO::FETCH_ASSOC);
            if ($frow) {
                $db->prepare("INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes) VALUES (?, 'WASTE', ?, 'g', 'REGISTERED', ?)")
                   ->execute([$frow['production_batch_id'], $unit['total_weight_g'], 'Afgekeurd via quarantaine: ' . $reason]);
            }
            auditLog($db, 'B29_QUARANTINE', 'packaging_units', $unitId, 'CORRECTION', 'Bakje afgekeurd (afval): ' . $unit['package_code'], ['status' => $unit['status']], ['status' => 'REJECTED', 'reason' => $reason, 'operator' => $operator], 'packaging_unit', $unitId);
            $message = sprintf(__('b29_msg_reject'), $unit['package_code']); $msg_type = 'error';
        } else {
            throw new RuntimeException(__('b29_err_unknown_action'));
        }
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $message = $e->getMessage(); $msg_type = 'error';
    }
}

$units = $db->query("
    SELECT pu.id, pu.package_code, pu.total_weight_g, pu.status, pu.quarantine_reason,
           fi.product_name, pb.batch_code, pb.track_color
    FROM packaging_units pu
    LEFT JOIN finished_inventory fi ON fi.id = pu.finished_inventory_id
    LEFT JOIN harvests h ON h.id = fi.harvest_id
    LEFT JOIN germination_records gr ON gr.id = h.germination_record_id
    LEFT JOIN production_batches pb ON pb.id = gr.production_batch_id
    ORDER BY pu.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$badge = ['AVAILABLE'=>'#16a34a','SOLD'=>'#6b7280','QUARANTINE'=>'#f59e0b','REJECTED'=>'#dc2626'];
$badge_lbl = ['AVAILABLE'=>__('b29_st_available'),'SOLD'=>__('b29_st_sold'),'QUARANTINE'=>__('b29_st_quarantine'),'REJECTED'=>__('b29_st_rejected')];
?>
<div style="max-width:1000px;margin:0 auto;padding:16px;">
  <h2>🚧 <?php echo __('b29_title'); ?></h2>
  <?php if ($message): ?><div class="flash flash-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead><tr style="text-align:left;border-bottom:2px solid #ddd;">
      <th><?php echo __('b29_col_batch'); ?></th>
      <th><?php echo __('b29_col_package'); ?></th>
      <th><?php echo __('b29_col_product'); ?></th>
      <th><?php echo __('b29_col_weight'); ?></th>
      <th><?php echo __('b29_col_status'); ?></th>
      <th><?php echo __('b29_col_action'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($units as $u2): $c = $badge[$u2['status']] ?? '#374151'; ?>
      <tr style="border-bottom:1px solid #eee;">
        <td>
          <?php if (!empty($u2['track_color'])): ?><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:<?php echo htmlspecialchars($u2['track_color']); ?>;margin-right:6px;"></span><?php endif; ?>
          <?php echo htmlspecialchars($u2['batch_code'] ?? '—'); ?>
        </td>
        <td><?php echo htmlspecialchars($u2['package_code']); ?></td>
        <td><?php echo htmlspecialchars($u2['product_name'] ?? '—'); ?></td>
        <td><?php echo number_format($u2['total_weight_g'],1); ?> g</td>
        <td><span style="color:<?php echo $c; ?>;font-weight:bold;"><?php echo htmlspecialchars($badge_lbl[$u2['status']] ?? $u2['status']); ?></span>
          <?php if ($u2['status']==='QUARANTINE' && !empty($u2['quarantine_reason'])): ?><br><small style="color:#92400e;">↳ <?php echo htmlspecialchars($u2['quarantine_reason']); ?></small><?php endif; ?>
        </td>
        <td>
          <?php if ($u2['status']==='AVAILABLE'): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__('b29_confirm_q')); ?>');">
              <input type="hidden" name="action" value="quarantine"><input type="hidden" name="unit_id" value="<?php echo (int)$u2['id']; ?>">
              <input type="text" name="quarantine_reason" placeholder="<?php echo htmlspecialchars(__('b29_ph_reason')); ?>" required style="padding:4px;border:1px solid #ddd;border-radius:4px;">
              <button type="submit" style="background:#f59e0b;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;"><?php echo __('b29_btn_quarantine'); ?></button>
            </form>
          <?php elseif ($u2['status']==='QUARANTINE'): ?>
            <form method="POST" style="display:inline-block;margin:2px;">
              <input type="hidden" name="action" value="release"><input type="hidden" name="unit_id" value="<?php echo (int)$u2['id']; ?>">
              <input type="text" name="quarantine_reason" placeholder="<?php echo htmlspecialchars(__('b29_ph_release')); ?>" required style="padding:4px;border:1px solid #ddd;border-radius:4px;">
              <button type="submit" style="background:#16a34a;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;"><?php echo __('b29_btn_release'); ?></button>
            </form>
            <form method="POST" style="display:inline-block;margin:2px;" onsubmit="return confirm('<?php echo htmlspecialchars(__('b29_confirm_reject')); ?>');">
              <input type="hidden" name="action" value="reject"><input type="hidden" name="unit_id" value="<?php echo (int)$u2['id']; ?>">
              <input type="text" name="quarantine_reason" placeholder="<?php echo htmlspecialchars(__('b29_ph_reject')); ?>" required style="padding:4px;border:1px solid #ddd;border-radius:4px;">
              <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;"><?php echo __('b29_btn_reject'); ?></button>
            </form>
          <?php else: ?>
            <span style="color:#9ca3af;"><?php echo __('b29_no_action'); ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
