<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b01');
?>
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
<div class="next-step-bar">
    <div><a href="index.php" style="color:#666; text-decoration:none;">← Dashboard</a></div>
    <div><a href="b28_watchdog_alerts.php">Watchdog →</a></div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
