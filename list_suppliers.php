<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$suppliers = $db->query("
    SELECT
        id,
        name
    FROM suppliers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1><?= htmlspecialchars(t('suppliers')) ?></h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('id')) ?></th>
                    <th><?= htmlspecialchars(t('name')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="2"><?= htmlspecialchars(t('no_suppliers_found')) ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$supplier['id']) ?></td>
                        <td><?= htmlspecialchars((string)$supplier['name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>