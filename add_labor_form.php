<?php
require_once __DIR__ . '/app/includes/auth.php';
auth_require_login();
include 'app/db_connect.php';
include 'app/includes/language.php';

$activityCodes = ['SOWING','WATERING','BLACKOUT','LIGHTING','CROP_CARE','HARVESTING','PACKAGING','DELIVERY','CLEANING','MAINTENANCE','ADMINISTRATION','RESEARCH_DEVELOPMENT','PURCHASING','MARKETING','TRAINING','OTHER'];
$typeCodes = ['medewerker','stage','vrijwilliger'];

$batches = $db->query("
    SELECT
        id,
        crop,
        sow_date,
        status
    FROM grow_batches
    ORDER BY sow_date DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$successMessage = '';

$workDate = date('Y-m-d');
$hoursWorked = '';
$activity = '';
$batchId = '';
$grossHourlyRate = '';
$notes = '';
$workerType = 'medewerker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workDate = trim($_POST['work_date'] ?? '');
    $hoursWorked = trim($_POST['hours_worked'] ?? '');
    $activity = trim($_POST['activity'] ?? '');
    $batchId = trim($_POST['batch_id'] ?? '');
    $grossHourlyRate = trim($_POST['gross_hourly_rate'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $workerType = $_POST['worker_type'] ?? 'medewerker';
    if (!in_array($workerType, $typeCodes, true)) { $workerType = 'medewerker'; }
    if ($workerType === 'vrijwilliger') { $grossHourlyRate = '0'; }

    $hoursWorkedValue = (float)$hoursWorked;
    $grossHourlyRateValue = (float)$grossHourlyRate;
    $batchIdValue = $batchId === '' ? null : (int)$batchId;

    if ($workDate === '') {
        $errors[] = __('labor_err_date');
    }
    if ($hoursWorked === '' || $hoursWorkedValue <= 0) {
        $errors[] = __('labor_err_hours');
    }
    if (!in_array($activity, $activityCodes, true)) {
        $errors[] = __('labor_err_activity');
    }
    if ($workerType === 'vrijwilliger') {
        $grossHourlyRateValue = 0.0;
    } elseif ($grossHourlyRate === '' || $grossHourlyRateValue < 0) {
        $errors[] = __('labor_err_rate_negative');
    } elseif ($workerType === 'medewerker' && $grossHourlyRateValue <= 0) {
        $errors[] = __('labor_err_rate_required');
    }
    if ($batchIdValue !== null && $batchIdValue <= 0) {
        $errors[] = __('labor_err_batch_invalid');
    }
    if ($batchIdValue !== null && empty($errors)) {
        $batchCheck = $db->prepare("SELECT id FROM grow_batches WHERE id = :id");
        $batchCheck->execute([':id' => $batchIdValue]);
        if (!$batchCheck->fetchColumn()) {
            $errors[] = __('labor_err_batch_missing');
        }
    }

    if (empty($errors)) {
        $insert = $db->prepare("
            INSERT INTO labor_entries
            (work_date, hours_worked, activity, batch_id, gross_hourly_rate, notes, worker_type)
            VALUES
            (:work_date, :hours_worked, :activity, :batch_id, :gross_hourly_rate, :notes, :worker_type)
        ");
        $insert->execute([
            ':work_date' => $workDate,
            ':hours_worked' => $hoursWorkedValue,
            ':activity' => $activity,
            ':batch_id' => $batchIdValue,
            ':gross_hourly_rate' => $grossHourlyRateValue,
            ':notes' => $notes !== '' ? $notes : null,
            ':worker_type' => $workerType
        ]);
        $grossLaborValue = $hoursWorkedValue * $grossHourlyRateValue;
        $successMessage = sprintf(__('labor_saved'), $hoursWorkedValue, $grossLaborValue);

        $workDate = date('Y-m-d');
        $hoursWorked = '';
        $activity = '';
        $batchId = '';
        $grossHourlyRate = '';
        $notes = '';
        $workerType = 'medewerker';
    }
}

include 'app/includes/header.php';
?>

<div class="main">
    <h1>⏱ <?= htmlspecialchars(__('labor_form_title')) ?></h1>

    <p>
        <a class="btn" href="dashboard.php">
            ← <?= htmlspecialchars(__('back')) ?>
        </a>
    </p>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <p><strong><?= htmlspecialchars($successMessage) ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="card">
            <h2><?= htmlspecialchars(__('labor_check_input')) ?></h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <p><?= htmlspecialchars(__('labor_rate_note')) ?></p>

        <form method="post">
            <label for="work_date"><?= htmlspecialchars(__('labor_work_date')) ?></label><br>
            <input id="work_date" type="date" name="work_date" value="<?= htmlspecialchars($workDate) ?>" required>
            <br><br>

            <label for="hours_worked"><?= htmlspecialchars(__('labor_hours_worked')) ?></label><br>
            <input id="hours_worked" type="number" name="hours_worked" min="0.01" step="0.01" value="<?= htmlspecialchars($hoursWorked) ?>" required>
            <br><br>

            <label for="activity"><?= htmlspecialchars(__('labor_activity')) ?></label><br>
            <select id="activity" name="activity" required>
                <option value="">-- <?= htmlspecialchars(__('labor_choose_activity')) ?> --</option>
                <?php foreach ($activityCodes as $activityCode): ?>
                    <option value="<?= htmlspecialchars($activityCode) ?>" <?= $activity === $activityCode ? 'selected' : '' ?>>
                        <?= htmlspecialchars(__('act_' . $activityCode)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="batch_id"><?= htmlspecialchars(__('labor_batch_optional')) ?></label><br>
            <select id="batch_id" name="batch_id">
                <option value="">-- <?= htmlspecialchars(__('labor_no_batch')) ?> --</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= htmlspecialchars((string)$batch['id']) ?>" <?= $batchId === (string)$batch['id'] ? 'selected' : '' ?>>
                        #<?= htmlspecialchars((string)$batch['id']) ?>
                        — <?= htmlspecialchars((string)$batch['crop']) ?>
                        — <?= htmlspecialchars((string)($batch['sow_date'] ?? '-')) ?>
                        — <?= htmlspecialchars((string)($batch['status'] ?? '-')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="worker_type"><?= htmlspecialchars(__('labor_type')) ?></label><br>
            <select id="worker_type" name="worker_type" onchange="laborToggleRate()" required>
                <?php foreach ($typeCodes as $tc): ?>
                    <option value="<?= htmlspecialchars($tc) ?>" <?= $workerType === $tc ? 'selected' : '' ?>>
                        <?= htmlspecialchars(__('type_' . $tc)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <span id="rate_wrap">
                <label for="gross_hourly_rate"><?= htmlspecialchars(__('labor_rate_label')) ?></label><br>
                <input id="gross_hourly_rate" type="number" name="gross_hourly_rate" min="0" step="0.01" value="<?= htmlspecialchars($grossHourlyRate) ?>">
                <br><br>
            </span>
            <script>
            function laborToggleRate(){
              var t = document.getElementById('worker_type').value;
              var w = document.getElementById('gross_hourly_rate');
              var wrap = document.getElementById('rate_wrap');
              if (t === 'vrijwilliger') { wrap.style.display = 'none'; w.value = '0'; }
              else { wrap.style.display = ''; }
            }
            document.addEventListener('DOMContentLoaded', laborToggleRate);
            </script>

            <label for="notes"><?= htmlspecialchars(__('notes')) ?></label><br>
            <textarea id="notes" name="notes" rows="4"><?= htmlspecialchars($notes) ?></textarea>
            <br><br>

            <button type="submit" class="btn"><?= htmlspecialchars(__('save')) ?></button>
        </form>
    </div>
</div>

<?php include 'app/includes/footer.php'; ?>