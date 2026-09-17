BEGIN IMMEDIATE;

-- B08: Freeze-Dry Process Details
-- Stores specific process parameters for freeze-drying cycles.
-- Links to a production_output of type 'FREEZE_DRY_INPUT'.

CREATE TABLE freeze_dry_processes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    output_id INTEGER NOT NULL UNIQUE,
        -- Linkt naar de input grondstof (de tak vanuit de batch)
        -- UNIQUE omdat één output maar in één vriesdroger-cyclust kan gaan

    machine_identifier TEXT NOT NULL
        CHECK (length(trim(machine_identifier)) > 0),

    cycle_code TEXT
        CHECK (length(trim(cycle_code)) > 0),

    started_at TEXT NOT NULL,

    completed_at TEXT,

    -- Process Parameters
    target_pressure REAL
        CHECK (target_pressure IS NULL OR target_pressure > 0),

    target_temp_c REAL
        CHECK (target_temp_c IS NULL OR target_temp_c > -100),

    -- Results
    input_weight REAL NOT NULL
        CHECK (input_weight > 0),

    output_weight REAL
        CHECK (output_weight IS NULL OR output_weight > 0),

    yield_percent REAL
        CHECK (yield_percent IS NULL OR yield_percent BETWEEN 0 AND 100),

    energy_kwh REAL
        CHECK (energy_kwh IS NULL OR energy_kwh >= 0),

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (output_id)
        REFERENCES production_outputs(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
        -- Voorkomt dat een output verwijderd wordt terwijl er een proces aan hangt

    CHECK (
        -- Als completed, dan moet er een eindtijd en outputgewicht zijn
        completed_at IS NULL
        OR (completed_at IS NOT NULL AND output_weight IS NOT NULL)
    )
);

CREATE INDEX idx_freeze_dry_processes_machine ON freeze_dry_processes(machine_identifier);
CREATE INDEX idx_freeze_dry_processes_cycle ON freeze_dry_processes(cycle_code);

COMMIT;
