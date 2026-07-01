<?php
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig voorraad-ID.');
}

$stmt = $db->prepare("DELETE FROM inventory WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$stmt->execute();

header('Location: list_inventory.php');
exit;
