<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$batches = $db->query("
    SELECT *
    FROM grow_batches
    ORDER BY sow_date DESC
");
?>

<div class="main">

<h1>🌱 Batchbeheer</h1>

<p>
    <a class="button" href="add_batch.php">➕ Nieuwe batch</a>
</p>

<table>
<tr>
    <th>ID</th>
    <th>Gewas</th>
    <th>Zaaidatum</th>
    <th>Verwachte oogst</th>
    <th>Trays</th>
    <th>Status</th>
    <th>Acties</th>
</tr>

<?php foreach ($batches as $batch): ?>
<tr>
    <td><?= $batch['id'] ?></td>
    <td><?= htmlspecialchars($batch['crop']) ?></td>
    <td><?= htmlspecialchars($batch['sow_date']) ?></td>
    <td><?= htmlspecialchars($batch['expected_harvest_date']) ?></td>
    <td><?= htmlspecialchars($batch['tray_count']) ?></td>
    <td><?= htmlspecialchars($batch['status']) ?></td>
    <td>
        <a href="edit_batch.php?id=<?= $batch['id'] ?>">✏️ Bewerken</a> |
        <a href="harvest_batch.php?id=<?= $batch['id'] ?>">🌾 Oogsten</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

</div>

<?php include 'includes/footer.php'; ?>
