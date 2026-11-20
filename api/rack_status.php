<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../app/db_connect.php";

try {
    // 1. Alle rekken
    $racks = $db->query(
        "SELECT id, name, capacity_trays, location FROM racks ORDER BY name"
    )->fetchAll(PDO::FETCH_ASSOC);

    // 2. Actieve batches per rack — rack_id bevat de rack-NAAM (geen id!)
    $stmt = $db->query(
        "SELECT rack_id,
                COUNT(*) AS batches,
                SUM(tray_count) AS trays,
                SUM(CASE WHEN status = 'READY_TO_HARVEST' THEN 1 ELSE 0 END) AS ready
         FROM production_batches
         WHERE archived_at IS NULL
           AND rack_id IS NOT NULL
         GROUP BY rack_id"
    );
    $occ = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $occ[$r["rack_id"]] = $r;
    }

    // 3. Samenvoegen (match op name)
    $result = [];
    foreach ($racks as $rk) {
        $name = $rk["name"];
        $cap  = (int)$rk["capacity_trays"];
        $used = isset($occ[$name]) ? (int)$occ[$name]["trays"] : 0;
        $free = max(0, $cap - $used);
        $pct  = $cap > 0 ? round($used / $cap * 100) : 0;

        $result[] = [
            "rack"        => $name,
            "location"    => $rk["location"],
            "capacity"    => $cap,
            "used_trays"  => $used,
            "free_trays"  => $free,
            "occupancy"   => $pct,
            "batches"     => isset($occ[$name]) ? (int)$occ[$name]["batches"] : 0,
            "ready"       => isset($occ[$name]) ? (int)$occ[$name]["ready"] : 0,
        ];
    }

    echo json_encode($result);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}