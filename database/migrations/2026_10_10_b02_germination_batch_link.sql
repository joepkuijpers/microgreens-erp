-- B02: kiemactie koppelen aan centrale productiebatch (traceability B01->B02)
-- NULLable: bestaande records intact.
ALTER TABLE germination_records ADD COLUMN production_batch_id INTEGER DEFAULT NULL;
ALTER TABLE germination_records ADD COLUMN notes TEXT DEFAULT NULL;
