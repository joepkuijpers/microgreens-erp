<?php
include 'db_connect.php';
include 'includes/language.php';

$activities = [
    'SOWING' => 'Zaaien',
    'WATERING' => 'Water geven',
    'BLACKOUT' => 'Black-out',
    'LIGHTING' => 'Belichting',
    'CROP_CARE' => 'Verzorging',
    'HARVESTING' => 'Oogsten',
    'PACKAGING' => 'Verpakken',
    'DELIVERY' => 'Leveren',
    'CLEANING' => 'Schoonmaken',
    'MAINTENANCE' => 'Onderhoud',
    'ADMINISTRATION' => 'Administratie',
    'RESEARCH_DEVELOPMENT' => 'Onderzoek en ontwikkeling',
    'PURCHASING' => 'Inkoop',
    'MARKETING' => 'Marketing',
    'TRAINING' => 'Opleiding',
    'OTHER' => 'Overig'
];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workDate = trim($_POST['work_date'] ?? '');
    $hoursWorked = trim($_POST['hours_worked'] ?? '');
    $activity = trim($_POST['activity'] ?? '');
    $batchId = trim($_POST['batch_id'] ?? '');
    $grossHourlyRate = trim($_POST['gross_hourly_rate'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $hoursWorkedValue = (float)$hoursWorked;
    $grossHourlyRateValue = (float)$grossHourlyRate;
    $batchIdValue = $batchId === '' ? null : (int)$batchId;

    if ($workDate === '') {
        $errors[] = 'Werkdatum is verplicht.';
    }

    if ($hoursWorked === '' || $hoursWorkedValue <= 0) {
        $errors[] = 'Gewerkte uren moeten groter zijn dan nul.';
    }

    if (!array_key_exists($activity, $activities)) {
        $errors[] = 'Kies een geldige activiteit.';
    }

    if ($grossHourlyRate === '' || $grossHourlyRateValue < 0) {
        $errors[] = 'Het bruto interne uurtarief mag niet negatief zijn.';
    }

    if ($batchIdValue !== null && $batchIdValue <= 0) {
        $errors[] = 'De gekozen batch is ongeldig.';
    }

    if ($batchIdValue !== null && empty($errors)) {
        $batchCheck = $db->prepare("
            SELECT id
            FROM grow_batches
            WHERE id = :id
        ");
        $batchCheck->execute([':id' => $batchIdValue]);

        if (!$batchCheck->fetchColumn()) {
            $errors[] = 'De gekozen batch bestaat niet.';
        }
    }

    if (empty($errors)) {
        $insert = $db->prepare("
            INSERT INTO labor_entries
            (
                work_date,
                hours_worked,
                activity,
                batch_id,
                gross_hourly_rate,
                notes
            )
            VALUES
            (
                :work_date,
                :hours_worked,
                :activity,
                :batch_id,
                :gross_hourly_rate,
                :notes
            )
        ");

        $insert->execute([
            ':work_date' => $workDate,
            ':hours_worked' => $hoursWorkedValue,
            ':activity' => $activity,
            ':batch_id' => $batchIdValue,
            ':gross_hourly_rate' => $grossHourlyRateValue,
            ':notes' => $notes !== '' ? $notes : null
        ]);

        $grossLaborValue = $hoursWorkedValue * $grossHourlyRateValue;

        $successMessage = sprintf(
            'Arbeidsregistratie opgeslagen: %.2f uur met een bruto arbeidswaarde van € %.2f.',
            $hoursWorkedValue,
            $grossLaborValue
        );

        $workDate = date('Y-m-d');
        $hoursWorked = '';
        $activity = '';
        $batchId = '';
        $grossHourlyRate = '';
        $notes = '';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>⏱ Arbeidsuren registreren</h1>

    <p>
        <a class="btn" href="dashboard.php">
            ← <?= htmlspecialchars(__('back')) ?>
        </a>
    </p>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <p>
                <strong><?= htmlspecialchars($successMessage) ?></strong>
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="card">
            <h2>Controleer de invoer</h2>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <p>
            <strong>Let op:</strong>
            het bruto interne uurtarief wordt gebruikt om de economische waarde
            van de gewerkte tijd te berekenen. Dit is geen nettoloon en ook geen
            daadwerkelijke loonuitbetaling.
        </p>

        <form method="post">
            <label for="work_date">Werkdatum</label><br>
            <input
                id="work_date"
                type="date"
                name="work_date"
                value="<?= htmlspecialchars($workDate) ?>"
                required
            >
            <br><br>

            <label for="hours_worked">Gewerkte uren</label><br>
            <input
                id="hours_worked"
                type="number"
                name="hours_worked"
                min="0.01"
                step="0.01"
                value="<?= htmlspecialchars($hoursWorked) ?>"
                required
            >
            <br><br>

            <label for="activity">Activiteit</label><br>
            <select id="activity" name="activity" required>
                <option value="">-- Kies activiteit --</option>

                <?php foreach ($activities as $activityCode => $activityLabel): ?>
                    <option
                        value="<?= htmlspecialchars($activityCode) ?>"
                        <?= $activity === $activityCode ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($activityLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="batch_id">Gekoppelde batch (optioneel)</label><br>
            <select id="batch_id" name="batch_id">
                <option value="">-- Geen batch gekoppeld --</option>

                <?php foreach ($batches as $batch): ?>
                    <option
                        value="<?= htmlspecialchars((string)$batch['id']) ?>"
                        <?= $batchId === (string)$batch['id'] ? 'selected' : '' ?>
                    >
                        #<?= htmlspecialchars((string)$batch['id']) ?>
                        — <?= htmlspecialchars((string)$batch['crop']) ?>
                        — <?= htmlspecialchars((string)($batch['sow_date'] ?? '-')) ?>
                        — <?= htmlspecialchars((string)($batch['status'] ?? '-')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="gross_hourly_rate">Bruto intern uurtarief (€)</label><br>
            <input
                id="gross_hourly_rate"
                type="number"
                name="gross_hourly_rate"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars($grossHourlyRate) ?>"
                required
            >
            <br><br>

            <label for="notes"><?= htmlspecialchars(__('notes')) ?></label><br>
            <textarea
                id="notes"
                name="notes"
                rows="4"
            ><?= htmlspecialchars($notes) ?></textarea>
            <br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(__('save')) ?>
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>