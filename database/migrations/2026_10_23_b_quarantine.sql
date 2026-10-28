-- Quarantaine: statuswaarde + reden op verpakte bakjes (geen nieuwe tabel, geen dataverlies)
ALTER TABLE packaging_units ADD COLUMN quarantine_reason TEXT;
