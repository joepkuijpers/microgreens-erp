<?php
header('Content-Type: application/json');
include '../db_connect.php';

$stmt = $db->query("
    SELECT timestamp, temperature, humidity, pressure, light
    FROM sensor_log
    ORDER BY id DESC
    LIMIT 1
");

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $data = [
        "timestamp" => null,
        "temperature" => "--",
        "humidity" => "--",
        "pressure" => "--",
        "light" => "--"
    ];
}

echo json_encode($data);
?>