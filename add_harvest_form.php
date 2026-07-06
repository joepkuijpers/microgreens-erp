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

    echo '<p>' . htmlspecialchars(__('harvest_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_harvest')) ?></h1>

<form method="post">

<?= htmlspecialchars(__('batch_id')) ?>:<br>
<input type="number" name="batch_id"><br><br>

<?= htmlspecialchars(__('harvest_date')) ?>:<br>
<input type="date" name="harvest_date"><br><br>

<?= htmlspecialchars(__('weight_grams')) ?>:<br>
<input type="number" step="0.1" name="weight_grams"><br><br>

<?= htmlspecialchars(__('quality_notes')) ?>:<br>
<input type="text" name="quality_notes"><br><br>

<input type="submit" value="<?= htmlspecialchars(__('save')) ?>">

</form>

<a href="index.php"><?= htmlspecialchars(__('menu')) ?></a>