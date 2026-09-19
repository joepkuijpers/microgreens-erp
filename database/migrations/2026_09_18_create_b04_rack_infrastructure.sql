-- B04: Rack & Spatial Allocation Infrastructure
-- Nodig voor fysieke locatie tracking van batches

-- 1. Physical Units (De fysieke bakken/trays)
CREATE TABLE IF NOT EXISTS physical_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER,
    unit_code TEXT NOT NULL UNIQUE,
    container_type TEXT DEFAULT '1020',
    status TEXT DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES grow_batches(id) ON DELETE SET NULL
);

-- 2. Spatial Allocations (Koppeling van Units aan Rack Posities)
CREATE TABLE IF NOT EXISTS spatial_allocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    physical_unit_id INTEGER NOT NULL,
    batch_id INTEGER NOT NULL,
    rack_id TEXT DEFAULT 'RACK-A',
    rack_position INTEGER DEFAULT NULL,
    allocation_type TEXT DEFAULT 'CROP',
    status TEXT DEFAULT 'ACTIVE',
    area_fraction REAL DEFAULT 1.0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (physical_unit_id) REFERENCES physical_units(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES grow_batches(id) ON DELETE CASCADE
);

-- 3. Indexes voor snelle zoekopdrachten
CREATE INDEX IF NOT EXISTS idx_spatial_rack ON spatial_allocations(rack_id, rack_position);
CREATE INDEX IF NOT EXISTS idx_spatial_batch ON spatial_allocations(batch_id);
CREATE INDEX IF NOT EXISTS idx_physical_batch ON physical_units(batch_id);
