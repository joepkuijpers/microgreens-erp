-- B15: inventory + composition (Verklaring van Samenstelling, NULLable)
ALTER TABLE inventory ADD COLUMN composition TEXT DEFAULT NULL;
