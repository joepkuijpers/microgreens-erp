<?php
// QR Proxy: Maakt qr_image.php bereikbaar
$data = $_GET["data"] ?? "TEST";
$size = $_GET["size"] ?? 8;
include '/var/www/html/microgreens/app/includes/qr_image.php';
