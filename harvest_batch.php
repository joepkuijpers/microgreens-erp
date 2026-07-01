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
        SET harvest_date = ?,
            status = 'Geoogst'
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['harvest_date'],
        $id
    ]);

    echo "<script>
        alert('Batch succesvol geoogst!');
        window.location='grow_batches.php';
    </script>";
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">

<h1>🌾 Batch oogsten</h1>

<p><strong>Gewas:</strong> <?= htmlspecialchars($batch['crop']) ?></p>

<p><strong>Zaaidatum:</strong> <?= htmlspecialchars($batch['sow_date']) ?></p>

<p><strong>Verwachte oogstdatum:</strong> <?= htmlspecialchars($batch['expected_harvest_date']) ?></p>

<form method="post">

<p>
Werkelijke oogstdatum<br>
<input
    type="date"
    name="harvest_date"
    value="<?= date('Y-m-d') ?>"
    required>
</p>

<p>
<button class="button" type="submit">
🌾 Oogst registreren
</button>
</p>

</form>

<p>
<a class="button" href="grow_batches.php">
← Terug naar batchbeheer
</a>
</p>

</div>

<?php include 'includes/footer.php'; ?>