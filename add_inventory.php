<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_inventory_form.php');
    exit;
}

$item_name = trim($_POST['item_name'] ?? '');
$category = trim($_POST['category'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$unit_cost = (float)($_POST['unit_cost'] ?? 0);

if ($item_name === '' || $quantity < 0 || $unit === '' || $unit_cost < 0) {
    die('Ongeldige invoer. Controleer artikelnaam, hoeveelheid, eenheid en kostprijs.');
}

$stmt = $db->prepare("
    INSERT INTO inventory (item_name, category, quantity, unit, unit_cost)
    VALUES (:item_name, :category, :quantity, :unit, :unit_cost)
");

$stmt->bindValue(':item_name', $item_name, SQLITE3_TEXT);
$stmt->bindValue(':category', $category, SQLITE3_TEXT);
$stmt->bindValue(':quantity', $quantity, SQLITE3_FLOAT);
$stmt->bindValue(':unit', $unit, SQLITE3_TEXT);
$stmt->bindValue(':unit_cost', $unit_cost, SQLITE3_FLOAT);

$stmt->execute();

header('Location: list_inventory.php');
exit;