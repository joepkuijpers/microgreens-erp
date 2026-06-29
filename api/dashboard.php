<?php
header('Content-Type: application/json');
include '../db_connect.php';

$data = [
    "products" => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    "inventory" => $db->query("SELECT COUNT(*) FROM inventory")->fetchColumn(),
    "batches" => $db->query("SELECT COUNT(*) FROM grow_batches")->fetchColumn(),
    "sales" => $db->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    "customers" => $db->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    "suppliers" => $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
    "harvests" => $db->query("SELECT COUNT(*) FROM harvests")->fetchColumn(),
    "revenue" => $db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn(),
    "expenses" => $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn()
];

$data["profit"] = $data["revenue"] - $data["expenses"];

echo json_encode($data);
