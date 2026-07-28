<?php
include '../app/db_connect.php';
include '../app/includes/language.php';

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
        r.id,
        r.session_id,
        r.is_valid
    FROM brix_measurement_readings r
    WHERE r.id = :reading_id
      AND r.session_id = :session_id
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
