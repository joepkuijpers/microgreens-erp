<?php
header("Content-Type: application/json");
ini_set("display_errors", 0);

$db_path = "/var/www/html/microgreens/PHP/app/MicrogreensERP_Live.sqlite";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Alleen POST verzoeken toegestaan."]);
    exit;
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true) ?: $_POST;

$temperature = isset($data['temperature']) ? (float)$data['temperature'] : null;
$humidity = isset($data['humidity']) ? (float)$data['humidity'] : null;
$sensor_location = isset($data['location']) ? trim($data['location']) : 'Kweekruimte 1';

if ($temperature === null || $humidity === null) {
    echo json_encode(["status" => "error", "message" => "Parameters 'temperature' en 'humidity' zijn verplicht."]);
    exit;
}

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO haccp_climate_logs (log_time, location, temperature, humidity, logged_by) VALUES (DATETIME('now', 'localtime'), ?, ?, ?, 'IoT Sensor')");
    $stmt->execute([$sensor_location, $temperature, $humidity]);

    echo json_encode([
        "status" => "success",
        "message" => "Klimaatdata succesvol opgeslagen.",
        "data" => [
            "location" => $sensor_location,
            "temperature" => $temperature,
            "humidity" => $humidity,
            "timestamp" => date("Y-m-d H:i:s")
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Fout: " . $e->getMessage()]);
}
