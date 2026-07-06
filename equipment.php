<?php
require_once 'includes/language.php';
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

<h1>⚡ <?= __('equipment') ?></h1>

<p>
    <a href="add_equipment.php" class="btn">
        ➕ <?= __('add_equipment') ?>
    </a>
</p>

<?php if (empty($rows)): ?>
    <p><?= __('no_equipment_found') ?></p>
<?php else: ?>

<table>
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('rack') ?></th>
            <th><?= __('wattage_w') ?></th>
            <th><?= __('hours_per_day') ?></th>
            <th><?= __('kwh_per_day') ?></th>
            <th><?= __('status') ?></th>
            <th><?= __('actions') ?></th>
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
                <td><?= $row['is_active'] ? __('active') : __('inactive') ?></td>
                <td>
                    <a href="edit_equipment.php?id=<?= htmlspecialchars((string) $row['id']) ?>">✏️ <?= __('edit') ?></a>
                    |
                    <a href="delete_equipment.php?id=<?= htmlspecialchars((string) $row['id']) ?>">🗑️ <?= __('delete') ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>