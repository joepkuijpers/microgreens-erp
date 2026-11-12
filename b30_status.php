<?php
require_once __DIR__ . '/app/includes/header.php';

$q = $db->query("
    SELECT pb.id, pb.batch_code, pb.status, pb.started_at, pb.completed_at,
           (SELECT COUNT(*) FROM germination_records g
             WHERE g.production_batch_id = pb.id) AS kiem,
           (SELECT COUNT(*) FROM germination_records g
             JOIN harvests h ON h.germination_record_id = g.id
             WHERE g.production_batch_id = pb.id) AS oogst,
           (SELECT COUNT(*) FROM freeze_dry_processes fdp
             JOIN production_outputs po ON po.id = fdp.production_output_id
             WHERE po.batch_id = pb.id) AS vries,
           (SELECT COUNT(*) FROM packaging_units pu
             JOIN finished_inventory fi ON fi.id = pu.finished_inventory_id
             JOIN harvests h ON h.id = fi.harvest_id
             JOIN germination_records g ON g.id = h.germination_record_id
             WHERE g.production_batch_id = pb.id) AS verpak,
           (SELECT COUNT(*) FROM sales s WHERE s.batch_id = pb.id) AS verkoop,
           (SELECT MIN(fdp.started_at) FROM freeze_dry_processes fdp
             JOIN production_outputs po ON po.id = fdp.production_output_id
             WHERE po.batch_id = pb.id) AS vries_datum,
           (SELECT MIN(pu.packaging_date) FROM packaging_units pu
             JOIN finished_inventory fi ON fi.id = pu.finished_inventory_id
             JOIN harvests h ON h.id = fi.harvest_id
             JOIN germination_records g ON g.id = h.germination_record_id
             WHERE g.production_batch_id = pb.id) AS verpak_datum,
           (SELECT MIN(s.sale_date) FROM sales s WHERE s.batch_id = pb.id) AS verkoop_datum,
            (SELECT COUNT(*) FROM sensor_log sl
             JOIN spatial_allocations sa
               ON sl.rack_id = sa.rack_id
              AND sl.timestamp >= sa.created_at
             WHERE sa.batch_id = pb.id) AS groei,
           (SELECT MIN(sl.timestamp) FROM sensor_log sl
             JOIN spatial_allocations sa
               ON sl.rack_id = sa.rack_id
              AND sl.timestamp >= sa.created_at
             WHERE sa.batch_id = pb.id) AS groei_datum    
           FROM production_batches pb
    ORDER BY pb.created_at DESC
");
$batches = $q->fetchAll(PDO::FETCH_ASSOC);

$fasen = [
    ['naam' => 'b30_phase_1', 'trace' => true],
    ['naam' => 'b30_phase_2', 'trace' => true],
    ['naam' => 'b30_phase_3', 'trace' => true],
    ['naam' => 'b30_phase_4', 'trace' => true],
    ['naam' => 'b30_phase_5', 'trace' => true],
    ['naam' => 'b30_phase_6', 'trace' => true],
    ['naam' => 'b30_phase_7', 'trace' => true],
];
$kleuren = ['#e53e3e','#ed8936','#ecc94b','#48bb78','#4299e1','#9f7aea','#a0aec0'];

$keur_map = [
    'SOWN'             => 'b30_keur_sown',
    'GERMINATING'      => 'b30_keur_germinating',
    'READY_TO_HARVEST' => 'b30_keur_ready',
    'HARVESTED'        => 'b30_keur_harvested',
];

function kortedatum(?string $dt): string {
    if (!$dt) return '';
    $t = strtotime($dt);
    return $t ? date('d-m', $t) : '';
}
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
        if ($b['kiem']   > 0) $bereikt = max($bereikt, 2);
        if ($b['groei']  > 0) $bereikt = max($bereikt, 3);
        if ($b['oogst']  > 0) $bereikt = max($bereikt, 4);
        if ($b['vries']  > 0) $bereikt = max($bereikt, 5);
        if ($b['verpak'] > 0) $bereikt = max($bereikt, 6);
        if ($b['verkoop']> 0) $bereikt = max($bereikt, 7);
        $pct = (int) round($bereikt / 7 * 100);

        $keur = $keur_map[$b['status']] ?? null;
        $fase_datum = [
            1 => kortedatum($b['started_at']),
            3 => kortedatum($b['groei_datum']),
            4 => kortedatum($b['completed_at']),
            5 => kortedatum($b['vries_datum']),
            6 => kortedatum($b['verpak_datum']),
            7 => kortedatum($b['verkoop_datum']),
        ];
    ?>
        <div style="margin-bottom:1.2rem;">
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <span>
                    <strong><?= htmlspecialchars($b['batch_code']) ?></strong>
                    <?php if ($keur): ?>
                        <span style="margin-left:8px; font-size:0.72rem; font-weight:700;
                                     background:#0f766e; color:#fff; border-radius:10px; padding:2px 9px;">
                            <?= __($keur) ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span style="font-size:0.8rem; color:#718096;">
                    <?= $bereikt ?>/7 · <?= $pct ?>%
                </span>
            </div>
            <div style="display:flex; gap:3px; margin-top:5px;">
                <?php foreach ($fasen as $i => $f):
                    $bereiktNu = ($i + 1) <= $bereikt;
                    $achtergrond = !$f['trace'] ? '#e2e8f0'
                                  : ($bereiktNu ? $kleuren[$i] : '#f1f5f9');
                    $titel = __($f['naam']);
                    if (!$f['trace']) {
                        $titel .= ' · ' . __('b30_not_linked');
                    } elseif ($i + 1 === 3) {
                        $titel .= ' · ' . __('b30_groei_rek');
                    }
                    $datum = $bereiktNu ? ($fase_datum[$i + 1] ?? '') : '';
                ?>
                    <div title="<?= htmlspecialchars($titel) ?>"
                         style="flex:1; height:30px; border-radius:4px; background:<?= $achtergrond ?>;
                                display:flex; align-items:center; justify-content:center;
                                font-size:0.8rem; font-weight:700; color:#fff;
                                <?= !$f['trace'] ? 'background-image:repeating-linear-gradient(45deg,#e2e8f0,#e2e8f0 4px,#cbd5e0 4px,#cbd5e0 8px);' : '' ?>">
                        <?= htmlspecialchars($datum) ?>
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