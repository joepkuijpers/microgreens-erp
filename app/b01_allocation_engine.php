<?php
/**
 * B01 Allocation Engine (v2.0 - Time & Activity Aware)
 * Volgt de "Time, Activity & Registration Architecture"
 */

require_once __DIR__ . '/includes/audit.php';

class B01AllocationEngine {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createBatchWithAllocation(array $data): array {
        $this->validateInput($data);
        $startTime = microtime(true); // START TIMER (Registration Time)
        
        $batchId = null;
        $allocationId = null;
        $startedTransaction = false;

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            $inventoryData = $this->checkInventory($data['inventory_id'], $data['seed_amount']);
            $this->checkCropCompatibility($data['crop_profile_id'] ?? null, $data['physical_unit_id'] ?? null);
            $batchId = $this->insertBatch($data);
            $this->updateInventory($data['inventory_id'], $data['seed_amount'], $batchId, $inventoryData);
            $allocationId = $this->allocateSpatialCells($batchId, $data);

            $endTime = microtime(true); // STOP TIMER
            $registrationTimeSeconds = $endTime - $startTime;

            $this->logAllocationEvent($batchId, $allocationId, $data, $registrationTimeSeconds);
            $this->saveRegistrationTime($batchId, $registrationTimeSeconds);

            if ($startedTransaction) { $this->db->commit(); }

            return [
                'success' => true,
                'batch_id' => $batchId,
                'allocation_id' => $allocationId,
                'registration_time_seconds' => round($registrationTimeSeconds, 3),
                'message' => 'Batch succesvol aangemaakt in ' . round($registrationTimeSeconds, 2) . 's.'
            ];

        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    private function validateInput(array $data): void {
        if (empty($data['crop']) || empty($data['sow_date'])) {
            throw new InvalidArgumentException("Gewas en zaaidatum zijn verplicht.");
        }
        if (($data['seed_amount'] ?? 0) <= 0) {
            throw new InvalidArgumentException("Zaadhoeveelheid moet groter zijn dan 0.");
        }
    }

    private function checkInventory(int $inventoryId, float $amount): array {
        $stmt = $this->db->prepare("SELECT id, item_name, quantity, unit FROM inventory WHERE id = :id");
        $stmt->execute([':id' => $inventoryId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) { throw new RuntimeException("Zaadinventaris niet gevonden."); }
        if ($item['quantity'] < $amount) {
            throw new RuntimeException("Onvoldoende voorraad: {$item['quantity']} {$item['unit']} beschikbaar, {$amount} nodig.");
        }
        return $item;
    }

    private function checkCropCompatibility(?int $cropProfileId, ?int $physicalUnitId): void {
        if (!$cropProfileId || !$physicalUnitId) return;
        $sql = "SELECT cr.relationship_type, cp.name as neighbor_crop FROM spatial_allocations sa JOIN grow_batches gb ON sa.batch_id = gb.id JOIN crop_profiles cp ON gb.crop_profile_id = cp.id JOIN crop_spatial_rules cr ON (cr.crop_profile_id_a = :crop_a AND cr.crop_profile_id_b = cp.id) OR (cr.crop_profile_id_b = :crop_b AND cr.crop_profile_id_a = cp.id) WHERE sa.physical_unit_id = :unit_id AND sa.status IN ('ACTIVE', 'PROPOSED') AND cr.active = 1 AND cr.relationship_type IN ('SEPARATION_REQUIRED', 'SEPARATION_RECOMMENDED')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':crop_a' => $cropProfileId, ':crop_b' => $cropProfileId, ':unit_id' => $physicalUnitId]);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($conflicts)) {
            $names = array_column($conflicts, 'neighbor_crop');
            throw new RuntimeException("Compatibiliteitsfout: Kan niet naast " . implode(', ', $names) . " staan.");
        }
    }

    private function insertBatch(array $data): int {
        $sql = "INSERT INTO grow_batches (crop, crop_profile_id, sow_date, expected_harvest_date, tray_count, tray_type, status, created_at) VALUES (:crop, :crop_profile_id, :sow_date, :expected_harvest_date, :tray_count, :tray_type, :status, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([ ':crop' => $data['crop'], ':crop_profile_id' => $data['crop_profile_id'] ?? null, ':sow_date' => $data['sow_date'], ':expected_harvest_date' => $data['expected_harvest_date'], ':tray_count' => $data['tray_count'], ':tray_type' => $data['tray_type'] ?? '1020', ':status' => $data['status'] ?? 'Groeiend' ]);
        return (int)$this->db->lastInsertId();
    }

    private function updateInventory(int $inventoryId, float $amount, int $batchId, array $itemData): void {
        $newQty = $itemData['quantity'] - $amount;
        $this->db->prepare("UPDATE inventory SET quantity = :q WHERE id = :i")->execute([':q' => $newQty, ':i' => $inventoryId]);
        $this->db->prepare("INSERT INTO inventory_transactions (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id) VALUES (:inv, 'VERBRUIK', :ch, :bef, :aft, :u, :n, 'grow_batch', :bid)")
            ->execute([':inv' => $inventoryId, ':ch' => -$amount, ':bef' => $itemData['quantity'], ':aft' => $newQty, ':u' => $itemData['unit'], ':n' => "Zaadgebruik batch", ':bid' => $batchId]);
    }

    private function allocateSpatialCells(int $batchId, array $data): int {
        $unitId = $data['physical_unit_id'] ?? 1;
        $sql = "INSERT INTO spatial_allocations (physical_unit_id, batch_id, allocation_type, status, area_fraction, created_at) VALUES (:uid, :bid, 'CROP', 'ACTIVE', 1.0, CURRENT_TIMESTAMP)";
        $this->db->prepare($sql)->execute([':uid' => $unitId, ':bid' => $batchId]);
        return (int)$this->db->lastInsertId();
    }

    private function logAllocationEvent(int $batchId, int $allocationId, array $data, float $timeSeconds): void {
        auditLog($this->db, 'B01_BATCH_CREATED', 'grow_batches', $batchId, 'CREATION_AND_ALLOCATION', "Batch aangemaakt in {$timeSeconds}s", null, ['batch_id' => $batchId, 'allocation_id' => $allocationId, 'crop' => $data['crop'], 'seed_amount' => $data['seed_amount'], 'registration_time_seconds' => $timeSeconds, 'activity_type' => 'ATTRIBUTABLE_WORK']);
    }

    private function saveRegistrationTime(int $batchId, float $seconds): void {
        $stmt = $this->db->prepare("UPDATE grow_batches SET registration_time_seconds = :t WHERE id = :i");
        $stmt->execute([':t' => $seconds, ':i' => $batchId]);
    }
}
