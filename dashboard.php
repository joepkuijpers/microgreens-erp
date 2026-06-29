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
$aantal_kosten = $db->query("SELECT COUNT(*) FROM expenses")->fetchColumn();

$omzet = $db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn();
$kosten_bedrag = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$winst = $omzet - $kosten_bedrag;

$lage_voorraad = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 1")->fetchColumn();
?>

<h1>Dashboard</h1>
<p><?= date('d-m-Y H:i') ?></p>

<div class="grid">

<div class="tile"><h2>🌱 Teelten</h2><p><?= $teelten ?></p></div>
<div class="tile"><h2>📦 Voorraad</h2><p><?= $voorraad ?></p></div>
<div class="tile"><h2>📋 Producten</h2><p><?= $producten ?></p></div>
<div class="tile"><h2>💰 Verkopen</h2><p><?= $verkopen ?></p></div>
<div class="tile"><h2>👥 Klanten</h2><p><?= $klanten ?></p></div>
<div class="tile"><h2>🚚 Leveranciers</h2><p><?= $leveranciers ?></p></div>
<div class="tile"><h2>🌾 Oogsten</h2><p><?= $oogsten ?></p></div>
<div class="tile"><h2>💸 Kostenregels</h2><p><?= $aantal_kosten ?></p></div>
<div class="tile"><h2>⚠️ Lage voorraad</h2><p><?= $lage_voorraad ?></p></div>

<div class="tile"><h2>💶 Omzet</h2><p>€<?= number_format($omzet,2) ?></p></div>
<div class="tile"><h2>📉 Kosten</h2><p>€<?= number_format($kosten_bedrag,2) ?></p></div>
<div class="tile"><h2>📈 Winst</h2><p>€<?= number_format($winst,2) ?></p></div>

</div>

<div class="card">
<h2>⚡ Snelle acties</h2>
<a class="button" href="add_batch_form.php">🌱 Nieuwe teelt</a>
<a class="button" href="add_inventory_form.php">📦 Nieuwe voorraad</a>
<a class="button" href="add_sale_form.php">💰 Nieuwe verkoop</a>
<a class="button" href="add_customer_form.php">👥 Nieuwe klant</a>
</div>

<div class="card">
<h2>📊 Bedrijfsoverzicht</h2>
<table>
<tr><td>Omzet</td><td>€<?= number_format($omzet,2) ?></td></tr>
<tr><td>Kosten</td><td>€<?= number_format($kosten_bedrag,2) ?></td></tr>
<tr><td>Winst</td><td>€<?= number_format($winst,2) ?></td></tr>
<tr><td>Lage voorraad</td><td><?= $lage_voorraad ?></td></tr>
</table>
</div>

<div class="card">
<h2>🟢 Systeemstatus</h2>
<p>✅ Raspberry Pi Online</p>
<p>✅ Apache Actief</p>
<p>✅ SQLite Verbonden</p>
<p>⏳ BME280 besteld / wacht op levering</p>
<p>✅ Dashboard v2.1 actief</p>
</div>

<?php
include 'includes/footer.php';
?>
