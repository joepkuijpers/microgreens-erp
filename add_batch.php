<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("
        INSERT INTO grow_batches
        (crop, sow_date, expected_harvest_date, tray_count, tray_type, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['crop'],
        $_POST['sow_date'],
        $_POST['expected_harvest_date'],
        $_POST['tray_count'],
        $_POST['tray_type'],
        $_POST['status']
    ]);

    header("Location: grow_batches.php");
    exit;
}
?>

<div class="main">
<h1>🌱 Nieuwe batch</h1>

<form method="post">
<p>Gewas<br><input type="text" name="crop" required></p>
<p>Zaaidatum<br><input type="date" name="sow_date" required></p>
<p>Verwachte oogstdatum<br><input type="date" name="expected_harvest_date" required></p>
<p>Aantal trays<br><input type="number" name="tray_count" value="1" min="1"></p>
<p>Traytype<br><input type="text" name="tray_type" value="1020"></p>

<p>Status<br>
<select name="status">
    <option value="Gepland">Gepland</option>
    <option value="Groeiend" selected>Groeiend</option>
    <option value="Oogstklaar">Oogstklaar</option>
    <option value="Geoogst">Geoogst</option>
</select>
</p>

<p><button class="button" type="submit">💾 Batch opslaan</button></p>
</form>
</div>

<?php include 'includes/footer.php'; ?>
