<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';
include '../app/includes/language.php';
include '../app/includes/header.php';
include '../app/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_brix_session_id'));
}

$sessionStmt = $db->prepare("
    SELECT
        s.*,
        g.crop,
        g.sow_date,
        g.status AS batch_status
    FROM brix_measurement_sessions s
    INNER JOIN grow_batches g
        ON g.id = s.batch_id
    WHERE s.id = :id
");
$sessionStmt->execute([
    ':id' => $id,
]);
$session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    die(__('brix_session_not_found'));
}

$readingsStmt = $db->prepare("
    SELECT
        id,
        replicate_number,
        sampling_position,
        sample_size,
        sample_size_unit,
        brix_value,
        sample_temperature,
        is_valid,
        invalid_reason,
        notes,
        created_at
    FROM brix_measurement_readings
    WHERE session_id = :session_id
    ORDER BY replicate_number ASC, id ASC
");
$readingsStmt->execute([
    ':session_id' => $id,
]);
$readings = $readingsStmt->fetchAll(PDO::FETCH_ASSOC);

$validValues = [];

foreach ($readings as $reading) {
    if (
        (int)$reading['is_valid'] === 1 &&
        $reading['brix_value'] !== null
    ) {
        $validValues[] = (float)$reading['brix_value'];
    }
}

$meanBrix = count($validValues) > 0
    ? array_sum($validValues) / count($validValues)
    : null;

$minimumBrix = count($validValues) > 0
    ? min($validValues)
    : null;

$maximumBrix = count($validValues) > 0
    ? max($validValues)
    : null;

function displayBrixValue($value, string $suffix = ''): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((float)$value, 2, ',', '.') . $suffix;
}

function displayOptionalValue($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return (string)$value;
}
?>

<div class="main">
    <h1>🔬 <?= htmlspecialchars(__('brix_session_details')) ?></h1>

    <?php if (($_GET['invalidated'] ?? '') === '1'): ?>
        <div class="card">
            <p>
                <?= htmlspecialchars(__('brix_reading_invalidated')) ?>
            </p>
        </div>
    <?php endif; ?>

    <p>
        <a
            class="btn"
            href="brix_measurements.php?batch_id=<?= urlencode((string)$session['batch_id']) ?>"
        >
            ← <?= htmlspecialchars(__('back_to_brix_history')) ?>
        </a>

        <a
            class="btn"
            href="batch_details.php?id=<?= urlencode((string)$session['batch_id']) ?>"
        >
            🌱 <?= htmlspecialchars(__('view_grow_batch')) ?>
        </a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('measurement_summary')) ?></h2>

        <table>
            <tr>
                <th>ID</th>
                <td><?= htmlspecialchars((string)$session['id']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('crop')) ?></th>
                <td><?= htmlspecialchars((string)$session['crop']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('date')) ?></th>
                <td><?= htmlspecialchars((string)$session['measured_at']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('measurement_purpose')) ?></th>
                <td><?= htmlspecialchars((string)$session['purpose']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('mean_brix')) ?></th>
                <td>
                    <?= htmlspecialchars(
                        displayBrixValue($meanBrix, ' °Bx')
                    ) ?>
                </td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('brix_range')) ?></th>
                <td>
                    <?php if (
                        $minimumBrix !== null &&
                        $maximumBrix !== null
                    ): ?>
                        <?= htmlspecialchars(
                            displayBrixValue($minimumBrix)
                        ) ?>
                        –
                        <?= htmlspecialchars(
                            displayBrixValue($maximumBrix, ' °Bx')
                        ) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('measurement_context')) ?></h2>

        <table>
            <tr>
                <th><?= htmlspecialchars(__('growth_stage')) ?></th>
                <td><?= htmlspecialchars(displayOptionalValue($session['growth_stage'])) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('plant_part')) ?></th>
                <td><?= htmlspecialchars((string)$session['plant_part']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('sampling_method')) ?></th>
                <td><?= htmlspecialchars((string)$session['sampling_method']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('instrument_identifier')) ?></th>
                <td><?= htmlspecialchars((string)$session['instrument_identifier']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('instrument_resolution')) ?></th>
                <td>
                    <?= htmlspecialchars(
                        displayBrixValue(
                            $session['instrument_resolution'],
                            ' °Bx'
                        )
                    ) ?>
                </td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('temperature_compensation')) ?></th>
                <td>
                    <?= htmlspecialchars(
                        (int)$session['temperature_compensation'] === 1
                            ? __('yes')
                            : __('no')
                    ) ?>
                </td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('calibration_passed')) ?></th>
                <td>
                    <?= htmlspecialchars(
                        (int)$session['calibration_passed'] === 1
                            ? __('yes')
                            : __('no')
                    ) ?>
                </td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('time_since_irrigation_minutes')) ?></th>
                <td><?= htmlspecialchars(displayOptionalValue($session['time_since_irrigation_minutes'])) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('observer')) ?></th>
                <td><?= htmlspecialchars(displayOptionalValue($session['observer'])) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('measurement_mode')) ?></th>
                <td><?= htmlspecialchars(__((string)$session['measurement_mode'])) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('notes')) ?></th>
                <td><?= nl2br(htmlspecialchars(displayOptionalValue($session['notes']))) ?></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('brix_readings')) ?></h2>

        <?php if (count($readings) === 0): ?>
            <p><?= htmlspecialchars(__('no_brix_readings')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(__('replicate')) ?></th>
                            <th><?= htmlspecialchars(__('brix_value')) ?></th>
                            <th><?= htmlspecialchars(__('measurement_validity')) ?></th>
                            <th><?= htmlspecialchars(__('sampling_position')) ?></th>
                            <th><?= htmlspecialchars(__('sample_size')) ?></th>
                            <th><?= htmlspecialchars(__('sample_temperature')) ?></th>
                            <th><?= htmlspecialchars(__('invalid_reason')) ?></th>
                            <th><?= htmlspecialchars(__('notes')) ?></th>
                            <th><?= htmlspecialchars(__('measurement_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readings as $reading): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string)$reading['replicate_number']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        displayBrixValue(
                                            $reading['brix_value'],
                                            ' °Bx'
                                        )
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        (int)$reading['is_valid'] === 1
                                            ? __('measurement_valid')
                                            : __('measurement_invalid')
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        displayOptionalValue(
                                            $reading['sampling_position']
                                        )
                                    ) ?>
                                </td>
                                <td>
                                    <?php if ($reading['sample_size'] !== null): ?>
                                        <?= htmlspecialchars(
                                            displayBrixValue(
                                                $reading['sample_size']
                                            )
                                        ) ?>
                                        <?= htmlspecialchars(
                                            displayOptionalValue(
                                                $reading['sample_size_unit']
                                            )
                                        ) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        displayBrixValue(
                                            $reading['sample_temperature'],
                                            ' °C'
                                        )
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        displayOptionalValue(
                                            $reading['invalid_reason']
                                        )
                                    ) ?>
                                </td>
                                <td>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            displayOptionalValue(
                                                $reading['notes']
                                            )
                                        )
                                    ) ?>
                                </td>
                                <td>
                                    <?php if ((int)$reading['is_valid'] === 1): ?>
                                        <form
                                            method="post"
                                            action="invalidate_brix_reading.php"
                                            onsubmit="return confirm('<?= htmlspecialchars(
                                                __('confirm_invalidate_brix_reading'),
                                                ENT_QUOTES
                                            ) ?>');"
                                        >
                                            <input
                                                type="hidden"
                                                name="reading_id"
                                                value="<?= htmlspecialchars((string)$reading['id']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="session_id"
                                                value="<?= htmlspecialchars((string)$session['id']) ?>"
                                            >

                                            <label>
                                                <?= htmlspecialchars(__('invalidation_reason')) ?>
                                            </label><br>

                                            <input
                                                type="text"
                                                name="invalid_reason"
                                                maxlength="500"
                                                required
                                            ><br><br>

                                            <button type="submit" class="btn">
                                                <?= htmlspecialchars(__('invalidate_measurement')) ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p>
        <a
            class="btn"
            href="brix_measurements.php?batch_id=<?= urlencode((string)$session['batch_id']) ?>"
        >
            ← <?= htmlspecialchars(__('back_to_brix_history')) ?>
        </a>
    </p>
</div>

<?php include '../app/includes/footer.php'; ?>
