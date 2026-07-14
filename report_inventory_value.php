<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$items = $db->query("
    SELECT
        item_name,
        quantity,
        unit,
        unit_cost,
        quantity * unit_cost AS value
    FROM inventory
    ORDER BY item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total = 0;

foreach ($items as $item) {
    $total += (float)$item['value'];
}
?>

<div class="main">
    <h1>📊 Voorraadwaarde</h1>

    <p>
        <a class="btn" href="list_inventory.php">← Terug naar voorraad</a>
    </p>

    <div class="card">
        <h2>Totale voorraadwaarde</h2>
        <h1>€ <?= number_format($total, 2, ',', '.') ?></h1>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Hoeveelheid</th>
                    <th>Eenheid</th>
                    <th>Kostprijs</th>
                    <th>Waarde</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5">Geen voorraaditems gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$item['item_name']) ?></td>
                        <td><?= number_format((float)$item['quantity'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)($item['unit'] ?? '-')) ?></td>
                        <td>€ <?= number_format((float)$item['unit_cost'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format((float)$item['value'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>