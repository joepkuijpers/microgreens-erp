<?php
include 'db_connect.php';
include 'includes/language.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_sale_form.php');
    exit;
}

$customer_id = (int)($_POST['customer_id'] ?? 0);
$finished_inventory_id = (int)($_POST['finished_inventory_id'] ?? 0);
$sale_date = trim($_POST['sale_date'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$status = trim($_POST['status'] ?? 'betaald');

if ($customer_id <= 0 || $finished_inventory_id <= 0 || $sale_date === '' || $quantity <= 0) {
    die(__('invalid_sale_input'));
}

$stmt = $db->prepare("
    SELECT id, name
    FROM customers
    WHERE id = :id
");
$stmt->execute([':id' => $customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die(__('customer_not_found'));
}

$stmt = $db->prepare("
    SELECT
        f.id AS finished_inventory_id,
        f.product_id,
        f.quantity AS stock_quantity,
        f.unit,
        f.batch_id,
        f.harvest_id,
        p.name,
        p.sale_price
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
    WHERE f.id = :finished_inventory_id
");
$stmt->execute([':finished_inventory_id' => $finished_inventory_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die(__('product_not_found_in_finished_inventory'));
}

$stock_before = (float)$product['stock_quantity'];
$stock_after = $stock_before - $quantity;

if ($stock_after < 0) {
    die(__('insufficient_finished_inventory'));
}

$amount = $quantity * (float)$product['sale_price'];

$db->beginTransaction();

try {
    $updateStock = $db->prepare("
        UPDATE finished_inventory
        SET quantity = :quantity
        WHERE id = :finished_inventory_id
    ");

    $updateStock->execute([
        ':quantity' => $stock_after,
        ':finished_inventory_id' => $finished_inventory_id
    ]);

    $insertSale = $db->prepare("
        INSERT INTO sales
        (customer_name, sale_date, product, quantity, amount, status, customer_id, product_id, batch_id, harvest_id)
        VALUES
        (:customer_name, :sale_date, :product, :quantity, :amount, :status, :customer_id, :product_id, :batch_id, :harvest_id)
    ");

    $insertSale->execute([
        ':customer_name' => $customer['name'],
        ':sale_date' => $sale_date,
        ':product' => $product['name'],
        ':quantity' => $quantity,
        ':amount' => $amount,
        ':status' => $status,
        ':customer_id' => $customer_id,
        ':product_id' => $product['product_id'],
        ':batch_id' => $product['batch_id'],
        ':harvest_id' => $product['harvest_id']
    ]);

    $db->commit();

    header('Location: list_sales.php');
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die(__('sale_save_error') . ': ' . $e->getMessage());
}