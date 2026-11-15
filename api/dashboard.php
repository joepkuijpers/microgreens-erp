<?php
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/db_connect.php';
include __DIR__ . '/../app/includes/sidebar.php';

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

<h1>ðŸŒ± Microgreens ERP Professional</h1>
<p>Laatste paginalaad: <?= date('d-m-Y H:i') ?></p>

<div class="dashboard-section">
    <h2>ðŸ“Š KPI-overzicht</h2>
</div>

<div class="grid">
    <div class="tile"><h2>ðŸŒ± Teelten</h2><p id="batches"><?= $teelten ?></p></div>
    <div class="tile"><h2>ðŸ“¦ Voorraad</h2><p id="inventory"><?= $voorraad ?></p></div>
    <div class="tile"><h2>ðŸ“‹ Producten</h2><p id="products"><?= $producten ?></p></div>
    <div class="tile"><h2>ðŸ’° Verkopen</h2><p id="sales"><?= $verkopen ?></p></div>
    <div class="tile"><h2>ðŸ‘¥ Klanten</h2><p id="customers"><?= $klanten ?></p></div>
    <div class="tile"><h2>ðŸšš Leveranciers</h2><p id="suppliers"><?= $leveranciers ?></p></div>
    <div class="tile"><h2>ðŸŒ¾ Oogsten</h2><p id="harvests"><?= $oogsten ?></p></div>
    <div class="tile"><h2>âš ï¸ Lage voorraad</h2><p><?= $lage_voorraad ?></p></div>
    <div class="tile"><h2>ðŸ’¶ Omzet</h2><p id="revenue">â‚¬<?= number_format($omzet, 2) ?></p></div>
    <div class="tile"><h2>ðŸ“‰ Kosten</h2><p id="expenses">â‚¬<?= number_format($kosten_bedrag, 2) ?></p></div>
    <div class="tile"><h2>ðŸ“ˆ Winst</h2><p id="profit">â‚¬<?= number_format($winst, 2) ?></p></div>
</div>

<div class="dashboard-section">
    <h2>ðŸŒ¡ Live monitoring</h2>
</div>

<div class="grid">
    <?php include __DIR__ . '/../app/includes/cards/sensors.php'; ?>
    <?php include __DIR__ . '/../app/includes/cards/status.php'; ?>
</div>

<div class="dashboard-section">
    <h2>ðŸ“ˆ Historische sensorgrafieken</h2>
</div>

<?php include __DIR__ . '/../app/includes/cards/charts.php'; ?>

<div class="dashboard-section">
    <h2>âš ï¸ Alarmen</h2>
</div>

<?php include __DIR__ . '/../app/includes/cards/alerts.php'; ?>

<div class="dashboard-section">
    <h2>ðŸ’° Financieel</h2>
</div>

<?php include __DIR__ . '/../app/includes/cards/finance.php'; ?>

<div class="dashboard-section">
    <h2>âš¡ Snelle acties</h2>
</div>

<?php include __DIR__ . '/../app/includes/cards/quickactions.php'; ?>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>