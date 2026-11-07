<?php
require_once __DIR__ . '/app/includes/header.php';

$q = $db->query("
    SELECT pb.id, pb.batch_code, pb.status,
           (SELECT COUNT(*) FROM germination_records g
             WHERE g.production_batch_id = pb.id) AS kiem,
           (SELECT COUNT(*) FROM germination_records g
             JOIN harvests h ON h.germination_record_id = g.id
             WHERE g.production_batch_id = pb.id) AS oogst
    FROM production_batches pb
    ORDER BY pb.created_at DESC
");
$batches = $q->fetchAll(PDO::FETCH_ASSOC);

// 7 fasen. trace = gekoppeld aan de DB (ja/nog niet). naam = taal-sleutel.
$fasen = [
    ['naam' => 'b30_phase_1', 'trace' => true],
    ['naam' => 'b30_phase_2', 'trace' => true],
    ['naam' => 'b30_phase_3', 'trace' => false],
    ['naam' => 'b30_phase_4', 'trace' => true],
    ['naam' => 'b30_phase_5', 'trace' => false],
    ['naam' => 'b30_phase_6', 'trace' => false],
    ['naam' => 'b30_phase_7', 'trace' => false],
];
$kleuren = ['#e53e3e','#ed8936','#ecc94b','#48bb78','#4299e1','#9f7aea','#a0aec0'];
?>
<div class="card">
    <h2><?= __('b30_title') ?></h2>
    <p style="font-size:0.85rem; color:#718096; margin-bottom:1rem;">
        <?= __('b30_subtitle') ?>
    </p>

    <?php if (!$batches): ?>
        <p><?= __('b30_no_batches') ?></p>
    <?php else: foreach ($batches as $b):
        $bereikt = 1;
        if ($b['kiem']  > 0) $bereikt = 2;
        if ($b['oogst'] > 0) $bereikt = 4;
        $pct = (int) round($bereikt / 7 * 100);
    ?>
        <div style="margin-bottom:1.2rem;">
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <strong><?= htmlspecialchars($b['batch_code']) ?></strong>
                <span style="font-size:0.8rem; color:#718096;">
                    <?= $bereikt ?>/7 · <?= $pct ?>%
                </span>
            </div>
            <div style="display:flex; gap:3px; margin-top:5px;">
                <?php foreach ($fasen as $i => $f):
                    $bereiktNu = ($i + 1) <= $bereikt;
                    $achtergrond = !$f['trace'] ? '#e2e8f0'
                                  : ($bereiktNu ? $kleuren[$i] : '#f1f5f9');
                    $titel = __($f['naam']) . ($f['trace'] ? '' : ' · ' . __('b30_not_linked'));
                ?>
                    <div title="<?= htmlspecialchars($titel) ?>"
                         style="flex:1; height:22px; border-radius:4px; background:<?= $achtergrond ?>;
                                <?= !$f['trace'] ? 'background-image:repeating-linear-gradient(45deg,#e2e8f0,#e2e8f0 4px,#cbd5e0 4px,#cbd5e0 8px);' : '' ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:3px; margin-top:3px;">
                <?php foreach ($fasen as $f): ?>
                    <span style="flex:1; text-align:center; font-size:0.62rem; color:#a0aec0;">
                        <?= htmlspecialchars(mb_substr(__($f['naam']), 0, 5)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>
<?php require_once __DIR__ . '/app/includes/workflow_nav.php'; ?>
</main></div></body></html>