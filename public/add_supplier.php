<?php
require_once '../app/db_connect.php';
require_once '../app/includes/language.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_supplier_form.php');
    exit;
}

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    header('Location: add_supplier_form.php?error=name_required');
    exit;
}

$stmt = $db->prepare("
    INSERT INTO suppliers (name)
    VALUES (:name)
");

$stmt->execute([
    ':name' => $name,
]);

header('Location: list_suppliers.php?created=1');
exit;
