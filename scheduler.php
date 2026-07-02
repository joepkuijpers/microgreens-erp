<?php
include 'includes/header.php';
include 'includes/sidebar.php';

require_once __DIR__ . '/includes/scheduler_engine.php';

$data = schedulerEvaluate();
$schedules = $data['schedules'] ?? [];
?>

<style>
.scheduler-page {
    padding: 24px;
}

.scheduler-header {
    background: linear-gradient(135deg, #7c3aed, #0f766e);
    color: white;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
}

.scheduler-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
}

.scheduler-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
}

.scheduler-table {
    width: 100%;
    border-collapse: collapse;
}

.scheduler-table th,
.scheduler-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}

.scheduler-table th {
    background: #f9fafb;
    color: #374151;
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

.badge-enabled {
    background: #dbeafe;
    color: #1e40af;
}

.badge-disabled {
    background: #f3f4f6;
    color: #6b7280;
}

.scheduler-footer {
    margin-top: 18px;
    color: #6b7280;
    font-size: 14px;
}
</style>

<div class="scheduler-page">
    <div class="scheduler-header">
        <h1>Scheduler</h1>
        <p>Automatische tijdschema’s voor verlichting, ventilatie en water.</p>
    </div>

    <div class="scheduler-card">
        <table class="scheduler-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Output</th>
                    <th>Start</th>
                    <th>Einde</th>
                    <th>Ingeschakeld</th>
                    <th>Beslissing</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?= htmlspecialchars($schedule['name']) ?></td>
                        <td><?= htmlspecialchars($schedule['output']) ?></td>
                        <td><?= htmlspecialchars($schedule['start_time']) ?></td>
                        <td><?= htmlspecialchars($schedule['end_time']) ?></td>
                        <td>
                            <span class="badge <?= $schedule['enabled'] ? 'badge-enabled' : 'badge-disabled' ?>">
                                <?= $schedule['enabled'] ? 'ACTIEF' : 'UITGESCHAKELD' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $schedule['should_be_on'] ? 'badge-on' : 'badge-off' ?>">
                                <?= htmlspecialchars($schedule['state_text']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="scheduler-footer">
            Laatste evaluatie: <?= htmlspecialchars($data['timestamp']) ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
