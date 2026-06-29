<?php
require 'config/database.php';

$result = $db->query("
SELECT
    products.name AS product_name,
    COUNT(sales.id) AS sale_count,
    SUM(sales.amount) AS total_revenue
FROM sales
LEFT JOIN products ON sales.product_id = products.id
GROUP BY products.id
ORDER BY total_revenue DESC
");

echo "<h1>Omzet per product</h1>";

foreach ($result as $row) {
    echo ($row['product_name'] ?? 'Onbekend product') .
         " - " . $row['sale_count'] .
         " verkopen - EUR " .
         number_format($row['total_revenue'], 2) . "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
