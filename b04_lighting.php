<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';

$t0 = microtime(true);
$errors = [];
$success_msg = '';
$is_fault = false;
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';

// --- HUIDIGE CONFIG ---
$config = ['zone_name' => 'Global', 'target_ppfd_min' => 150, 'target_ppfd_max' => 250,
           'spectrum_factor' => 54.0, 'gpio_pin' => 17, 'light_min_lux' => 2000, 'light_max_lux' => 10000];
try {
    $stmt = $db->query("SELECT * FROM lighting_configs ORDER BY id LIMIT 1");
    $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cfg) $config = $cfg;
} catch (Exception $e) {}

// --- POST: CONFIG BEWERKEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $ppfd_min = $_POST['target_ppfd_min'] ?? '';
    $ppfd_max = $_POST['target_ppfd_max'] ?? '';
    $factor = $_POST['spectrum_factor'] ?? '';
    $gpio = $_POST['gpio_pin'] ?? '';

    if ($ppfd_min === '' || !is_numeric($ppfd_min)) $errors[] = __('b04_err_ppfd_min');
    if ($ppfd_max === '' || !is_numeric($ppfd_max)) $errors[] = __('b04_err_ppfd_max');
    if ($factor === '' || !is_numeric($factor) || floatval($factor) <= 0) $errors[] = __('b04_err_factor');
    if (is_numeric($ppfd_min) && is_numeric($ppfd_max) && floatval($ppfd_min) >= floatval($ppfd_max)) {
        $errors[] = __('b04_err_range');
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                UPDATE lighting_configs
                SET target_ppfd_min = ?, target_ppfd_max = ?, spectrum_factor = ?, gpio_pin = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([floatval($ppfd_min), floatval($ppfd_max), floatval($factor), intval($gpio), intval($config['id'])]);
            $db->commit();
            auditLog($db, 'B04_CONFIG_UPDATE', 'lighting_configs', intval($config['id']), 'UPDATE',
                __('b04_audit_config'), null,
                ['target_ppfd_min' => floatval($ppfd_min), 'target_ppfd_max' => floatval($ppfd_max), 'spectrum_factor' => floatval($factor), 'gpio_pin' => intval($gpio), 'operator' => $operator]);
            $success_msg = __('b04_success_config');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = __('b04_err_save') . ': ' . $e->getMessage();
        }
    }
}

// --- POST: HANDMATIGE LICHTMETING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_lux') {
    $rack_id = trim($_POST['rack_id'] ?? '');
    $lux = $_POST['lux'] ?? '';

    if ($rack_id === '') $errors[] = __('b04_err_no_rack');
    if ($lux === '' || !is_numeric($lux)) $errors[] = __('b04_err_no_lux');

    $lux_f = floatval($lux);
    $factor = floatval($config['spectrum_factor']) ?: 54.0;
    $ppfd = $lux_f / $factor;
    $tmin = floatval($config['target_ppfd_min']);
    $tmax = floatval($config['target_ppfd_max']);

    // Beslissing (conform lighting_engine)
    $action = 'NO_ACTION';
    $status = 'OK';
    if ($ppfd < $tmin) { $action = 'TURN_ON'; $status = 'TE_DONKER'; }
    elseif ($ppfd > $tmax) { $action = 'TURN_OFF'; $status = 'TE_LICHT'; }
    $is_fault = ($status !== 'OK');

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Bron-meting -> sensor_log.light (source=MANUAL)
            $stmt = $db->prepare("
                INSERT INTO sensor_log (timestamp, temperature, humidity, pressure, light, location_id, rack_id, batch_id, status, source)
                VALUES (datetime('now'), 0, 0, 0, ?, ?, ?, NULL, 'OK', 'MANUAL')
            ");
            $stmt->execute([$lux_f, $rack_id, $rack_id]);

            // 2. Beslissing -> lighting_log
            $stmt2 = $db->prepare("
                INSERT INTO lighting_log (timestamp, measured_lux, calculated_ppfd, target_min, target_max, action_taken, error_message, rack_id, source)
                VALUES (datetime('now'), ?, ?, ?, ?, ?, NULL, ?, 'MANUAL')
            ");
            $stmt2->execute([$lux_f, $ppfd, $tmin, $tmax, $action, $rack_id]);
            $log_id = intval($db->lastInsertId());

            $db->commit();

            // 3. Audit
            $t1 = microtime(true);
            $reg_ms = round(($t1 - $t0) * 1000);
            auditLog($db, 'B04_LIGHT_READING', 'lighting_log', $log_id, 'INSERT',
                __('b04_audit_reading'), null,
                ['rack_id' => $rack_id, 'lux' => $lux_f, 'ppfd' => round($ppfd, 1), 'action' => $action, 'status' => $status, 'operator' => $operator, 'reg_ms' => $reg_ms]);

            // 4. Buiten band -> B28 alert
            if ($is_fault) {
                $stmt3 = $db->prepare("
                    INSERT INTO system_alerts (alert_type, severity, message, related_id, resolved)
                    VALUES ('LIGHT_FAULT', 'WARNING', ?, ?, 0)
                ");
                $alert_msg = __('b04_alert_fault') . ' - Rack: ' . $rack_id . ', PPFD: ' . round($ppfd, 1) . ' (' . $status . ')';
                $stmt3->execute([$alert_msg, $log_id]);
                auditLog($db, 'B04_ALERT_CREATED', 'system_alerts', intval($db->lastInsertId()), 'INSERT',
                    __('b04_audit_alert'), null, ['rack_id' => $rack_id, 'status' => $status, 'operator' => $operator]);
            }

            if ($is_fault) {
                $success_msg = __('b04_alert_fault') . ': ' . $status . ' (PPFD ' . round($ppfd, 1) . ', ' . $reg_ms . ' ms)';
            } else {
                $success_msg = __('b04_success_reading') . ' (PPFD ' . round($ppfd, 1) . ', ' . $reg_ms . ' ms)';
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = __('b04_err_save') . ': ' . $e->getMessage();
        }
    }
}

// --- DATA OPHALEN ---
$racks = [];
try {
    $stmt = $db->query("SELECT id, name FROM racks ORDER BY name");
    $racks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Laatste lichtmeting per rack (geen TEST)
$rack_status = [];
try {
    $stmt = $db->query("
        SELECT ll.rack_id, ll.measured_lux, ll.calculated_ppfd, ll.action_taken, ll.timestamp
        FROM lighting_log ll
        WHERE ll.source != 'TEST' AND ll.rack_id IS NOT NULL
        GROUP BY ll.rack_id
        HAVING ll.id = MAX(ll.id)
        ORDER BY ll.rack_id
    ");
    $rack_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Recente beslissingen (geen TEST)
$recent = [];
try {
    $stmt = $db->query("
        SELECT id, timestamp, measured_lux, calculated_ppfd, action_taken, rack_id
        FROM lighting_log
        WHERE source != 'TEST'
        ORDER BY id DESC LIMIT 20
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<div class="card">
    <h2>💡 <?= __('b04_title') ?></h2>
    <p class="text-muted"><?= __('b04_subtitle') ?></p>

    <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="flash <?= $is_fault ? 'flash-warning' : 'flash-success' ?>"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📡 <?= __('b04_measure_title') ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="log_lux">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b04_label_rack') ?></label>
                <select name="rack_id" required>
                    <option value=""><?= __('b04_choose_rack') ?></option>
                    <?php foreach ($racks as $r): ?>
                        <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('b04_label_lux') ?></label>
                <input type="number" step="1" name="lux" placeholder="5000" required>
            </div>
        </div>
        <button type="submit"><?= __('b04_btn_measure') ?></button>
    </form>
    <p class="text-muted"><?= __('b04_ppfd_hint') ?>: <?= htmlspecialchars($config['target_ppfd_min']) ?>–<?= htmlspecialchars($config['target_ppfd_max']) ?> PPFD (<?= __('b04_factor') ?> <?= htmlspecialchars($config['spectrum_factor']) ?>)</p>
</div>

<div class="card">
    <h3>⚙️ <?= __('b04_config_title') ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="save_config">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('b04_label_ppfd_min') ?></label>
                <input type="number" step="1" name="target_ppfd_min" value="<?= htmlspecialchars($config['target_ppfd_min']) ?>" required>
            </div>
            <div class="form-group">
                <label><?= __('b04_label_ppfd_max') ?></label>
                <input type="number" step="1" name="target_ppfd_max" value="<?= htmlspecialchars($config['target_ppfd_max']) ?>" required>
            </div>
            <div class="form-group">
                <label><?= __('b04_label_factor') ?></label>
                <input type="number" step="0.1" name="spectrum_factor" value="<?= htmlspecialchars($config['spectrum_factor']) ?>" required>
            </div>
            <div class="form-group">
                <label><?= __('b04_label_gpio') ?></label>
                <input type="number" step="1" name="gpio_pin" value="<?= htmlspecialchars($config['gpio_pin']) ?>">
            </div>
        </div>
        <button type="submit"><?= __('b04_btn_save_config') ?></button>
    </form>
</div>

<div class="card">
    <h3>📊 <?= __('b04_status_title') ?></h3>
    <?php if (empty($rack_status)): ?>
        <p class="text-muted"><?= __('b04_no_data') ?></p>
    <?php else: ?>
        <table>
            <thead><tr>
                <th><?= __('b04_col_rack') ?></th>
                <th><?= __('b04_col_time') ?></th>
                <th><?= __('b04_col_lux') ?></th>
                <th><?= __('b04_col_ppfd') ?></th>
                <th><?= __('b04_col_action') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rack_status as $rs): ?>
                <tr>
                    <td><?= htmlspecialchars($rs['rack_id']) ?></td>
                    <td><?= htmlspecialchars($rs['timestamp']) ?></td>
                    <td><?= number_format(floatval($rs['measured_lux']), 0) ?> lx</td>
                    <td><?= number_format(floatval($rs['calculated_ppfd']), 1) ?></td>
                    <td>
                        <?php if ($rs['action_taken'] === 'NO_ACTION'): ?>
                            <span style="color:green;font-weight:bold">OK</span>
                        <?php else: ?>
                            <span style="color:red;font-weight:bold"><?= htmlspecialchars($rs['action_taken']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📋 <?= __('b04_recent_title') ?></h3>
    <?php if (empty($recent)): ?>
        <p class="text-muted"><?= __('b04_no_data') ?></p>
    <?php else: ?>
        <table>
            <thead><tr>
                <th><?= __('b04_col_time') ?></th>
                <th><?= __('b04_col_rack') ?></th>
                <th><?= __('b04_col_lux') ?></th>
                <th><?= __('b04_col_ppfd') ?></th>
                <th><?= __('b04_col_action') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['timestamp']) ?></td>
                    <td><?= htmlspecialchars($r['rack_id'] ?? '-') ?></td>
                    <td><?= number_format(floatval($r['measured_lux']), 0) ?> lx</td>
                    <td><?= number_format(floatval($r['calculated_ppfd']), 1) ?></td>
                    <td>
                        <?php if ($r['action_taken'] === 'NO_ACTION'): ?>
                            <span style="color:green">OK</span>
                        <?php else: ?>
                            <span style="color:red"><?= htmlspecialchars($r['action_taken']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>