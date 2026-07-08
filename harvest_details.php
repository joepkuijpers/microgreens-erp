<?php
include 'db_connect.php';
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_harvest_id'));
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
    LEFT JOIN grow_batches g ON h.batch_id = g.id
    WHERE h.id = :id
");
$stmt->execute([':id' => $id]);
$harvest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$harvest) {
    die(__('harvest_not_found'));
}
?>

<div class="main">
    <h1>🌾 <?= htmlspecialchars(__('harvest_details')) ?></h1>

    <p>
        <a class="btn" href="list_harvests.php">← <?= htmlspecialchars(__('back_to_harvests')) ?></a>

        <?php if (!empty($harvest['batch_id'])): ?>
            <a class="btn" href="batch_details.php?id=<?= urlencode((string)$harvest['batch_id']) ?>">
                🌱 <?= htmlspecialchars(__('view_grow_batch')) ?>
            </a>
        <?php endif; ?>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('harvest_information')) ?></h2>

        <table>
            <tr><th>ID</th><td><?= htmlspecialchars((string)$harvest['id']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('date')) ?></th><td><?= htmlspecialchars((string)($harvest['harvest_date'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('weight_grams')) ?></th><td><?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('quality_notes')) ?></th><td><?= htmlspecialchars((string)($harvest['quality_notes'] ?? '-')) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_information')) ?></h2>

        <table>
            <tr><th>ID</th><td><?= htmlspecialchars((string)($harvest['batch_id'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('crop')) ?></th><td><?= htmlspecialchars((string)($harvest['crop'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('sowing_date')) ?></th><td><?= htmlspecialchars((string)($harvest['sow_date'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('expected_harvest_date')) ?></th><td><?= htmlspecialchars((string)($harvest['expected_harvest_date'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('actual_harvest_date')) ?></th><td><?= htmlspecialchars((string)($harvest['batch_harvest_date'] ?? '-')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('status')) ?></th><td><?= htmlspecialchars((string)($harvest['batch_status'] ?? '-')) ?></td></tr>
        </table>
    </div>

    <p>
        <a class="btn" href="list_harvests.php">← <?= htmlspecialchars(__('back_to_harvests')) ?></a>

        <?php if (!empty($harvest['batch_id'])): ?>
            <a class="btn" href="batch_details.php?id=<?= urlencode((string)$harvest['batch_id']) ?>">
                🌱 <?= htmlspecialchars(__('view_grow_batch')) ?>
            </a>
        <?php endif; ?>
    </p>
</div>

<?php include 'includes/footer.php'; ?>