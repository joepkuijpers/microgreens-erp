-- trays: kolomset exact uit b02_germination.php
CREATE TABLE IF NOT EXISTS trays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tray_code TEXT NOT NULL UNIQUE,
    rack_id TEXT,
    status TEXT NOT NULL DEFAULT 'EMPTY'
);

-- germination_records: kolomset exact uit de INSERT in b02_germination.php
CREATE TABLE IF NOT EXISTS germination_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seed_inventory_id INTEGER,
    tray_id INTEGER,
    production_batch_id INTEGER,
    quantity_seeds REAL,
    start_date TEXT,
    expected_harvest_date TEXT,
    status TEXT NOT NULL DEFAULT 'GERMINATING',
    operator TEXT,
    notes TEXT
);

-- crop_spatial_rules: kolommen uit b01_allocation_engine.php + de CHECK-list
-- was afgebroken in je grep, dus die laten we weg (geen gok).
CREATE TABLE IF NOT EXISTS crop_spatial_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crop_profile_id_a INTEGER NOT NULL,
    crop_profile_id_b INTEGER NOT NULL,
    relationship_type TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1
);