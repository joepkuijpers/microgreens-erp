<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$harvests = $db->query("
    SELECT
        id,
        weight_grams,
        quality_notes
    FROM harvests
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🌾 <?= htmlspecialchars(__('harvests')) ?></h1>

    <div class="card harvests-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= htmlspecialchars(__('weight')) ?></th>
                        <th><?= htmlspecialchars(__('quality_notes')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($harvests)): ?>
                        <tr>
                            <td colspan="3"><?= htmlspecialchars(__('no_harvests_found')) ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($harvests as $harvest): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$harvest['id']) ?></td>
                            <td><?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?> gram</td>
                            <td><?= htmlspecialchars((string)($harvest['quality_notes'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>