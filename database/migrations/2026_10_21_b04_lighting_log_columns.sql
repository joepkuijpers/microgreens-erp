-- B04: lighting_log + rack_id + source (NULLable, geen dataverlies)
ALTER TABLE lighting_log ADD COLUMN rack_id TEXT DEFAULT NULL;
ALTER TABLE lighting_log ADD COLUMN source TEXT DEFAULT 'MANUAL';
