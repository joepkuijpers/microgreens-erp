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
$endpoints = ['status','system','dashboard','sensors','sensor_health','climate','lighting','water','automation','scheduler','gpio','relay_test','override','rack_status','history','get_dose','hardware_health','start_freeze_dry','watchdog','widget_growth','widget_seed'];
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'test') {
        $ep = preg_replace('/[^a-z_]/', '', strtolower($_POST['endpoint'] ?? ''));
        if ($ep !== '' && in_array($ep, $endpoints, true)) {
            $url = 'https://127.0.0.1/api/' . $ep . '.php';
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false], 'http' => ['timeout' => 4]]);
            $body = @file_get_contents($url, false, $ctx);
            $code = 0;
            if (isset($http_response_header[0]) && preg_match('/(\d{3})/', $http_response_header[0], $m)) { $code = (int)$m[1]; }
            $testResult = ['endpoint' => $ep, 'code' => $code, 'ok' => ($body !== false && $code >= 200 && $code < 400), 'preview' => $body === false ? '' : substr(trim($body), 0, 200)];
        }
    } elseif ($action === 'add') {
        $key = trim($_POST['src_key'] ?? '');
        $name = trim($_POST['src_name'] ?? '');
        $type = $_POST['src_type'] ?? 'EXTERNAL';
        $url = trim($_POST['src_url'] ?? '');
        $note = trim($_POST['src_note'] ?? '');
        if ($key === '') { $errors[] = __('b27_key_required'); }
        if ($name === '') { $errors[] = __('b27_name_required'); }
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("INSERT INTO integration_sources (key, name, source_type, endpoint_url, source_note) VALUES (:k, :n, :t, :u, :no)");
                $stmt->bindValue(':k', $key, PDO::PARAM_STR);
                $stmt->bindValue(':n', $name, PDO::PARAM_STR);
                $stmt->bindValue(':t', $type, PDO::PARAM_STR);
                $stmt->bindValue(':u', $url !== '' ? $url : null, $url !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':no', $note !== '' ? $note : null, $note !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->execute();
                $nid = (int)$db->lastInsertId();
                $reg_ms = (int)round((microtime(true) - $t0) * 1000);
                auditLog($db, 'B27_SOURCE_ADD', 'integration_sources', $nid, 'CREATE', 'Bron geregistreerd', null, ['key' => $key, 'name' => $name, 'operator' => $operator, 'reg_ms' => $reg_ms]);
                $flash = __('b27_src_added'); $flashClass = 'flash-success';
            } catch (Exception $e) { $flash = __('b27_dup_key'); $flashClass = 'flash-warning'; }
        } else { $flash = implode(' · ', $errors); $flashClass = 'flash-error'; }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $cur = (int)$db->query("SELECT is_active FROM integration_sources WHERE id=" . $id)->fetchColumn();
        $new = $cur ? 0 : 1;
        $stmt = $db->prepare("UPDATE integration_sources SET is_active=:a WHERE id=:id");
        $stmt->bindValue(':a', $new, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B27_SOURCE_TOGGLE', 'integration_sources', $id, 'UPDATE', $new ? 'Bron geactiveerd' : 'Bron gedeactiveerd', null, ['is_active' => $new, 'operator' => $operator]);
        $flash = __('b27_toggled'); $flashClass = 'flash-success';
    } elseif ($action === 'sync') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE integration_sources SET sync_status='SYNCED', last_sync_at=datetime('now') WHERE id=:id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        auditLog($db, 'B27_SOURCE_SYNC', 'integration_sources', $id, 'UPDATE', 'Sync gemarkeerd', null, ['operator' => $operator]);
        $flash = __('b27_synced'); $flashClass = 'flash-success';
    }
}
$sources = $db->query("SELECT * FROM integration_sources ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$activeCount = 0;
foreach ($sources as $s) { if ($s['is_active']) { $activeCount++; } }
$card = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:20px';
$inp = 'width:100%;padding:9px;border-radius:10px;border:1px solid #d1d5db';
function b27SyncBadge($s) {
    $map = ['SYNCED' => '#dcfce7:#166534', 'PENDING' => '#fef3c7:#92400e', 'INACTIVE' => '#f3f4f6:#374151', 'ERROR' => '#fee2e2:#991b1b'];
    $c = $map[$s] ?? $map['INACTIVE']; list($bg, $fg) = explode(':', $c);
    echo '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px;background:' . $bg . ';color:' . $fg . '">' . htmlspecialchars(__($s)) . '</span>';
}
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#1e293b,#0f766e);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b27_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b27_subtitle')); ?></p>
    </div>
    <?php if ($flash !== ''): ?><div class="<?php echo $flashClass; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <?php if ($activeCount === 0): ?><div class="flash-warning"><?php echo htmlspecialchars(__('b27_no_external')); ?></div><?php endif; ?>

    <div style="<?php echo $card; ?>">
        <h2 style="margin:0 0 12px"><?php echo htmlspecialchars(__('b27_endpoints')); ?> (<?php echo count($endpoints); ?>)</h2>
        <p style="color:#6b7280;margin:0 0 12px"><?php echo htmlspecialchars(__('b27_endpoint_hint')); ?></p>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px">Endpoint</th><th style="padding:8px"><?php echo htmlspecialchars(__('b27_present')); ?></th><th style="padding:8px"></th></tr></thead>
            <tbody>
            <?php foreach ($endpoints as $ep): $exists = file_exists(__DIR__ . '/api/' . $ep . '.php'); ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:8px"><code>/api/<?php echo htmlspecialchars($ep); ?>.php</code></td>
                    <td style="padding:8px"><?php echo $exists ? '<span style="color:#16a34a;font-weight:700">' . htmlspecialchars(__('b27_yes')) . '</span>' : '<span style="color:#991b1b;font-weight:700">' . htmlspecialchars(__('b27_no')) . '</span>'; ?></td>
                    <td style="padding:8px;text-align:right">
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="test"><input type="hidden" name="endpoint" value="<?php echo htmlspecialchars($ep); ?>"><button type="submit" style="background:#0f766e;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b27_test')); ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($testResult !== null): ?>
            <div style="margin-top:12px;padding:12px;border-radius:10px;background:<?php echo $testResult['ok'] ? '#f0fdf4' : '#fef2f2'; ?>;border:1px solid <?php echo $testResult['ok'] ? '#bbf7d0' : '#fecaca'; ?>">
                <strong><?php echo htmlspecialchars(__('b27_test_result')); ?>:</strong> /api/<?php echo htmlspecialchars($testResult['endpoint']); ?>.php → HTTP <?php echo (int)$testResult['code']; ?>
                <?php if ($testResult['preview'] !== ''): ?><pre style="margin:8px 0 0;font-size:12px;white-space:pre-wrap;word-break:break-all;color:#374151;max-height:160px;overflow:auto"><?php echo htmlspecialchars($testResult['preview']); ?></pre><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="<?php echo $card; ?>">
        <h2 style="margin:0 0 12px"><?php echo htmlspecialchars(__('b27_sources')); ?></h2>
        <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px">
            <input type="hidden" name="action" value="add">
            <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b27_key')); ?></label><input name="src_key" style="<?php echo $inp; ?>"></div>
            <div style="flex:2;min-width:140px"><label><?php echo htmlspecialchars(__('b27_name')); ?></label><input name="src_name" style="<?php echo $inp; ?>"></div>
            <div style="flex:1;min-width:120px"><label><?php echo htmlspecialchars(__('b27_type')); ?></label><select name="src_type" style="<?php echo $inp; ?>"><option value="EXTERNAL"><?php echo htmlspecialchars(__('EXTERNAL')); ?></option><option value="SENSOR"><?php echo htmlspecialchars(__('SENSOR')); ?></option><option value="CERTIFICATION"><?php echo htmlspecialchars(__('CERTIFICATION')); ?></option><option value="EXPORT"><?php echo htmlspecialchars(__('EXPORT')); ?></option></select></div>
            <div style="flex:2;min-width:140px"><label><?php echo htmlspecialchars(__('b27_url')); ?></label><input name="src_url" style="<?php echo $inp; ?>"></div>
            <div style="flex:2;min-width:140px"><label><?php echo htmlspecialchars(__('b27_note')); ?></label><input name="src_note" style="<?php echo $inp; ?>"></div>
            <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:800;cursor:pointer"><?php echo htmlspecialchars(__('b27_add')); ?></button>
        </form>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:8px"><?php echo htmlspecialchars(__('b27_name')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b27_type')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b27_sync')); ?></th><th style="padding:8px"><?php echo htmlspecialchars(__('b27_last_sync')); ?></th><th style="padding:8px"></th></tr></thead>
            <tbody>
            <?php if (empty($sources)): ?><tr><td colspan="5" style="padding:14px;color:#6b7280"><?php echo htmlspecialchars(__('b27_no_sources')); ?></td></tr><?php else: foreach ($sources as $s): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:8px"><strong><?php echo htmlspecialchars($s['name']); ?></strong> <span style="color:#6b7280"><code><?php echo htmlspecialchars($s['key']); ?></code></span><?php if (!empty($s['source_note'])): ?><div style="color:#6b7280;font-size:13px"><?php echo htmlspecialchars($s['source_note']); ?></div><?php endif; ?></td>
                    <td style="padding:8px;color:#6b7280"><?php echo htmlspecialchars(__($s['source_type'])); ?></td>
                    <td style="padding:8px"><?php b27SyncBadge($s['sync_status']); ?></td>
                    <td style="padding:8px;color:#6b7280"><?php echo htmlspecialchars($s['last_sync_at'] ?? '—'); ?></td>
                    <td style="padding:8px;text-align:right">
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="sync"><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"><button type="submit" style="background:#0f766e;color:#fff;border:none;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer"><?php echo htmlspecialchars(__('b27_sync')); ?></button></form>
                        <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"><button type="submit" style="background:<?php echo $s['is_active'] ? '#dc2626' : '#16a34a'; ?>;color:#fff;border:none;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer;margin-left:4px"><?php echo $s['is_active'] ? htmlspecialchars(__('b27_on')) : htmlspecialchars(__('b27_off')); ?></button></form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
