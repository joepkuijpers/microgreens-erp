<?php
include 'db_connect.php';
include 'includes/language.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$activityLabels = [
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

$laborEntries = $db->query("
    SELECT
        l.id,
        l.work_date,
        l.hours_worked,
        l.activity,
        l.batch_id,
        l.gross_hourly_rate,
        l.notes,
        l.created_at,
        g.crop AS batch_crop
    FROM labor_entries l
    LEFT JOIN grow_batches g
        ON g.id = l.batch_id
    ORDER BY
        l.work_date DESC,
        l.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$laborTotals = $db->query("
    SELECT
        COALESCE(SUM(hours_worked), 0) AS total_hours,
        COALESCE(
            SUM(hours_worked * gross_hourly_rate),
            0
        ) AS total_gross_labor_value
    FROM labor_entries
")->fetch(PDO::FETCH_ASSOC);

$totalHours = (float)($laborTotals['total_hours'] ?? 0);
$totalGrossLaborValue = (float)($laborTotals['total_gross_labor_value'] ?? 0);
?>

<div class="main">

    <h1>⏱ Arbeidsregistratie</h1>

    <p>
        <a class="btn" href="add_labor_form.php">
            ➕ Arbeidsuren registreren
        </a>

        <a class="btn" href="dashboard.php">
            ← <?= htmlspecialchars(__('back')) ?>
        </a>
    </p>

    <div class="grid">

        <div class="tile">
            <h2>⏱ Totaal gewerkte uren</h2>

            <p>
                <?= number_format($totalHours, 2, ',', '.') ?> uur
            </p>
        </div>

        <div class="tile">
            <h2>💶 Totale bruto arbeidswaarde</h2>

            <p>
                € <?= number_format($totalGrossLaborValue, 2, ',', '.') ?>
            </p>
        </div>

    </div>

    <div class="card">

        <p>
            <strong>Financiële uitleg:</strong>

            De bruto arbeidswaarde is uitsluitend een interne economische
            berekening.

            <br><br>

            <strong>
                Gewerkte uren × bruto intern uurtarief.
            </strong>

            <br><br>

            Dit is géén nettoloon en géén daadwerkelijke loonuitbetaling.
        </p>

    </div>

    <div class="card labor-table-card">

        <div class="table-scroll">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Werkdatum</th>

                        <th>Activiteit</th>

                        <th>Batch</th>

                        <th>Gewerkte uren</th>

                        <th>Bruto uurtarief</th>

                        <th>Bruto arbeidswaarde</th>

                        <th>Notities</th>

                    </tr>

                </thead>

                <tbody>
<?php if (empty($laborEntries)): ?>

    <tr>
        <td colspan="8">
            Nog geen arbeidsregistraties gevonden.
        </td>
    </tr>

<?php endif; ?>

<?php foreach ($laborEntries as $entry): ?>

<?php
$hoursWorked = (float)$entry['hours_worked'];
$grossHourlyRate = (float)$entry['gross_hourly_rate'];
$grossLaborValue = $hoursWorked * $grossHourlyRate;

$activityCode = (string)$entry['activity'];

$activityLabel = $activityLabels[$activityCode] ?? $activityCode;
?>

<tr>

    <td>
        <?= htmlspecialchars((string)$entry['id']) ?>
    </td>

    <td>
        <?= htmlspecialchars((string)$entry['work_date']) ?>
    </td>

    <td>
        <?= htmlspecialchars($activityLabel) ?>
    </td>

    <td>

        <?php if (!empty($entry['batch_id'])): ?>

            <a href="batch_details.php?id=<?= urlencode((string)$entry['batch_id']) ?>">

                #<?= htmlspecialchars((string)$entry['batch_id']) ?>

                <?php if (!empty($entry['batch_crop'])): ?>

                    —
                    <?= htmlspecialchars((string)$entry['batch_crop']) ?>

                <?php endif; ?>

            </a>

        <?php else: ?>

            -

        <?php endif; ?>

    </td>

    <td>
        <?= number_format($hoursWorked, 2, ',', '.') ?> uur
    </td>

    <td>
        €
        <?= number_format($grossHourlyRate, 2, ',', '.') ?>
    </td>

    <td>

        <strong>
            €
            <?= number_format($grossLaborValue, 2, ',', '.') ?>
        </strong>

    </td>

    <td>

        <?= htmlspecialchars((string)($entry['notes'] ?? '-')) ?>

    </td>

</tr>

<?php endforeach; ?>
                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>