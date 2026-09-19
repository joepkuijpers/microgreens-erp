CREATE TABLE IF NOT EXISTS rack_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rack_code TEXT NOT NULL,
    shelf_number INTEGER NOT NULL,
    tray_position INTEGER NOT NULL,
    tray_type TEXT DEFAULT '1020',
    is_active INTEGER DEFAULT 1,
    notes TEXT,
    UNIQUE(rack_code, shelf_number, tray_position)
);

CREATE TABLE IF NOT EXISTS tray_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL,
    rack_location_id INTEGER NOT NULL,
    assigned_at TEXT DEFAULT CURRENT_TIMESTAMP,
    released_at TEXT,
    status TEXT DEFAULT 'occupied',
    notes TEXT,
    UNIQUE(batch_id, rack_location_id)
);

WITH RECURSIVE
shelves(n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM shelves WHERE n < 6
),
positions(p) AS (
    SELECT 1
    UNION ALL
    SELECT p + 1 FROM positions WHERE p < 4
)
INSERT OR IGNORE INTO rack_locations
(rack_code, shelf_number, tray_position, tray_type, is_active, notes)
SELECT
    'RACK-A',
    shelves.n,
    positions.p,
    '1020',
    1,
    'Standaard microgreens rack'
FROM shelves
CROSS JOIN positions;
