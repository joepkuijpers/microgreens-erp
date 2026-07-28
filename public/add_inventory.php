<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';
require_once '../app/includes/language.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_inventory_form.php');
    exit;
}

$item_name = trim($_POST['item_name'] ?? '');
$supplier_id_raw = trim((string)($_POST['supplier_id'] ?? ''));
$category = trim($_POST['category'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$unit_cost = (float)($_POST['unit_cost'] ?? 0);

if (
    $item_name === '' ||
    !ctype_digit($supplier_id_raw) ||
    (int)$supplier_id_raw <= 0 ||
    $quantity < 0 ||
    $unit === '' ||
    $unit_cost < 0
) {
    die(__('invalid_inventory_input'));
}

$supplier_id = (int)$supplier_id_raw;

$supplierCheck = $db->prepare("
    SELECT COUNT(*)
    FROM suppliers
    WHERE id = :supplier_id
");

$supplierCheck->execute([
    ':supplier_id' => $supplier_id,
]);

if ((int)$supplierCheck->fetchColumn() !== 1) {
    die(__('invalid_inventory_input'));
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO inventory
        (item_name, supplier_id, category, quantity, unit, unit_cost)
        VALUES
        (:item_name, :supplier_id, :category, :quantity, :unit, :unit_cost)
    ");

    $stmt->execute([
        ':item_name' => $item_name,
        ':supplier_id' => $supplier_id,
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

    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    throw $exception;
}

header('Location: list_inventory.php');
exit;
