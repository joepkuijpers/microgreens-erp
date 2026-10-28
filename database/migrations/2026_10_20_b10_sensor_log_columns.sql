-- B10: sensor_log + rack_id + source (NULLable, geen dataverlies)
ALTER TABLE sensor_log ADD COLUMN rack_id TEXT DEFAULT NULL;
ALTER TABLE sensor_log ADD COLUMN source TEXT DEFAULT 'MANUAL';
