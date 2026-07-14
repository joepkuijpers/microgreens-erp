<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        customers.name,
        SUM(sales.amount) AS omzet
    FROM sales
    JOIN customers ON sales.customer_id = customers.id
    GROUP BY customers.id, customers.name
    ORDER BY omzet DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>🏆 Top klanten</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen verkopen gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Klant</th>
                <th>Omzet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['name']) ?></td>
                    <td>€ <?= htmlspecialchars(number_format((float) $row['omzet'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>