<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $db->prepare("
        INSERT INTO products
        (name, category, unit, sale_price, notes)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['unit'],
        $_POST['sale_price'],
        $_POST['notes']
    ]);

    echo "<p>Product opgeslagen!</p>";
}
?>

<h1>Nieuw product</h1>

<form method="post">

Naam:<br>
<input type="text" name="name"><br><br>

Categorie:<br>
<input type="text" name="category"><br><br>

Eenheid:<br>
<input type="text" name="unit" value="bakje"><br><br>

Verkoopprijs:<br>
<input type="number" step="0.01" name="sale_price"><br><br>

Notities:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="Opslaan">

</form>

<br>
<a href="index.php">Menu</a>
