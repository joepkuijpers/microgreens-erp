<?php
require 'config/database.php';

$topProducts = $db->query("
SELECT product, SUM(amount) revenue
FROM sales
GROUP BY product
ORDER BY revenue DESC
LIMIT 5
");

echo "<h1>Top Producten</h1>";

foreach($topProducts as $row){
    echo $row['product'] .
         " - EUR " .
         number_format($row['revenue'],2) .
         "<br>";
}
?>
