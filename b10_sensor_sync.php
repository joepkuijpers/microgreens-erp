<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';

$t0 = microtime(true);
$errors = [];
$success_msg = '';
$is_fault = false;

// --- POST: Handmatige meting ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_reading') {
    $rack_id = trim($_POST['rack_id'] ?? '');
    $temp = $_POST['temperature'] ?? '';
    $hum = $_POST['humidity'] ?? '';
    $light = $_POST['light_lux'] ?? '';

    // Validatie
    if ($rack_id === '') {
        $errors[] = __('b10_err_no_rack');
    }
    if ($temp === '' || !is_numeric($temp)) {
        $errors[] = __('b10_err_no_temp');
    }
    if ($hum === '' || !is_numeric($hum)) {
        $errors[] = __('b10_err_no_hum');
    }

    // Status bepalen
    $status = 'OK';
    $temp_f = floatval($temp);
    $hum_f = floatval($hum);
    $light_f = $light !== '' ? intval($light) : 0;

    if ($temp_f < 0 || $temp_f > 50) $status = 'FAULT_TEMP';
    if ($hum_f < 0 || $hum_f > 100) $status = 'FAULT_HUM';
    if ($temp_f == 0 && $hum_f == 0) $status = 'FAULT_NO_DATA';

    $is_fault = ($status !== 'OK');

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Schrijf naar sensor_log
            $stmt = $db->prepare("
                INSERT INTO sensor_log (timestamp, temperature, humidity, pressure, light, location_id, rack_id, batch_id, status, source)
                VALUES (datetime('now'), ?, ?, 0, ?, ?, ?, NULL, ?, 'MANUAL')
            ");
            $stmt->execute([$temp_f, $hum_f, $light_f, $rack_id, $rack_id, $status]);
            $sensor_id = intval($db->lastInsertId());

            // 2. Bridge: ook naar climate_data (zodat B03/B06/traceability het ziet)
            $stmt2 = $db->prepare("
                INSERT INTO climate_data (timestamp, temperature, humidity, rack_id)
                VALUES (datetime('now'), ?, ?, ?)
            ");
            $stmt2->execute([$temp_f, $hum_f, $rack_id]);

            $db->commit();

            // 3. Audit
            $t1 = microtime(true);
            $reg_ms = round(($t1 - $t0) * 1000);
            $u = auth_current_user();
            $operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';

            auditLog($db, 'B10_SENSOR_READING', 'sensor_log', $sensor_id, 'INSERT',
                __('b10_audit_reading'), null,
                ['rack_id' => $rack_id, 'temperature' => $temp_f, 'humidity' => $hum_f, 'light_lux' => $light_f, 'status' => $status, 'operator' => $operator, 'reg_ms' => $reg_ms]
            );

            // 4. Sad path: FAULT -> system_alert (B28-koppeling)
            if ($is_fault) {
                $stmt3 = $db->prepare("
                    INSERT INTO system_alerts (alert_type, severity, message, related_id, resolved)
                    VALUES ('SENSOR_FAULT', 'WARNING', ?, ?, 0)
                ");
                $alert_msg = __('b10_alert_fault') . ' - Rack: ' . $rack_id . ', Status: ' . $status;
                $stmt3->execute([$alert_msg, $sensor_id]);
                auditLog($db, 'B10_ALERT_CREATED', 'system_alerts', intval($db->lastInsertId()), 'INSERT',
                    __('b10_audit_alert'), null, ['rack_id' => $rack_id, 'status' => $status, 'operator' => $operator]);
            }

            // 5. Melding (oranje bij fault, groen bij OK)
            if ($is_fault) {
                $success_msg = __('b10_alert_fault') . ': ' . $status . ' (' . $reg_ms . ' ms)';
            } else {
                $success_msg = __('b10_success_reading') . ' (' . $reg_ms . ' ms)';
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = __('b10_err_save') . ': ' . $e->getMessage();
        }
    }
}

// --- Data ophalen ---
$recent = [];
try {
    $stmt = $db->query("
        SELECT sl.id, sl.timestamp, sl.temperature, sl.humidity, sl.light, sl.rack_id, sl.status, sl.source
        FROM sensor_log sl
        WHERE sl.source != 'TEST'
        ORDER BY sl.id DESC LIMIT 20
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$racks = [];
try {
    $stmt = $db->query("SELECT id, name FROM racks ORDER BY name");
    $racks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$rack_status = [];
try {
    $stmt = $db->query("
        SELECT sl.rack_id, sl.status, sl.timestamp, sl.temperature, sl.humidity
        FROM sensor_log sl
        WHERE sl.source != 'TEST' AND sl.rack_id IS NOT NULL
        GROUP BY sl.rack_id
        HAVING sl.id = MAX(sl.id)
        ORDER BY sl.rack_id
    ");
    $rack_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<div class="card">
    <h2>📡 <?= __('b10_title') ?></h2>
    <p class="text-muted"><?= __('b10_subtitle') ?></p>

    <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="flash <?= $is_fault ? 'flash-warning' : 'flash-success' ?>"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <h3><?= __('b10_form_title') ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="log_reading">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b10_label_rack') ?></label>
                <select name="rack_id" required>
                    <option value=""><?= __('b10_choose_rack') ?></option>
                    <?php foreach ($racks as $r): ?>
                        <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b10_label_temp') ?></label>
                <input type="number" step="0.1" name="temperature" placeholder="22.5" required>
            </div>
            <div class="form-group">
                <label><?= __('b10_label_hum') ?></label>
                <input type="number" step="0.1" name="humidity" placeholder="65.0" required>
            </div>
            <div class="form-group">
                <label><?= __('b10_label_light') ?></label>
                <input type="number" name="light_lux" placeholder="0">
            </div>
        </div>
        <button type="submit"><?= __('b10_btn_save') ?></button>
    </form>
</div>

<div class="card">
    <h3>📊 <?= __('b10_status_title') ?></h3>
    <?php if (empty($rack_status)): ?>
        <p class="text-muted"><?= __('b10_no_data') ?></p>
    <?php else: ?>
        <table>
            <thead><tr>
                <th><?= __('b10_col_rack') ?></th>
                <th><?= __('b10_col_time') ?></th>
                <th><?= __('b10_col_temp') ?></th>
                <th><?= __('b10_col_hum') ?></th>
                <th><?= __('b10_col_status') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rack_status as $rs): ?>
                <tr>
                    <td><?= htmlspecialchars($rs['rack_id']) ?></td>
                    <td><?= htmlspecialchars($rs['timestamp']) ?></td>
                    <td><?= number_format(floatval($rs['temperature']), 1) ?>&deg;C</td>
                    <td><?= number_format(floatval($rs['humidity']), 1) ?>%</td>
                    <td>
                        <?php if ($rs['status'] === 'OK'): ?>
                            <span style="color:green;font-weight:bold">OK</span>
                        <?php else: ?>
                            <span style="color:red;font-weight:bold"><?= htmlspecialchars($rs['status']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📋 <?= __('b10_recent_title') ?></h3>
    <?php if (empty($recent)): ?>
        <p class="text-muted"><?= __('b10_no_data') ?></p>
    <?php else: ?>
        <table>
            <thead><tr>
                <th><?= __('b10_col_time') ?></th>
                <th><?= __('b10_col_rack') ?></th>
                <th><?= __('b10_col_temp') ?></th>
                <th><?= __('b10_col_hum') ?></th>
                <th><?= __('b10_col_light') ?></th>
                <th><?= __('b10_col_status') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['timestamp']) ?></td>
                    <td><?= htmlspecialchars($r['rack_id'] ?? '-') ?></td>
                    <td><?= number_format(floatval($r['temperature']), 1) ?>&deg;C</td>
                    <td><?= number_format(floatval($r['humidity']), 1) ?>%</td>
                    <td><?= $r['light'] ? intval($r['light']) . ' lx' : '-' ?></td>
                    <td>
                        <?php if ($r['status'] === 'OK'): ?>
                            <span style="color:green">OK</span>
                        <?php else: ?>
                            <span style="color:red"><?= htmlspecialchars($r['status']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>