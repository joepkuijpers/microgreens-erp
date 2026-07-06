<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$products = $db->query("
    SELECT
        id,
        name,
        category,
        unit,
        sale_price
    FROM products
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1><?= htmlspecialchars(__('products')) ?></h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('id')) ?></th>
                    <th><?= htmlspecialchars(__('product')) ?></th>
                    <th><?= htmlspecialchars(__('category')) ?></th>
                    <th><?= htmlspecialchars(__('unit')) ?></th>
                    <th><?= htmlspecialchars(__('sale_price')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5"><?= htmlspecialchars(__('no_products_found')) ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$product['id']) ?></td>
                        <td><?= htmlspecialchars((string)$product['name']) ?></td>
                        <td><?= htmlspecialchars((string)($product['category'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($product['unit'] ?? '-')) ?></td>
                        <td>€ <?= number_format((float)$product['sale_price'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>