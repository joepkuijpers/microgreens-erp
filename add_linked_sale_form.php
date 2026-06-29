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

    echo "<p>Gekoppelde verkoop opgeslagen!</p>";
}
?>

<h1>Nieuwe gekoppelde verkoop</h1>

<form method="post">

Klant:<br>
<select name="customer_id">
<?php
foreach ($customers as $c) {
    echo "<option value='{$c['id']}'>{$c['name']}</option>";
}
?>
</select><br><br>

Product:<br>
<select name="product_id">
<?php
foreach ($products as $p) {
    echo "<option value='{$p['id']}'>{$p['name']} - EUR {$p['sale_price']}</option>";
}
?>
</select><br><br>

Datum:<br>
<input type="date" name="sale_date"><br><br>

Aantal:<br>
<input type="number" step="0.01" name="quantity"><br><br>

Bedrag:<br>
<input type="number" step="0.01" name="amount"><br><br>

Status:<br>
<input type="text" name="status" value="betaald"><br><br>

<input type="submit" value="Opslaan">

</form>

<br>
<a href="index.php">Menu</a>
