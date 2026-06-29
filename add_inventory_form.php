<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];

    $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit) VALUES (?, ?, ?, ?)");
    $stmt->execute([$item_name, $category, $quantity, $unit]);

    echo "<p>Voorraaditem opgeslagen!</p>";
}
?>

<h1>Nieuw voorraaditem</h1>

<form method="post">
Item naam:<br>
<input type="text" name="item_name"><br><br>

Categorie:<br>
<input type="text" name="category"><br><br>

Aantal:<br>
<input type="number" step="0.01" name="quantity"><br><br>

Eenheid:<br>
<input type="text" name="unit"><br><br>

<input type="submit" value="Opslaan">
</form>

<br>
<a href="index.php">Menu</a>
