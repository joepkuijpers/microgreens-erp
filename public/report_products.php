<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/includes/header.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$rows = $db->query("
    SELECT
        product,
        count(*) AS sales_count,
        SUM(amount) AS revenue
    FROM sales
    GROUP BY product
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>📦 Omzet per product</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen verkopen gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Aantal verkopen</th>
                <th>Omzet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['product']) ?></td>
                    <td><?= htmlspecialchars((string) $row['sales_count']) ?></td>
                    <td>€ <?= htmlspecialchars(number_format((float) $row['revenue'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include '../app/includes/footer.php'; ?>
