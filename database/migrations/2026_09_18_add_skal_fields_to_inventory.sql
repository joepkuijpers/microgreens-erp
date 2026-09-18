-- Migratie: Skal Certificering eisen voor Inventory
-- Datum: 2026-09-18
-- Doel: Traceerbaarheid van zaadpartijen (Lot) en biologische status

-- 1. Lot Number (Verplicht voor traceerbaarheid Batch <-> Zaad)
ALTER TABLE inventory ADD COLUMN lot_number TEXT DEFAULT NULL;

-- 2. Expiration Date (Houdbaarheid zaden)
ALTER TABLE inventory ADD COLUMN expiration_date DATE DEFAULT NULL;

-- 3. Organic Certified (Skal eis: is dit input toegestaan?)
-- 1 = Ja (Biologisch), 0 = Nee (Conventioneel/Ontheffing)
ALTER TABLE inventory ADD COLUMN organic_certified INTEGER DEFAULT 1 CHECK (organic_certified IN (0, 1));

-- 4. Supplier Batch Ref (Extra referentie naar leverancier)
ALTER TABLE inventory ADD COLUMN supplier_batch_ref TEXT DEFAULT NULL;

-- Indexes voor snelle searches op lot en houdbaarheid
CREATE INDEX IF NOT EXISTS idx_inventory_lot ON inventory(lot_number);
CREATE INDEX IF NOT EXISTS idx_inventory_expiration ON inventory(expiration_date);
CREATE INDEX IF NOT EXISTS idx_inventory_organic ON inventory(organic_certified);
