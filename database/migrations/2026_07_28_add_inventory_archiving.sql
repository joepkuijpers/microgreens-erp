BEGIN IMMEDIATE;

ALTER TABLE inventory
ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1
CHECK (is_active IN (0, 1));

CREATE INDEX idx_inventory_is_active
ON inventory(is_active);

COMMIT;
