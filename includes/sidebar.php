<?php
$page = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];
$navPrefix = $navPrefix ?? '';

function activeClass($condition) {
    return $condition ? 'active' : '';
}
?>

<div class="sidebar">

    <h2>🌱 Microgreens ERP</h2>

    <a class="<?= activeClass($page == 'dashboard.php') ?>" href="<?= $navPrefix ?>dashboard.php">🏠 Dashboard</a>

    <a class="<?= activeClass(in_array($page, ['grow_batches.php','add_batch.php','edit_batch.php','harvest_batch.php','batch_details.php'])) ?>" href="<?= $navPrefix ?>grow_batches.php">🌱 Teelten</a>

    <a class="<?= activeClass(in_array($page, ['list_inventory.php','add_inventory_form.php','edit_inventory.php','delete_inventory.php'])) ?>" href="<?= $navPrefix ?>list_inventory.php">📦 Grondstoffen</a>

    <a class="<?= activeClass(in_array($page, ['inventory_transactions.php','add_inventory_transaction.php'])) ?>" href="<?= $navPrefix ?>inventory_transactions.php">🔄 Voorraadmutaties</a>

    <a class="<?= activeClass($page == 'list_finished_inventory.php') ?>" href="<?= $navPrefix ?>list_finished_inventory.php">📦 Eindvoorraad</a>

    <a class="<?= activeClass($page == 'list_sales.php') ?>" href="<?= $navPrefix ?>list_sales.php">🛒 Verkoop</a>

    <a class="<?= activeClass($page == 'list_customers.php') ?>" href="<?= $navPrefix ?>list_customers.php">👥 Klanten</a>

    <a class="<?= activeClass($page == 'list_suppliers.php') ?>" href="<?= $navPrefix ?>list_suppliers.php">🚚 Leveranciers</a>

    <a class="<?= activeClass($page == 'list_harvests.php') ?>" href="<?= $navPrefix ?>list_harvests.php">🌾 Oogsten</a>

    <a class="<?= activeClass($page == 'list_products.php') ?>" href="<?= $navPrefix ?>list_products.php">🌿 Producten</a>

    <a class="<?= activeClass(in_array($page, ['hardware_control.php','gpio_logs.php'])) ?>" href="<?= $navPrefix ?>hardware_control.php">🔌 Hardware</a>

    <a class="<?= activeClass($page == 'gpio_logs.php') ?>" href="<?= $navPrefix ?>gpio_logs.php">📜 GPIO Logs</a>

    <a class="<?= activeClass($page == 'settings.php' || strpos($currentPath, '/settings/') !== false) ?>" href="<?= $navPrefix ?>settings/">⚙ Instellingen</a>

</div>
