<?php
require_once 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

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
    echo "<h1>🗑 Apparatuur verwijderen</h1>";
    echo "<p>Apparaat niet gevonden.</p>";
    include 'includes/footer.php';
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

echo "<h1>🗑 " . __('delete_equipment') . "</h1>";

<p>Weet je zeker dat je dit apparaat wilt verwijderen?</p>

<p>
    <strong><?= htmlspecialchars((string) $equipment['name']) ?></strong>
</p>

<form method="post">
    <button type="submit">Ja, verwijderen</button>
    <a href="equipment.php">Annuleren</a>
</form>

<?php include 'includes/footer.php'; ?>