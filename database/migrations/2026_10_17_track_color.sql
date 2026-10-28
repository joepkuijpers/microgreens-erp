-- Visuele volgkleur op batch + output-tak (puur label, geen compliance-signaal)
ALTER TABLE production_batches ADD COLUMN track_color TEXT;   -- hoofdkleur, blijft bij de batch
ALTER TABLE production_outputs ADD COLUMN track_color TEXT;   -- sub-kleur, alleen bij opsplitsing
