<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $db->prepare("
        INSERT INTO harvests
        (batch_id, harvest_date, weight_grams, quality_notes)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['batch_id'],
        $_POST['harvest_date'],
        $_POST['weight_grams'],
        $_POST['quality_notes']
    ]);

    echo "<p>Oogst opgeslagen!</p>";
}
?>

<h1>Nieuwe oogst</h1>

<form method="post">

Batch ID:<br>
<input type="number" name="batch_id"><br><br>

Oogstdatum:<br>
<input type="date" name="harvest_date"><br><br>

Gewicht (gram):<br>
<input type="number" step="0.1" name="weight_grams"><br><br>

Opmerking:<br>
<input type="text" name="quality_notes"><br><br>

<input type="submit" value="Opslaan">

</form>

<a href="index.php">Menu</a>
