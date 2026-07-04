<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT
        id,
        name,
        rack_name,
        wattage,
        hours_per_day,
        is_active
    FROM equipment
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);

$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
 echo "<h1>✏️ " . __('edit_equipment') . "</h1>";   
    echo "<p>Apparaat niet gevonden.</p>";
    include 'includes/footer.php';
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $rack = trim($_POST['rack_name'] ?? '');
    $wattage = (float) ($_POST['wattage'] ?? 0);
    $hours = (float) ($_POST['hours_per_day'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    $update = $db->prepare("
        UPDATE equipment
        SET
            name = :name,
            rack_name = :rack,
            wattage = :wattage,
            hours_per_day = :hours,
            is_active = :active
        WHERE id = :id
    ");

    $update->execute([
        ':name' => $name,
        ':rack' => $rack,
        ':wattage' => $wattage,
        ':hours' => $hours,
        ':active' => $active,
        ':id' => $id
    ]);

    $message = 'Apparaat succesvol bijgewerkt.';

    $stmt->execute([
        ':id' => $id
    ]);

    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<h1>✏️ <?= __('edit_equipment') ?></h1>

<?php if ($message !== ''): ?>
    <p><?= htmlspecialchars((string) $message) ?></p>
<?php endif; ?>

<form method="post">
    <label>Naam</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars((string) $equipment['name']) ?>" required><br><br>

    <label>Rek</label><br>
    <input type="text" name="rack_name" value="<?= htmlspecialchars((string) $equipment['rack_name']) ?>"><br><br>

    <label>Vermogen (W)</label><br>
    <input type="number" name="wattage" step="0.1" min="0" value="<?= htmlspecialchars((string) $equipment['wattage']) ?>"><br><br>

    <label>Uren per dag</label><br>
    <input type="number" name="hours_per_day" step="0.1" min="0" value="<?= htmlspecialchars((string) $equipment['hours_per_day']) ?>"><br><br>

    <label>
        <input type="checkbox" name="is_active" <?= $equipment['is_active'] ? 'checked' : '' ?>>
        Actief
    </label><br><br>

    <button type="submit">Opslaan</button>
</form>

<?php include 'includes/footer.php'; ?>