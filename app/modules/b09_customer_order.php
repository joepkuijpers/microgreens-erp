<?php
if (!isset($db_path)) {
    $db_path = __DIR__ . "/MicrogreensERP_Live.sqlite";
}
$order_msg = "";

try {
    $pdo_b09 = new PDO("sqlite:" . $db_path);
    $pdo_b09->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo_b09->exec("CREATE TABLE IF NOT EXISTS customer_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        product_name TEXT,
        quantity INTEGER,
        status TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["customer_name"])) {
        $cname = trim($_POST["customer_name"]);
        $pname = trim($_POST["product_name"]);
        $qty = (int)$_POST["quantity"];
        $stat = "Nieuw";

        $stmt = $pdo_b09->prepare("INSERT INTO customer_orders (customer_name, product_name, quantity, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cname, $pname, $qty, $stat]);
        $order_msg = "Verkooporder succesvol opgeslagen voor " . htmlspecialchars($cname) . "!";
    }

    $orders = $pdo_b09->query("SELECT * FROM customer_orders ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $order_msg = "Database Fout in B09: " . $e->getMessage();
    $orders = [];
}
?>
<div style="background: #fff; padding: 15px; border-radius: 6px;">
    <p>Plaats direct een nieuwe verkooporder of bekijk recente bestellingen.</p>
    <?php if (!empty($order_msg)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?php echo $order_msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Klantnaam:</label>
        <input type="text" name="customer_name" required>
        <label>Product / Gewas:</label>
        <input type="text" name="product_name" required>
        <label>Aantal / Stuks:</label>
        <input type="number" name="quantity" required>
        <button type="submit">Bestelling Plaatsen</button>
    </form>

    <h3 style="margin-top: 25px;">Recente Verkooporders</h3>
    <table>
        <tr><th>ID</th><th>Klant</th><th>Product</th><th>Aantal</th><th>Status</th><th>Tijd</th></tr>
        <?php if (!empty($orders)): foreach ($orders as $ord): ?>
        <tr>
            <td><?php echo $ord["id"]; ?></td>
            <td><?php echo htmlspecialchars($ord["customer_name"]); ?></td>
            <td><?php echo htmlspecialchars($ord["product_name"]); ?></td>
            <td><?php echo $ord["quantity"]; ?></td>
            <td><?php echo htmlspecialchars($ord["status"]); ?></td>
            <td><?php echo htmlspecialchars($ord["created_at"]); ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6">Nog geen bestellingen geplaatst.</td></tr>
        <?php endif; ?>
    </table>
</div>
