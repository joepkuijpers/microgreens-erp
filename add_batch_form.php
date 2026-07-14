<?php
include 'db_connect.php';
include 'includes/language.php';

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

    echo '<p>' . htmlspecialchars(__('batch_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_batch')) ?></h1>

<form method="post">

<?= htmlspecialchars(__('crop')) ?>:<br>
<input type="text" name="crop"><br><br>

<?= htmlspecialchars(__('sow_date')) ?>:<br>
<input type="date" name="sow_date"><br><br>

<?= htmlspecialchars(__('tray_count')) ?>:<br>
<input type="number" name="tray_count"><br><br>

<?= htmlspecialchars(__('tray_type')) ?>:<br>
<input type="text" name="tray_type" value="1020 tray"><br><br>

<?= htmlspecialchars(__('status')) ?>:<br>
<input type="text" name="status" value="gezaaid"><br><br>

<input type="submit" value="<?= htmlspecialchars(__('save')) ?>">

</form>