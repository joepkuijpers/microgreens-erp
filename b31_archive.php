<?php
require_once __DIR__ . '/app/includes/header.php';

// ── POST-dispatch ──────────────────────────────────────────────
$flash = ''; $flashType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $system = ($_POST['system'] ?? 'production') === 'grow' ? 'grow' : 'production';
    $table  = $system === 'grow' ? 'grow_batches' : 'production_batches';
    $id     = (int)($_POST['id'] ?? 0);
    $op     = function_exists('auth_current_user') ? (auth_current_user()['username'] ?? 'onbekend') : 'onbekend';

    // batch opzoeken (alleen niet-gearchiveerd)
    $st = $db->prepare("SELECT id, batch_code FROM $table WHERE id = ? AND archived_at IS NULL");
    $st->execute([$id]);
    $batch = $st->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        $flash = __('b31_not_found'); $flashType = 'error';
    } elseif ($action === 'archive') {
        $db->prepare("UPDATE $table SET archived_at = datetime('now') WHERE id = ?")->execute([$id]);
        $db->prepare("INSERT INTO deletion_log (action, system, batch_id, batch_code, operator, related_rows)
                      VALUES ('archive', ?, ?, ?, ?, 0)")
           ->execute([$system, $id, $batch['batch_code'], $op]);
        if (function_exists('auditLog')) { auditLog($db, 'b31_archive', "Batch {$batch['batch_code']} gearchiveerd"); }
        $flash = sprintf(__('b31_archived'), $batch['batch_code']); $flashType = 'ok';
    } elseif ($action === 'unarchive') {
        $db->prepare("UPDATE $table SET archived_at = NULL WHERE id = ?")->execute([$id]);
        $flash = sprintf(__('b31_unarchived'), $batch['batch_code']); $flashType = 'ok';
    } elseif ($action === 'hard_delete') {
        // EXTRA CHECK: code exact overgetypt?
        $confirm = trim($_POST['confirm_code'] ?? '');
        if ($confirm !== $batch['batch_code']) {
            $flash = __('b31_confirm_mismatch'); $flashType = 'error';
        } else {
            $count = 0;
            if ($system === 'production') {
                $db->beginTransaction();
                $db->prepare("DELETE FROM freeze_dry_processes WHERE production_output_id IN
                              (SELECT id FROM production_outputs WHERE production_batch_id = ?)")->execute([$id]);
                $db->prepare("DELETE FROM invoice_items WHERE production_batch_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM harvest_logs WHERE production_batch_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM production_outputs WHERE production_batch_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM batch_materials WHERE batch_id = ?")->execute([$id]); // FK-loos, expliciet
                $db->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
                $db->commit();
            } else {
                $db->beginTransaction();
                foreach (['brix_measurement_readings','brix_measurement_sessions','labor_entries',
                          'physical_units','spatial_allocations'] as $t) {
                    $db->prepare("DELETE FROM $t WHERE grow_batch_id = ?")->execute([$id]);
                }
                $db->prepare("DELETE FROM batch_materials WHERE batch_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
                $db->commit();
            }
            $db->prepare("INSERT INTO deletion_log (action, system, batch_id, batch_code, operator, related_rows)
                          VALUES ('hard_delete', ?, ?, ?, ?, ?)")
               ->execute([$system, $id, $batch['batch_code'], $op, $count]);
            if (function_exists('auditLog')) { auditLog($db, 'b31_hard_delete', "Batch {$batch['batch_code']} definitief gewist"); }
            $flash = sprintf(__('b31_deleted'), $batch['batch_code']); $flashType = 'ok';
        }
    }
}

// ── Data ophalen ───────────────────────────────────────────────
$prod = $db->query("SELECT id, batch_code, status, archived_at FROM production_batches ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$grow = $db->query("SELECT id, batch_code, status, archived_at FROM grow_batches ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <h2><?= __('b31_title') ?></h2>

    <?php if ($flash): ?>
        <div style="padding:10px 14px; border-radius:8px; margin-bottom:1rem;
                    background:<?= $flashType==='ok' ? '#d1fae5' : '#fee2e2' ?>;
                    color:<?= $flashType==='ok' ? '#065f46' : '#991b1b' ?>;">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <p style="font-size:0.85rem; color:#718096;"><?= __('b31_intro') ?></p>

    <?php foreach (['production' => $prod, 'grow' => $grow] as $sys => $rows): ?>
        <h3 style="margin-top:1.4rem;"><?= __('b31_sys_' . $sys) ?></h3>
        <?php if (!$rows): ?><p style="color:#a0aec0;">—</p><?php endif; ?>
        <?php foreach ($rows as $b): $arch = !empty($b['archived_at']); ?>
            <div style="display:flex; justify-content:space-between; align-items:center;
                        padding:8px 0; border-bottom:1px solid #edf2f7;
                        <?= $arch ? 'opacity:0.55;' : '' ?>">
                <span>
                    <strong><?= htmlspecialchars($b['batch_code']) ?></strong>
                    <span style="font-size:0.75rem; color:#718096;"> · <?= htmlspecialchars($b['status']) ?></span>
                    <?php if ($arch): ?>
                        <span style="font-size:0.7rem; background:#e2e8f0; color:#475569;
                                     border-radius:8px; padding:2px 8px; margin-left:6px;">
                            <?= __('b31_archived_tag') ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span>
                    <?php if (!$arch): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="system" value="<?= $sys ?>">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" style="background:#0f766e; color:#fff; border:none;
                                    border-radius:8px; padding:5px 12px; cursor:pointer;"><?= __('b31_btn_archive') ?></button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="unarchive">
                            <input type="hidden" name="system" value="<?= $sys ?>">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" style="background:#64748b; color:#fff; border:none;
                                    border-radius:8px; padding:5px 12px; cursor:pointer;"><?= __('b31_btn_unarchive') ?></button>
                        </form>
                    <?php endif; ?>
                    <button onclick="openDel('<?= $sys ?>', <?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['batch_code'], ENT_QUOTES) ?>')"
                            style="background:#fff; color:#b91c1c; border:1px solid #fca5a5;
                                   border-radius:8px; padding:5px 12px; cursor:pointer;"><?= __('b31_btn_del') ?></button>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<!-- ── Admin-delete modal (klein menuutje met uitleg + extra check) ── -->
<div id="delModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
                          align-items:center; justify-content:center; z-index:999;">
    <div style="background:#fff; border-radius:12px; max-width:440px; padding:22px;">
        <h3 style="color:#b91c1c; margin-top:0;"><?= __('b31_modal_title') ?></h3>
        <p style="font-size:0.85rem; color:#475569;"><?= __('b31_modal_explain') ?></p>
        <p style="font-size:0.8rem; color:#0f766e; background:#ecfdf5; border-radius:8px; padding:8px;">
            <?= __('b31_modal_backup') ?>
        </p>
        <p style="font-size:0.85rem;"><?= __('b31_modal_type') ?>
            <strong id="delCodeLabel"></strong></p>
        <form method="post" onsubmit="return checkDel()">
            <input type="hidden" name="action" value="hard_delete">
            <input type="hidden" name="system" id="delSystem">
            <input type="hidden" name="id" id="delId">
            <input type="text" id="delConfirm" name="confirm_code" autocomplete="off"
                   placeholder="<?= __('b31_modal_placeholder') ?>"
                   style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;">
            <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:14px;">
                <button type="button" onclick="closeDel()" style="background:#e2e8f0; border:none;
                        border-radius:8px; padding:8px 14px; cursor:pointer;"><?= __('b31_btn_cancel') ?></button>
                <button type="submit" id="delGo" disabled style="background:#b91c1c; color:#fff; border:none;
                        border-radius:8px; padding:8px 14px; cursor:not-allowed; opacity:.5;"><?= __('b31_btn_del_forever') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openDel(sys, id, code){
    document.getElementById('delSystem').value = sys;
    document.getElementById('delId').value = id;
    document.getElementById('delCodeLabel').textContent = code;
    document.getElementById('delConfirm').value = '';
    document.getElementById('delConfirm').dataset.code = code;
    var go = document.getElementById('delGo');
    go.disabled = true; go.style.opacity = '.5'; go.style.cursor = 'not-allowed';
    document.getElementById('delModal').style.display = 'flex';
}
function closeDel(){ document.getElementById('delModal').style.display = 'none'; }
function checkDel(){
    var inp = document.getElementById('delConfirm');
    var ok = inp.value.trim() === inp.dataset.code;
    var go = document.getElementById('delGo');
    go.disabled = !ok; go.style.opacity = ok ? '1' : '.5'; go.style.cursor = ok ? 'pointer' : 'not-allowed';
}
document.getElementById('delConfirm').addEventListener('input', checkDel);
</script>

<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>
<?php require_once __DIR__ . '/app/includes/workflow_nav.php'; ?>
</main></div></body></html>