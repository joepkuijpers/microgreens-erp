<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/includes/header.php';
include '../app/includes/language.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$customers = $db->query("
    SELECT
        id,
        name,
        email,
        phone
    FROM customers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1><?= htmlspecialchars(__('customers')) ?></h1>

    <div class="card customers-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('id')) ?></th>
                        <th><?= htmlspecialchars(__('name')) ?></th>
                        <th><?= htmlspecialchars(__('email')) ?></th>
                        <th><?= htmlspecialchars(__('phone')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="4"><?= htmlspecialchars(__('no_customers_found')) ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$customer['id']) ?></td>
                            <td><?= htmlspecialchars((string)$customer['name']) ?></td>
                            <td><?= htmlspecialchars((string)($customer['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($customer['phone'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../app/includes/footer.php'; ?>
