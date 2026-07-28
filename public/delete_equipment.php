<?php
require_once '../app/includes/auth.php';
auth_require_login();
require_once '../app/includes/language.php';
include '../app/includes/header.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT
        id,
        name
    FROM equipment
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);

$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
    echo "<h1>🗑 " . __('delete_equipment') . "</h1>";
    echo "<p>" . htmlspecialchars(__('equipment_not_found')) . "</p>";
    include '../app/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete = $db->prepare("
        DELETE FROM equipment
        WHERE id = :id
    ");

    $delete->execute([
        ':id' => $id
    ]);

    header('Location: equipment.php');
    exit;
}
?>

<h1>🗑 <?= __('delete_equipment') ?></h1>

<p><?= __('confirm_delete_equipment') ?></p>

<p>
    <strong><?= htmlspecialchars((string) $equipment['name']) ?></strong>
</p>

<form method="post">
    <button type="submit"><?= __('yes_delete') ?></button>
    <a href="equipment.php"><?= __('cancel') ?></a>
</form>

<?php include '../app/includes/footer.php'; ?>
