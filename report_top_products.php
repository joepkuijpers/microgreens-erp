<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        products.name AS product_name,
        COALESCE(SUM(sales.amount), 0) AS revenue
    FROM sales
    JOIN products ON sales.product_id = products.id
    GROUP BY products.id, products.name
    ORDER BY revenue DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📊 Top producten</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Omzet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="2">Nog geen verkopen gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['product_name']) ?></td>
                        <td>€ <?= number_format((float)$row['revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>