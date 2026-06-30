<?php

header('Content-Type: application/json');

require_once '../db_connect.php';

try {

    $stmt = $db->query("
        SELECT
            timestamp,
            temperature,
            humidity,
            pressure,
            light
        FROM sensor_log
        ORDER BY id DESC
        LIMIT 1
    ");

    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sensor) {

        echo json_encode([
            "success" => false,
            "message" => "Geen sensorgegevens gevonden."
        ]);

        exit;
    }

    echo json_encode([
        "success"     => true,
        "timestamp"   => $sensor["timestamp"],
        "temperature" => (float)$sensor["temperature"],
        "humidity"    => (float)$sensor["humidity"],
        "pressure"    => (float)$sensor["pressure"],
        "light"       => (float)$sensor["light"]
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}