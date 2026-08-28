<?php

require_once '../app/includes/auth.php';
auth_require_login();

require_once '../app/db_connect.php';
require_once '../app/includes/language.php';
require_once '../app/includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: grow_batches.php');
    exit;
}

$readingIdRaw = trim((string)($_POST['reading_id'] ?? ''));
$sessionIdRaw = trim((string)($_POST['session_id'] ?? ''));
$invalidReason = trim((string)($_POST['invalid_reason'] ?? ''));

if (
    !ctype_digit($readingIdRaw) ||
    (int)$readingIdRaw <= 0 ||
    !ctype_digit($sessionIdRaw) ||
    (int)$sessionIdRaw <= 0 ||
    $invalidReason === '' ||
    strlen($invalidReason) > 500
) {
    die(__('invalid_brix_invalidation_input'));
}

$readingId = (int)$readingIdRaw;
$sessionId = (int)$sessionIdRaw;

$readingStmt = $db->prepare("
    SELECT
        id,
        session_id,
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
    WHERE id = :reading_id
      AND session_id = :session_id
");
$readingStmt->execute([
    ':reading_id' => $readingId,
    ':session_id' => $sessionId,
]);

$reading = $readingStmt->fetch(PDO::FETCH_ASSOC);

if (!$reading) {
    die(__('brix_reading_not_found'));
}

if ((int)$reading['is_valid'] !== 1) {
    die(__('brix_reading_already_invalid'));
}

$beforeData = [
    'id' => (int)$reading['id'],
    'session_id' => (int)$reading['session_id'],
    'replicate_number' => (int)$reading['replicate_number'],
    'sampling_position' => $reading['sampling_position'],
    'sample_size' => $reading['sample_size'],
    'sample_size_unit' => $reading['sample_size_unit'],
    'brix_value' => $reading['brix_value'],
    'sample_temperature' => $reading['sample_temperature'],
    'is_valid' => (int)$reading['is_valid'],
    'invalid_reason' => $reading['invalid_reason'],
    'notes' => $reading['notes'],
    'created_at' => $reading['created_at'],
];

try {
    $db->beginTransaction();

    $updateStmt = $db->prepare("
        UPDATE brix_measurement_readings
        SET
            is_valid = 0,
            invalid_reason = :invalid_reason
        WHERE id = :reading_id
          AND session_id = :session_id
          AND is_valid = 1
    ");

    $updateStmt->execute([
        ':invalid_reason' => $invalidReason,
        ':reading_id' => $readingId,
        ':session_id' => $sessionId,
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException(
            'Brix reading was not invalidated.'
        );
    }

    $afterStmt = $db->prepare("
        SELECT
            id,
            session_id,
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
        WHERE id = :reading_id
          AND session_id = :session_id
    ");

    $afterStmt->execute([
        ':reading_id' => $readingId,
        ':session_id' => $sessionId,
    ]);

    $afterReading = $afterStmt->fetch(PDO::FETCH_ASSOC);

    if (!$afterReading) {
        throw new RuntimeException(
            'Brix reading could not be re-read after invalidation.'
        );
    }

    $afterData = [
        'id' => (int)$afterReading['id'],
        'session_id' => (int)$afterReading['session_id'],
        'replicate_number' => (int)$afterReading['replicate_number'],
        'sampling_position' => $afterReading['sampling_position'],
        'sample_size' => $afterReading['sample_size'],
        'sample_size_unit' => $afterReading['sample_size_unit'],
        'brix_value' => $afterReading['brix_value'],
        'sample_temperature' => $afterReading['sample_temperature'],
        'is_valid' => (int)$afterReading['is_valid'],
        'invalid_reason' => $afterReading['invalid_reason'],
        'notes' => $afterReading['notes'],
        'created_at' => $afterReading['created_at'],
    ];

    auditLog(
        $db,
        'DATA_CORRECTION',
        'brix_measurement_reading',
        $readingId,
        'INVALIDATE',
        $invalidReason,
        $beforeData,
        $afterData,
        'brix_measurement_session',
        $sessionId
    );

    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    throw $exception;
}

header(
    'Location: brix_measurement_details.php?id=' .
    urlencode((string)$sessionId) .
    '&invalidated=1'
);
exit;
