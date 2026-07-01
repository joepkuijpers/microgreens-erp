<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig batch-ID.');
}

$stmt = $db->prepare("
    SELECT
        g.*,
        i.item_name AS seed_name,
        i.category AS seed_category,
        i.unit AS seed_stock_unit
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    WHERE g.id = :id
");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die('Batch niet gevonden.');
}

$harvests = $db->prepare("
    SELECT *
    FROM harvests
    WHERE batch_id = :id
    ORDER BY harvest_date DESC, id DESC
");
$harvests->execute([':id' => $id]);
$harvestRows = $harvests->fetchAll(PDO::FETCH_ASSOC);

$transactions = $db->prepare("
    SELECT *
    FROM inventory_transactions
    WHERE reference_type = 'grow_batch'
      AND reference_id = :id
    ORDER BY transaction_date DESC, id DESC
");
$transactions->execute([':id' => $id]);
$transactionRows = $transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🔍 Batch details</h1>

    <p>
        <a class="btn" href="grow_batches.php">← Terug naar batchbeheer</a>
        <a class="btn" href="edit_batch.php?id=<?= urlencode($batch['id']) ?>">✏️ Bewerken</a>
        <a class="btn" href="harvest_batch.php?id=<?= urlencode($batch['id']) ?>">🌾 Oogsten</a>
    </p>

    <div class="card">
        <h2>Batchinformatie</h2>
        <table>
            <tr><th>ID</th><td><?= htmlspecialchars($batch['id']) ?></td></tr>
            <tr><th>Gewas</th><td><?= htmlspecialchars($batch['crop']) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($batch['status']) ?></td></tr>
            <tr><th>Zaaidatum</th><td><?= htmlspecialchars($batch['sow_date']) ?></td></tr>
            <tr><th>Verwachte oogstdatum</th><td><?= htmlspecialchars($batch['expected_harvest_date'] ?? '-') ?></td></tr>
            <tr><th>Werkelijke oogstdatum</th><td><?= htmlspecialchars($batch['harvest_date'] ?? '-') ?></td></tr>
            <tr><th>Aantal trays</th><td><?= htmlspecialchars($batch['tray_count']) ?></td></tr>
            <tr><th>Traytype</th><td><?= htmlspecialchars($batch['tray_type']) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Zaad / grondstof</h2>
        <table>
            <tr><th>Zaaditem</th><td><?= htmlspecialchars($batch['seed_name'] ?? 'Niet gekoppeld') ?></td></tr>
            <tr><th>Categorie</th><td><?= htmlspecialchars($batch['seed_category'] ?? '-') ?></td></tr>
            <tr>
                <th>Verbruikt</th>
                <td>
                    <?= number_format((float)($batch['seed_amount'] ?? 0), 2, ',', '.') ?>
                    <?= htmlspecialchars($batch['seed_unit'] ?? '') ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Oogsten</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Datum</th>
                    <th>Gewicht gram</th>
                    <th>Kwaliteit / opmerkingen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($harvestRows) === 0): ?>
                    <tr><td colspan="4">Nog geen oogsten geregistreerd.</td></tr>
                <?php endif; ?>

                <?php foreach ($harvestRows as $harvest): ?>
                    <tr>
                        <td><?= htmlspecialchars($harvest['id']) ?></td>
                        <td><?= htmlspecialchars($harvest['harvest_date']) ?></td>
                        <td><?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($harvest['quality_notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Voorraadmutaties gekoppeld aan deze batch</h2>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Type</th>
                    <th>Voor</th>
                    <th>Na</th>
                    <th>Verschil</th>
                    <th>Eenheid</th>
                    <th>Opmerking</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactionRows) === 0): ?>
                    <tr><td colspan="7">Geen gekoppelde voorraadmutaties.</td></tr>
                <?php endif; ?>

                <?php foreach ($transactionRows as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                        <td><?= htmlspecialchars($transaction['type']) ?></td>
                        <td><?= number_format((float)$transaction['quantity_before'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$transaction['quantity_after'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$transaction['quantity_change'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($transaction['unit'] ?? '') ?></td>
                        <td><?= htmlspecialchars($transaction['note'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
