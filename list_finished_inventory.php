<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$items = $db->query("
    SELECT
        f.id,
        f.product_id,
        f.quantity,
        f.unit,
        f.batch_id,
        f.harvest_id,
        p.name AS product_name,
        p.category,
        p.sale_price,
        (f.quantity * p.sale_price) AS total_value
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
    ORDER BY p.name ASC, f.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalValue = $db->query("
    SELECT
        COALESCE(SUM(f.quantity * p.sale_price), 0) AS total
    FROM finished_inventory f
    LEFT JOIN products p ON p.id = f.product_id
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>📦 <?= htmlspecialchars(__('finished_inventory')) ?></h1>

    <p>
        <a class="btn" href="grow_batches.php">
            🌱 <?= htmlspecialchars(__('go_to_batch_management')) ?>
        </a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('total_finished_inventory_sales_value')) ?></h2>
        <h1>€ <?= htmlspecialchars(number_format((float)$totalValue['total'], 2, ',', '.')) ?></h1>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Categorie</th>
                    <th>Batch</th>
                    <th>Harvest</th>
                    <th>Hoeveelheid</th>
                    <th>Eenheid</th>
                    <th>Verkoopprijs</th>
                    <th>Totale waarde</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$item['id']) ?></td>
                        <td><?= htmlspecialchars((string)($item['product_name'] ?? 'Onbekend product')) ?></td>
                        <td><?= htmlspecialchars((string)($item['category'] ?? '-')) ?></td>
                        <td>
                            <?php if (!empty($item['batch_id'])): ?>
                                <a href="batch_details.php?id=<?= urlencode((string)$item['batch_id']) ?>">
                                    #<?= htmlspecialchars((string)$item['batch_id']) ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['harvest_id'])): ?>
                                <a href="harvest_details.php?id=<?= urlencode((string)$item['harvest_id']) ?>">
                                    #<?= htmlspecialchars((string)$item['harvest_id']) ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(number_format((float)$item['quantity'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars((string)($item['unit'] ?? '-')) ?></td>
                        <td>€ <?= htmlspecialchars(number_format((float)$item['sale_price'], 2, ',', '.')) ?></td>
                        <td>€ <?= htmlspecialchars(number_format((float)$item['total_value'], 2, ',', '.')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>