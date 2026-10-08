-- B05/B14: water- en irrigatieregistratie krijgt locatiekoppeling (rack_id).
-- NULLable: bestaande rijen blijven intact. zone_name blijft als vrije tekst.
-- Alleen toevoegen; geen bestaande kolommen gewijzigd (historische integriteit).
ALTER TABLE water_measurements ADD COLUMN rack_id TEXT DEFAULT NULL;
ALTER TABLE irrigation_logs    ADD COLUMN rack_id TEXT DEFAULT NULL;
