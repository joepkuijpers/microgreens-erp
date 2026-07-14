<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        COALESCE(products.name, 'Onbekend product') AS product_name,
        count(sales.id) AS sale_count,
        COALESCE(SUM(sales.amount), 0) AS total_revenue
    FROM sales
    LEFT JOIN products ON sales.product_id = products.id
    GROUP BY products.id, products.name
    ORDER BY total_revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📊 Omzet per product</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Aantal verkopen</th>
                    <th>Totale omzet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3">Nog geen verkopen gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['product_name']) ?></td>
                        <td><?= (int)$row['sale_count'] ?></td>
                        <td>€ <?= number_format((float)$row['total_revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>