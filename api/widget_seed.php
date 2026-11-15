<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../app/db_connect.php";

try {
    // 1. Huidige voorraad per gewas (zaadlot.variety = gewasnaam)
    $stmt = $db->query("SELECT variety, current_stock_grams FROM seed_lots");
    $stockMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $crop = $item["variety"] ?? "Onbekend";
        $stockMap[$crop] = ($stockMap[$crop] ?? 0) + (float)$item["current_stock_grams"];
    }

    // 2. Actieve batches per gewas, behoefte 10g per batch (demo)
    $stmt = $db->query("SELECT crop_type FROM production_batches WHERE started_at IS NOT NULL");
    $demandMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $crop = $b["crop_type"] ?? "Onbekend";
        $demandMap[$crop] = ($demandMap[$crop] ?? 0) + 10;
    }

    // 3. Vergelijk, match op kleine letters
    $stockByLower = array_change_key_case($stockMap, CASE_LOWER);
    $demandByLower = array_change_key_case($demandMap, CASE_LOWER);
    $allKeys = array_unique(array_merge(array_keys($stockByLower), array_keys($demandByLower)));

    $result = [];
    foreach ($allKeys as $key) {
        $stock = $stockByLower[$key] ?? 0;
        $demand = $demandByLower[$key] ?? 0;
        $balance = $stock - $demand;

        $status = "OK";
        if ($balance < 0) $status = "TE KOORT";
        elseif ($balance < 50) $status = "LAAG";

        $result[] = [
            "crop" => $key,
            "stock" => $stock,
            "demand" => $demand,
            "balance" => $balance,
            "status" => $status
        ];
    }

    echo json_encode($result);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}