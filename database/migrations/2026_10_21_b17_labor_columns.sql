-- B17 Personeel: operator-identiteit + centrale batch-link (NULLable, geen dataverlies)
-- operator_name: actor uit auth_current_user() (audit-spoor, Master Prompt Deel II)
-- production_batch_id: koppeling aan centrale productie-identiteit (productie_batches)
ALTER TABLE labor_entries ADD COLUMN operator_name TEXT;
ALTER TABLE labor_entries ADD COLUMN production_batch_id INTEGER;
CREATE INDEX IF NOT EXISTS idx_labor_operator ON labor_entries(operator_name);
CREATE INDEX IF NOT EXISTS idx_labor_prod_batch ON labor_entries(production_batch_id);
