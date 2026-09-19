<?php

function b07_register_harvest(PDO $db, int $batchId, string $harvestDate, float $weightGrams, int $productId, float $finishedQty, string $notes, ?int $userId = null): array {
    $startTime = microtime(true);
    $result = ['success' => false, 'message' => '', 'registration_time' => 0, 'harvest_id' => null];

    try {
        if (!$db->inTransaction()) { $db->beginTransaction(); }

        // 1. Validatie Batch
        $stmt = $db->prepare("SELECT id, crop, status FROM grow_batches WHERE id = ?");
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) throw new Exception("Batch niet gevonden.");
        if ($batch['status'] === 'Geoogst') throw new Exception("Deze batch is al geoogst.");

        // 2. Validatie Product
        $stmt = $db->prepare("SELECT id, name, unit FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new Exception("Product niet gevonden.");

        // 3. Insert Harvest
        $stmt = $db->prepare("INSERT INTO harvests (batch_id, harvest_date, weight_grams, quality_notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$batchId, $harvestDate, $weightGrams, $notes]);
        $harvestId = $db->lastInsertId();
        $result['harvest_id'] = $harvestId;

        // 4. Insert Finished Inventory
        $stmt = $db->prepare("INSERT INTO finished_inventory (product_id, quantity, unit, batch_id, harvest_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$productId, $finishedQty, $product['unit'], $batchId, $harvestId]);

        // 5. Update Batch Status
        $stmt = $db->prepare("UPDATE grow_batches SET status = 'Geoogst', harvest_date = ? WHERE id = ?");
        $stmt->execute([$harvestDate, $batchId]);

        // 6. Audit Log
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $auditStmt = $db->prepare("INSERT INTO audit_events (event_type, table_name, record_id, action, description, actor_user_id, entity_type, entity_id, after_data, activity_type, registration_time_seconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $auditStmt->execute(['B07_HARVEST', 'harvests', $harvestId, 'REGISTER_HARVEST', "Harvested {$weightGrams}g of {$batch['crop']}", $userId, 'harvest', $harvestId, json_encode(['batch_id' => $batchId, 'weight' => $weightGrams]), 'CREATE', $duration]);

        $db->commit();
        $result['success'] = true;
        $result['message'] = "✅ Oogst geregistreerd: {$weightGrams}g {$batch['crop']}. Voorraad: {$finishedQty} {$product['unit']}.";
        $result['registration_time'] = $duration;

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $result['message'] = "❌ Fout: " . $e->getMessage();
    }
    return $result;
}
