<?php
require 'config/database.php';

$result = $db->query("
SELECT
    substr(sale_date,1,7) AS maand,
    SUM(amount) AS omzet
FROM sales
GROUP BY maand
ORDER BY maand DESC
");

echo "<h1>Omzet per maand</h1>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Maand</th><th>Omzet</th></tr>";

foreach ($result as $row) {
    echo "<tr>";
    echo "<td>" . $row['maand'] . "</td>";
    echo "<td>EUR " . number_format($row['omzet'], 2, ',', '.') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><br><a href='index.php'>Menu</a>";
?>