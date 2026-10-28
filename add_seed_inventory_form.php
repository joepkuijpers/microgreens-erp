<?php
require_once __DIR__ . '/app/includes/header.php';
$err = ''; $ok = false;
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $qty = (float)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'g');
    $organic_status = $_POST['organic_status'] ?? 'UNKNOWN';
    $exception = isset($_POST['no_supplier']);
    $supplier_id = $exception ? null : (int)($_POST['supplier_id'] ?? 0);
    $reason = $exception ? trim($_POST['no_supplier_reason'] ?? '') : null;
    if ($item_name === '' || $qty <= 0) { $err = __('seed_err_required'); }
    elseif (!$exception && $supplier_id <= 0) { $err = __('seed_err_supplier'); }
    elseif ($exception && $reason === '') { $err = __('seed_err_reason'); }
    else {
        $st = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, supplier_id, lot_number, expiration_date, purchase_date, organic_status, certificate_code, is_active, no_supplier_reason, registered_at, created_at) VALUES (?, 'seed', ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, datetime('now'), datetime('now'))");
        $st->execute([$item_name, $qty, $unit, $supplier_id, trim($_POST['lot_number'] ?? '') ?: null, $_POST['expiration_date'] ?: null, $_POST['purchase_date'] ?: null, $organic_status, trim($_POST['certificate_code'] ?? '') ?: null, $reason]);
        $ok = true;
    }
}
?>
<div style="max-width:640px;margin:24px auto;padding:0 16px;">
  <a href="add_batch.php" style="text-decoration:none;">&larr; <?= __('seed_back') ?></a>
  <h2 style="margin:16px 0;">🌱 <?= __('seed_register_new') ?></h2>
  <?php if ($err): ?><div style="background:#ffe6e6;padding:12px;border-radius:8px;margin-bottom:12px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div style="background:#e6f7e6;padding:12px;border-radius:8px;margin-bottom:12px;"><?= __('seed_saved') ?> <a href="add_batch.php"><?= __('seed_go_batch') ?></a> <a href="add_seed_inventory_form.php"><?= __('seed_add_another') ?></a></div><?php endif; ?>
  <form method="post" style="display:flex;flex-direction:column;gap:12px;">
    <label><?= __('seed_item_name') ?>*<input name="item_name" required style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    <div style="display:flex;gap:12px;">
      <label style="flex:2;"><?= __('seed_qty') ?>*<input type="number" step="0.01" name="quantity" required style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
      <label style="flex:1;"><?= __('seed_unit') ?><input name="unit" value="g" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    </div>
    <label><?= __('seed_supplier') ?>*<select name="supplier_id" id="supSel" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"><option value="0">—</option><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></label>
    <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" id="excChk" onchange="toggleExc()"> <?= __('seed_no_supplier') ?></label>
    <div id="excBox" style="display:none;"><label><?= __('seed_reason') ?>*<input name="no_supplier_reason" id="excReason" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label></div>
    <div style="display:flex;gap:12px;">
      <label style="flex:1;"><?= __('seed_lot') ?><input name="lot_number" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
      <label style="flex:1;"><?= __('seed_purchase') ?><input type="date" name="purchase_date" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
      <label style="flex:1;"><?= __('seed_expiry') ?><input type="date" name="expiration_date" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    </div>
    <label><?= __('seed_organic') ?><select name="organic_status" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"><option value="UNKNOWN">UNKNOWN</option><option value="CERTIFIED">CERTIFIED</option><option value="NOT_ORGANIC">NOT_ORGANIC</option></select></label>
    <label><?= __('seed_cert') ?><input name="certificate_code" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    <button type="submit" style="padding:14px;font-size:18px;background:#2e7d32;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;"><?= __('seed_save') ?></button>
  </form>
</div>
<script>
function toggleExc(){var c=document.getElementById('excChk').checked;var s=document.getElementById('supSel');var r=document.getElementById('excReason');document.getElementById('excBox').style.display=c?'block':'none';s.disabled=c;s.required=!c;r.required=c;}
</script>
