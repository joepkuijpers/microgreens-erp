<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        substr(sale_date, 1, 7) AS month,
        SUM(amount) AS revenue
    FROM sales
    GROUP BY month
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📊 Omzet per maand</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Maand</th>
                    <th>Omzet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="2">Nog geen omzet gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['month']) ?></td>
                        <td>€ <?= number_format((float)$row['revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>