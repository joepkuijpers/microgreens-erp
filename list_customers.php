<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

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
    <h1><?= htmlspecialchars(t('customers')) ?></h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('id')) ?></th>
                    <th><?= htmlspecialchars(t('name')) ?></th>
                    <th><?= htmlspecialchars(t('email')) ?></th>
                    <th><?= htmlspecialchars(t('phone')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="4"><?= htmlspecialchars(t('no_customers_found')) ?></td>
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

<?php include 'includes/footer.php'; ?>