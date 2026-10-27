<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
$pageTitle = __('module_b30');

$days = isset($_GET['days']) ? max(1,(int)$_GET['days']) : 30;
$since = date('Y-m-d H:i:s', strtotime("-$days days"));

$adminStmt = $db->prepare("SELECT substr(event_type,1,instr(event_type,'_')-1) AS module,
    SUM(registration_time_seconds) AS sec, COUNT(*) AS n
    FROM audit_events
    WHERE registration_time_seconds IS NOT NULL AND created_at >= ?
    GROUP BY module ORDER BY sec DESC");
$adminStmt->execute([$since]);
$rows = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
$adminTotal = array_sum(array_column($rows,'sec'));

$lab = $db->prepare("SELECT COALESCE(SUM(hours_worked),0) AS h FROM labor_entries WHERE work_date >= date(?)");
$lab->execute([$since]);
$labSec = (float)$lab->fetch(PDO::FETCH_ASSOC)['h'] * 3600;

$fd = $db->prepare("SELECT COALESCE(SUM((julianday(ended_at)-julianday(started_at))*86400),0) AS s
    FROM freeze_dry_processes WHERE started_at IS NOT NULL AND ended_at IS NOT NULL AND started_at >= ?");
$fd->execute([$since]);
$fdSec = (float)$fd->fetch(PDO::FETCH_ASSOC)['s'];

$cnt = function($db,$t,$col,$since){
    $s=$db->prepare("SELECT COUNT(*) AS c FROM $t WHERE $col >= ?");
    $s->execute([$since]); return (int)$s->fetch(PDO::FETCH_ASSOC)['c'];
};
$estSec = $cnt($db,'harvests','harvest_date',$since)*22
        + $cnt($db,'packaging_units','packaging_date',$since)*15
        + $cnt($db,'production_outputs','produced_at',$since)*15
        + $cnt($db,'cleaning_logs','cleaned_at',$since)*30;

$opSec = $labSec + $fdSec + $estSec;
$reliable = $opSec > 0 && $adminTotal > 0;
$measured = $labSec > 0;
$pct = $reliable ? ($adminTotal / $opSec * 100) : null;
$badge = !$measured ? 'geschat' : 'gemeten';
?>
    </div>
    <h3><?= __('b30_title') ?> · <?= (int)$days ?> <?= $languageCode==='nl'?'dagen':'days' ?></h3>

    <p><strong><?= __('b30_admin_time') ?>:</strong> <?= number_format($adminTotal,0,',','.') ?> <?= __('b30_seconds') ?>
       &nbsp;|&nbsp; <strong><?= __('b30_op_time') ?>:</strong> <?= number_format($opSec,0,',','.') ?> <?= __('b30_seconds') ?>
       <em style="color:#7f8c8d;">(<?= $badge ?>)</em></p>

    <?php if (!$reliable): ?>
      <div style="padding:10px;border-radius:4px;background:#e8f4fd;margin:10px 0;"><?= __('b30_no_data') ?></div>
    <?php else: ?>
      <?php $col = $pct>5 ? '#f8d7da' : ($pct>=3 ? '#fff3cd' : '#d4edda'); ?>
      <div style="padding:10px;border-radius:4px;background:<?= $col ?>;margin:10px 0;">
        <?= __('b30_load') ?>: <strong><?= number_format($pct,2,',','.') ?>%</strong>
        · <?= __('b30_target') ?> · <?= __('b30_limit') ?>
      </div>
    <?php endif; ?>

    <table>
      <thead><tr><th><?= __('b30_module') ?></th><th><?= __('b30_seconds') ?></th><th>#</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><strong><?= htmlspecialchars($r['module']) ?></strong></td>
            <td><?= number_format((float)$r['sec'],0,',','.') ?></td><td><?= (int)$r['n'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="3" style="text-align:center;color:#7f8c8d;">—</td></tr><?php endif; ?>
      </tbody>
    </table>

    <div class="next-step-bar">
      <div><a href="dashboard.php" style="color:#666;text-decoration:none;">← Dashboard</a></div>
    </div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
