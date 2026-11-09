<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b04');
?>
<div class="next-step-bar">
    <div><a href="index.php" style="color:#666; text-decoration:none;">Dashboard</a></div>
    <div><a href="b28_watchdog_alerts.php">Watchdog</a></div>
</div>
<?php require_once __DIR__ . '/app/includes/cards/rack_capacity.php'; ?>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>