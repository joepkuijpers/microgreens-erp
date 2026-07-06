<?php
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$customers = $db->query("
    SELECT
        id,
        name
    FROM customers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$products = $db->query("
    SELECT
        f.product_id,
        f.quantity,
        f.unit,
        p.name,
        p.sale_price
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
    WHERE f.quantity > 0
    ORDER BY p.name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
<h1>➕ <?= htmlspecialchars(__('new_sale')) ?></h1>

<p>
    <a class="btn" href="list_sales.php">← <?= htmlspecialchars(__('back_to_sales')) ?></a>
</p>

    <div class="card">
        <form method="post" action="add_sale.php">
            <label>Klant</label><br>
            <select name="customer_id" required>
                <option value="">-- Kies klant --</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= htmlspecialchars((string)$customer['id']) ?>">
                        <?= htmlspecialchars((string)$customer['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Product uit eindvoorraad</label><br>
            <select name="product_id" required>
                <option value="">-- Kies product --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= htmlspecialchars((string)$product['product_id']) ?>">
                        <?= htmlspecialchars((string)$product['name']) ?>
                        (<?= number_format((float)$product['quantity'], 2, ',', '.') ?>
                        <?= htmlspecialchars((string)($product['unit'] ?? '')) ?> beschikbaar,
                        € <?= number_format((float)$product['sale_price'], 2, ',', '.') ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Verkoopdatum</label><br>
            <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required><br><br>

            <label>Aantal</label><br>
            <input type="number" step="0.01" name="quantity" required><br><br>

            <label>Status</label><br>
            <select name="status" required>
                <option value="betaald">Betaald</option>
                <option value="open">Open</option>
                <option value="geannuleerd">Geannuleerd</option>
            </select><br><br>

            <button type="submit" class="btn"><?= htmlspecialchars(__('save_sale')) ?></button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
