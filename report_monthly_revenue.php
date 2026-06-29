<?php
require 'config/database.php';

$result = $db->query("
SELECT
    substr(sale_date,1,7) as month,
    SUM(amount) as revenue
FROM sales
GROUP BY month
ORDER BY month
");

echo "<h1>Omzet per maand</h1>";

foreach($result as $row){
    echo $row['month'] .
         " - EUR " .
         number_format($row['revenue'],2) .
         "<br>";
}
?>
