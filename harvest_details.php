<?php
include 'db_connect.php';
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid harvest ID.');
}

$stmt = $db->prepare("
    SELECT
        h.id,
        h.harvest_date,
        h.weight_grams,
        h.quality_notes,
        g.id AS batch_id,
        g.crop,
        g.sow_date,
        g.expected_harvest_date,
        g.harvest_date AS batch_harvest_date,
        g.status AS batch_status
    FROM harvests h
    LEFT JOIN grow_batches g
        ON h.batch_id = g.id
    WHERE h.id = :id
");

$stmt->execute([
    ':id' => $id
]);

$harvest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$harvest) {
    die('Harvest not found.');
}
?>

<div class="main">

<h1>🌾 Harvest Details</h1>

<p>
    <a class="btn" href="list_harvests.php">← Back to Harvests</a>

    <?php if (!empty($harvest['batch_id'])): ?>
        <a class="btn" href="batch_details.php?id=<?= urlencode((string)$harvest['batch_id']) ?>">
            🌱 View Grow Batch
        </a>
    <?php endif; ?>
</p>

<div class="card">
    <h2>Harvest</h2>

    <p><strong>ID:</strong> <?= htmlspecialchars((string)$harvest['id']) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars((string)($harvest['harvest_date'] ?? '-')) ?></p>
    <p><strong>Weight:</strong> <?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?> g</p>
    <p><strong>Quality:</strong> <?= htmlspecialchars((string)($harvest['quality_notes'] ?? '-')) ?></p>
</div>

<div class="card">
    <h2>Grow Batch</h2>

    <p><strong>Batch ID:</strong> <?= htmlspecialchars((string)($harvest['batch_id'] ?? '-')) ?></p>
    <p><strong>Crop:</strong> <?= htmlspecialchars((string)($harvest['crop'] ?? '-')) ?></p>
    <p><strong>Sow date:</strong> <?= htmlspecialchars((string)($harvest['sow_date'] ?? '-')) ?></p>
    <p><strong>Expected harvest:</strong> <?= htmlspecialchars((string)($harvest['expected_harvest_date'] ?? '-')) ?></p>
    <p><strong>Harvest date:</strong> <?= htmlspecialchars((string)($harvest['batch_harvest_date'] ?? '-')) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars((string)($harvest['batch_status'] ?? '-')) ?></p>
</div>

</div>

<?php include 'includes/footer.php'; ?>