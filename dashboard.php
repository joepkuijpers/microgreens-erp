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

<h1>Dashboard</h1>
<p><?= date('d-m-Y H:i') ?></p>

<div class="grid">
<div class="tile"><h2>🌱 Teelten</h2><p id="batches"><?= $teelten ?></p></div>
<div class="tile"><h2>📦 Voorraad</h2><p id="inventory"><?= $voorraad ?></p></div>
<div class="tile"><h2>📋 Producten</h2><p id="products"><?= $producten ?></p></div>
<div class="tile"><h2>💰 Verkopen</h2><p id="sales"><?= $verkopen ?></p></div>
<div class="tile"><h2>👥 Klanten</h2><p id="customers"><?= $klanten ?></p></div>
<div class="tile"><h2>🚚 Leveranciers</h2><p id="suppliers"><?= $leveranciers ?></p></div>
<div class="tile"><h2>🌾 Oogsten</h2><p id="harvests"><?= $oogsten ?></p></div>
<div class="tile"><h2>⚠️ Lage voorraad</h2><p><?= $lage_voorraad ?></p></div>
<div class="tile"><h2>💶 Omzet</h2><p id="revenue">€<?= number_format($omzet,2) ?></p></div>
<div class="tile"><h2>📉 Kosten</h2><p id="expenses">€<?= number_format($kosten_bedrag,2) ?></p></div>
<div class="tile"><h2>📈 Winst</h2><p id="profit">€<?= number_format($winst,2) ?></p></div>
</div>

<?php
include 'includes/cards/sensors.php';
include 'includes/cards/status.php';
include 'includes/cards/finance.php';
include 'includes/cards/quickactions.php';
include 'includes/footer.php';
?>