<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/includes/header.php';
include '../app/includes/language.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$suppliers = $db->query("
    SELECT
        id,
        name
    FROM suppliers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1><?= htmlspecialchars(__('suppliers')) ?></h1>

    <p>
        <a class="btn" href="add_supplier_form.php">
            <?= htmlspecialchars(__('new_supplier')) ?>
        </a>
    </p>

    <?php if (isset($_GET['created'])): ?>
        <div class="card">
            <p><?= htmlspecialchars(__('supplier_saved')) ?></p>
        </div>
    <?php endif; ?>

    <div class="card suppliers-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('id')) ?></th>
                        <th><?= htmlspecialchars(__('name')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="2"><?= htmlspecialchars(__('no_suppliers_found')) ?></td>
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
</div>

<?php include '../app/includes/footer.php'; ?>
