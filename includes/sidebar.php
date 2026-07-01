<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <h2>🌱 Microgreens ERP</h2>

    <a class="<?= $page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">🏠 Dashboard</a>
    <a class="<?= $page == 'grow_batches.php' ? 'active' : '' ?>" href="grow_batches.php">🌱 Teelten</a>
    <a class="<?= $page == 'harvests.php' ? 'active' : '' ?>" href="harvests.php">🌾 Oogsten</a>
    <a class="<?= $page == 'inventory.php' ? 'active' : '' ?>" href="inventory.php">📦 Voorraad</a>
    <a class="<?= $page == 'sales.php' ? 'active' : '' ?>" href="sales.php">🛒 Verkoop</a>
    <a class="<?= $page == 'customers.php' ? 'active' : '' ?>" href="customers.php">👥 Klanten</a>
    <a class="<?= $page == 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">🚚 Leveranciers</a>
    <a class="<?= $page == 'expenses.php' ? 'active' : '' ?>" href="expenses.php">💰 Kosten</a>
    <a class="<?= $page == 'reports.php' ? 'active' : '' ?>" href="reports.php">📊 Rapportages</a>
    <a class="<?= $page == 'settings.php' ? 'active' : '' ?>" href="settings.php">⚙ Instellingen</a>
</div>