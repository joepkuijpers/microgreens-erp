<?php
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/b04_entity_linkage.php';

function b06_create_measurement(PDO $db, string $type, float $value, string $unit, ?int $actorUserId = null, ?string $measuredAt = null, ?string $instrumentIdentifier = null, ?string $sessionReference = null, ?string $notes = null, ?string $entityType = null, ?int $entityId = null): array {
    if (trim($type) === '') throw new InvalidArgumentException("Type empty");
    if (trim($unit) === '') throw new InvalidArgumentException("Unit empty");
    if ($entityType && !$entityId) throw new InvalidArgumentException("Entity ID required");
    if ($entityId && !$entityType) throw new InvalidArgumentException("Entity Type required");
    $measuredAt = $measuredAt ?? date('Y-m-d H:i:s');
    $effectiveActor = $actorUserId;
    if (!$effectiveActor && function_exists('auth_current_user')) { $u = auth_current_user(); if ($u && isset($u['id'])) $effectiveActor = (int)$u['id']; }
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO measurements (measurement_type, value, unit, measured_at, actor_user_id, quality_status, instrument_identifier, session_reference, notes) VALUES (:t,:v,:u,:m,:a,'RAW',:i,:s,:n)");
        $stmt->execute([':t'=>$type,':v'=>$value,':u'=>$unit,':m'=>$measuredAt,':a'=>$effectiveActor,':i'=>$instrumentIdentifier,':s'=>$sessionReference,':n'=>$notes]);
        $id = (int)$db->lastInsertId();
        $auditId = $linkId = null;
        if ($effectiveActor) $auditId = auditLog($db, 'B06_MEASUREMENT', 'measurements', $id, 'CREATE', null, null, ['type'=>$type,'value'=>$value,'unit'=>$unit], $entityType, $entityId);
        if ($entityType && $entityId && $effectiveActor) $linkId = b04_create_entity_link($db, 'measurements', $id, 'MEASURED_FROM', $entityType, (int)$entityId, null, null, $effectiveActor, null, null, $notes);
        $db->commit();
        return ['id'=>$id, 'audit_id'=>$auditId, 'link_id'=>$linkId];
    } catch (Exception $e) { $db->rollBack(); throw $e; }
}

function b06_update_measurement_status(PDO $db, int $id, string $status, ?int $actorUserId = null, ?string $reason = null): array {
    if (!in_array($status, ['RAW','VALIDATED','INVALID','OUTLIER'])) throw new InvalidArgumentException("Invalid status");
    if ($status === 'INVALID' && !trim($reason??'')) throw new InvalidArgumentException("Reason required");
    $actor = $actorUserId;
    if (!$actor && function_exists('auth_current_user')) { $u = auth_current_user(); if ($u && isset($u['id'])) $actor = (int)$u['id']; }
    if (!$actor) throw new InvalidArgumentException("Actor required");
    $db->beginTransaction();
    try {
        $cur = $db->prepare("SELECT * FROM measurements WHERE id=:i"); $cur->execute([':i'=>$id]); $row = $cur->fetch();
        if (!$row) throw new InvalidArgumentException("Not found");
        $upd = $db->prepare("UPDATE measurements SET quality_status=:s, notes=CASE WHEN :n IS NOT NULL THEN notes||CHAR(10)||:n ELSE notes END WHERE id=:i");
        $upd->execute([':s'=>$status,':n'=>$reason,':i'=>$id]);
        $auditId = auditLog($db, 'B06_MEASUREMENT', 'measurements', $id, 'UPDATE_STATUS', $reason, ['quality_status'=>$row['quality_status']], ['quality_status'=>$status]);
        $db->commit();
        return ['id'=>$id, 'audit_id'=>$auditId];
    } catch (Exception $e) { $db->rollBack(); throw $e; }
}

function b06_get_measurements(PDO $db, ?string $type=null, ?string $session=null, ?int $limit=100): array {
    $sql = "SELECT * FROM measurements WHERE 1=1"; $p = [];
    if ($type) { $sql.=" AND measurement_type=:t"; $p[':t']=$type; }
    if ($session) { $sql.=" AND session_reference=:s"; $p[':s']=$session; }
    $sql.=" ORDER BY measured_at DESC LIMIT :l";
    $stmt = $db->prepare($sql);
    foreach($p as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':l',(int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
