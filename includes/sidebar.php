<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <h2>🌱 Microgreens ERP</h2>

    <a class="<?= $page == 'dashboard.php' ? 'active' : '' ?>"
       href="dashboard.php">
       🏠 Dashboard
    </a>

    <a class="<?= in_array($page,['grow_batches.php','add_batch.php','edit_batch.php','harvest_batch.php']) ? 'active' : '' ?>"
       href="grow_batches.php">
       🌱 Teelten
    </a>

    <a class="<?= in_array($page,['list_inventory.php','add_inventory_form.php','edit_inventory.php','delete_inventory.php']) ? 'active' : '' ?>"
       href="list_inventory.php">
       📦 Grondstoffen
    </a>

    <a class="<?= in_array($page,['inventory_transactions.php','add_inventory_transaction.php']) ? 'active' : '' ?>"
       href="inventory_transactions.php">
       🔄 Voorraadmutaties
    </a>

    <a class="<?= $page == 'list_finished_inventory.php' ? 'active' : '' ?>"
       href="list_finished_inventory.php">
       📦 Eindvoorraad
    </a>

    <a class="<?= $page == 'list_sales.php' ? 'active' : '' ?>"
       href="list_sales.php">
       🛒 Verkoop
    </a>

    <a class="<?= $page == 'list_customers.php' ? 'active' : '' ?>"
       href="list_customers.php">
       👥 Klanten
    </a>

    <a class="<?= $page == 'list_suppliers.php' ? 'active' : '' ?>"
       href="list_suppliers.php">
       🚚 Leveranciers
    </a>

    <a class="<?= $page == 'list_harvests.php' ? 'active' : '' ?>"
       href="list_harvests.php">
       🌾 Oogsten
    </a>

    <a class="<?= $page == 'list_products.php' ? 'active' : '' ?>"
       href="list_products.php">
       🌿 Producten
    </a>

    <a class="<?= $page == 'settings.php' ? 'active' : '' ?>"
       href="settings.php">
       ⚙ Instellingen
    </a>

</div>
