<?php
require_once __DIR__ . '/audit.php';

/**
 * B02: Koppel een batch aan trays op een specifieke locatie.
 * Doel: < 30 seconden per actie.
 */
function b02_assign_trays(PDO $db, int $batchId, int $locationId, int $trayCount, ?int $userId = null): array {
    $startTime = microtime(true);
    $result = ['success' => false, 'message' => '', 'registration_time' => 0];

    try {
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }

        // 1. Validatie: Bestaat de batch?
        $stmt = $db->prepare("SELECT id, crop, status FROM grow_batches WHERE id = ?");
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$batch) {
            throw new Exception("Batch niet gevonden.");
        }

        // 2. Validatie: Bestaat de locatie?
        $stmt = $db->prepare("SELECT rack_code, shelf_number, tray_position FROM rack_locations WHERE id = ? AND is_active = 1");
        $stmt->execute([$locationId]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            throw new Exception("Locatie niet gevonden of inactief.");
        }

        // 3. Validatie: Is de locatie al bezet?
        $stmt = $db->prepare("SELECT id FROM tray_assignments WHERE rack_location_id = ? AND status = 'occupied'");
        $stmt->execute([$locationId]);
        if ($stmt->fetch()) {
            throw new Exception("Deze locatie is al bezet.");
        }

        // 4. Insert: Maak de koppeling
        $stmt = $db->prepare("INSERT INTO tray_assignments (batch_id, rack_location_id, tray_count, status, notes) VALUES (?, ?, ?, 'occupied', 'Assigned via B02 Engine')");
        $stmt->execute([$batchId, $locationId, $trayCount]);
        $assignmentId = $db->lastInsertId();

        // 5. Audit: Log de actie
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Bereid data voor voor audit_events
        // Volgorde kolommen: action, event_type, table_name, record_id, action(dup?), description, actor, entity_type, entity_id, reason, before, after, ref_type, ref_id, details, activity_type, reg_time
        // We vullen alleen de verplichte en relevante velden in.
        
        $auditAction = 'TRAY_ASSIGNMENT';
        $auditEventType = 'B02_ASSIGNMENT'; // Vult de NOT NULL event_type
        $auditTableName = 'tray_assignments';
        $auditRecordId = $assignmentId;
        $auditDescription = "Tray assignment created for batch {$batchId} at location {$locationId}";
        $auditEntityType = 'tray_assignment';
        $auditEntityId = $assignmentId;
        $auditAfterData = json_encode(['batch_id' => $batchId, 'location_id' => $locationId, 'tray_count' => $trayCount]);
        $auditActivityType = 'CREATE';

        // Insert in audit_events met de correcte volgorde en waarden
        $auditStmt = $db->prepare("
            INSERT INTO audit_events (
                event_type, table_name, record_id, action, description, actor_user_id, 
                entity_type, entity_id, before_data, after_data, activity_type, registration_time_seconds
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $auditStmt->execute([
            $auditEventType,      // event_type (NOT NULL)
            $auditTableName,      // table_name
            $auditRecordId,       // record_id
            $auditAction,         // action
            $auditDescription,    // description
            $userId,              // actor_user_id
            $auditEntityType,     // entity_type
            $auditEntityId,       // entity_id
            null,                 // before_data
            $auditAfterData,      // after_data
            $auditActivityType,   // activity_type
            $duration             // registration_time_seconds
        ]);

        $db->commit();
        $result['success'] = true;
        $result['message'] = "✅ {$trayCount} tray(s) gekoppeld aan {$batch['crop']} op locatie {$location['rack_code']}-{$location['shelf_number']}";
        $result['registration_time'] = $duration;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $result['message'] = "❌ Fout: " . $e->getMessage();
    }

    return $result;
}
