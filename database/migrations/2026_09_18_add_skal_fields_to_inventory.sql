ALTER TABLE inventory ADD COLUMN lot_number TEXT DEFAULT NULL;
ALTER TABLE inventory ADD COLUMN expiration_date DATE DEFAULT NULL;
ALTER TABLE inventory ADD COLUMN organic_certified INTEGER DEFAULT 1 CHECK (organic_certified IN (0, 1));
ALTER TABLE inventory ADD COLUMN supplier_batch_ref TEXT DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_inventory_lot ON inventory(lot_number);
CREATE INDEX IF NOT EXISTS idx_inventory_expiration ON inventory(expiration_date);
CREATE INDEX IF NOT EXISTS idx_inventory_organic ON inventory(organic_certified);
