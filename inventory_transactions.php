<?php
include 'db_connect.php';

$columns = $db->query("PRAGMA table_info(grow_batches)")->fetchAll(PDO::FETCH_ASSOC);
$existing = [];

foreach ($columns as $column) {
    $existing[] = $column['name'];
}

if (!in_array('inventory_id', $existing)) {
    $db->exec("ALTER TABLE grow_batches ADD COLUMN inventory_id INTEGER");
}

if (!in_array('seed_amount', $existing)) {
    $db->exec("ALTER TABLE grow_batches ADD COLUMN seed_amount REAL DEFAULT 0");
}

if (!in_array('seed_unit', $existing)) {
    $db->exec("ALTER TABLE grow_batches ADD COLUMN seed_unit TEXT");
}

echo "grow_batches voorraadkoppeling is aangemaakt of bestond al.";