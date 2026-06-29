<?php
require 'config/database.php';

$result = $db->query("
SELECT
    customers.name AS customer_name,
    COUNT(sales.id) AS sale_count,
    SUM(sales.amount) AS total_revenue
FROM sales
LEFT JOIN customers ON sales.customer_id = customers.id
GROUP BY customers.id
ORDER BY total_revenue DESC
");

echo "<h1>Omzet per klant</h1>";

foreach ($result as $row) {
    echo ($row['customer_name'] ?? 'Onbekende klant') .
         " - " . $row['sale_count'] .
         " verkopen - EUR " .
         number_format($row['total_revenue'], 2) . "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
