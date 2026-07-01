<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$batches = $db->query("
    SELECT
        g.*,
        i.item_name AS inventory_item_name
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    ORDER BY g.sow_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🌱 Batchbeheer</h1>

    <p>
        <a class="btn" href="add_batch.php">➕ Nieuwe batch</a>
    </p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gewas</th>
                    <th>Zaaidatum</th>
                    <th>Verwachte oogst</th>
                    <th>Trays</th>
                    <th>Zaadvoorraad</th>
                    <th>Zaadverbruik</th>
                    <th>Status</th>
                    <th>Acties</th>
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
                        <td><?= htmlspecialchars($batch['id']) ?></td>
                        <td><?= htmlspecialchars($batch['crop']) ?></td>
                        <td><?= htmlspecialchars($batch['sow_date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($batch['expected_harvest_date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($batch['tray_count'] ?? '') ?></td>
                        <td><?= htmlspecialchars($batch['inventory_item_name'] ?? '-') ?></td>
                        <td>
                            <?= number_format((float)($batch['seed_amount'] ?? 0), 2, ',', '.') ?>
                            <?= htmlspecialchars($batch['seed_unit'] ?? '') ?>
                        </td>
                        <td>
                            <span style="background:<?= $kleur ?>; color:white; padding:6px 12px; border-radius:20px; font-weight:bold; display:inline-block; min-width:100px; text-align:center;">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
          <td>
    <a href="batch_details.php?id=<?= urlencode($batch['id']) ?>">🔍 Details</a> |
    <a href="edit_batch.php?id=<?= urlencode($batch['id']) ?>">✏️ Bewerken</a> |
    <a href="harvest_batch.php?id=<?= urlencode($batch['id']) ?>">🌾 Oogsten</a>
</td>           
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>