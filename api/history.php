<?php

header('Content-Type: application/json');

include '../db_connect.php';

$range = $_GET['range'] ?? '24h';

switch ($range) {

    case '1h':
        $where = "WHERE timestamp >= datetime('now','-1 hour','localtime')";
        break;

    case '7d':
        $where = "WHERE timestamp >= datetime('now','-7 day','localtime')";
        break;

    case '30d':
        $where = "WHERE timestamp >= datetime('now','-30 day','localtime')";
        break;

    default:
        $where = "WHERE timestamp >= datetime('now','-1 day','localtime')";
        break;
}

$stmt = $db->query("
SELECT
    timestamp,
    temperature,
    humidity,
    pressure,
    light
FROM sensor_log
$where
ORDER BY timestamp ASC
");

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);