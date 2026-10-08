ALTER TABLE production_batches ADD COLUMN rack_position INTEGER DEFAULT NULL;
ALTER TABLE production_batches ADD COLUMN rack_id TEXT DEFAULT 'RACK-A';
CREATE INDEX IF NOT EXISTS idx_rack_position ON production_batches(rack_position);