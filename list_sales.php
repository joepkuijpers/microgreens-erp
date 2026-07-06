<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$sales = $db->query("
    SELECT
        s.id,
        s.sale_date,
        s.quantity,
        s.amount,
        s.status,
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
    <h1>🛒 <?= htmlspecialchars(t('sales')) ?></h1>

<p>
    <a class="btn" href="add_sale_form.php">➕ <?= htmlspecialchars(t('new_sale')) ?></a>
    <a class="btn" href="list_finished_inventory.php">📦 <?= htmlspecialchars(t('finished_inventory')) ?></a>
</p>

<div class="card">
    <h2><?= htmlspecialchars(t('total_revenue')) ?></h2>
        <h1>€ <?= number_format((float)$total['total'], 2, ',', '.') ?></h1>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
        <th><?= htmlspecialchars(t('id')) ?></th>
        <th><?= htmlspecialchars(t('date')) ?></th>
        <th><?= htmlspecialchars(t('customer')) ?></th>
        <th><?= htmlspecialchars(t('product')) ?></th>
        <th><?= htmlspecialchars(t('quantity')) ?></th>
        <th><?= htmlspecialchars(t('amount')) ?></th>
        <th><?= htmlspecialchars(t('status')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                         <td><?= htmlspecialchars((string)$sale['id']) ?></td>
                        <td><?= htmlspecialchars((string)$sale['sale_date']) ?></td>
                        <td><?= htmlspecialchars((string)($sale['customer_name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($sale['product_name'] ?? '-')) ?></td>
                        <td><?= number_format((float)$sale['quantity'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format((float)$sale['amount'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)$sale['status']) ?></td>                  
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>