BEGIN IMMEDIATE;

UPDATE suppliers
SET name = trim(name);

CREATE UNIQUE INDEX IF NOT EXISTS
idx_suppliers_normalised_name_unique
ON suppliers(lower(trim(name)));

CREATE TRIGGER IF NOT EXISTS
trg_suppliers_name_not_blank_insert
BEFORE INSERT ON suppliers
FOR EACH ROW
WHEN trim(NEW.name) = ''
BEGIN
    SELECT RAISE(ABORT, 'Supplier name must not be blank.');
END;

CREATE TRIGGER IF NOT EXISTS
trg_suppliers_name_not_blank_update
BEFORE UPDATE OF name ON suppliers
FOR EACH ROW
WHEN trim(NEW.name) = ''
BEGIN
    SELECT RAISE(ABORT, 'Supplier name must not be blank.');
END;

COMMIT;
