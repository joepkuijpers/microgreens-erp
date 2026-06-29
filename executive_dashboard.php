<?php
require 'config/database.php';

$revenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn();
$expenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();

$profit = $revenue - $expenses;

$topCustomer = $db->query("
SELECT customer_name
FROM sales
GROUP BY customer_name
ORDER BY SUM(amount) DESC
LIMIT 1
")->fetchColumn();

$topProduct = $db->query("
SELECT product
FROM sales
GROUP BY product
ORDER BY SUM(amount) DESC
LIMIT 1
")->fetchColumn();

echo "<h1>Bedrijfsdashboard</h1>";

echo "<p>Omzet: EUR " . number_format($revenue,2) . "</p>";
echo "<p>Kosten: EUR " . number_format($expenses,2) . "</p>";
echo "<p>Winst: EUR " . number_format($profit,2) . "</p>";

echo "<hr>";

echo "<p>Beste klant: " . $topCustomer . "</p>";
echo "<p>Best product: " . $topProduct . "</p>";

echo "<br><a href='index.php'>Menu</a>";
?>
