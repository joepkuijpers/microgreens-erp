<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $rack = trim($_POST['rack_name'] ?? '');
    $wattage = (float) ($_POST['wattage'] ?? 0);
    $hours = (float) ($_POST['hours_per_day'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($name !== '') {

        $stmt = $db->prepare("
            INSERT INTO equipment
                (
                    name,
                    rack_name,
                    wattage,
                    hours_per_day,
                    is_active
                )
            VALUES
                (
                    :name,
                    :rack,
                    :wattage,
                    :hours,
                    :active
                )
        ");

        $stmt->execute([
            ':name'     => $name,
            ':rack'     => $rack,
            ':wattage'  => $wattage,
            ':hours'    => $hours,
            ':active'   => $active
        ]);

        $message = 'Apparaat succesvol opgeslagen.';

    } else {

        $message = 'Naam is verplicht.';
    }
}
?>

<h1>➕ Apparatuur toevoegen</h1>

<?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">

    <label>Naam</label><br>
    <input
        type="text"
        name="name"
        required
    ><br><br>

    <label>Rek</label><br>
    <input
        type="text"
        name="rack_name"
    ><br><br>

    <label>Vermogen (W)</label><br>
    <input
        type="number"
        name="wattage"
        step="0.1"
        min="0"
    ><br><br>

    <label>Uren per dag</label><br>
    <input
        type="number"
        name="hours_per_day"
        step="0.1"
        min="0"
    ><br><br>

    <label>
        <input
            type="checkbox"
            name="is_active"
            checked
        >
        Actief
    </label>

    <br><br>

    <button type="submit">
        Opslaan
    </button>

</form>

<?php
include 'includes/footer.php';
?>