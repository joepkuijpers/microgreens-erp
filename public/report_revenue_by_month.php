<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/includes/header.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$rows = $db->query("
    SELECT
        substr(sale_date,1,7) AS maand,
        SUM(amount) AS omzet
    FROM sales
    GROUP BY maand
    ORDER BY maand DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>📅 Omzet per maand</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen omzetgegevens gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Maand</th>
                <th>Omzet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['maand']) ?></td>
                    <td>€ <?= htmlspecialchars(number_format((float) $row['omzet'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include '../app/includes/footer.php'; ?>
