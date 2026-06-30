<?php
date_default_timezone_set("Europe/Amsterdam");
header("Content-Type: application/json");

include "../db_connect.php";

$settings = $db->query("
    SELECT refresh_seconds
    FROM settings
    WHERE id = 1
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$refreshSeconds = (int)($settings["refresh_seconds"] ?? 10);
$offlineAfter = $refreshSeconds * 3;

$row = $db->query("
    SELECT timestamp
    FROM sensor_log
    ORDER BY id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$status = "offline";
$secondsAgo = null;

if ($row) {
    $last = strtotime($row["timestamp"]);
    $now  = time();
    $secondsAgo = $now - $last;

    if ($secondsAgo <= $offlineAfter) {
        $status = "online";
    }
}

echo json_encode([
    "status" => $status,
    "last_update" => $row["timestamp"] ?? null,
    "seconds_ago" => $secondsAgo,
    "refresh_seconds" => $refreshSeconds,
    "offline_after" => $offlineAfter,
    "database" => "ok"
]);
