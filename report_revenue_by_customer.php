<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        COALESCE(customers.name, 'Onbekende klant') AS customer_name,
        count(sales.id) AS sale_count,
        COALESCE(SUM(sales.amount), 0) AS total_revenue
    FROM sales
    LEFT JOIN customers ON sales.customer_id = customers.id
    GROUP BY customers.id, customers.name
    ORDER BY total_revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📊 Omzet per klant</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Klant</th>
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
                        <td><?= htmlspecialchars((string)$row['customer_name']) ?></td>
                        <td><?= (int)$row['sale_count'] ?></td>
                        <td>€ <?= number_format((float)$row['total_revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>