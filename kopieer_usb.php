<?php
// kopieer_usb.php — zet de 4 nieuwste .gpg-backups op de USB, dan loskoppelen
$usb   = "D:/ERP-reddingslijn";      // ← pas aan naar jouw USB-letter
$bron  = __DIR__ . "/database/ERP-backup";

@mkdir($usb, 0777, true);
$z = glob("$bron/ERP_*.sqlite.gpg") ?: [];
usort($z, fn($a,$b) => filemtime($b) <=> filemtime($a));

$n = 0;
foreach (array_slice($z, 0, 4) as $f) {          // 4 nieuwste = ~2 dagen
    if (copy($f, "$usb/" . basename($f))) $n++;
}
echo "$n backups gekopieerd naar $usb\n";