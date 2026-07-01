<?php
include 'db_connect.php';

$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM grow_batches WHERE id=?");
$stmt->execute([$id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die("Batch niet gevonden.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $db->prepare("
        UPDATE grow_batches
        SET crop=?,
            sow_date=?,
            expected_harvest_date=?,
            tray_count=?,
            tray_type=?,
            status=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['crop'],
        $_POST['sow_date'],
        $_POST['expected_harvest_date'],
        $_POST['tray_count'],
        $_POST['tray_type'],
        $_POST['status'],
        $id
    ]);

    echo "<script>
        alert('Batch opgeslagen');
        window.location='grow_batches.php';
    </script>";
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">

<h1>✏️ Batch bewerken</h1>

<form method="post">

<p>
Gewas<br>
<input type="text" name="crop"
value="<?= htmlspecialchars($batch['crop']) ?>">
</p>

<p>
Zaaidatum<br>
<input type="date" name="sow_date"
value="<?= $batch['sow_date'] ?>">
</p>

<p>
Verwachte oogstdatum<br>
<input type="date"
name="expected_harvest_date"
value="<?= $batch['expected_harvest_date'] ?>">
</p>

<p>
Aantal trays<br>
<input type="number"
name="tray_count"
value="<?= $batch['tray_count'] ?>">
</p>

<p>
Traytype<br>
<input type="text"
name="tray_type"
value="<?= htmlspecialchars($batch['tray_type']) ?>">
</p>

<p>
Status<br>

<select name="status">

<?php

$statussen = [
    "Gepland",
    "Groeiend",
    "Oogstklaar",
    "Geoogst"
];

foreach($statussen as $status){

    $selected =
        ($batch['status']==$status)
        ? "selected"
        : "";

    echo "<option $selected>$status</option>";

}

?>

</select>

</p>

<p>

<button class="button">

💾 Opslaan

</button>

</p>

</form>

</div>

<?php include 'includes/footer.php'; ?>