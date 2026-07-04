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
    <h1>🌿 Producten</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Categorie</th>
                    <th>Eenheid</th>
                    <th>Verkoopprijs</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5">Nog geen producten gevonden.</td>
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