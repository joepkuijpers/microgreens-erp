<?php
$planning = $productionData['planning'] ?? [];
?>

<div class="card">
    <h2>📅 Productieplanning</h2>

    <?php if (empty($planning)): ?>
        <p>Geen geplande productie gevonden.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Zaaidatum</th>
                    <th>Oogstdatum</th>
                    <th>Product</th>
                    <th>Klant</th>
                    <th>Hoeveelheid</th>
                    <th>Trays</th>
                    <th>Zaad</th>
                    <th>Status</th>
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
