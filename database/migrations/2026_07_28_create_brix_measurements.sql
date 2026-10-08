BEGIN IMMEDIATE;

CREATE TABLE brix_measurement_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL,
    measured_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    purpose TEXT NOT NULL,
    growth_stage TEXT,
    plant_part TEXT NOT NULL,
    sampling_method TEXT NOT NULL,
    instrument_identifier TEXT NOT NULL,
    instrument_resolution REAL
        CHECK (
            instrument_resolution IS NULL
            OR instrument_resolution > 0
        ),
    temperature_compensation INTEGER NOT NULL DEFAULT 0
        CHECK (temperature_compensation IN (0, 1)),
    calibration_passed INTEGER NOT NULL
        CHECK (calibration_passed IN (0, 1)),
    time_since_irrigation_minutes INTEGER
        CHECK (
            time_since_irrigation_minutes IS NULL
            OR time_since_irrigation_minutes >= 0
        ),
    observer TEXT,
    measurement_mode TEXT NOT NULL DEFAULT 'experimental'
        CHECK (measurement_mode IN ('experimental', 'routine')),
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id)
        REFERENCES grow_batches(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE brix_measurement_readings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    replicate_number INTEGER NOT NULL
        CHECK (replicate_number > 0),
    sampling_position TEXT,
    sample_size REAL
        CHECK (
            sample_size IS NULL
            OR sample_size > 0
        ),
    sample_size_unit TEXT,
    brix_value REAL
        CHECK (
            brix_value IS NULL
            OR brix_value >= 0
        ),
    sample_temperature REAL,
    is_valid INTEGER NOT NULL DEFAULT 1
        CHECK (is_valid IN (0, 1)),
    invalid_reason TEXT,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id)
        REFERENCES brix_measurement_sessions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    UNIQUE (session_id, replicate_number),
    CHECK (
        is_valid = 0
        OR brix_value IS NOT NULL
    ),
    CHECK (
        is_valid = 1
        OR (
            invalid_reason IS NOT NULL
            AND length(trim(invalid_reason)) > 0
        )
    ),
    CHECK (
        sample_size IS NULL
        OR (
            sample_size_unit IS NOT NULL
            AND length(trim(sample_size_unit)) > 0
        )
    )
);

CREATE INDEX idx_brix_sessions_batch_measured
ON brix_measurement_sessions(batch_id, measured_at);

CREATE INDEX idx_brix_readings_session
ON brix_measurement_readings(session_id);

COMMIT;
