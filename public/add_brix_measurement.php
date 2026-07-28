<?php

include '../app/db_connect.php';
include '../app/includes/language.php';

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

$batchId = trim((string)($_GET['batch_id'] ?? ''));
$measuredAt = date('Y-m-d\TH:i');
$purpose = '';
$growthStage = '';
$plantPart = 'shoot';
$samplingMethod = 'pooled expressed sap';
$instrumentIdentifier = '';
$instrumentResolution = '0.1';
$temperatureCompensation = true;
$calibrationPassed = true;
$timeSinceIrrigation = '';
$observer = '';
$measurementMode = 'experimental';
$notes = '';

$readings = [];

for ($index = 0; $index < 3; $index++) {
    $readings[] = [
        'sampling_position' => '',
        'sample_size' => '',
        'sample_size_unit' => 'plants',
        'brix_value' => '',
        'sample_temperature' => '',
        'is_valid' => true,
        'invalid_reason' => '',
        'notes' => '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchId = trim((string)($_POST['batch_id'] ?? ''));
    $measuredAt = trim((string)($_POST['measured_at'] ?? ''));
    $purpose = trim((string)($_POST['purpose'] ?? ''));
    $growthStage = trim((string)($_POST['growth_stage'] ?? ''));
    $plantPart = trim((string)($_POST['plant_part'] ?? ''));
    $samplingMethod = trim((string)($_POST['sampling_method'] ?? ''));
    $instrumentIdentifier = trim(
        (string)($_POST['instrument_identifier'] ?? '')
    );
    $instrumentResolution = trim(
        (string)($_POST['instrument_resolution'] ?? '')
    );
    $temperatureCompensation =
        ($_POST['temperature_compensation'] ?? '0') === '1';
    $calibrationPassed =
        ($_POST['calibration_passed'] ?? '0') === '1';
    $timeSinceIrrigation = trim(
        (string)($_POST['time_since_irrigation_minutes'] ?? '')
    );
    $observer = trim((string)($_POST['observer'] ?? ''));
    $measurementMode = trim(
        (string)($_POST['measurement_mode'] ?? '')
    );
    $notes = trim((string)($_POST['notes'] ?? ''));

    $positions = $_POST['sampling_position'] ?? [];
    $sampleSizes = $_POST['sample_size'] ?? [];
    $sampleSizeUnits = $_POST['sample_size_unit'] ?? [];
    $brixValues = $_POST['brix_value'] ?? [];
    $sampleTemperatures = $_POST['sample_temperature'] ?? [];
    $validities = $_POST['reading_valid'] ?? [];
    $invalidReasons = $_POST['invalid_reason'] ?? [];
    $readingNotes = $_POST['reading_notes'] ?? [];

    $readings = [];

    for ($index = 0; $index < 3; $index++) {
        $readings[] = [
            'sampling_position' => trim(
                (string)($positions[$index] ?? '')
            ),
            'sample_size' => trim(
                (string)($sampleSizes[$index] ?? '')
            ),
            'sample_size_unit' => trim(
                (string)($sampleSizeUnits[$index] ?? '')
            ),
            'brix_value' => trim(
                (string)($brixValues[$index] ?? '')
            ),
            'sample_temperature' => trim(
                (string)($sampleTemperatures[$index] ?? '')
            ),
            'is_valid' =>
                ($validities[$index] ?? '0') === '1',
            'invalid_reason' => trim(
                (string)($invalidReasons[$index] ?? '')
            ),
            'notes' => trim(
                (string)($readingNotes[$index] ?? '')
            ),
        ];
    }

    $batchIdValue = ctype_digit($batchId)
        ? (int)$batchId
        : 0;

    if ($batchIdValue <= 0) {
        $errors[] = __('invalid_brix_input');
    }

    $batchExists = false;

    if ($batchIdValue > 0) {
        $batchCheck = $db->prepare("
            SELECT COUNT(*)
            FROM grow_batches
            WHERE id = :id
        ");

        $batchCheck->execute([
            ':id' => $batchIdValue,
        ]);

        $batchExists = (int)$batchCheck->fetchColumn() === 1;

        if (!$batchExists) {
            $errors[] = __('invalid_brix_input');
        }
    }

    $measurementDate = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $measuredAt
    );

    if (!$measurementDate) {
        $errors[] = __('invalid_brix_input');
    }

    if (
        $purpose === '' ||
        $plantPart === '' ||
        $samplingMethod === '' ||
        $instrumentIdentifier === ''
    ) {
        $errors[] = __('invalid_brix_input');
    }

    if (
        $instrumentResolution !== '' &&
        (
            !is_numeric($instrumentResolution) ||
            (float)$instrumentResolution <= 0
        )
    ) {
        $errors[] = __('invalid_brix_input');
    }

    if (
        $timeSinceIrrigation !== '' &&
        (
            !ctype_digit($timeSinceIrrigation) ||
            (int)$timeSinceIrrigation < 0
        )
    ) {
        $errors[] = __('invalid_brix_input');
    }

    if (
        !in_array(
            $measurementMode,
            ['experimental', 'routine'],
            true
        )
    ) {
        $errors[] = __('invalid_brix_input');
    }

    foreach ($readings as $reading) {
        if (
            $reading['brix_value'] !== '' &&
            (
                !is_numeric($reading['brix_value']) ||
                (float)$reading['brix_value'] < 0
            )
        ) {
            $errors[] = __('invalid_brix_input');
        }

        if (
            $reading['is_valid'] &&
            $reading['brix_value'] === ''
        ) {
            $errors[] = __('invalid_brix_input');
        }

        if (
            !$reading['is_valid'] &&
            $reading['invalid_reason'] === ''
        ) {
            $errors[] = __('invalid_brix_input');
        }

        if (
            $reading['sample_size'] !== '' &&
            (
                !is_numeric($reading['sample_size']) ||
                (float)$reading['sample_size'] <= 0 ||
                $reading['sample_size_unit'] === ''
            )
        ) {
            $errors[] = __('invalid_brix_input');
        }

        if (
            $reading['sample_temperature'] !== '' &&
            !is_numeric($reading['sample_temperature'])
        ) {
            $errors[] = __('invalid_brix_input');
        }

        if (
            !$calibrationPassed &&
            $reading['is_valid']
        ) {
            $errors[] = __('invalid_brix_input');
        }
    }

    $errors = array_values(array_unique($errors));

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $sessionInsert = $db->prepare("
                INSERT INTO brix_measurement_sessions (
                    batch_id,
                    measured_at,
                    purpose,
                    growth_stage,
                    plant_part,
                    sampling_method,
                    instrument_identifier,
                    instrument_resolution,
                    temperature_compensation,
                    calibration_passed,
                    time_since_irrigation_minutes,
                    observer,
                    measurement_mode,
                    notes
                )
                VALUES (
                    :batch_id,
                    :measured_at,
                    :purpose,
                    :growth_stage,
                    :plant_part,
                    :sampling_method,
                    :instrument_identifier,
                    :instrument_resolution,
                    :temperature_compensation,
                    :calibration_passed,
                    :time_since_irrigation_minutes,
                    :observer,
                    :measurement_mode,
                    :notes
                )
            ");

            $sessionInsert->execute([
                ':batch_id' => $batchIdValue,
                ':measured_at' => $measurementDate->format(
                    'Y-m-d H:i:s'
                ),
                ':purpose' => $purpose,
                ':growth_stage' =>
                    $growthStage !== '' ? $growthStage : null,
                ':plant_part' => $plantPart,
                ':sampling_method' => $samplingMethod,
                ':instrument_identifier' => $instrumentIdentifier,
                ':instrument_resolution' =>
                    $instrumentResolution !== ''
                        ? (float)$instrumentResolution
                        : null,
                ':temperature_compensation' =>
                    $temperatureCompensation ? 1 : 0,
                ':calibration_passed' =>
                    $calibrationPassed ? 1 : 0,
                ':time_since_irrigation_minutes' =>
                    $timeSinceIrrigation !== ''
                        ? (int)$timeSinceIrrigation
                        : null,
                ':observer' =>
                    $observer !== '' ? $observer : null,
                ':measurement_mode' => $measurementMode,
                ':notes' => $notes !== '' ? $notes : null,
            ]);

            $sessionId = (int)$db->lastInsertId();

            $readingInsert = $db->prepare("
                INSERT INTO brix_measurement_readings (
                    session_id,
                    replicate_number,
                    sampling_position,
                    sample_size,
                    sample_size_unit,
                    brix_value,
                    sample_temperature,
                    is_valid,
                    invalid_reason,
                    notes
                )
                VALUES (
                    :session_id,
                    :replicate_number,
                    :sampling_position,
                    :sample_size,
                    :sample_size_unit,
                    :brix_value,
                    :sample_temperature,
                    :is_valid,
                    :invalid_reason,
                    :notes
                )
            ");

            foreach ($readings as $index => $reading) {
                $readingInsert->execute([
                    ':session_id' => $sessionId,
                    ':replicate_number' => $index + 1,
                    ':sampling_position' =>
                        $reading['sampling_position'] !== ''
                            ? $reading['sampling_position']
                            : null,
                    ':sample_size' =>
                        $reading['sample_size'] !== ''
                            ? (float)$reading['sample_size']
                            : null,
                    ':sample_size_unit' =>
                        $reading['sample_size'] !== ''
                            ? $reading['sample_size_unit']
                            : null,
                    ':brix_value' =>
                        $reading['brix_value'] !== ''
                            ? (float)$reading['brix_value']
                            : null,
                    ':sample_temperature' =>
                        $reading['sample_temperature'] !== ''
                            ? (float)$reading['sample_temperature']
                            : null,
                    ':is_valid' =>
                        $reading['is_valid'] ? 1 : 0,
                    ':invalid_reason' =>
                        $reading['invalid_reason'] !== ''
                            ? $reading['invalid_reason']
                            : null,
                    ':notes' =>
                        $reading['notes'] !== ''
                            ? $reading['notes']
                            : null,
                ]);
            }

            $db->commit();

            header(
                'Location: add_brix_measurement.php?saved=1'
                . '&batch_id='
                . urlencode((string)$batchIdValue)
            );
            exit;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }
}

include '../app/includes/header.php';
include '../app/includes/sidebar.php';
?>

<div class="main">
    <h1>🔬 <?= htmlspecialchars(__('brix_quick_entry')) ?></h1>

    <p>
        <a class="btn" href="grow_batches.php">
            ← <?= htmlspecialchars(__('back')) ?>
        </a>
    </p>

    <div class="card">
        <p>
            <?= htmlspecialchars(__('brix_optional_explanation')) ?>
        </p>
    </div>

    <?php if (($_GET['saved'] ?? '') === '1'): ?>
        <div class="card">
            <p>
                <strong>
                    <?= htmlspecialchars(__('brix_measurement_saved')) ?>
                </strong>
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="card">
            <p>
                <strong>
                    <?= htmlspecialchars(__('invalid_brix_input')) ?>
                </strong>
            </p>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="card">
            <h2><?= htmlspecialchars(__('brix_quick_entry')) ?></h2>

            <label for="batch_id">
                <?= htmlspecialchars(__('batch')) ?>
            </label><br>

            <select id="batch_id" name="batch_id" required>
                <option value="">-- <?= htmlspecialchars(__('batch')) ?> --</option>

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

            <label for="measured_at">
                <?= htmlspecialchars(__('date')) ?>
            </label><br>

            <input
                id="measured_at"
                type="datetime-local"
                name="measured_at"
                value="<?= htmlspecialchars($measuredAt) ?>"
                required
            >
            <br><br>

            <label for="purpose">
                <?= htmlspecialchars(__('measurement_purpose')) ?>
            </label><br>

            <input
                id="purpose"
                type="text"
                name="purpose"
                value="<?= htmlspecialchars($purpose) ?>"
                required
            >
            <br><br>

            <label for="instrument_identifier">
                <?= htmlspecialchars(__('instrument_identifier')) ?>
            </label><br>

            <input
                id="instrument_identifier"
                type="text"
                name="instrument_identifier"
                value="<?= htmlspecialchars($instrumentIdentifier) ?>"
                required
            >
            <br><br>

            <input type="hidden" name="calibration_passed" value="0">
            <label>
                <input
                    type="checkbox"
                    name="calibration_passed"
                    value="1"
                    <?= $calibrationPassed ? 'checked' : '' ?>
                >
                <?= htmlspecialchars(__('calibration_passed')) ?>
            </label>
        </div>

        <?php foreach ($readings as $index => $reading): ?>
            <div class="card">
                <h2>
                    <?= htmlspecialchars(__('replicate')) ?>
                    <?= $index + 1 ?>
                </h2>

                <label for="brix_value_<?= $index ?>">
                    <?= htmlspecialchars(__('brix_value')) ?> (°Bx)
                </label><br>

                <input
                    id="brix_value_<?= $index ?>"
                    type="number"
                    name="brix_value[<?= $index ?>]"
                    min="0"
                    step="0.1"
                    value="<?= htmlspecialchars($reading['brix_value']) ?>"
                >
                <br><br>

                <input
                    type="hidden"
                    name="reading_valid[<?= $index ?>]"
                    value="0"
                >

                <label>
                    <input
                        type="checkbox"
                        name="reading_valid[<?= $index ?>]"
                        value="1"
                        <?= $reading['is_valid'] ? 'checked' : '' ?>
                    >
                    <?= htmlspecialchars(__('valid_measurement')) ?>
                </label>

                <details>
                    <summary>
                        <?= htmlspecialchars(
                            __('additional_measurement_details')
                        ) ?>
                    </summary>

                    <br>

                    <label for="sampling_position_<?= $index ?>">
                        <?= htmlspecialchars(__('sampling_position')) ?>
                    </label><br>

                    <input
                        id="sampling_position_<?= $index ?>"
                        type="text"
                        name="sampling_position[<?= $index ?>]"
                        value="<?= htmlspecialchars(
                            $reading['sampling_position']
                        ) ?>"
                    >
                    <br><br>

                    <label for="sample_size_<?= $index ?>">
                        <?= htmlspecialchars(__('sample_size')) ?>
                    </label><br>

                    <input
                        id="sample_size_<?= $index ?>"
                        type="number"
                        name="sample_size[<?= $index ?>]"
                        min="0.01"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            $reading['sample_size']
                        ) ?>"
                    >

                    <input
                        type="text"
                        name="sample_size_unit[<?= $index ?>]"
                        value="<?= htmlspecialchars(
                            $reading['sample_size_unit']
                        ) ?>"
                        aria-label="<?= htmlspecialchars(
                            __('sample_size_unit')
                        ) ?>"
                    >
                    <br><br>

                    <label for="sample_temperature_<?= $index ?>">
                        <?= htmlspecialchars(__('sample_temperature')) ?> (°C)
                    </label><br>

                    <input
                        id="sample_temperature_<?= $index ?>"
                        type="number"
                        name="sample_temperature[<?= $index ?>]"
                        step="0.1"
                        value="<?= htmlspecialchars(
                            $reading['sample_temperature']
                        ) ?>"
                    >
                    <br><br>

                    <label for="invalid_reason_<?= $index ?>">
                        <?= htmlspecialchars(__('invalid_reason')) ?>
                    </label><br>

                    <input
                        id="invalid_reason_<?= $index ?>"
                        type="text"
                        name="invalid_reason[<?= $index ?>]"
                        value="<?= htmlspecialchars(
                            $reading['invalid_reason']
                        ) ?>"
                    >
                    <br><br>

                    <label for="reading_notes_<?= $index ?>">
                        <?= htmlspecialchars(__('notes')) ?>
                    </label><br>

                    <textarea
                        id="reading_notes_<?= $index ?>"
                        name="reading_notes[<?= $index ?>]"
                        rows="2"
                    ><?= htmlspecialchars($reading['notes']) ?></textarea>
                </details>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <details>
                <summary>
                    <?= htmlspecialchars(
                        __('additional_measurement_details')
                    ) ?>
                </summary>

                <br>

                <label for="growth_stage">
                    <?= htmlspecialchars(__('growth_stage')) ?>
                </label><br>

                <input
                    id="growth_stage"
                    type="text"
                    name="growth_stage"
                    value="<?= htmlspecialchars($growthStage) ?>"
                >
                <br><br>

                <label for="plant_part">
                    <?= htmlspecialchars(__('plant_part')) ?>
                </label><br>

                <input
                    id="plant_part"
                    type="text"
                    name="plant_part"
                    value="<?= htmlspecialchars($plantPart) ?>"
                    required
                >
                <br><br>

                <label for="sampling_method">
                    <?= htmlspecialchars(__('sampling_method')) ?>
                </label><br>

                <input
                    id="sampling_method"
                    type="text"
                    name="sampling_method"
                    value="<?= htmlspecialchars($samplingMethod) ?>"
                    required
                >
                <br><br>

                <label for="instrument_resolution">
                    <?= htmlspecialchars(__('instrument_resolution')) ?>
                </label><br>

                <input
                    id="instrument_resolution"
                    type="number"
                    name="instrument_resolution"
                    min="0.01"
                    step="0.01"
                    value="<?= htmlspecialchars($instrumentResolution) ?>"
                >
                <br><br>

                <input
                    type="hidden"
                    name="temperature_compensation"
                    value="0"
                >

                <label>
                    <input
                        type="checkbox"
                        name="temperature_compensation"
                        value="1"
                        <?= $temperatureCompensation ? 'checked' : '' ?>
                    >
                    <?= htmlspecialchars(
                        __('temperature_compensation')
                    ) ?>
                </label>
                <br><br>

                <label for="time_since_irrigation_minutes">
                    <?= htmlspecialchars(
                        __('time_since_irrigation_minutes')
                    ) ?>
                </label><br>

                <input
                    id="time_since_irrigation_minutes"
                    type="number"
                    name="time_since_irrigation_minutes"
                    min="0"
                    step="1"
                    value="<?= htmlspecialchars($timeSinceIrrigation) ?>"
                >
                <br><br>

                <label for="observer">
                    <?= htmlspecialchars(__('observer')) ?>
                </label><br>

                <input
                    id="observer"
                    type="text"
                    name="observer"
                    value="<?= htmlspecialchars($observer) ?>"
                >
                <br><br>

                <label for="measurement_mode">
                    <?= htmlspecialchars(__('measurement_mode')) ?>
                </label><br>

                <select
                    id="measurement_mode"
                    name="measurement_mode"
                >
                    <option
                        value="experimental"
                        <?= $measurementMode === 'experimental'
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars(__('experimental')) ?>
                    </option>

                    <option
                        value="routine"
                        <?= $measurementMode === 'routine'
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars(__('routine')) ?>
                    </option>
                </select>
                <br><br>

                <label for="notes">
                    <?= htmlspecialchars(__('notes')) ?>
                </label><br>

                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                ><?= htmlspecialchars($notes) ?></textarea>
            </details>
        </div>

        <button type="submit" class="btn">
            <?= htmlspecialchars(__('save_brix_measurement')) ?>
        </button>
    </form>
</div>

<?php include '../app/includes/footer.php'; ?>
