<?php
include 'includes/header.php';
include 'includes/sidebar.php';

require_once __DIR__ . '/includes/scheduler_engine.php';

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    schedulerUpdateFromPost($_POST);
    $saved = true;
}

$data = schedulerEvaluate();
$schedules = $data['schedules'] ?? [];
$allowedOutputs = schedulerAllowedOutputs();
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

.scheduler-input,
.scheduler-select {
    width: 100%;
    padding: 9px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
}

.scheduler-checkbox {
    transform: scale(1.25);
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

.save-message {
    background: #dcfce7;
    color: #166534;
    border-left: 6px solid #16a34a;
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 18px;
    font-weight: 800;
}

.scheduler-actions {
    margin-top: 18px;
}

.scheduler-save {
    background: #16a34a;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 18px;
    font-weight: 800;
    cursor: pointer;
}
</style>

<div class="scheduler-page">
    <div class="scheduler-header">
        <h1>Scheduler</h1>
        <p>Beheer automatische tijdschema’s voor verlichting, ventilatie en water.</p>
    </div>

    <?php if ($saved): ?>
        <div class="save-message">Scheduler opgeslagen.</div>
    <?php endif; ?>

    <div class="scheduler-card">
        <form method="post">
            <table class="scheduler-table">
                <thead>
                    <tr>
                        <th>Actief</th>
                        <th>Naam</th>
                        <th>Output</th>
                        <th>Start</th>
                        <th>Einde</th>
                        <th>Beslissing nu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <?php $id = $schedule['id']; ?>
                        <tr>
                            <td>
                                <input class="scheduler-checkbox" type="checkbox" name="enabled[<?= htmlspecialchars($id) ?>]" <?= $schedule['enabled'] ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <input class="scheduler-input" type="text" name="name[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($schedule['name']) ?>">
                            </td>
                            <td>
                                <select class="scheduler-select" name="output[<?= htmlspecialchars($id) ?>]">
                                    <?php foreach ($allowedOutputs as $output): ?>
                                        <option value="<?= htmlspecialchars($output) ?>" <?= $schedule['output'] === $output ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($output) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input class="scheduler-input" type="time" name="start_time[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($schedule['start_time']) ?>">
                            </td>
                            <td>
                                <input class="scheduler-input" type="time" name="end_time[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($schedule['end_time']) ?>">
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

            <div class="scheduler-actions">
                <button class="scheduler-save" type="submit">Scheduler opslaan</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
