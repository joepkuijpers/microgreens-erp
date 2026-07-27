BEGIN IMMEDIATE;

ALTER TABLE inventory
ADD COLUMN supplier_id INTEGER
REFERENCES suppliers(id)
ON UPDATE CASCADE
ON DELETE SET NULL;

CREATE INDEX idx_inventory_supplier_id
ON inventory(supplier_id);

COMMIT;
