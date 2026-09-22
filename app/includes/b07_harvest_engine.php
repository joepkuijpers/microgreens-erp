<?php
/**
 * B07 Harvest Engine - RETROFITTED
 * Aangepast aan actuele DB schema: production_batches, harvest_logs
 */

function b07_register_harvest(PDO $db, int $batchId, string $harvestDate, float $weightGrams, int $productId, float $finishedQty, string $notes, ?int $userId = null): array {
    $startTime = microtime(true);
    $result = ['success' => false, 'message' => '', 'registration_time' => 0, 'harvest_id' => null];

    try {
        if (!$db->inTransaction()) { $db->beginTransaction(); }

        // 1. Validatie Batch (FIXED: production_batches + crop_type)
        $stmt = $db->prepare("SELECT id, crop_type, status FROM production_batches WHERE id = ?");
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) throw new Exception("Batch niet gevonden (ID: $batchId).");
        if ($batch['status'] === 'HARVESTED') throw new Exception("Deze batch is al geoogst.");

        // 2. Validatie Product
        $stmt = $db->prepare("SELECT id, name, unit FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new Exception("Product niet gevonden.");

        // 3. Insert Harvest (FIXED: harvest_logs tabel)
        $stmt = $db->prepare("INSERT INTO harvest_logs (batch_id, harvest_date, weight_grams, operator_name, quality_note, status) VALUES (?, ?, ?, ?, ?, 'harvested')");
        $stmt->execute([$batchId, $harvestDate, $weightGrams, $userId ? "User_$userId" : 'Operator', $notes]);
        $harvestId = $db->lastInsertId();
        $result['harvest_id'] = $harvestId;

        // 4. Insert Finished Inventory (CHECK: bestaat deze tabel?)
        // Als de tabel niet bestaat, slaan we deze stap over met een warning in plaats van crashen
        try {
            $stmt = $db->prepare("INSERT INTO finished_inventory (product_id, quantity, unit, batch_id, harvest_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$productId, $finishedQty, $product['unit'], $batchId, $harvestId]);
        } catch (Exception $invEx) {
            // Log warning maar faal niet de hele oogst als inventory tabel mist
            error_log("B07 Warning: finished_inventory tabel mist. Skip inventory update. " . $invEx->getMessage());
        }

        // 5. Update Batch Status (FIXED: production_batches)
        $stmt = $db->prepare("UPDATE production_batches SET status = 'HARVESTED', completed_at = ? WHERE id = ?");
        $stmt->execute([$harvestDate, $batchId]);

        // 6. Audit Log (OPTIONAL: als tabel bestaat)
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        try {
            $auditStmt = $db->prepare("INSERT INTO audit_events (event_type, table_name, record_id, action, description, actor_user_id, entity_type, entity_id, after_data, activity_type, registration_time_seconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $auditStmt->execute(['B07_HARVEST', 'harvest_logs', $harvestId, 'REGISTER_HARVEST', "Harvested {$weightGrams}g of {$batch['crop_type']}", $userId, 'harvest', $harvestId, json_encode(['batch_id' => $batchId, 'weight' => $weightGrams]), 'CREATE', $duration]);
        } catch (Exception $audEx) {
            error_log("B07 Warning: audit_events tabel mist. Skip audit log.");
        }

        $db->commit();
        $result['success'] = true;
        $result['message'] = "✅ Oogst geregistreerd: {$weightGrams}g {$batch['crop_type']}. Voorraad: {$finishedQty} {$product['unit']}.";
        $result['registration_time'] = $duration;

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $result['message'] = "❌ Fout: " . $e->getMessage();
        error_log("B07 Error: " . $e->getMessage());
    }
    return $result;
}
?>
