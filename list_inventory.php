<?php
include 'includes/header.php';
include 'includes/language.php';
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
    <h1>📦 <?= htmlspecialchars(__('inventory_management')) ?></h1>

    <p>
        <a class="btn" href="add_inventory_form.php">➕ <?= htmlspecialchars(__('add_inventory')) ?></a>
        <a class="btn" href="inventory_transactions.php">📋 <?= htmlspecialchars(__('transactions')) ?></a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('total_inventory_value')) ?></h2>
        <h1>€ <?= number_format((float)$totalValue['total'], 2, ',', '.') ?></h1>
    </div>

    <div class="card inventory-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= htmlspecialchars(__('item')) ?></th>
                        <th><?= htmlspecialchars(__('category')) ?></th>
                        <th><?= htmlspecialchars(__('quantity')) ?></th>
                        <th><?= htmlspecialchars(__('unit')) ?></th>
                        <th><?= htmlspecialchars(__('unit_cost_short')) ?></th>
                        <th><?= htmlspecialchars(__('value')) ?></th>
                        <th><?= htmlspecialchars(__('actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$item['id']) ?></td>
                            <td><?= htmlspecialchars((string)$item['item_name']) ?></td>
                            <td><?= htmlspecialchars((string)($item['category'] ?? '-')) ?></td>
                            <td><?= number_format((float)$item['quantity'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($item['unit'] ?? '-')) ?></td>
                            <td>€ <?= number_format((float)$item['unit_cost'], 2, ',', '.') ?></td>
                            <td>€ <?= number_format((float)$item['total_value'], 2, ',', '.') ?></td>
                            <td>
                                <a class="btn" href="edit_inventory.php?id=<?= urlencode((string)$item['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
                                <a class="btn" href="delete_inventory.php?id=<?= urlencode((string)$item['id']) ?>" onclick="return confirm('<?= htmlspecialchars(__('confirm_delete_inventory_item')) ?>');">🗑️ <?= htmlspecialchars(__('delete')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>