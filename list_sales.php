<?php
include 'db_connect.php';
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$sales = $db->query("
    SELECT
        s.id,
        s.sale_date,
        s.quantity,
        s.amount,
        s.status,
        s.batch_id,
        s.harvest_id,
        COALESCE(c.name, s.customer_name) AS customer_name,
        COALESCE(p.name, s.product) AS product_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN products p ON s.product_id = p.id
    ORDER BY s.sale_date DESC, s.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total = $db->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM sales
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🛒 <?= htmlspecialchars(__('sales')) ?></h1>

<p>
    <a class="btn" href="add_sale_form.php">➕ <?= htmlspecialchars(__('new_sale')) ?></a>
    <a class="btn" href="list_finished_inventory.php">📦 <?= htmlspecialchars(__('finished_inventory')) ?></a>
</p>

<div class="card">
    <h2><?= htmlspecialchars(__('total_revenue')) ?></h2>
        <h1>€ <?= number_format((float)$total['total'], 2, ',', '.') ?></h1>
    </div>

    <div class="card">
            <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('id')) ?></th>
                    <th><?= htmlspecialchars(__('date')) ?></th>
                    <th><?= htmlspecialchars(__('customer')) ?></th>
                    <th><?= htmlspecialchars(__('product')) ?></th>
                    <th>Batch</th>
                    <th>Harvest</th>
                    <th><?= htmlspecialchars(__('quantity')) ?></th>
                    <th><?= htmlspecialchars(__('amount')) ?></th>
                    <th><?= htmlspecialchars(__('status')) ?></th>
                <th>Details</th>
                </tr>
            </thead>
            <tbody>    
                <?php foreach ($sales as $sale): ?>
                    <tr>
                         <td><?= htmlspecialchars((string)$sale['id']) ?></td>
                        <td><?= htmlspecialchars((string)$sale['sale_date']) ?></td>
                        <td><?= htmlspecialchars((string)($sale['customer_name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($sale['product_name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($sale['batch_id'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($sale['harvest_id'] ?? '-')) ?></td>
                        <td><?= number_format((float)$sale['quantity'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format((float)$sale['amount'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)$sale['status']) ?></td>                  
                    <td><a class="btn" href="traceability_sale_detail.php?id=<?= urlencode((string)$sale['id']) ?>">🔎 Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>