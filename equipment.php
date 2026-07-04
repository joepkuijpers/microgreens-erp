<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';
include 'includes/energy_engine.php';

$rows = $db->query("
    SELECT
        id,
        name,
        rack_name,
        wattage,
        hours_per_day,
        is_active
    FROM equipment
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>⚡ Apparatuur</h1>

<p>
    <a href="add_equipment.php" class="btn">
        ➕ Apparatuur toevoegen
    </a>
</p>

<?php if (empty($rows)): ?>
    <p>Nog geen apparatuur geregistreerd.</p>
<?php else: ?>

<table>
    <thead>
        <tr>
            <th>Naam</th>
            <th>Rek</th>
            <th>Vermogen (W)</th>
            <th>Uren / dag</th>
            <th>kWh / dag</th>
            <th>Status</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $dailyKwh = calculateDailyKwh(
                (float) $row['wattage'],
                (float) $row['hours_per_day']
            );
            ?>
            <tr>
                <td><?= htmlspecialchars((string) $row['name']) ?></td>
                <td><?= htmlspecialchars((string) $row['rack_name']) ?></td>
                <td><?= htmlspecialchars((string) $row['wattage']) ?></td>
                <td><?= htmlspecialchars((string) $row['hours_per_day']) ?></td>
                <td><?= htmlspecialchars(number_format($dailyKwh, 3, ',', '.')) ?></td>
                <td><?= $row['is_active'] ? 'Actief' : 'Inactief' ?></td>
                <td>
    <a href="edit_equipment.php?id=<?= htmlspecialchars((string) $row['id']) ?>">✏️ Bewerken</a>
    |
    <a href="delete_equipment.php?id=<?= htmlspecialchars((string) $row['id']) ?>">🗑️ Verwijderen</a>
</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>