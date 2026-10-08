<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/includes/header.php';
include '../app/includes/language.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$items = $db->query("
    SELECT
        i.id,
        i.item_name,
        i.category,
        i.quantity,
        i.unit,
        i.unit_cost,
        s.name AS supplier_name,
        (i.quantity * i.unit_cost) AS total_value
    FROM inventory i
    LEFT JOIN suppliers s
        ON s.id = i.supplier_id
    WHERE i.is_active = 1
    ORDER BY i.category ASC, i.item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalValue = $db->query("
    SELECT COALESCE(SUM(quantity * unit_cost), 0) AS total
    FROM inventory
    WHERE is_active = 1
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
                        <th><?= htmlspecialchars(__('supplier')) ?></th>
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
                            <td>
                                <?= htmlspecialchars(
                                    (string)(
                                        $item['supplier_name']
                                        ?? __('supplier_not_linked')
                                    )
                                ) ?>
                            </td>
                            <td><?= htmlspecialchars((string)($item['category'] ?? '-')) ?></td>
                            <td><?= number_format((float)$item['quantity'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($item['unit'] ?? '-')) ?></td>
                            <td>€ <?= number_format((float)$item['unit_cost'], 2, ',', '.') ?></td>
                            <td>€ <?= number_format((float)$item['total_value'], 2, ',', '.') ?></td>
                            <td>
                                <a class="btn" href="edit_inventory.php?id=<?= urlencode((string)$item['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
                                <form
                                    method="post"
                                    action="delete_inventory.php"
                                    style="display: inline;"
                                    onsubmit="return confirm('<?= htmlspecialchars(__('confirm_archive_inventory_item')) ?>');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= htmlspecialchars((string)$item['id']) ?>"
                                    >
                                    <button type="submit" class="btn">
                                        📦 <?= htmlspecialchars(__('archive')) ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../app/includes/footer.php'; ?>
