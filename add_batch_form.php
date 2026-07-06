<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("
        INSERT INTO grow_batches
        (crop, sow_date, tray_count, tray_type, status)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['crop'],
        $_POST['sow_date'],
        $_POST['tray_count'],
        $_POST['tray_type'],
        $_POST['status']
    ]);

    echo "<p>Teelt opgeslagen!</p>";
}
?>

<h1>Nieuwe teelt</h1>

<form method="post">
Gewas:<br>
<input type="text" name="crop"><br><br>

Zaaidatum:<br>
<input type="date" name="sow_date"><br><br>

Aantal trays:<br>
<input type="number" name="tray_count"><br><br>

Tray type:<br>
<input type="text" name="tray_type" value="1020 tray"><br><br>

Status:<br>
<input type="text" name="status" value="gezaaid"><br><br>

<input type="submit" value="Opslaan">
</form>