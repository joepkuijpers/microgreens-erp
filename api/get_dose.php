<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../app/db_connect.php";
require_once __DIR__ . "/../../app/dose_calculator.php";

$batch_code = $_GET["batch_code"] ?? "";
$target_grams = (float)($_GET["target_grams"] ?? 100);

if (empty($batch_code)) {
    echo json_encode(["status" => "error", "message" => "Geen batchcode opgegeven"]);
    exit;
}

$result = calculateSeedingDose($db, $batch_code, $target_grams);
echo json_encode(["status" => "success", "data" => $result]);
