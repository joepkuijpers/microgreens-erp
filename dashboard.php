<?php
include 'includes/header.php';
include 'db_connect.php';
include 'includes/language.php';

require_once 'includes/batch_rotation_engine.php';

$batchRotation = rotateActiveBatch($db);

$producten = $db->query("SELECT count(*) FROM products")->fetchColumn();
$voorraad = $db->query("SELECT count(*) FROM inventory")->fetchColumn();
$teelten = $db->query("SELECT count(*) FROM grow_batches")->fetchColumn();
$verkopen = $db->query("SELECT count(*) FROM sales")->fetchColumn();
$klanten = $db->query("SELECT count(*) FROM customers")->fetchColumn();
$leveranciers = $db->query("SELECT count(*) FROM suppliers")->fetchColumn();
$oogsten = $db->query("SELECT count(*) FROM harvests")->fetchColumn();

$omzet = $db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn();
$kosten_bedrag = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();

$winst = $omzet - $kosten_bedrag;

$lage_voorraad = $db->query("SELECT count(*) FROM inventory WHERE quantity <= 1")->fetchColumn();
?>

<h1>🌱 <?= __('dashboard_title') ?></h1>
<p><?= __('last_page_load') ?>: <?= date('d-m-Y H:i') ?></p>

<?php include 'includes/cards/topstatus.php'; ?>

<div class="dashboard-section">
    <h2>⚠️ <?= __('alarms') ?></h2>
</div>
<?php include 'includes/cards/alerts.php'; ?>

<div class="dashboard-section">
    <h2>⚡ <?= __('quick_actions') ?></h2>
</div>
<?php include 'includes/cards/quickactions.php'; ?>

<?php include 'includes/cards/live_sensor_status.php'; ?>
<?php include 'includes/cards/climate_status.php'; ?>
<?php include 'includes/cards/lighting_status.php'; ?>
<?php include 'includes/cards/water_status.php'; ?>
<?php include 'includes/cards/automation_status.php'; ?>
<?php include 'includes/cards/hardware_control_center.php'; ?>

<?php include 'includes/cards/growth_command_center.php'; ?>
<?php include 'includes/cards/growth_timeline.php'; ?>
<?php include 'includes/cards/batch_queue.php'; ?>
<?php include 'includes/cards/production_planner.php'; ?>
<?php include 'includes/cards/harvest_forecast.php'; ?>
<?php include 'includes/cards/seed_planning.php'; ?>
<?php include 'includes/cards/rack_capacity.php'; ?>

<div class="dashboard-section">
    <h2>📊 <?= __('kpi_overview') ?></h2>
</div>

<div class="grid">
    <div class="tile"><h2>🌱 <?= __('batches') ?></h2><p id="batches"><?= $teelten ?></p></div>
    <div class="tile"><h2>🌾 <?= __('harvests') ?></h2><p id="harvests"><?= $oogsten ?></p></div>
    <div class="tile"><h2>⚠️ <?= __('low_stock') ?></h2><p><?= $lage_voorraad ?></p></div>

    <div class="tile"><h2>📦 <?= __('inventory') ?></h2><p id="inventory"><?= $voorraad ?></p></div>
    <div class="tile"><h2>📋 <?= __('products') ?></h2><p id="products"><?= $producten ?></p></div>
    <div class="tile"><h2>💰 <?= __('sales') ?></h2><p id="sales"><?= $verkopen ?></p></div>
    <div class="tile"><h2>👥 <?= __('customers') ?></h2><p id="customers"><?= $klanten ?></p></div>
    <div class="tile"><h2>🚚 <?= __('suppliers') ?></h2><p id="suppliers"><?= $leveranciers ?></p></div>

    <div class="tile"><h2>💶 <?= __('revenue') ?></h2><p id="revenue">€<?= number_format($omzet, 2, ',', '.') ?></p></div>
    <div class="tile"><h2>📉 <?= __('expenses') ?></h2><p id="expenses">€<?= number_format($kosten_bedrag, 2, ',', '.') ?></p></div>
    <div class="tile"><h2>📈 <?= __('profit') ?></h2><p id="profit">€<?= number_format($winst, 2, ',', '.') ?></p></div>
</div>

<div class="dashboard-section">
    <h2>🌡 <?= __('live_monitoring') ?></h2>
</div>

<div class="grid">
    <?php include 'includes/cards/sensors.php'; ?>
    <?php include 'includes/cards/status.php'; ?>
</div>

<div class="dashboard-section">
    <h2>💰 <?= __('financial') ?></h2>
</div>

<?php include 'includes/cards/finance.php'; ?>

<div class="dashboard-section">
    <h2>📈 <?= __('historical_sensor_charts') ?></h2>
</div>

<?php include 'includes/cards/quickoverview.php'; ?>
<?php include 'includes/cards/charts.php'; ?>

<?php include 'includes/footer.php'; ?>