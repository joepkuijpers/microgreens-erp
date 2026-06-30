<?php

header("Content-Type: application/json");

include "../db_connect.php";

$row = $db->query("
SELECT timestamp
FROM sensor_log
ORDER BY id DESC
LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$status = "offline";

if ($row) {

    $last = strtotime($row["timestamp"]);
    $now  = time();

    if (($now - $last) <= 300) {
        $status = "online";
    }
}

echo json_encode([
    "status" => $status,
    "last_update" => $row["timestamp"] ?? null
]);