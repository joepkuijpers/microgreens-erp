<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';
include 'includes/production_engine.php';

$productionData = getProductionPlanning($db);
?>

<div class="main-content">

    <h1>📋 Productieplanner</h1>

    <?php include 'includes/cards/capacity_overview.php'; ?>

    <?php include 'includes/cards/production_alerts.php'; ?>

    <?php include 'includes/cards/production_schedule.php'; ?>

</div>

<?php include 'includes/footer.php'; ?>
