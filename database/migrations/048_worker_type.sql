-- 048: worker_type op labor_entries (medewerker / stage / vrijwilliger)
ALTER TABLE labor_entries ADD COLUMN worker_type TEXT NOT NULL DEFAULT 'medewerker';