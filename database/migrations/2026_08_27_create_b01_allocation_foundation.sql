BEGIN IMMEDIATE;

-- ============================================================
-- B01 ALLOCATION FOUNDATION
-- Physical units, grids, allocations and substrate traceability
-- ============================================================

-- ------------------------------------------------------------
-- 1. Physical production units
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS physical_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    batch_id INTEGER NOT NULL,

    unit_code TEXT NOT NULL UNIQUE,

    container_type TEXT NOT NULL,
    container_size TEXT,

    length_mm REAL,
    width_mm REAL,
    depth_mm REAL,

    status TEXT NOT NULL DEFAULT 'PLANNED'
        CHECK (
            status IN (
                'PLANNED',
                'ACTIVE',
                'COMPLETED',
                'CLOSED',
                'CANCELLED'
            )
        ),

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,

    FOREIGN KEY (batch_id)
        REFERENCES grow_batches(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_physical_units_batch
ON physical_units(batch_id);


-- ------------------------------------------------------------
-- 2. Grid definition
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS unit_grids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    physical_unit_id INTEGER NOT NULL UNIQUE,

    rows INTEGER NOT NULL CHECK (rows > 0),
    columns INTEGER NOT NULL CHECK (columns > 0),

    total_units INTEGER NOT NULL
        CHECK (total_units = rows * columns),

    reference_width_mm REAL,
    reference_height_mm REAL,

    unit_width_mm REAL,
    unit_height_mm REAL,

    grid_type TEXT NOT NULL DEFAULT 'RECTANGULAR',

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (physical_unit_id)
        REFERENCES physical_units(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_unit_grids_unit
ON unit_grids(physical_unit_id);


-- ------------------------------------------------------------
-- 3. Individual grid cells
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS allocation_cells (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    unit_grid_id INTEGER NOT NULL,

    row_number INTEGER NOT NULL CHECK (row_number > 0),
    column_number INTEGER NOT NULL CHECK (column_number > 0),

    physical_area_fraction REAL
        CHECK (
            physical_area_fraction IS NULL
            OR (
                physical_area_fraction > 0
                AND physical_area_fraction <= 1
            )
        ),

    status TEXT NOT NULL DEFAULT 'UNASSIGNED'
        CHECK (
            status IN (
                'UNASSIGNED',
                'ALLOCATED',
                'BUFFER',
                'EMPTY',
                'BLOCKED'
            )
        ),

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (unit_grid_id)
        REFERENCES unit_grids(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE (unit_grid_id, row_number, column_number)
);

CREATE INDEX IF NOT EXISTS idx_allocation_cells_grid
ON allocation_cells(unit_grid_id);


-- ------------------------------------------------------------
-- 4. Spatial allocations
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS spatial_allocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    physical_unit_id INTEGER NOT NULL,
    batch_id INTEGER NOT NULL,

    allocation_type TEXT NOT NULL
        CHECK (
            allocation_type IN (
                'CROP',
                'BUFFER',
                'EMPTY'
            )
        ),

    status TEXT NOT NULL DEFAULT 'DRAFT'
        CHECK (
            status IN (
                'DRAFT',
                'PROPOSED',
                'CONFIRMED',
                'ACTIVE',
                'COMPLETED',
                'CANCELLED'
            )
        ),

    crop_profile_id INTEGER,

    variety_name TEXT,

    substrate_instance_id INTEGER,

    -- Normalized representation
    area_fraction REAL
        CHECK (
            area_fraction IS NULL
            OR (
                area_fraction >= 0
                AND area_fraction <= 1
            )
        ),

    area_units REAL
        CHECK (
            area_units IS NULL
            OR area_units >= 0
        ),

    -- Original input representation
    input_mode TEXT
        CHECK (
            input_mode IS NULL
            OR input_mode IN (
                'PERCENTAGE',
                'GRID_UNITS',
                'RATIO',
                'AREA',
                'AUTOMATED'
            )
        ),

    input_numerator REAL,
    input_denominator REAL,

    buffer_type TEXT,
    buffer_reason TEXT,

    related_allocation_id INTEGER,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TEXT,

    FOREIGN KEY (physical_unit_id)
        REFERENCES physical_units(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (batch_id)
        REFERENCES grow_batches(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (crop_profile_id)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (substrate_instance_id)
        REFERENCES substrate_instances(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (related_allocation_id)
        REFERENCES spatial_allocations(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CHECK (
        allocation_type != 'CROP'
        OR crop_profile_id IS NOT NULL
    ),

    CHECK (
        allocation_type = 'CROP'
        OR substrate_instance_id IS NULL
    )
);

CREATE INDEX IF NOT EXISTS idx_spatial_allocations_unit
ON spatial_allocations(physical_unit_id);

CREATE INDEX IF NOT EXISTS idx_spatial_allocations_batch
ON spatial_allocations(batch_id);

CREATE INDEX IF NOT EXISTS idx_spatial_allocations_crop
ON spatial_allocations(crop_profile_id);

CREATE INDEX IF NOT EXISTS idx_spatial_allocations_substrate
ON spatial_allocations(substrate_instance_id);


-- ------------------------------------------------------------
-- 5. Allocation ? grid cells
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS allocation_cell_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    allocation_id INTEGER NOT NULL,
    allocation_cell_id INTEGER NOT NULL,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (allocation_id)
        REFERENCES spatial_allocations(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (allocation_cell_id)
        REFERENCES allocation_cells(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE (allocation_id, allocation_cell_id),

    UNIQUE (allocation_cell_id)
);

CREATE INDEX IF NOT EXISTS idx_cell_assignments_allocation
ON allocation_cell_assignments(allocation_id);

CREATE INDEX IF NOT EXISTS idx_cell_assignments_cell
ON allocation_cell_assignments(allocation_cell_id);


-- ------------------------------------------------------------
-- 6. Substrate profiles
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS substrate_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    profile_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,

    composition TEXT,

    water_holding_capacity TEXT,
    drainage_characteristics TEXT,

    ph_target_min REAL,
    ph_target_max REAL,

    ec_target_min REAL,
    ec_target_max REAL,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- ------------------------------------------------------------
-- 7. Physical substrate instances
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS substrate_instances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    instance_code TEXT NOT NULL UNIQUE,

    substrate_profile_id INTEGER,

    source_lot TEXT,

    quantity_initial REAL,
    quantity_unit TEXT,

    lifecycle_status TEXT NOT NULL DEFAULT 'AVAILABLE'
        CHECK (
            lifecycle_status IN (
                'AVAILABLE',
                'IN_USE',
                'STORED',
                'RECOVERED',
                'REUSED',
                'DISCARDED',
                'SOLD_WITH_PRODUCT',
                'CLOSED'
            )
        ),

    first_used_at TEXT,
    closed_at TEXT,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (substrate_profile_id)
        REFERENCES substrate_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_substrate_instances_profile
ON substrate_instances(substrate_profile_id);

CREATE INDEX IF NOT EXISTS idx_substrate_instances_status
ON substrate_instances(lifecycle_status);


-- ------------------------------------------------------------
-- 8. Substrate use events
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS substrate_use_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    substrate_instance_id INTEGER NOT NULL,

    batch_id INTEGER,
    physical_unit_id INTEGER,
    allocation_id INTEGER,

    crop_profile_id INTEGER,

    variety_name TEXT,

    cycle_number INTEGER NOT NULL
        CHECK (cycle_number > 0),

    start_time TEXT,
    end_time TEXT,

    lifecycle_result TEXT
        CHECK (
            lifecycle_result IS NULL
            OR lifecycle_result IN (
                'RECOVERED',
                'REUSED',
                'DISCARDED',
                'SOLD_WITH_PRODUCT',
                'UNKNOWN'
            )
        ),

    treatment_history TEXT,

    condition_before TEXT,
    condition_after TEXT,

    outcome_notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (substrate_instance_id)
        REFERENCES substrate_instances(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (batch_id)
        REFERENCES grow_batches(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (physical_unit_id)
        REFERENCES physical_units(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (allocation_id)
        REFERENCES spatial_allocations(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (crop_profile_id)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_substrate_use_instance
ON substrate_use_events(substrate_instance_id);

CREATE INDEX IF NOT EXISTS idx_substrate_use_batch
ON substrate_use_events(batch_id);

CREATE INDEX IF NOT EXISTS idx_substrate_use_allocation
ON substrate_use_events(allocation_id);


-- ------------------------------------------------------------
-- 9. Substrate water events
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS substrate_water_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    substrate_use_event_id INTEGER NOT NULL,

    event_time TEXT NOT NULL,

    amount REAL NOT NULL CHECK (amount >= 0),

    unit TEXT NOT NULL DEFAULT 'L',

    source TEXT
        CHECK (
            source IS NULL
            OR source IN (
                'MANUAL',
                'SENSOR',
                'AUTOMATION',
                'IMPORTED'
            )
        ),

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (substrate_use_event_id)
        REFERENCES substrate_use_events(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_substrate_water_use
ON substrate_water_events(substrate_use_event_id);

CREATE INDEX IF NOT EXISTS idx_substrate_water_time
ON substrate_water_events(event_time);


-- ------------------------------------------------------------
-- 10. Crop spatial interaction rules
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS crop_spatial_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    crop_profile_id_a INTEGER NOT NULL,
    crop_profile_id_b INTEGER NOT NULL,

    relationship_type TEXT NOT NULL
        CHECK (
            relationship_type IN (
                'COMPATIBLE',
                'SEPARATION_PREFERRED',
                'SEPARATION_RECOMMENDED',
                'SEPARATION_REQUIRED'
            )
        ),

    minimum_distance_units REAL,

    reason TEXT,
    evidence_reference TEXT,

    confidence TEXT
        CHECK (
            confidence IS NULL
            OR confidence IN (
                'LOW',
                'MEDIUM',
                'HIGH'
            )
        ),

    active INTEGER NOT NULL DEFAULT 1
        CHECK (active IN (0, 1)),

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (crop_profile_id_a)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (crop_profile_id_b)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CHECK (crop_profile_id_a != crop_profile_id_b)
);


-- ------------------------------------------------------------
-- 11. Crop rotation rules
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS crop_rotation_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    previous_crop_profile_id INTEGER NOT NULL,
    successor_crop_profile_id INTEGER NOT NULL,

    relationship_type TEXT NOT NULL,

    expected_effect TEXT,

    evidence_reference TEXT,

    confidence TEXT
        CHECK (
            confidence IS NULL
            OR confidence IN (
                'LOW',
                'MEDIUM',
                'HIGH'
            )
        ),

    active INTEGER NOT NULL DEFAULT 1
        CHECK (active IN (0, 1)),

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (previous_crop_profile_id)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (successor_crop_profile_id)
        REFERENCES crop_profiles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

COMMIT;

