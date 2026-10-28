<?php
// Workflow-navigatie: data-gedreven keten. Eén plek om de route te wijzigen.
// Hoofdroute (spine) + vertakking bij B07 (vers vs vriesdroog).
$wf_spine = [
    ['file' => 'b01_batch.php',              'key' => 'wf_b01'],
    ['file' => 'b02_germination.php',        'key' => 'wf_b02'],
    ['file' => 'b07_harvest.php',            'key' => 'wf_b07'],
    ['file' => 'b09_packaging.php',          'key' => 'wf_b11'],
    ['file' => 'b09_sale.php',               'key' => 'wf_b09'],
    ['file' => 'b21_logistics.php',          'key' => 'wf_b21'],
    ['file' => 'b25_customer_feedback.php',  'key' => 'wf_b25'],
];
// Vertakking: B07 heeft twee volgende-stappen.
$wf_branch = [
    'b07_harvest.php' => [
        ['file' => 'b08_freeze_dry.php', 'key' => 'wf_b08'],
        ['file' => 'b09_packaging.php',  'key' => 'wf_b11'],
    ],
];
// B08 komt uit bij verpakken.
$wf_next_override = [
    'b08_freeze_dry.php' => 'b09_packaging.php',
];
$wf_self = basename($_SERVER['PHP_SELF'] ?? '');
$wf_pos = -1;
foreach ($wf_spine as $i => $n) { if ($n['file'] === $wf_self) { $wf_pos = $i; break; } }
$wf_in_chain = ($wf_pos >= 0) || isset($wf_branch[$wf_self]) || array_key_exists($wf_self, $wf_next_override);
$wf_total = count($wf_spine);
function wfBtn($href, $label, $primary) {
    $bg = $primary ? '#0f766e' : '#e5e7eb';
    $fg = $primary ? '#fff' : '#374151';
    return '<a href="' . htmlspecialchars($href) . '" style="text-decoration:none;background:' . $bg . ';color:' . $fg . ';border-radius:10px;padding:10px 16px;font-weight:800">' . htmlspecialchars($label) . '</a>';
}
if ($wf_in_chain):
    $prev = null; $nexts = [];
    if (isset($wf_branch[$wf_self])) {
        $prev = $wf_pos >= 1 ? $wf_spine[$wf_pos - 1] : null;
        $nexts = $wf_branch[$wf_self];
    } elseif (isset($wf_next_override[$wf_self])) {
        $prev = ['file' => 'b07_harvest.php', 'key' => 'wf_b07'];
        $nf = $wf_next_override[$wf_self];
        foreach ($wf_spine as $n) { if ($n['file'] === $nf) { $nexts = [$n]; break; } }
    } else {
        if ($wf_pos > 0) { $prev = $wf_spine[$wf_pos - 1]; }
        if ($wf_pos < $wf_total - 1) { $nexts = [$wf_spine[$wf_pos + 1]]; }
    }
    $stepNo = ($wf_pos >= 0) ? ($wf_pos + 1) : null;
?>
<div style="margin-top:24px;padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="font-weight:800;color:#0f766e"><?php echo $stepNo ? htmlspecialchars(__('wf_step')) . ' ' . $stepNo . '/' . $wf_total : htmlspecialchars(__('wf_branch')); ?></div>
    <div style="flex:1"></div>
    <?php if ($prev): ?>
        <?php echo wfBtn($prev['file'], '← ' . __($prev['key']), false); ?>
    <?php endif; ?>
    <?php foreach ($nexts as $nx): ?>
        <?php echo wfBtn($nx['file'], __($nx['key']) . ' →', true); ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
