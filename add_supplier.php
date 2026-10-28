<?php
require_once '../app/includes/auth.php';
auth_require_login();
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

$duplicateCheck = $db->prepare("
    SELECT id
    FROM suppliers
    WHERE name COLLATE NOCASE = :name
    LIMIT 1
");

$duplicateCheck->execute([
    ':name' => $name,
]);

if ($duplicateCheck->fetchColumn() !== false) {
    header('Location: add_supplier_form.php?error=supplier_exists');
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
