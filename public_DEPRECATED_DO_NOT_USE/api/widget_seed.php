<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../app/includes/db_connection.php";

try {
    $db = getDbConnection();
    
    // 1. Haal huidige voorraad op
    $stmt = $db->query("SELECT crop_type, stock_grams FROM seed_inventory");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stockMap = [];
    foreach ($inventory as $item) {
        $stockMap[$item["crop_type"]] = $item["stock_grams"];
    }

    // 2. Haal actieve batches op en bereken behoefte (standaard 10g per batch voor demo)
    $stmt = $db->query("SELECT crop_type FROM production_batches WHERE started_at IS NOT NULL");
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $demandMap = [];
    foreach ($batches as $b) {
        $crop = $b["crop_type"] ?? "Onbekend";
        if (!isset($demandMap[$crop])) $demandMap[$crop] = 0;
        $demandMap[$crop] += 10; // Standaard behoefte per batch
    }

    // 3. Vergelijk en bouw resultaat
    $result = [];
    // Combineer alle gewassen uit voorraad én vraag
    $allCrops = array_unique(array_merge(array_keys($stockMap), array_keys($demandMap)));

    foreach ($allCrops as $crop) {
        $stock = $stockMap[$crop] ?? 0;
        $demand = $demandMap[$crop] ?? 0;
        $balance = $stock - $demand;
        
        $status = "OK";
        if ($balance < 0) $status = "TE KOORT";
        elseif ($balance < 50) $status = "LAAG";

        $result[] = [
            "crop" => $crop,
            "stock" => $stock,
            "demand" => $demand,
            "balance" => $balance,
            "status" => $status
        ];
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
