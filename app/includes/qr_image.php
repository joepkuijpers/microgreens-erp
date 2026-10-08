<?php
// Genereert een QR code image on-the-fly met de geïnstalleerde phpqrcode library
header("Content-Type: image/png");

$data = $_GET['data'] ?? 'Geen data';
$size = intval($_GET['size'] ?? 8); // Grootte van de modules

// Probeer de systeembibliotheek te laden
// Op Debian/Raspberry Pi OS zit phpqrcode vaak in /usr/share/php/phpqrcode/
if (file_exists('/usr/share/phpqrcode/qrlib.php')) {
    include_once '/usr/share/phpqrcode/qrlib.php';
} elseif (file_exists(__DIR__ . '/qrcode.php')) {
    include_once __DIR__ . '/qrcode.php';
} else {
    // Fallback als niets gevonden wordt (zou niet moeten gebeuren na installatie)
    die("QR Library niet gevonden.");
}

// Genereer de QR code direct naar de browser
// Parameters: (data, bestand=false, errorcorrectie=L, grootte=4, marge=2)
// We gebruiken 'false' voor bestand om output naar browser te sturen
QRcode::png($data, false, 'L', $size, 2);
?>
