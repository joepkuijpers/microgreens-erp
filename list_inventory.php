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
")->fetchAll(PDO::FETCH_ASSOC);

$totalValue = $db->query("
    SELECT COALESCE(SUM(quantity * unit_cost), 0) AS total
    FROM inventory
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📦 Voorraadbeheer</h1>

    <p>
        <a class="btn" href="add_inventory_form.php">➕ Voorraad toevoegen</a>
        <a class="btn" href="inventory_transactions.php">📋 Mutaties</a>
    </p>
    
    <div class="card">
        <h2>Totale voorraadwaarde</h2>
        <h1>€ <?= number_format((float)$totalValue['total'], 2, ',', '.') ?></h1>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Artikel</th>
                    <th>Categorie</th>
                    <th>Hoeveelheid</th>
                    <th>Eenheid</th>
                    <th>Kostprijs</th>
                    <th>Waarde</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['category'] ?? '-') ?></td>
                        <td><?= number_format((float)$item['quantity'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                        <td>€ <?= number_format((float)$item['unit_cost'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format((float)$item['total_value'], 2, ',', '.') ?></td>
                        <td>
                            <a class="btn" href="edit_inventory.php?id=<?= urlencode($item['id']) ?>">✏️ Bewerken</a>
                            <a class="btn" href="delete_inventory.php?id=<?= urlencode($item['id']) ?>" onclick="return confirm('Weet je zeker dat je dit voorraaditem wilt verwijderen?');">🗑️ Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>