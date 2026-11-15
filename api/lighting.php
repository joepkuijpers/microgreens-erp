<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../app/db_connect.php";

try {
    // 1. Verlichtingsconfiguratie (doelwaarden)
    $cfg = $db->query(
        "SELECT zone_name, light_type, target_ppfd_min, target_ppfd_max,
                light_min_lux, light_max_lux, is_active, updated_at
         FROM lighting_configs
         ORDER BY id LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    // 2. Laatste verlichtingsmeting / -beslissing
    $log = $db->query(
        "SELECT timestamp, measured_lux, calculated_ppfd, action_taken, error_message, rack_id, source
         FROM lighting_log
         ORDER BY timestamp DESC
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    // 3. Data-versheid (zelfde 600s-tolerantie als de Pi-engine)
    $maxAge = 600;
    $dataFresh = false;
    $ageSeconds = null;
    if ($log && $log["timestamp"]) {
        $ageSeconds = time() - strtotime($log["timestamp"]);
        $dataFresh = $ageSeconds <= $maxAge;
    }

    echo json_encode([
        "status"      => "ok",
        "active"      => $cfg ? (bool)$cfg["is_active"] : false,
        "zone"        => $cfg["zone_name"] ?? null,
        "light_type"  => $cfg["light_type"] ?? null,
        "target_min"  => $cfg ? (float)$cfg["target_ppfd_min"] : null,
        "target_max"  => $cfg ? (float)$cfg["target_ppfd_max"] : null,
        "last_lux"    => $log ? (float)$log["measured_lux"] : null,
        "last_ppfd"   => $log ? (float)$log["calculated_ppfd"] : null,
        "last_action" => $log["action_taken"] ?? null,
        "last_error"  => $log["error_message"] ?? null,
        "last_seen"   => $log["timestamp"] ?? null,
        "age_seconds" => $ageSeconds,
        "data_fresh"  => $dataFresh,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}