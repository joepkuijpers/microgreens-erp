<?php
require_once __DIR__ . '/app/includes/header.php';

$stmt = $db->query("SELECT phase_label, phase_num, phase_total, updated_at
                    FROM project_status
                    ORDER BY updated_at DESC
                    LIMIT 1");
$status = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$status) { $status = ['phase_label'=>'onbekend','phase_num'=>0,'phase_total'=>7,'updated_at'=>'—']; }

$pct = (int) round(($status['phase_num'] / $status['phase_total']) * 100);
$kleuren = [1=>'#e53e3e',2=>'#ed8936',3=>'#ecc94b',4=>'#48bb78',5=>'#4299e1',6=>'#9f7aea',7=>'#a0aec0'];
$kleur = $kleuren[$status['phase_num']] ?? '#a0aec0';
?>
<div class="card">
    <h2>B30 · Projectstatus</h2>
    <p style="font-size:1.4rem; margin-bottom:0.5rem;">
        <strong><?= $status['phase_num'] ?>/<?= $status['phase_total'] ?></strong>
        &middot; <?= $pct ?>% &middot; <?= htmlspecialchars($status['phase_label']) ?>
    </p>
    <div style="background:#e2e8f0; border-radius:8px; height:28px; overflow:hidden; position:relative;">
        <div style="width:<?= $pct ?>%; height:100%; background:<?= $kleur ?>;"></div>
        <span style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                     font-size:0.85rem; font-weight:600; color:#1a202c;"><?= $pct ?>%</span>
    </div>
    <p style="margin-top:0.8rem; font-size:0.8rem; color:#718096;">
        Laatst bijgewerkt: <?= htmlspecialchars($status['updated_at']) ?>
    </p>
</div>
<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>
<?php require_once __DIR__ . '/app/includes/workflow_nav.php'; ?>
</main></div></body></html>