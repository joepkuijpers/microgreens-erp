PRAGMA foreign_keys = OFF;

-- Nieuwe tabel met verruimde statuslijst, alle 18 kolommen behouden.
CREATE TABLE production_batches_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_code TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL CHECK (
        status IN ('PLANNED','STARTED','SOWN','GERMINATING','GROWING',
                   'READY_TO_HARVEST','HARVESTED','FREEZE_DRIED','COMPLETED','CANCELLED')
    ),
    planned_quantity REAL CHECK (planned_quantity IS NULL OR planned_quantity >= 0),
    actual_quantity REAL CHECK (actual_quantity IS NULL OR actual_quantity >= 0),
    quantity_unit TEXT CHECK (quantity_unit IS NULL OR length(trim(quantity_unit)) > 0),
    started_at TEXT,
    completed_at TEXT,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    crop_type TEXT,
    rack_id TEXT,
    rack_position TEXT,
    track_color TEXT,
    harvest_date TEXT,
    expected_harvest_date TEXT,
    registration_time_seconds REAL,
    planned_sow_date DATE
);

-- Data overzetten (alle 18 kolommen expliciet).
INSERT INTO production_batches_new (
    id, batch_code, status, planned_quantity, actual_quantity, quantity_unit,
    started_at, completed_at, notes, created_at, crop_type, rack_id,
    rack_position, track_color, harvest_date, expected_harvest_date,
    registration_time_seconds, planned_sow_date
)
SELECT
    id, batch_code, status, planned_quantity, actual_quantity, quantity_unit,
    started_at, completed_at, notes, created_at, crop_type, rack_id,
    rack_position, track_color, harvest_date, expected_harvest_date,
    registration_time_seconds, planned_sow_date
FROM production_batches;

DROP TABLE production_batches;
ALTER TABLE production_batches_new RENAME TO production_batches;

PRAGMA foreign_keys = ON;
PRAGMA foreign_key_check;