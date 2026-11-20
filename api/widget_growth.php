<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../app/db_connect.php";

try {
    // 1. Faserverdeling van actieve batches (niet geoogst, niet gearchiveerd)
    $stmt = $db->query(
        "SELECT status, COUNT(*) AS n
         FROM production_batches
         WHERE archived_at IS NULL
         GROUP BY status
         ORDER BY status"
    );
    $phases = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $phases[] = [
            "status" => $row["status"],
            "count"  => (int)$row["n"],
        ];
    }

    // 2. Actieve batches per gewas (SOWN + READY_TO_HARVEST = nog in groei)
    $stmt = $db->prepare(
        "SELECT crop_type, COUNT(*) AS n
         FROM production_batches
         WHERE archived_at IS NULL
           AND status IN ('SOWN', 'READY_TO_HARVEST')
         GROUP BY crop_type
         ORDER BY crop_type"
    );
    $stmt->execute();
    $crops = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $crops[] = [
            "crop"  => $row["crop_type"] ?? "Onbekend",
            "count" => (int)$row["n"],
        ];
    }

    // 3. Totaal actief
    $totalActive = array_sum(array_column($crops, "count"));

    echo json_encode([
        "phases"       => $phases,
        "crops"        => $crops,
        "total_active" => $totalActive,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}