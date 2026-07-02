<?php
include 'includes/header.php';
include 'includes/sidebar.php';

$logFile = __DIR__ . '/hardware/gpio/gpio_actions.log';
$entries = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines);

    foreach ($lines as $line) {
        $item = json_decode($line, true);

        if (is_array($item)) {
            $entries[] = $item;
        }
    }
}
?>

<style>
.gpio-page {
    padding: 24px;
}

.gpio-header {
    background: linear-gradient(135deg, #1e3a8a, #0f766e);
    color: white;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
}

.gpio-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
}

.gpio-header p {
    margin: 0;
    opacity: 0.9;
}

.gpio-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
}

.gpio-table {
    width: 100%;
    border-collapse: collapse;
}

.gpio-table th,
.gpio-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}

.gpio-table th {
    color: #374151;
    font-size: 14px;
    background: #f9fafb;
}

.badge {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 13px;
}

.badge-on {
    background: #dcfce7;
    color: #166534;
}

.badge-off {
    background: #fee2e2;
    color: #991b1b;
}

.empty-log {
    color: #6b7280;
    padding: 18px;
}
</style>

<div class="gpio-page">
    <div class="gpio-header">
        <h1>GPIO Log Viewer</h1>
        <p>Overzicht van handmatige relaisacties via de GPIO HAL.</p>
    </div>

    <div class="gpio-card">
        <?php if (empty($entries)): ?>
            <div class="empty-log">Nog geen GPIO-acties gelogd.</div>
        <?php else: ?>
            <table class="gpio-table">
                <thead>
                    <tr>
                        <th>Tijdstip</th>
                        <th>Output</th>
                        <th>Label</th>
                        <th>GPIO pin</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $result = $entry['result'] ?? [];
                        $state = (bool)($entry['state'] ?? false);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['timestamp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($entry['output'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($result['label'] ?? '-') ?></td>
                            <td><?= htmlspecialchars((string)($result['gpio_pin'] ?? '-')) ?></td>
                            <td>
                                <span class="badge <?= $state ? 'badge-on' : 'badge-off' ?>">
                                    <?= htmlspecialchars($entry['state_text'] ?? '-') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
