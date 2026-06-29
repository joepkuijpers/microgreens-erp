<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $db->prepare("
        INSERT INTO customers
        (name, email, phone, notes)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['notes']
    ]);

    echo "<p>Klant opgeslagen!</p>";
}
?>

<h1>Nieuwe klant</h1>

<form method="post">

Naam:<br>
<input type="text" name="name"><br><br>

Email:<br>
<input type="email" name="email"><br><br>

Telefoon:<br>
<input type="text" name="phone"><br><br>

Notities:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="Opslaan">

</form>

<br>
<a href="index.php">Menu</a>
