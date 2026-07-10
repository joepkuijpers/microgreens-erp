<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$transactions = $db->query("
    SELECT
        it.id,
        it.inventory_id,
        it.transaction_date,
        it.type,
        it.quantity_change,
        it.quantity_before,
        it.quantity_after,
        it.unit,
        it.note,
        i.item_name
    FROM inventory_transactions it
    LEFT JOIN inventory i ON i.id = it.inventory_id
    ORDER BY it.transaction_date DESC, it.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🔄 <?= htmlspecialchars(__('inventory_transactions')) ?></h1>

    <p>
        <a class="btn" href="list_inventory.php">
            ← <?= htmlspecialchars(__('back_to_inventory')) ?>
        </a>

        <a class="btn" href="add_inventory_transaction.php">
            ➕ <?= htmlspecialchars(__('add_inventory_transaction')) ?>
        </a>
    </p>

    <div class="card inventory-transactions-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Wijziging</th>
                        <th>Voor</th>
                        <th>Na</th>
                        <th>Opmerking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7">Nog geen voorraadmutaties gevonden.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$transaction['transaction_date']) ?></td>
                            <td>
                                <?php if (!empty($transaction['inventory_id'])): ?>
                                    <a href="edit_inventory.php?id=<?= urlencode((string)$transaction['inventory_id']) ?>">
                                        <?= htmlspecialchars((string)($transaction['item_name'] ?? 'Onbekend item')) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars((string)($transaction['item_name'] ?? 'Onbekend item')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)$transaction['type']) ?></td>
                            <td>
                                <?= htmlspecialchars(number_format((float)$transaction['quantity_change'], 2, ',', '.')) ?>
                                <?= htmlspecialchars((string)($transaction['unit'] ?? '')) ?>
                            </td>
                            <td><?= htmlspecialchars(number_format((float)$transaction['quantity_before'], 2, ',', '.')) ?></td>
                            <td><?= htmlspecialchars(number_format((float)$transaction['quantity_after'], 2, ',', '.')) ?></td>
                            <td><?= htmlspecialchars((string)($transaction['note'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>