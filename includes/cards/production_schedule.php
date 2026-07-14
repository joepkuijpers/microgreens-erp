<?php
$planning = $productionData['planning'] ?? [];
?>

<div class="card">
    <h2>📅 <?= htmlspecialchars(__('production_schedule')) ?></h2>

    <?php if (empty($planning)): ?>
        <p><?= htmlspecialchars(__('no_production_planning_found')) ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('sowing_date')) ?></th>
                    <th><?= htmlspecialchars(__('harvest_date')) ?></th>
                    <th><?= htmlspecialchars(__('product')) ?></th>
                    <th><?= htmlspecialchars(__('customer')) ?></th>
                    <th><?= htmlspecialchars(__('quantity')) ?></th>
                    <th><?= htmlspecialchars(__('trays')) ?></th>
                    <th><?= htmlspecialchars(__('seed')) ?></th>
                    <th><?= htmlspecialchars(__('status')) ?></th>
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