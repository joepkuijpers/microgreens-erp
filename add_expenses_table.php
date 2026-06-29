<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expense_date TEXT,
    description TEXT,
    amount REAL
);
");

echo "Expenses tabel aangemaakt!";
?>
