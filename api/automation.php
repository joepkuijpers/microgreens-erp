<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../app/db_connect.php";

try {
    // Regelaar (GPIO/relais) is Pi-only en niet actief op deze server.
    // Deze endpoint toont daarom ALLEEN de laatste context per subsysteem,
    // puur gelezen uit de DB — er wordt niets geschakeld.

    $climate = $db->query(
        "SELECT timestamp, temperature, humidity, rack_id
         FROM climate_data
         ORDER BY timestamp DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $lighting = $db->query(
        "SELECT timestamp, measured_lux, calculated_ppfd, action_taken
         FROM lighting_log
         ORDER BY timestamp DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $water = $db->query(
        "SELECT log_date, log_time, ph_value, ec_value, temperature
         FROM water_measurements
         ORDER BY log_date DESC, log_time DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"          => "ok",
        "regelaar_actief" => false,
        "melding"         => "Automatiseringsregelaar (GPIO/relais) is niet actief op dit platform. Alleen context getoond, er wordt niet geschakeld.",
        "context"         => [
            "climate"  => $climate ?: null,
            "lighting" => $lighting ?: null,
            "water"    => $water ?: null,
        ],
        "timestamp"       => date("Y-m-d H:i:s"),
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}