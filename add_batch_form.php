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

    echo '<p>' . htmlspecialchars(t('batch_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_batch')) ?></h1>

<form method="post">

<?= htmlspecialchars(t('crop')) ?>:<br>
<input type="text" name="crop"><br><br>

<?= htmlspecialchars(t('sow_date')) ?>:<br>
<input type="date" name="sow_date"><br><br>

<?= htmlspecialchars(t('tray_count')) ?>:<br>
<input type="number" name="tray_count"><br><br>

<?= htmlspecialchars(t('tray_type')) ?>:<br>
<input type="text" name="tray_type" value="1020 tray"><br><br>

<?= htmlspecialchars(t('status')) ?>:<br>
<input type="text" name="status" value="gezaaid"><br><br>

<input type="submit" value="<?= htmlspecialchars(t('save')) ?>">

</form>