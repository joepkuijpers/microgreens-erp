<?php
// Visueel volgmiddel: een kleur die bij de batch blijft (geen compliance-signaal).
function trackColorCss(): string {
    return '<style>.tc{display:inline-block;width:13px;height:13px;border-radius:3px;'
         . 'border:1px solid rgba(0,0,0,.25);vertical-align:middle;margin-right:5px;'
         . 'box-shadow:inset 0 0 0 2px #fff;}.tc2{margin-left:-7px;}</style>';
}
function trackSwatch(?string $main, ?string $sub = null): string {
    if ($main === null || $main === '') return '';
    $m = htmlspecialchars($main, ENT_QUOTES);
    $out = '<span class="tc" style="background:'.$m.'"></span>';
    if ($sub !== null && $sub !== '') {
        $s = htmlspecialchars($sub, ENT_QUOTES);
        $out .= '<span class="tc tc2" style="background:'.$s.'"></span>';
    }
    return $out;
}
function batchColor(PDO $db, int $batchId): ?string {
    $st = $db->prepare("SELECT track_color FROM production_batches WHERE id=:id");
    $st->execute([':id' => $batchId]);
    $v = $st->fetchColumn();
    return ($v === false || $v === null) ? null : (string)$v;
}
function outputColor(PDO $db, int $outputId): ?string {
    $st = $db->prepare("SELECT track_color FROM production_outputs WHERE id=:id");
    $st->execute([':id' => $outputId]);
    $v = $st->fetchColumn();
    return ($v === false || $v === null) ? null : (string)$v;
}