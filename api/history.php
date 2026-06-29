<?php

header('Content-Type: application/json');

include '../db_connect.php';

$stmt = $db->query("
SELECT
timestamp,
temperature,
humidity,
pressure,
light
FROM sensor_log
ORDER BY id DESC
LIMIT 50
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_reverse($data));

?>