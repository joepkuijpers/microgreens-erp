<?php
$planning = $productionData['planning'] ?? [];
?>

<div class="card">
    <h2>📅 <?= htmlspecialchars(t('production_schedule')) ?></h2>

    <?php if (empty($planning)): ?>
        <p><?= htmlspecialchars(t('no_production_planning_found')) ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('sowing_date')) ?></th>
                    <th><?= htmlspecialchars(t('harvest_date')) ?></th>
                    <th><?= htmlspecialchars(t('product')) ?></th>
                    <th><?= htmlspecialchars(t('customer')) ?></th>
                    <th><?= htmlspecialchars(t('quantity')) ?></th>
                    <th><?= htmlspecialchars(t('trays')) ?></th>
                    <th><?= htmlspecialchars(t('seed')) ?></th>
                    <th><?= htmlspecialchars(t('status')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($planning as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['sow_date']) ?></td>
                        <td><?= htmlspecialchars($item['expected_harvest_date']) ?></td>
                        <td><?= htmlspecialchars($item['product']) ?></td>
                        <td><?= htmlspecialchars($item['customer_name'] ?: '-') ?></td>
                        <td><?= number_format((float)$item['quantity'], 1, ',', '.') ?> g</td>
                        <td><?= (int)$item['trays_needed'] ?></td>
                        <td><?= number_format((float)$item['seed_needed_grams'], 1, ',', '.') ?> g</td>
                        <td><?= htmlspecialchars($item['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>