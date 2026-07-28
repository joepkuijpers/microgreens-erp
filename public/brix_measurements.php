<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';
include '../app/includes/language.php';
include '../app/includes/header.php';
include '../app/includes/sidebar.php';

$batchId = (int)($_GET['batch_id'] ?? 0);

if ($batchId <= 0) {
    die(__('invalid_batch_id'));
}

$batchStmt = $db->prepare("
    SELECT id, crop, sow_date, status
    FROM grow_batches
    WHERE id = :batch_id
");
$batchStmt->execute([
    ':batch_id' => $batchId,
]);
$batch = $batchStmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die(__('batch_not_found'));
}

$sessionsStmt = $db->prepare("
    SELECT
        s.id,
        s.measured_at,
        s.purpose,
        s.measurement_mode,
        s.calibration_passed,
        COUNT(r.id) AS reading_count,
        COUNT(
            CASE WHEN r.is_valid = 1 THEN 1 END
        ) AS valid_reading_count,
        COUNT(
            CASE WHEN r.is_valid = 0 THEN 1 END
        ) AS invalid_reading_count,
        AVG(
            CASE WHEN r.is_valid = 1 THEN r.brix_value END
        ) AS mean_brix,
        MIN(
            CASE WHEN r.is_valid = 1 THEN r.brix_value END
        ) AS minimum_brix,
        MAX(
            CASE WHEN r.is_valid = 1 THEN r.brix_value END
        ) AS maximum_brix
    FROM brix_measurement_sessions s
    LEFT JOIN brix_measurement_readings r
        ON r.session_id = s.id
    WHERE s.batch_id = :batch_id
    GROUP BY
        s.id,
        s.measured_at,
        s.purpose,
        s.measurement_mode,
        s.calibration_passed
    ORDER BY s.measured_at DESC, s.id DESC
");
$sessionsStmt->execute([
    ':batch_id' => $batchId,
]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🔬 <?= htmlspecialchars(__('brix_history')) ?></h1>

    <p>
        <?= htmlspecialchars(__('brix_optional_explanation')) ?>
    </p>

    <p>
        <a
            class="btn"
            href="batch_details.php?id=<?= urlencode((string)$batch['id']) ?>"
        >
            ← <?= htmlspecialchars(__('back_to_batch_details')) ?>
        </a>

        <a
            class="btn"
            href="add_brix_measurement.php?batch_id=<?= urlencode((string)$batch['id']) ?>"
        >
            🔬 <?= htmlspecialchars(__('add_brix_measurement')) ?>
        </a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_information')) ?></h2>

        <table>
            <tr>
                <th>ID</th>
                <td><?= htmlspecialchars((string)$batch['id']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('crop')) ?></th>
                <td><?= htmlspecialchars((string)$batch['crop']) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('sowing_date')) ?></th>
                <td><?= htmlspecialchars((string)($batch['sow_date'] ?? '-')) ?></td>
            </tr>
            <tr>
                <th><?= htmlspecialchars(__('status')) ?></th>
                <td><?= htmlspecialchars((string)($batch['status'] ?? '-')) ?></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('measurement_sessions')) ?></h2>

        <?php if (count($sessions) === 0): ?>
            <p><?= htmlspecialchars(__('no_brix_measurements')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= htmlspecialchars(__('date')) ?></th>
                            <th><?= htmlspecialchars(__('measurement_purpose')) ?></th>
                            <th><?= htmlspecialchars(__('measurement_mode')) ?></th>
                            <th><?= htmlspecialchars(__('valid_readings')) ?></th>
                            <th><?= htmlspecialchars(__('mean_brix')) ?></th>
                            <th><?= htmlspecialchars(__('brix_range')) ?></th>
                            <th><?= htmlspecialchars(__('details')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars((string)$session['id']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string)$session['measured_at']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string)$session['purpose']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        __((string)$session['measurement_mode'])
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string)$session['valid_reading_count']
                                    ) ?>
                                    /
                                    <?= htmlspecialchars(
                                        (string)$session['reading_count']
                                    ) ?>
                                </td>
                                <td>
                                    <?php if ($session['mean_brix'] !== null): ?>
                                        <?= number_format(
                                            (float)$session['mean_brix'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?> °Bx
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (
                                        $session['minimum_brix'] !== null &&
                                        $session['maximum_brix'] !== null
                                    ): ?>
                                        <?= number_format(
                                            (float)$session['minimum_brix'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                        –
                                        <?= number_format(
                                            (float)$session['maximum_brix'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?> °Bx
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a
                                        href="brix_measurement_details.php?id=<?= urlencode((string)$session['id']) ?>"
                                    >
                                        🔍 <?= htmlspecialchars(__('details')) ?>
                                    </a>
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
            href="batch_details.php?id=<?= urlencode((string)$batch['id']) ?>"
        >
            ← <?= htmlspecialchars(__('back_to_batch_details')) ?>
        </a>
    </p>
</div>

<?php include '../app/includes/footer.php'; ?>
