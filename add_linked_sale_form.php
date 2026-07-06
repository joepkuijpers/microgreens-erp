<?php
require 'db_connect.php';
require 'includes/language.php';

$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$products = $db->query("SELECT id, name, sale_price FROM products ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $customer_id = $_POST['customer_id'];
    $product_id = $_POST['product_id'];

    $customer_name = $db->query("SELECT name FROM customers WHERE id = $customer_id")->fetchColumn();
    $product_name = $db->query("SELECT name FROM products WHERE id = $product_id")->fetchColumn();

$inventory_stmt = $db->prepare("
    SELECT batch_id, harvest_id
    FROM finished_inventory
    WHERE product_id = ?
      AND quantity > 0
    ORDER BY id ASC
    LIMIT 1
");
$inventory_stmt->execute([$product_id]);
$inventory_trace = $inventory_stmt->fetch(PDO::FETCH_ASSOC);

$batch_id = $inventory_trace['batch_id'] ?? null;
$harvest_id = $inventory_trace['harvest_id'] ?? null;

$stmt = $db->prepare("
    INSERT INTO sales
    (customer_id, product_id, customer_name, product, sale_date, quantity, amount, status, batch_id, harvest_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $customer_id,
    $product_id,
    $customer_name,
    $product_name,
    $_POST['sale_date'],
    $_POST['quantity'],
    $_POST['amount'],
    $_POST['status'],
    $batch_id,
    $harvest_id
]);

    echo '<p>' . htmlspecialchars(__('linked_sale_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_linked_sale')) ?></h1>

<form method="post">

<?= htmlspecialchars(__('customer')) ?>:<br>
<select name="customer_id">
<?php
foreach ($customers as $c) {
    echo "<option value='{$c['id']}'>{$c['name']}</option>";
}
?>
</select><br><br>

<?= htmlspecialchars(__('product')) ?>:<br>
<select name="product_id">
<?php
foreach ($products as $p) {
    echo "<option value='{$p['id']}'>{$p['name']} - EUR {$p['sale_price']}</option>";
}
?>
</select><br><br>

<?= htmlspecialchars(__('date')) ?>:<br>
<input type="date" name="sale_date"><br><br>

<?= htmlspecialchars(__('quantity')) ?>:<br>
<input type="number" step="0.01" name="quantity"><br><br>

<?= htmlspecialchars(__('amount')) ?>:<br>
<input type="number" step="0.01" name="amount"><br><br>

<?= htmlspecialchars(__('status')) ?>:<br>
<input type="text" name="status" value="betaald"><br><br>

<input type="submit" value="<?= htmlspecialchars(__('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(__('menu')) ?></a>