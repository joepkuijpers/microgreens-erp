-- B03/B06: klimaatmeting krijgt locatiekoppeling (rack_id).
-- NULLable: de 49 bestaande rijen blijven intact, rack_id staat NULL.
-- Alleen toevoegen; geen bestaande kolommen gewijzigd (historische integriteit).
ALTER TABLE climate_data ADD COLUMN rack_id TEXT DEFAULT NULL;
