<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';

$t0 = microtime(true);
$errors = [];
$success_msg = '';
$is_warning = false;
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';

$today = date('Y-m-d');

// --- POST: SUBSTRAAT TOEVOEGEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_substrate') {
    $name = trim($_POST['item_name'] ?? '');
    $qty = $_POST['quantity'] ?? '';
    $unit = trim($_POST['unit'] ?? 'stuks');
    $lot = trim($_POST['lot_number'] ?? '');
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $organic_status = $_POST['organic_status'] ?? 'PENDING';
    $cert = trim($_POST['certificate_code'] ?? '');
    $expiry = trim($_POST['expiration_date'] ?? '');
    $composition = trim($_POST['composition'] ?? '');

    if ($name === '') $errors[] = __('b15_err_name');
    if ($qty === '' || !is_numeric($qty) || floatval($qty) < 0) $errors[] = __('b15_err_qty');

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO inventory (item_name, category, quantity, unit, supplier_id, lot_number,
                    expiration_date, organic_status, certificate_code, composition, is_active, created_at, updated_at)
                VALUES (?, 'Substraat', ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $name, floatval($qty), $unit,
                $supplier_id > 0 ? $supplier_id : null,
                $lot !== '' ? $lot : null,
                $expiry !== '' ? $expiry : null,
                $organic_status,
                $cert !== '' ? $cert : null,
                $composition !== '' ? $composition : null
            ]);
            $inv_id = intval($db->lastInsertId());
            $db->commit();

            $t1 = microtime(true);
            $reg_ms = round(($t1 - $t0) * 1000);
            auditLog($db, 'B15_SUBSTRATE_ADD', 'inventory', $inv_id, 'INSERT',
                __('b15_audit_add'), null,
                ['item_name' => $name, 'organic_status' => $organic_status, 'lot_number' => $lot, 'quantity' => floatval($qty), 'operator' => $operator, 'reg_ms' => $reg_ms]);

            $success_msg = __('b15_success_add') . ' (' . $reg_ms . ' ms)';
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = __('b15_err_save') . ': ' . $e->getMessage();
        }
    }
}

// --- POST: SUBSTRAAT AAN BATCH TOEWIJZEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_batch') {
    $inv_id = intval($_POST['inventory_id'] ?? 0);
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $qty = $_POST['quantity_used'] ?? '';

    if ($inv_id <= 0) $errors[] = __('b15_err_pick_substrate');
    if ($batch_id <= 0) $errors[] = __('b15_err_pick_batch');
    if ($qty === '' || !is_numeric($qty) || floatval($qty) <= 0) $errors[] = __('b15_err_qty');

    if (empty($errors)) {
        try {
            // Substraat + organic-status opvragen
            $stmt = $db->prepare("SELECT item_name, quantity, organic_status, expiration_date FROM inventory WHERE id = ?");
            $stmt->execute([$inv_id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sub) {
                $errors[] = __('b15_err_save');
            } elseif (floatval($sub['quantity']) < floatval($qty)) {
                $errors[] = __('b15_err_stock');
            } else {
                // Organic-gate: CERTIFIED = OK, anders oranje waarschuwing (geen blokkade)
                $os = $sub['organic_status'] ?? 'UNKNOWN';
                $expired = !empty($sub['expiration_date']) && $sub['expiration_date'] < $today;
                $validated = ($os === 'CERTIFIED' && !$expired);
                $is_warning = !$validated;

                $db->beginTransaction();

                // 1. batch_materials (traceability-brug)
                $stmt2 = $db->prepare("INSERT INTO batch_materials (batch_id, inventory_id, quantity_used) VALUES (?, ?, ?)");
                $stmt2->execute([$batch_id, $inv_id, floatval($qty)]);

                // 2. Voorraad verlagen
                $stmt3 = $db->prepare("UPDATE inventory SET quantity = quantity - ?, updated_at = datetime('now') WHERE id = ?");
                $stmt3->execute([floatval($qty), $inv_id]);

                $db->commit();

                $t1 = microtime(true);
                $reg_ms = round(($t1 - $t0) * 1000);
                $reason = $validated ? __('b15_audit_assign') : __('b15_audit_assign_warn');
                auditLog($db, 'B15_SUBSTRATE_ASSIGN', 'batch_materials', intval($db->lastInsertId()), 'INSERT',
                    $reason, null,
                    ['batch_id' => $batch_id, 'inventory_id' => $inv_id, 'item_name' => $sub['item_name'], 'quantity_used' => floatval($qty), 'organic_status' => $os, 'validated' => $validated, 'operator' => $operator, 'reg_ms' => $reg_ms]);

                if ($is_warning) {
                    $success_msg = __('b15_warn_not_validated') . ' (' . $os . ') (' . $reg_ms . ' ms)';
                } else {
                    $success_msg = __('b15_success_assign') . ' (' . $reg_ms . ' ms)';
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = __('b15_err_save') . ': ' . $e->getMessage();
        }
    }
}

// --- DATA OPHALEN ---
$substraat = [];
try {
    $stmt = $db->query("
        SELECT i.id, i.item_name, i.quantity, i.unit, i.lot_number, i.organic_status,
               i.expiration_date, i.composition, s.name AS supplier_name
        FROM inventory i
        LEFT JOIN suppliers s ON s.id = i.supplier_id
        WHERE i.category = 'Substraat' AND i.is_active = 1
        ORDER BY i.id DESC
    ");
    $substraat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$batches = [];
try {
    $stmt = $db->query("
        SELECT id, batch_code, crop_type, status FROM production_batches
        WHERE status IN ('SOWN','GERMINATING','GROWING','READY_TO_HARVEST')
        ORDER BY id DESC
    ");
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$suppliers = [];
try {
    $stmt = $db->query("SELECT id, name FROM suppliers ORDER BY name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<div class="card">
    <h2>🌱 <?= __('b15_title') ?></h2>
    <p class="text-muted"><?= __('b15_subtitle') ?></p>

    <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="flash <?= $is_warning ? 'flash-warning' : 'flash-success' ?>"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>➕ <?= __('b15_add_title') ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="add_substrate">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b15_label_name') ?></label>
                <input type="text" name="item_name" placeholder="Coco-verpakking / Hennep" required>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_qty') ?></label>
                <input type="number" step="0.1" name="quantity" placeholder="50" required>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_unit') ?></label>
                <input type="text" name="unit" value="stuks">
            </div>
            <div class="form-group">
                <label><?= __('b15_label_lot') ?></label>
                <input type="text" name="lot_number" placeholder="LOT-001">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b15_label_supplier') ?>
                    <a href="b18_suppliers.php" target="_blank" rel="noopener" style="font-weight:normal;font-size:0.85em">+ <?= __('b15_link_add_supplier') ?></a>
                </label>
                <select name="supplier_id">
                    <option value="0"><?= __('b15_no_supplier') ?></option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= intval($s['id']) ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_organic') ?></label>
                <select name="organic_status">
                    <option value="CERTIFIED">CERTIFIED</option>
                    <option value="PENDING">PENDING</option>
                    <option value="UNKNOWN">UNKNOWN</option>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_cert') ?></label>
                <input type="text" name="certificate_code" placeholder="SKAL-...">
            </div>
            <div class="form-group">
                <label><?= __('b15_label_expiry') ?></label>
                <input type="date" name="expiration_date">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b15_label_composition') ?></label>
                <input type="text" name="composition" placeholder="<?= __('b15_composition_ph') ?>">
            </div>
        </div>
        <button type="submit"><?= __('b15_btn_add') ?></button>
    </form>
</div>

<div class="card">
    <h3>🔗 <?= __('b15_assign_title') ?></h3>
    <?php if (empty($batches)): ?>
        <p class="text-muted"><?= __('b15_no_batch') ?></p>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="action" value="assign_batch">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b15_label_substrate') ?></label>
                <select name="inventory_id" required>
                    <option value=""><?= __('b15_pick_substrate') ?></option>
                    <?php foreach ($substraat as $s): ?>
                        <option value="<?= intval($s['id']) ?>"><?= htmlspecialchars($s['item_name']) ?> (<?= htmlspecialchars($s['organic_status']) ?>, <?= number_format(floatval($s['quantity']), 1) ?> <?= htmlspecialchars($s['unit']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_batch') ?></label>
                <select name="batch_id" required>
                    <option value=""><?= __('b15_pick_batch') ?></option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['batch_code']) ?> (<?= htmlspecialchars($b['status']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b15_label_qty_used') ?></label>
                <input type="number" step="0.1" name="quantity_used" placeholder="1" required>
            </div>
        </div>
        <button type="submit"><?= __('b15_btn_assign') ?></button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📋 <?= __('b15_list_title') ?></h3>
    <?php if (empty($substraat)): ?>
        <p class="text-muted"><?= __('b15_no_data') ?></p>
    <?php else: ?>
        <table>
            <thead><tr>
                <th><?= __('b15_col_name') ?></th>
                <th><?= __('b15_col_lot') ?></th>
                <th><?= __('b15_col_supplier') ?></th>
                <th><?= __('b15_col_stock') ?></th>
                <th><?= __('b15_col_organic') ?></th>
                <th><?= __('b15_col_expiry') ?></th>
                <th><?= __('b15_col_composition') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($substraat as $s): ?>
                <?php
                    $os = $s['organic_status'] ?? 'UNKNOWN';
                    $expired = !empty($s['expiration_date']) && $s['expiration_date'] < $today;
                    $validated = ($os === 'CERTIFIED' && !$expired);
                ?>
                <tr>
                    <td><?= htmlspecialchars($s['item_name']) ?></td>
                    <td><?= htmlspecialchars($s['lot_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['supplier_name'] ?? '-') ?></td>
                    <td><?= number_format(floatval($s['quantity']), 1) ?> <?= htmlspecialchars($s['unit']) ?></td>
                    <td>
                        <?php if ($validated): ?>
                            <span style="color:green;font-weight:bold">CERTIFIED</span>
                        <?php else: ?>
                            <span style="color:#e67e22;font-weight:bold"><?= htmlspecialchars($os) ?><?= $expired ? ' ⏳' : '' ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s['expiration_date'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['composition'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>