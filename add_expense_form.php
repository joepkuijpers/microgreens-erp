<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("
        INSERT INTO expenses
        (expense_date, description, amount)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $_POST['expense_date'],
        $_POST['description'],
        $_POST['amount']
    ]);

    echo "<p>Kost opgeslagen!</p>";
}
?>

<h1>Nieuwe kost</h1>

<form method="post">
Datum:<br>
<input type="date" name="expense_date"><br><br>

Omschrijving:<br>
<input type="text" name="description"><br><br>

Bedrag:<br>
<input type="number" step="0.01" name="amount"><br><br>

<input type="submit" value="Opslaan">
</form>

<br>
<a href="index.php">Menu</a>
