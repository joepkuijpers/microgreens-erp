<?php
require 'config/database.php';

$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$products = $db->query("SELECT id, name, sale_price FROM products ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $customer_id = $_POST['customer_id'];
    $product_id = $_POST['product_id'];

    $customer_name = $db->query("SELECT name FROM customers WHERE id = $customer_id")->fetchColumn();
    $product_name = $db->query("SELECT name FROM products WHERE id = $product_id")->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO sales
        (customer_id, product_id, customer_name, product, sale_date, quantity, amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $customer_id,
        $product_id,
        $customer_name,
        $product_name,
        $_POST['sale_date'],
        $_POST['quantity'],
        $_POST['amount'],
        $_POST['status']
    ]);

    echo '<p>' . htmlspecialchars(t('linked_sale_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_linked_sale')) ?></h1>

<form method="post">

<?= htmlspecialchars(t('customer')) ?>:<br>
<select name="customer_id">
<?php
foreach ($customers as $c) {
    echo "<option value='{$c['id']}'>{$c['name']}</option>";
}
?>
</select><br><br>

<?= htmlspecialchars(t('product')) ?>:<br>
<select name="product_id">
<?php
foreach ($products as $p) {
    echo "<option value='{$p['id']}'>{$p['name']} - EUR {$p['sale_price']}</option>";
}
?>
</select><br><br>

<?= htmlspecialchars(t('date')) ?>:<br>
<input type="date" name="sale_date"><br><br>

<?= htmlspecialchars(t('quantity')) ?>:<br>
<input type="number" step="0.01" name="quantity"><br><br>

<?= htmlspecialchars(t('amount')) ?>:<br>
<input type="number" step="0.01" name="amount"><br><br>

<?= htmlspecialchars(t('status')) ?>:<br>
<input type="text" name="status" value="betaald"><br><br>

<input type="submit" value="<?= htmlspecialchars(t('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(t('menu')) ?></a>