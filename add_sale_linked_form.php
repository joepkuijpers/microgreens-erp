<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $product_id = $_POST['product_id'];
    $sale_date = $_POST['sale_date'];
    $quantity = $_POST['quantity'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    $customer = $db->query("SELECT name FROM customers WHERE id = $customer_id")->fetchColumn();
    $product = $db->query("SELECT name FROM products WHERE id = $product_id")->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO sales
        (customer_id, product_id, customer_name, sale_date, product, quantity, amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$customer_id, $product_id, $customer, $sale_date, $product, $quantity, $amount, $status]);

    echo "<p>Gekoppelde verkoop opgeslagen!</p>";
}

$customers = $db->query("SELECT * FROM customers ORDER BY name");
$products = $db->query("SELECT * FROM products ORDER BY name");
?>

<h1>Nieuwe verkoop gekoppeld</h1>

<form method="post">

Klant:<br>
<select name="customer_id">
<?php foreach ($customers as $c) { echo "<option value='".$c['id']."'>".$c['name']."</option>"; } ?>
</select><br><br>

Product:<br>
<select name="product_id">
<?php foreach ($products as $p) { echo "<option value='".$p['id']."'>".$p['name']."</option>"; } ?>
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
