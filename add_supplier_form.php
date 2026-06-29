<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];

    $stmt = $db->prepare("
        INSERT INTO suppliers (name)
        VALUES (?)
    ");

    $stmt->execute([$name]);

    echo "<p>Leverancier opgeslagen!</p>";
}
?>

<h1>Nieuwe leverancier</h1>

<form method="post">
    Naam:<br>
    <input type="text" name="name"><br><br>

    <input type="submit" value="Opslaan">
</form>

<br>
<a href="index.php">Menu</a>