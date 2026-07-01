<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$transactions = $db->query("
    SELECT
        t.id,
        t.transaction_date,
        t.type,
        t.quantity_change,
        t.quantity_before,
        t.quantity_after,
        t.unit,
        t.note,
        i.item_name
    FROM inventory_transactions t
    LEFT JOIN inventory i ON i.id = t.inventory_id
    ORDER BY t.transaction_date DESC, t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📋 Voorraadmutaties</h1>

    <p>
        <a class="btn" href="list_inventory.php">← Terug naar voorraad</a>
    </p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Artikel</th>
                    <th>Type</th>
                    <th>Voor</th>
                    <th>Na</th>
                    <th>Verschil</th>
                    <th>Eenheid</th>
                    <th>Opmerking</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['transaction_date']) ?></td>
                        <td><?= htmlspecialchars($t['item_name'] ?? 'Onbekend/verwijderd') ?></td>
                        <td><?= htmlspecialchars($t['type']) ?></td>
                        <td><?= number_format((float)$t['quantity_before'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$t['quantity_after'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$t['quantity_change'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($t['unit'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['note'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
