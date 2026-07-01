<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$customers = $db->query("
    SELECT id, name
    FROM customers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$products = $db->query("
    SELECT
        f.product_id,
        p.name,
        f.quantity,
        f.unit,
        p.sale_price
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
    WHERE f.quantity > 0
    ORDER BY p.name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🛒 Nieuwe verkoop</h1>

    <div class="card">
        <form method="post" action="add_sale.php">
            <p>
                Klant<br>
                <select name="customer_id" required>
                    <option value="">-- Kies klant --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= htmlspecialchars($customer['id']) ?>">
                            <?= htmlspecialchars($customer['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                Verkoopdatum<br>
                <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required>
            </p>

            <p>
                Product uit eindvoorraad<br>
                <select name="product_id" required>
                    <option value="">-- Kies product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= htmlspecialchars($product['product_id']) ?>">
                            <?= htmlspecialchars($product['name']) ?>
                            (<?= number_format((float)$product['quantity'], 2, ',', '.') ?>
                            <?= htmlspecialchars($product['unit']) ?> beschikbaar,
                            €<?= number_format((float)$product['sale_price'], 2, ',', '.') ?> per stuk)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                Aantal<br>
                <input type="number" step="0.01" name="quantity" required>
            </p>

            <p>
                Status<br>
                <select name="status">
                    <option value="betaald">Betaald</option>
                    <option value="open">Open</option>
                    <option value="geannuleerd">Geannuleerd</option>
                </select>
            </p>

            <p>
                <button class="btn" type="submit">💾 Verkoop opslaan</button>
                <a class="btn" href="list_sales.php">Terug</a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>