<?php
// QR Proxy: Maakt qr_image.php bereikbaar
$data = $_GET["data"] ?? "TEST";
$size = $_GET["size"] ?? 8;
$dbPath = __DIR__ . '/database/MicrogreensERP_Live.sqlite';