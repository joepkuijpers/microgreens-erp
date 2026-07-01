<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$items = $db->query("
    SELECT 
        id,
        item_name,
        category,
        quantity,
        unit,
        unit_cost,
        (quantity * unit_cost) AS total_value
    FROM inventory
    ORDER BY category ASC, item_name ASC
");

$totalValue = $db->query("
    SELECT COALESCE(SUM(quantity * unit_cost), 0) AS total
    FROM inventory
")->fetchArray(SQLITE3_ASSOC);
?>

<div class="main">
    <h1>Voorraadbeheer</h1>

    <div style="margin-bottom: 20px;">
        <a href="add_inventory_form.php" class="btn">+ Voorraad toevoegen</a>
    </div>

    <div class="card">
        <h2>Voorraadwaarde</h2>
        <p style="font-size: 24px; font-weight: bold;">
            € <?= number_format((float)$totalValue['total'], 2, ',', '.') ?>
        </p>
    </div>

    <div class="card">
        <h2>Voorraadlijst</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Artikel</th>
                    <th>Categorie</th>
                    <th>Hoeveelheid</th>
                    <th>Eenheid</th>
                    <th>Kostprijs</th>
                    <th>Totale waarde</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['category'] ?? '-') ?></td>
                        <td><?= number_format((float)$item['quantity'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                        <td>€ <?= number_format((float)$item['unit_cost'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format((float)$item['total_value'], 2, ',', '.') ?></td>
                        <td>
                            <a class="btn" href="edit_inventory.php?id=<?= urlencode($item['id']) ?>">Bewerken</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>