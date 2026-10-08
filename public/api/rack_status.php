<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../app/includes/db_connection.php";

try {
    $db = getDbConnection();
    // Fix: rack_id waarde moet tussen aanhalingstekens staan
    $sql = "SELECT rack_position, batch_code, started_at FROM production_batches 
            WHERE rack_id = 'RACK-A' AND rack_position IS NOT NULL";
    $stmt = $db->query($sql);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($batches as $b) {
        $class = "occupied"; 
        if ($b["started_at"]) {
            $days = (strtotime("now") - strtotime($b["started_at"])) / 86400;
            if ($days < 3) $class = "blackout";
            elseif ($days > 10) $class = "harvest";
        }
        $result[] = [
            "rack_position" => (int)$b["rack_position"],
            "batch_code" => $b["batch_code"],
            "class" => $class
        ];
    }
    echo json_encode(["occupied" => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
