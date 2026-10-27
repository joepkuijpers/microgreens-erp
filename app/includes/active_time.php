<?php
require_once __DIR__ . '/header.php';
$secs = (int)file_get_contents('php://input');
if ($secs > 0) {
    if (!isset($_SESSION['active_seconds']) || $secs > $_SESSION['active_seconds']) {
        $_SESSION['active_seconds'] = $secs;
    }
}
http_response_code(204);
