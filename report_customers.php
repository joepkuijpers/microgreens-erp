<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        customer_name,
        COUNT(*) AS orders,
        SUM(amount) AS revenue
    FROM sales
    GROUP BY customer_name
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>👥 Omzet per klant</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen verkopen gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Klant</th>
                <th>Aantal orders</th>
                <th>Omzet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['customer_name']) ?></td>
                    <td><?= htmlspecialchars((string) $row['orders']) ?></td>
                    <td>€ <?= htmlspecialchars(number_format((float) $row['revenue'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>