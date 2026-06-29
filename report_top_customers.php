<?php
require 'config/database.php';

$result = $db->query("
SELECT customers.name, SUM(sales.amount) AS omzet
FROM sales
JOIN customers ON sales.customer_id = customers.id
GROUP BY customers.id
ORDER BY omzet DESC
LIMIT 10
");

echo "<h1>Top klanten</h1>";

foreach ($result as $row) {
    echo $row['name'] . " - EUR " . number_format($row['omzet'], 2) . "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
