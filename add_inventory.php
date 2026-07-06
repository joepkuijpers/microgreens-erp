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
    die(__('invalid_inventory_input'));
}

$stmt = $db->prepare("
    INSERT INTO inventory (item_name, category, quantity, unit, unit_cost)
    VALUES (:item_name, :category, :quantity, :unit, :unit_cost)
");

$stmt->execute([
    ':item_name' => $item_name,
    ':category' => $category,
    ':quantity' => $quantity,
    ':unit' => $unit,
    ':unit_cost' => $unit_cost
]);

$inventory_id = $db->lastInsertId();

$log = $db->prepare("
    INSERT INTO inventory_transactions
    (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
    VALUES
    (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
");

$log->execute([
    ':inventory_id' => $inventory_id,
    ':type' => 'TOEVOEGING',
    ':quantity_change' => $quantity,
    ':quantity_before' => 0,
    ':quantity_after' => $quantity,
    ':unit' => $unit,
    ':note' => __('new_inventory_item_created'),
    ':reference_type' => 'inventory',
    ':reference_id' => $inventory_id
]);

header('Location: list_inventory.php');
exit;