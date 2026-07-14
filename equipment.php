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

<div class="main">
    <h1>⚡ <?= htmlspecialchars(__('equipment')) ?></h1>

    <p>
        <a href="add_equipment.php" class="btn">
            ➕ <?= htmlspecialchars(__('add_equipment')) ?>
        </a>
    </p>

    <?php if (empty($rows)): ?>
        <div class="card">
            <p><?= htmlspecialchars(__('no_equipment_found')) ?></p>
        </div>
    <?php else: ?>
        <div class="card equipment-table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(__('name')) ?></th>
                            <th><?= htmlspecialchars(__('rack')) ?></th>
                            <th><?= htmlspecialchars(__('wattage_w')) ?></th>
                            <th><?= htmlspecialchars(__('hours_per_day')) ?></th>
                            <th><?= htmlspecialchars(__('kwh_per_day')) ?></th>
                            <th><?= htmlspecialchars(__('status')) ?></th>
                            <th><?= htmlspecialchars(__('actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $dailyKwh = calculateDailyKwh(
                                (float)$row['wattage'],
                                (float)$row['hours_per_day']
                            );
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['name']) ?></td>
                                <td><?= htmlspecialchars((string)$row['rack_name']) ?></td>
                                <td><?= htmlspecialchars((string)$row['wattage']) ?></td>
                                <td><?= htmlspecialchars((string)$row['hours_per_day']) ?></td>
                                <td><?= htmlspecialchars(number_format($dailyKwh, 3, ',', '.')) ?></td>
                                <td><?= htmlspecialchars($row['is_active'] ? __('active') : __('inactive')) ?></td>
                                <td>
                                    <a href="edit_equipment.php?id=<?= urlencode((string)$row['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
                                    |
                                    <a href="delete_equipment.php?id=<?= urlencode((string)$row['id']) ?>">🗑️ <?= htmlspecialchars(__('delete')) ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>