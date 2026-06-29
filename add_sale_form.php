<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("
        INSERT INTO sales
        (customer_name, sale_date, product, quantity, amount, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['customer_name'],
        $_POST['sale_date'],
        $_POST['product'],
        $_POST['quantity'],
        $_POST['amount'],
        $_POST['status']
    ]);

    echo "<p>Verkoop opgeslagen!</p>";
}
?>

<h1>Nieuwe verkoop</h1>

<form method="post">
Klant:<br>
<input type="text" name="customer_name"><br><br>

Datum:<br>
<input type="date" name="sale_date"><br><br>

Product:<br>
<input type="text" name="product"><br><br>

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
