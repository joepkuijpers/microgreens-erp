<?php
require_once __DIR__ . '/audit.php';
const TASK_CLEAN_INTERVAL_DAYS = 7;
function taskGenerateFromCertificates($db) {
    $created = 0;
    $sql = "SELECT id, name, certificate_expiry FROM suppliers WHERE certificate_expiry IS NOT NULL AND certificate_expiry != '' AND date(certificate_expiry) <= date('now', '+30 days')";
    $res = $db->query($sql);
    if ($res) {
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $sid = (int)$row['id'];
            $chk = $db->prepare("SELECT COUNT(*) FROM tasks WHERE source_type='CERTIFICATE' AND source_id=:sid AND status IN ('OPEN','IN_PROGRESS')");
            $chk->bindValue(':sid', $sid, PDO::PARAM_INT);
            $chk->execute();
            if ((int)$chk->fetchColumn() > 0) { continue; }
            $expired = strtotime($row['certificate_expiry']) < strtotime(date('Y-m-d'));
            $prio = $expired ? 'CRITICAL' : 'HIGH';
            $title = 'Certificaat vernieuwen: ' . $row['name'];
            $due = $expired ? date('Y-m-d') : date('Y-m-d', strtotime('+7 days'));
            $stmt = $db->prepare("INSERT INTO tasks (title, description, category, priority, status, due_date, source_type, source_id) VALUES (:t, :d, 'CERTIFICATE', :p, 'OPEN', :due, 'CERTIFICATE', :sid)");
            $stmt->bindValue(':t', $title, PDO::PARAM_STR);
            $stmt->bindValue(':d', 'Biologisch certificaat ' . ($expired ? 'VERLOPEN' : 'verloopt binnenkort') . ' op ' . $row['certificate_expiry'], PDO::PARAM_STR);
            $stmt->bindValue(':p', $prio, PDO::PARAM_STR);
            $stmt->bindValue(':due', $due, PDO::PARAM_STR);
            $stmt->bindValue(':sid', $sid, PDO::PARAM_INT);
            $stmt->execute();
            $created++;
            if ($expired) {
                $aex = $db->prepare("SELECT COUNT(*) FROM system_alerts WHERE alert_type='EXPIRING_CERT' AND related_id=:rid AND resolved=0");
                $aex->bindValue(':rid', $sid, PDO::PARAM_INT);
                $aex->execute();
                if ((int)$aex->fetchColumn() == 0) {
                    $ast = $db->prepare("INSERT INTO system_alerts (alert_type, severity, message, related_id) VALUES ('EXPIRING_CERT', 'CRITICAL', :m, :rid)");
                    $ast->bindValue(':m', 'Certificaat verlopen: ' . $row['name'], PDO::PARAM_STR);
                    $ast->bindValue(':rid', $sid, PDO::PARAM_INT);
                    $ast->execute();
                }
            }
        }
    }
    return $created;
}
function taskGenerateFromCleaning($db) {
    $created = 0;
    $sql = "SELECT object_name, object_type, MAX(cleaned_at) AS last_clean FROM cleaning_logs GROUP BY object_name HAVING julianday('now') - julianday(last_clean) > " . TASK_CLEAN_INTERVAL_DAYS;
    $res = $db->query($sql);
    if ($res) {
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $obj = $row['object_name'];
            $chk = $db->prepare("SELECT COUNT(*) FROM tasks WHERE source_type='CLEANING' AND title LIKE :pat AND status IN ('OPEN','IN_PROGRESS')");
            $chk->bindValue(':pat', 'Reiniging: ' . $obj . '%', PDO::PARAM_STR);
            $chk->execute();
            if ((int)$chk->fetchColumn() > 0) { continue; }
            $title = 'Reiniging: ' . $obj;
            $days = (int)floor((strtotime('now') - strtotime($row['last_clean'])) / 86400);
            $stmt = $db->prepare("INSERT INTO tasks (title, description, category, priority, status, due_date, source_type, source_id) VALUES (:t, :d, 'CLEANING', 'NORMAL', 'OPEN', date('now'), 'CLEANING', NULL)");
            $stmt->bindValue(':t', $title, PDO::PARAM_STR);
            $stmt->bindValue(':d', 'Laatste reiniging ' . $days . ' dagen geleden (interval ' . TASK_CLEAN_INTERVAL_DAYS . ' dgn)', PDO::PARAM_STR);
            $stmt->execute();
            $created++;
        }
    }
    return $created;
}
function taskGenerateAll($db) {
    $c = taskGenerateFromCertificates($db);
    $k = taskGenerateFromCleaning($db);
    return ['certificates' => $c, 'cleaning' => $k, 'total' => $c + $k];
}
