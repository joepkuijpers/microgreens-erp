<?php
require 'config/database.php';

$result = $db->query("
SELECT
    sales.id,
    sales.sale_date,
    sales.quantity,
    sales.amount,
    sales.status,
    customers.name AS customer_name,
    products.name AS product_name
FROM sales
LEFT JOIN customers ON sales.customer_id = customers.id
LEFT JOIN products ON sales.product_id = products.id
ORDER BY sales.id DESC
");

echo "<h1>Verkopen</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         ($row['customer_name'] ?? 'Onbekende klant') . " - " .
         ($row['product_name'] ?? 'Onbekend product') . " - " .
         $row['quantity'] . " stuks - EUR " .
         number_format($row['amount'], 2) . " - " .
         $row['status'] . "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
