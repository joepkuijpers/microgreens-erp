<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_sale_form.php');
    exit;
}

$customer_id = (int)($_POST['customer_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$sale_date = trim($_POST['sale_date'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$status = trim($_POST['status'] ?? 'betaald');

if ($customer_id <= 0 || $product_id <= 0 || $sale_date === '' || $quantity <= 0) {
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
        f.product_id,
        f.quantity AS stock_quantity,
        f.unit,
        p.name,
        p.sale_price
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
    WHERE f.product_id = :product_id
");
$stmt->execute([':product_id' => $product_id]);
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
        WHERE product_id = :product_id
    ");

    $updateStock->execute([
        ':quantity' => $stock_after,
        ':product_id' => $product_id
    ]);

    $insertSale = $db->prepare("
        INSERT INTO sales
        (customer_name, sale_date, product, quantity, amount, status, customer_id, product_id)
        VALUES
        (:customer_name, :sale_date, :product, :quantity, :amount, :status, :customer_id, :product_id)
    ");

    $insertSale->execute([
        ':customer_name' => $customer['name'],
        ':sale_date' => $sale_date,
        ':product' => $product['name'],
        ':quantity' => $quantity,
        ':amount' => $amount,
        ':status' => $status,
        ':customer_id' => $customer_id,
        ':product_id' => $product_id
    ]);

    $db->commit();

    header('Location: list_sales.php');
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die(__('sale_save_error') . ': ' . $e->getMessage());
}