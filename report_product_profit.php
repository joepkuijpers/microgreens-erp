<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        product,
        COUNT(*) AS sales_count,
        COALESCE(SUM(amount), 0) AS revenue
    FROM sales
    GROUP BY product
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📊 Winst per product (voorlopig)</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Verkopen</th>
                    <th>Omzet</th>
                    <th>Geschatte kosten</th>
                    <th>Geschatte winst</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5">Nog geen verkopen gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <?php
                    $estimatedCost = (float)$row['revenue'] * 0.40;
                    $profit = (float)$row['revenue'] - $estimatedCost;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['product']) ?></td>
                        <td><?= (int)$row['sales_count'] ?></td>
                        <td>€ <?= number_format((float)$row['revenue'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format($estimatedCost, 2, ',', '.') ?></td>
                        <td>€ <?= number_format($profit, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>