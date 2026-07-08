<?php
include 'includes/header.php';
include 'includes/language.php';
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
        i.item_name AS inventory_item_name,
g.crop_profile_id,
cp.crop_name AS crop_profile_name
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    LEFT JOIN crop_profiles cp ON cp.id = g.crop_profile_id
    ORDER BY g.sow_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🌱 <?= htmlspecialchars(__('batch_management')) ?></h1>

    <p>
        <a class="btn" href="add_batch.php">➕ <?= htmlspecialchars(__('new_batch')) ?></a>
    </p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= htmlspecialchars(__('crop')) ?></th>
                    <th>Crop Profile</th>
                    <th><?= htmlspecialchars(__('sowing_date')) ?></th>
                    <th><?= htmlspecialchars(__('expected_harvest')) ?></th>
                    <th><?= htmlspecialchars(__('trays')) ?></th>
                    <th><?= htmlspecialchars(__('seed_inventory')) ?></th>
                    <th><?= htmlspecialchars(__('seed_usage')) ?></th>
                    <th><?= htmlspecialchars(__('status')) ?></th>
                    <th><?= htmlspecialchars(__('actions')) ?></th>
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
                        <td>
<?php if (!empty($batch['crop_profile_name'])): ?>
    <a href="crop_profile_details.php?id=<?= urlencode((string)$batch['crop_profile_id']) ?>">
        <?= htmlspecialchars($batch['crop_profile_name']) ?>
    </a>
<?php else: ?>
    -
<?php endif; ?>
</td>
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
                            <a href="batch_details.php?id=<?= urlencode((string)$batch['id']) ?>">🔍 <?= htmlspecialchars(__('details')) ?></a> |
                            <a href="edit_batch.php?id=<?= urlencode((string)$batch['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a> |
                            <a href="harvest_batch.php?id=<?= urlencode((string)$batch['id']) ?>">🌾 <?= htmlspecialchars(__('harvest')) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>