<?php
include 'db_connect.php';
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid sale ID.');
}

$stmt = $db->prepare("
    SELECT
        s.id,
        s.sale_date,
        s.quantity,
        s.amount,
        s.status,
        COALESCE(p.name, s.product) AS product_name,
        h.id AS harvest_id,
        h.harvest_date,
        h.weight_grams,
        h.quality_notes,
        g.id AS batch_id,
        g.crop,
        g.sow_date,
        g.expected_harvest_date,
        g.harvest_date AS batch_harvest_date,
        g.status AS batch_status
    FROM sales s
    LEFT JOIN products p
        ON s.product_id = p.id
    LEFT JOIN harvests h
        ON s.harvest_id = h.id
    LEFT JOIN grow_batches g
        ON h.batch_id = g.id
    WHERE s.id = :id
");

$stmt->execute([
    ':id' => $id
]);

$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('Sale not found.');
}
?>

<div class="main">

<h1>🔎 Traceability Detail</h1>
<div class="card">
    <h2>Traceability Chain</h2>

    <p>
        <strong>Sale #<?= htmlspecialchars((string)$sale['id']) ?></strong>
        →
        <strong><?= htmlspecialchars((string)$sale['product_name']) ?></strong>
        →
        <strong>Harvest #<?= htmlspecialchars((string)($sale['harvest_id'] ?? '-')) ?></strong>
        →
        <?php if (!empty($sale['batch_id'])): ?>
    <a class="btn" href="batch_details.php?id=<?= urlencode((string)$sale['batch_id']) ?>">
        Grow Batch #<?= htmlspecialchars((string)$sale['batch_id']) ?>
    </a>
<?php else: ?>
    <strong>Grow Batch #-</strong>
<?php endif; ?>

    </p>
</div>
<div class="card">
    <h2>Sale</h2>

    <p><strong>ID:</strong> <?= htmlspecialchars((string)$sale['id']) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars((string)$sale['sale_date']) ?></p>
    <p><strong>Product:</strong> <?= htmlspecialchars((string)$sale['product_name']) ?></p>
    <p><strong>Quantity:</strong> <?= number_format((float)$sale['quantity'], 2, ',', '.') ?></p>
    <p><strong>Amount:</strong> € <?= number_format((float)$sale['amount'], 2, ',', '.') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars((string)$sale['status']) ?></p>
</div>

<div class="card">
    <h2>Harvest</h2>

    <p><strong>Harvest ID:</strong> <?= htmlspecialchars((string)($sale['harvest_id'] ?? '-')) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars((string)($sale['harvest_date'] ?? '-')) ?></p>
    <p><strong>Weight:</strong> <?= htmlspecialchars((string)($sale['weight_grams'] ?? '-')) ?> g</p>
    <p><strong>Quality:</strong> <?= htmlspecialchars((string)($sale['quality_notes'] ?? '-')) ?></p>
</div>

<div class="card">
    <h2>Grow Batch</h2>

    <p><strong>Batch ID:</strong> <?= htmlspecialchars((string)($sale['batch_id'] ?? '-')) ?></p>
    <p><strong>Crop:</strong> <?= htmlspecialchars((string)($sale['crop'] ?? '-')) ?></p>
    <p><strong>Sow date:</strong> <?= htmlspecialchars((string)($sale['sow_date'] ?? '-')) ?></p>
    <p><strong>Expected harvest:</strong> <?= htmlspecialchars((string)($sale['expected_harvest_date'] ?? '-')) ?></p>
    <p><strong>Harvest date:</strong> <?= htmlspecialchars((string)($sale['batch_harvest_date'] ?? '-')) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars((string)($sale['batch_status'] ?? '-')) ?></p>
</div>

<p>
    <a class="btn" href="list_sales.php">← Back to Sales</a>
</p>

</div>

<?php include 'includes/footer.php'; ?>