<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../app/includes/db_connection.php";

try {
    $db = getDbConnection();
    // Haal alle gestarte batches op
    $sql = "SELECT batch_code, started_at, crop_type FROM production_batches 
            WHERE started_at IS NOT NULL 
            ORDER BY started_at DESC";
    $stmt = $db->query($sql);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    $now = time();

    foreach ($batches as $b) {
        $start = strtotime($b["started_at"]);
        $days = floor(($now - $start) / 86400);
        
        $phase = "Onbekend";
        $next_action = "";
        $days_left = 0;

        // Faselogica (Standaard: 3d Blackout, 7d Growth, dan Oogst)
        if ($days < 3) {
            $phase = "Blackout";
            $days_left = 3 - $days;
            $next_action = "Licht aan";
        } elseif ($days < 10) {
            $phase = "Groei";
            $days_left = 10 - $days;
            $next_action = "Oogsten";
        } else {
            $phase = "Oogst Gereed";
            $days_left = 0;
            $next_action = "Nu oogsten";
        }

        $result[] = [
            "batch_code" => $b["batch_code"],
            "crop" => $b["crop_type"] ?? "Niet gespecificeerd",
            "days_running" => $days,
            "phase" => $phase,
            "days_left" => $days_left,
            "next_action" => $next_action
        ];
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
