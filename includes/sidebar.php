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

    <a class="<?= activeClass($page == 'dashboard.php') ?>" href="<?= $navPrefix ?>dashboard.php">🏠 <?= __('dashboard') ?></a>

    <a class="<?= activeClass($page == 'operations_dashboard.php') ?>" href="<?= $navPrefix ?>operations_dashboard.php">🧭 <?= __('operations') ?></a>

    <a class="<?= activeClass(in_array($page, ['grow_batches.php','add_batch.php','edit_batch.php','harvest_batch.php','batch_details.php'])) ?>" href="<?= $navPrefix ?>grow_batches.php">🌱 <?= __('batches') ?></a>

    <a class="<?= activeClass($page == 'crop_profiles.php') ?>" href="<?= $navPrefix ?>crop_profiles.php">🌿 <?= __('crop_profiles') ?></a>

    <a class="<?= activeClass(in_array($page, ['list_inventory.php','add_inventory_form.php','edit_inventory.php','delete_inventory.php'])) ?>" href="<?= $navPrefix ?>list_inventory.php">📦 <?= __('raw_materials') ?></a>

    <a class="<?= activeClass($page == 'equipment.php' || $page == 'add_equipment.php') ?>" href="<?= $navPrefix ?>equipment.php">⚡ <?= __('equipment') ?></a>

    <a class="<?= activeClass(in_array($page, ['inventory_transactions.php','add_inventory_transaction.php'])) ?>" href="<?= $navPrefix ?>inventory_transactions.php">🔄 <?= __('inventory_transactions') ?></a>

    <a class="<?= activeClass($page == 'list_finished_inventory.php') ?>" href="<?= $navPrefix ?>list_finished_inventory.php">📦 <?= __('finished_inventory') ?></a>

    <a class="<?= activeClass($page == 'list_sales.php') ?>" href="<?= $navPrefix ?>list_sales.php">🛒 <?= __('sales') ?></a>

    <a class="<?= activeClass($page == 'list_customers.php') ?>" href="<?= $navPrefix ?>list_customers.php">👥 <?= __('customers') ?></a>

    <a class="<?= activeClass($page == 'list_suppliers.php') ?>" href="<?= $navPrefix ?>list_suppliers.php">🚚 <?= __('suppliers') ?></a>

    <a class="<?= activeClass($page == 'list_harvests.php') ?>" href="<?= $navPrefix ?>list_harvests.php">🌾 <?= __('harvests') ?></a>

    <a class="<?= activeClass($page == 'list_products.php') ?>" href="<?= $navPrefix ?>list_products.php">🌿 <?= __('products') ?></a>

    <a class="<?= activeClass(in_array($page, ['list_labor.php', 'add_labor_form.php'])) ?>" href="<?= $navPrefix ?>list_labor.php">👷 <?= __('labor_registration') ?></a>

    <a class="<?= activeClass($page == 'scheduler.php') ?>" href="<?= $navPrefix ?>scheduler.php">⏱ <?= __('scheduler') ?></a>

    <a class="<?= activeClass(in_array($page, ['hardware_control.php','gpio_logs.php'])) ?>" href="<?= $navPrefix ?>hardware_control.php">🔌 <?= __('hardware') ?></a>

    <a class="<?= activeClass($page == 'gpio_logs.php') ?>" href="<?= $navPrefix ?>gpio_logs.php">📜 <?= __('gpio_logs') ?></a>

    <a class="<?= activeClass($page == 'settings.php' || strpos($currentPath, '/settings/') !== false) ?>" href="<?= $navPrefix ?>settings/">⚙ <?= __('settings') ?></a>

</div>