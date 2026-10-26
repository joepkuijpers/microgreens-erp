<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
$t0 = microtime(true);
$u = auth_current_user();
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='emoji_ui' && $u) {
    $val = empty($_POST['emoji_on']) ? 0 : 1;
    $_SESSION['emoji_ui'] = $val;
    $st = $db->prepare("UPDATE erp_users SET emoji_ui=:e, updated_at=datetime(now) WHERE id=:id");
    $st->execute([':e'=>$val, ':id'=>$u['id']]);
    auditLog($db, 'B26_EMOJI_UI', 'erp_users', $u['id'], 'UPDATE', 'Emoji-UI '.($val?'aan':'uit'), null, ['emoji_ui'=>$val]);
    header('Location: b26_system_settings.php'); exit;
}
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$me = is_array($u) ? (int)($u['id'] ?? 0) : 0;
$flash = '';
$flashClass = 'flash-info';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'config') {
        $lmin = (int)($_POST['light_min'] ?? 0);
        $lmax = (int)($_POST['light_max'] ?? 0);
        $tmin = (float)($_POST['temp_min'] ?? 0);
        $tmax = (float)($_POST['temp_max'] ?? 0);
        $hmin = (float)($_POST['humidity_min'] ?? 0);
        $hmax = (float)($_POST['humidity_max'] ?? 0);
        $ref = (int)($_POST['refresh_seconds'] ?? 0);
        $kwh = (float)($_POST['electricity_price_per_kwh'] ?? 0);
        if ($lmax <= $lmin) { $errors[] = __('b26_light_band'); }
        if ($tmax <= $tmin) { $errors[] = __('b26_temp_band'); }
        if ($hmax <= $hmin) { $errors[] = __('b26_hum_band'); }
        if ($ref < 1) { $errors[] = __('b26_refresh'); }
        if ($kwh <= 0) { $errors[] = __('b26_kwh'); }
        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE settings SET light_min=:lmin, light_max=:lmax, temp_min=:tmin, temp_max=:tmax, humidity_min=:hmin, humidity_max=:hmax, refresh_seconds=:ref, electricity_price_per_kwh=:kwh WHERE id=1");
            $stmt->bindValue(':lmin', $lmin, PDO::PARAM_INT);
            $stmt->bindValue(':lmax', $lmax, PDO::PARAM_INT);
            $stmt->bindValue(':tmin', $tmin);
            $stmt->bindValue(':tmax', $tmax);
            $stmt->bindValue(':hmin', $hmin);
            $stmt->bindValue(':hmax', $hmax);
            $stmt->bindValue(':ref', $ref, PDO::PARAM_INT);
            $stmt->bindValue(':kwh', $kwh);
            $stmt->execute();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B26_CONFIG_SAVE', 'settings', 1, 'UPDATE', 'Configuratie opgeslagen', null, ['light_min' => $lmin, 'light_max' => $lmax, 'temp_min' => $tmin, 'temp_max' => $tmax, 'humidity_min' => $hmin, 'humidity_max' => $hmax, 'refresh_seconds' => $ref, 'electricity_price_per_kwh' => $kwh, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b26_cfg_saved'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'param') {
        $key = trim($_POST['param_key'] ?? '');
        $val = trim($_POST['param_value'] ?? '');
        if ($key === '') { $errors[] = __('b26_key_required'); }
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO system_settings (key, value) VALUES (:k, :v) ON CONFLICT(key) DO UPDATE SET value=:v2");
            $stmt->bindValue(':k', $key, PDO::PARAM_STR);
            $stmt->bindValue(':v', $val, PDO::PARAM_STR);
            $stmt->bindValue(':v2', $val, PDO::PARAM_STR);
            $stmt->execute();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B26_PARAM_SAVE', 'system_settings', 0, 'UPSERT', 'Parameter opgeslagen', null, ['key' => $key, 'value' => $val, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b26_param_saved'); $flashClass = 'flash-success';
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'role') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['admin','operator','viewer'], true)) { $role = 'viewer'; }
        if ($uid === $me) { $flash = __('b26_self_block'); $flashClass = 'flash-warning'; }
        else {
            $stmt = $db->prepare("UPDATE erp_users SET role=:r, updated_at=datetime('now') WHERE id=:id");
            $stmt->bindValue(':r', $role, PDO::PARAM_STR);
            $stmt->bindValue(':id', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B26_USER_ROLE', 'erp_users', $uid, 'UPDATE', 'Rol gewijzigd', null, ['role' => $role, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b26_role_saved'); $flashClass = 'flash-success';
        }
    } elseif ($action === 'active') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === $me) { $flash = __('b26_self_block'); $flashClass = 'flash-warning'; }
        else {
            $cur = (int)$db->query("SELECT is_active FROM erp_users WHERE id=" . $uid)->fetchColumn();
            $new = $cur ? 0 : 1;
            $stmt = $db->prepare("UPDATE erp_users SET is_active=:a, updated_at=datetime('now') WHERE id=:id");
            $stmt->bindValue(':a', $new, PDO::PARAM_INT);
            $stmt->bindValue(':id', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $reg_ms = (int)round((microtime(true) - $t0) * 1000);
            auditLog($db, 'B26_USER_ACTIVE', 'erp_users', $uid, 'UPDATE', $new ? 'Gebruiker geactiveerd' : 'Gebruiker gedeactiveerd', null, ['is_active' => $new, 'operator' => $operator, 'reg_ms' => $reg_ms]);
            $flash = __('b26_active_saved'); $flashClass = 'flash-success';
        }
    }
}
$cfg = $db->query("SELECT light_min, light_max, temp_min, temp_max, humidity_min, humidity_max, refresh_seconds, electricity_price_per_kwh FROM settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
$params = $db->query("SELECT key, value FROM system_settings ORDER BY key ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query("SELECT id, username, display_name, role, is_active, locked_until FROM erp_users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
function b26RoleBadge($r) {
    $map = ['admin' => '#ede9fe:#5b21b6', 'operator' => '#dbeafe:#1e40af', 'viewer' => '#f3f4f6:#374151'];
    $c = $map[$r] ?? $map['viewer']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($r)) . '</span>';
}
$inp = 'width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db';
$card = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:20px';
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#0f766e,#1e293b);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b26_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b26_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

    <div style="<?php echo $card; ?>">
        <h2 style="margin:0 0 12px"><?php echo htmlspecialchars(__('b26_config')); ?></h2>
        <div style="margin:10px 0;padding:12px;border:1px solid #e5e7eb;border-radius:8px;"><strong>Mijn weergave</strong> <form method="post" style="display:inline-flex;gap:8px;align-items:center;margin-left:12px;"><input type="hidden" name="action" value="emoji_ui"><label style="display:inline-flex;gap:6px;align-items:center;"><input type="checkbox" name="emoji_on" value="1" > Iconen in interface tonen</label><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer;">Opslaan</button></form></div>
<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <input type="hidden" name="action" value="config">
            <div><label><?php echo htmlspecialchars(__('b26_light_min')); ?></label><input type="number" step="1" name="light_min" value="<?php echo (int)($cfg['light_min'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_light_max')); ?></label><input type="number" step="1" name="light_max" value="<?php echo (int)($cfg['light_max'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_temp_min')); ?></label><input type="number" step="0.1" name="temp_min" value="<?php echo htmlspecialchars($cfg['temp_min'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_temp_max')); ?></label><input type="number" step="0.1" name="temp_max" value="<?php echo htmlspecialchars($cfg['temp_max'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_hum_min')); ?></label><input type="number" step="0.1" name="humidity_min" value="<?php echo htmlspecialchars($cfg['humidity_min'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_hum_max')); ?></label><input type="number" step="0.1" name="humidity_max" value="<?php echo htmlspecialchars($cfg['humidity_max'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_refresh')); ?></label><input type="number" step="1" name="refresh_seconds" value="<?php echo (int)($cfg['refresh_seconds'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            <div><label><?php echo htmlspecialchars(__('b26_kwh')); ?></label><input type="number" step="0.01" name="electricity_price_per_kwh" value="<?php echo htmlspecialchars($cfg['electricity_price_per_kwh'] ?? 0); ?>" style="<?php echo $inp; ?>"></div>
            
            <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b26_save')); ?></button>
        </form>
    </div>

    <div style="<?php echo $card; ?>">
        <h2 style="margin:0 0 12px"><?php echo htmlspecialchars(__('b26_params')); ?></h2>
        <table style="width:100%;border-collapse:collapse;margin-bottom:12px">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px"><?php echo htmlspecialchars(__('b26_key')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b26_value')); ?></th></tr></thead>
            <tbody>
            <?php if (empty($params)): ?><tr><td colspan="2" style="padding:12px;color:#6b7280"><?php echo htmlspecialchars(__('b26_no_params')); ?></td></tr><?php else: foreach ($params as $p): ?>
                <tr style="border-top:1px solid #e5e7eb"><td style="padding:8px"><code><?php echo htmlspecialchars($p['key']); ?></code></td><td style="padding:8px"><?php echo htmlspecialchars($p['value']); ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <input type="hidden" name="action" value="param">
            <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b26_key')); ?></label><input name="param_key" style="<?php echo $inp; ?>"></div>
            <div style="flex:2;min-width:160px"><label><?php echo htmlspecialchars(__('b26_value')); ?></label><input name="param_value" style="<?php echo $inp; ?>"></div>
            <button type="submit" style="background:#0f766e;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b26_param_save')); ?></button>
        </form>
    </div>

    <div style="<?php echo $card; ?>">
        <h2 style="margin:0 0 12px"><?php echo htmlspecialchars(__('b26_users')); ?></h2>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px"><?php echo htmlspecialchars(__('b26_user')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b26_role')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b26_active')); ?></th><th style="padding:8px"></th></tr></thead>
            <tbody>
            <?php foreach ($users as $usr): $isMe = ((int)$usr['id'] === $me); ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:8px"><strong><?php echo htmlspecialchars($usr['display_name']); ?></strong> <span style="color:#6b7280">(<?php echo htmlspecialchars($usr['username']); ?>)</span><?php if ($isMe): ?> <span style="color:#0f766e;font-weight:700"><?php echo htmlspecialchars(__('b26_you')); ?></span><?php endif; ?></td>
                    <td style="padding:8px"><?php b26RoleBadge($usr['role']); ?></td>
                    <td style="padding:8px"><?php echo $usr['is_active'] ? '<span style="color:#16a34a;font-weight:700">' . htmlspecialchars(__('b26_on')) . '</span>' : '<span style="color:#991b1b;font-weight:700">' . htmlspecialchars(__('b26_off')) . '</span>'; ?></td>
                    <td style="padding:8px;text-align:right">
                        <?php if ($isMe): ?><span style="color:#9ca3af;font-size:13px"><?php echo htmlspecialchars(__('b26_locked')); ?></span><?php else: ?>
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?php echo (int)$usr['id']; ?>"><select name="role" style="padding:6px;border-radius:8px;border:1px solid #d1d5db"><option value="admin" <?php echo $usr['role']==='admin'?'selected':''; ?>><?php echo htmlspecialchars(__('admin')); ?></option><option value="operator" <?php echo $usr['role']==='operator'?'selected':''; ?>><?php echo htmlspecialchars(__('operator')); ?></option><option value="viewer" <?php echo $usr['role']==='viewer'?'selected':''; ?>><?php echo htmlspecialchars(__('viewer')); ?></option></select><button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer;margin-left:6px"><?php echo htmlspecialchars(__('b26_save')); ?></button></form>
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="active"><input type="hidden" name="user_id" value="<?php echo (int)$usr['id']; ?>"><button type="submit" style="background:<?php echo $usr['is_active'] ? '#dc2626' : '#16a34a'; ?>;color:#fff;border:none;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer;margin-left:6px"><?php echo $usr['is_active'] ? htmlspecialchars(__('b26_off')) : htmlspecialchars(__('b26_on')); ?></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
