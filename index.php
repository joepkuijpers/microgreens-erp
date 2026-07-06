<?php
include 'includes/header.php';
?>

<h1><?= __('dashboard') ?></h1>

<div class="card">
<h2>🌱 Microgreens ERP</h2>

<p><?= __('choose_section') ?></p>

<a class="button" href="dashboard.php"><?= __('dashboard') ?></a>
<a class="button" href="list_inventory.php"><?= __('inventory') ?></a>
<a class="button" href="list_batches.php"><?= __('batches') ?></a>
<a class="button" href="list_sales.php"><?= __('sales') ?></a>
<a class="button" href="list_products.php"><?= __('products') ?></a>
<a class="button" href="report_profit_by_month.php"><?= __('reports') ?></a>

</div>

<?php
include 'includes/footer.php';
?>