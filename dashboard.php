<?php
include 'includes/header.php';
include 'db_connect.php';

$producten = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$voorraad = $db->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$teelten = $db->query("SELECT COUNT(*) FROM grow_batches")->fetchColumn();
$verkopen = $db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$klanten = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$leveranciers = $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$oogsten = $db->query("SELECT COUNT(*) FROM harvests")->fetchColumn();

$omzet = $db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn();
$kosten_bedrag = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$winst = $omzet - $kosten_bedrag;
$lage_voorraad = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 1")->fetchColumn();
?>

<h1>🌱 Microgreens ERP Professional</h1>
<p>Laatste paginalaad: <?= date('d-m-Y H:i') ?></p>

<?php include 'includes/cards/topstatus.php'; ?>

<?php include 'includes/cards/live_sensor_status.php'; ?>
<?php include 'includes/cards/climate_status.php'; ?>
<?php include 'includes/cards/lighting_status.php'; ?>
<?php include 'includes/cards/water_status.php'; ?>
<?php include 'includes/cards/automation_status.php'; ?>
<?php include 'includes/cards/growth_command_center.php'; ?>
<?php include 'includes/cards/hardware_control_center.php'; ?>

<div class="dashboard-section">
    <h2>📊 KPI-overzicht</h2>
</div>

<div class="grid">
    <div class="tile"><h2>🌱 Teelten</h2><p id="batches"><?= $teelten ?></p></div>
    <div class="tile"><h2>📦 Voorraad</h2><p id="inventory"><?= $voorraad ?></p></div>
    <div class="tile"><h2>📋 Producten</h2><p id="products"><?= $producten ?></p></div>
    <div class="tile"><h2>💰 Verkopen</h2><p id="sales"><?= $verkopen ?></p></div>
    <div class="tile"><h2>👥 Klanten</h2><p id="customers"><?= $klanten ?></p></div>
    <div class="tile"><h2>🚚 Leveranciers</h2><p id="suppliers"><?= $leveranciers ?></p></div>
    <div class="tile"><h2>🌾 Oogsten</h2><p id="harvests"><?= $oogsten ?></p></div>
    <div class="tile"><h2>⚠️ Lage voorraad</h2><p><?= $lage_voorraad ?></p></div>
    <div class="tile"><h2>💶 Omzet</h2><p id="revenue">€<?= number_format($omzet, 2, ',', '.') ?></p></div>
    <div class="tile"><h2>📉 Kosten</h2><p id="expenses">€<?= number_format($kosten_bedrag, 2, ',', '.') ?></p></div>
    <div class="tile"><h2>📈 Winst</h2><p id="profit">€<?= number_format($winst, 2, ',', '.') ?></p></div>
</div>

<div class="dashboard-section"><h2>🌡 Live monitoring</h2></div>
<div class="grid">
    <?php include 'includes/cards/sensors.php'; ?>
    <?php include 'includes/cards/status.php'; ?>
</div>

<div class="dashboard-section"><h2>📈 Historische sensorgrafieken</h2></div>
<?php include 'includes/cards/quickoverview.php'; ?>
<?php include 'includes/cards/charts.php'; ?>

<div class="dashboard-section"><h2>⚠️ Alarmen</h2></div>
<?php include 'includes/cards/alerts.php'; ?>

<div class="dashboard-section"><h2>💰 Financieel</h2></div>
<?php include 'includes/cards/finance.php'; ?>

<div class="dashboard-section"><h2>⚡ Snelle acties</h2></div>
<?php include 'includes/cards/quickactions.php'; ?>

<?php include 'includes/footer.php'; ?>