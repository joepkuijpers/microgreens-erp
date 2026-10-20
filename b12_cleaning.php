<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';

$flash = ""; $flashType = "";
$t0 = microtime(true);
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : (is_string($u) ? $u : 'system');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $obj_type = trim($_POST['object_type'] ?? '');
    $obj_name = trim($_POST['object_name'] ?? '');
    $agent_id = (int)($_POST['agent_id'] ?? 0);
    $method   = trim($_POST['method'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    $errors = [];
    if ($obj_name === '') $errors[] = __('b12_err_object');
    if ($agent_id <= 0)   $errors[] = __('b12_err_agent');

    $agent = null;
    if (!$errors) {
        $st = $db->prepare("SELECT id, name, is_organic_approved, supplier_id FROM cleaning_agents WHERE id = ?");
        $st->execute([$agent_id]);
        $agent = $st->fetch(PDO::FETCH_ASSOC);
        if (!$agent) $errors[] = __('b12_err_agent');
    }

    if ($errors) {
        $flash = implode(' ', $errors); $flashType = 'error';
    } else {
        $approved = (int)$agent['is_organic_approved'];
        if ($approved !== 1) { $flash = __('b12_warn_agent'); $flashType = 'warning'; }
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO cleaning_logs
                (object_type, object_name, supplier_id, product_name, method, is_validated, notes, operator_name, cleaned_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmt->execute([$obj_type, $obj_name, $agent['supplier_id'], $agent['name'],
                           $method, $approved, $notes, $operator]);
            $logId = (int)$db->lastInsertId();
            $reg = round((microtime(true) - $t0) * 1000);
            $reason = __('b12_reason_log') . ' ' . $obj_name . ' / ' . $agent['name'];
            $afterData = ['operator' => $operator, 'reg_ms' => $reg,
                          'agent_id' => $agent_id, 'agent_approved' => $approved];
            auditLog($db, 'B12_CLEANING_LOGGED', 'cleaning_logs', $logId, 'CREATE', $reason, null, $afterData);
            $db->commit();
            if ($flashType !== 'warning') { $flash = __('b12_success'); $flashType = 'success'; }
        } catch (Exception $e) {
            $db->rollBack();
            $flash = __('b12_error_db'); $flashType = 'error';
        }
    }
}

$agents = $db->query("SELECT id, name, brand, is_organic_approved, certificate_ref, supplier_id
    FROM cleaning_agents ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$logs = $db->query("SELECT cl.*, s.name AS supplier_name
    FROM cleaning_logs cl LEFT JOIN suppliers s ON cl.supplier_id = s.id
    ORDER BY cl.cleaned_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>
<h1><?php echo __('b12_title'); ?></h1>
<?php if ($flash !== ""): ?>
<div class="flash flash-<?php echo $flashType; ?>"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>

<form method="post">
  <div class="form-row">
    <label><?php echo __('b12_object_type'); ?></label>
    <select name="object_type">
      <option value="ruimte"><?php echo __('b12_type_room'); ?></option>
      <option value="bak"><?php echo __('b12_type_tray'); ?></option>
      <option value="apparatuur"><?php echo __('b12_type_equipment'); ?></option>
    </select>
  </div>
  <div class="form-row">
    <label><?php echo __('b12_object_name'); ?></label>
    <input type="text" name="object_name" required>
  </div>
  <div class="form-row">
    <label><?php echo __('b12_agent'); ?></label>
    <select name="agent_id" required>
      <option value=""><?php echo __('b12_agent_choose'); ?></option>
      <?php foreach ($agents as $a): ?>
      <option value="<?php echo (int)$a['id']; ?>">
        <?php echo htmlspecialchars($a['name']); ?>
        <?php echo ((int)$a['is_organic_approved'] === 1) ? '✓' : '⚠'; ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-row">
    <label><?php echo __('b12_method'); ?></label>
    <input type="text" name="method">
  </div>
  <div class="form-row">
    <label><?php echo __('b12_notes'); ?></label>
    <textarea name="notes" rows="2"></textarea>
  </div>
  <button type="submit" class="btn-primary"><?php echo __('b12_save'); ?></button>
</form>
<h2><?php echo __('b12_recent'); ?></h2>
<table class="data-table">
  <thead>
    <tr>
      <th><?php echo __('b12_col_date'); ?></th>
      <th><?php echo __('b12_col_object'); ?></th>
      <th><?php echo __('b12_col_product'); ?></th>
      <th><?php echo __('b12_col_supplier'); ?></th>
      <th><?php echo __('b12_col_method'); ?></th>
      <th><?php echo __('b12_col_status'); ?></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?php echo htmlspecialchars($l['cleaned_at']); ?></td>
      <td><?php echo htmlspecialchars($l['object_name']); ?></td>
      <td><?php echo htmlspecialchars($l['product_name']); ?></td>
      <td><?php echo htmlspecialchars($l['supplier_name'] ?? __('b12_no_supplier')); ?></td>
      <td><?php echo htmlspecialchars($l['method']); ?></td>
      <td><?php echo ((int)$l['is_validated'] === 1) ? __('b12_ok') : __('b12_check'); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php
require_once __DIR__ . '/app/includes/footer.php';
