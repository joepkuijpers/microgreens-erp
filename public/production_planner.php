<?php
include '../app/includes/header.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';
include '../app/includes/production_engine.php';

$productionData = getProductionPlanning($db);
?>

<div class="main-content">

    <h1>📋 Productieplanner</h1>

    <?php include '../app/includes/cards/capacity_overview.php'; ?>

    <?php include '../app/includes/cards/production_alerts.php'; ?>

    <?php include '../app/includes/cards/production_schedule.php'; ?>

</div>

<?php include '../app/includes/footer.php'; ?>
