<?php
require 'config/database.php';

$result = $db->query("
SELECT products.name, SUM(sales.amount) AS omzet
FROM sales
JOIN products ON sales.product_id = products.id
GROUP BY products.id
ORDER BY omzet DESC
LIMIT 10
");

echo "<h1>Top producten</h1>";

foreach ($result as $row) {
    echo $row['name'] . " - EUR " . number_format($row['omzet'], 2) . "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
