<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$batches = $db->query("
    SELECT
        g.id,
        g.crop,
        g.sow_date,
        g.expected_harvest_date,
        g.tray_count,
        g.seed_amount,
        g.seed_unit,
        g.status,
        i.item_name AS inventory_item_name
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    ORDER BY g.sow_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🌱 <?= htmlspecialchars(t('batch_management')) ?></h1>

    <p>
        <a class="btn" href="add_batch.php">➕ <?= htmlspecialchars(t('new_batch')) ?></a>
    </p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= htmlspecialchars(t('crop')) ?></th>
                    <th><?= htmlspecialchars(t('sowing_date')) ?></th>
                    <th><?= htmlspecialchars(t('expected_harvest')) ?></th>
                    <th><?= htmlspecialchars(t('trays')) ?></th>
                    <th><?= htmlspecialchars(t('seed_inventory')) ?></th>
                    <th><?= htmlspecialchars(t('seed_usage')) ?></th>
                    <th><?= htmlspecialchars(t('status')) ?></th>
                    <th><?= htmlspecialchars(t('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                    <?php
                    $status = $batch['status'] ?? '';
                    $kleur = '#2563eb';

                    if ($status === 'Groeiend' || $status === 'gezaaid') {
                        $kleur = '#16a34a';
                    }

                    if ($status === 'Geoogst') {
                        $kleur = '#6b7280';
                    }

                    if ($status === 'Oogstklaar') {
                        $kleur = '#ea580c';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$batch['id']) ?></td>
                        <td><?= htmlspecialchars((string)$batch['crop']) ?></td>
                        <td><?= htmlspecialchars((string)($batch['sow_date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($batch['expected_harvest_date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($batch['tray_count'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($batch['inventory_item_name'] ?? '-')) ?></td>
                        <td>
                            <?= number_format((float)($batch['seed_amount'] ?? 0), 2, ',', '.') ?>
                            <?= htmlspecialchars((string)($batch['seed_unit'] ?? '')) ?>
                        </td>
                        <td>
                            <span style="background:<?= htmlspecialchars((string)$kleur) ?>; color:white; padding:6px 12px; border-radius:20px; font-weight:bold; display:inline-block; min-width:100px; text-align:center;">
                                <?= htmlspecialchars((string)$status) ?>
                            </span>
                        </td>
                        <td>
                            <a href="batch_details.php?id=<?= urlencode((string)$batch['id']) ?>">🔍 <?= htmlspecialchars(t('details')) ?></a> |
                            <a href="edit_batch.php?id=<?= urlencode((string)$batch['id']) ?>">✏️ <?= htmlspecialchars(t('edit')) ?></a> |
                            <a href="harvest_batch.php?id=<?= urlencode((string)$batch['id']) ?>">🌾 <?= htmlspecialchars(t('harvest')) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>