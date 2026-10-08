CREATE TABLE IF NOT EXISTS measurements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    measurement_type TEXT NOT NULL
        CHECK (length(trim(measurement_type)) > 0),

    value REAL NOT NULL,

    unit TEXT NOT NULL
        CHECK (length(trim(unit)) > 0),

    measured_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    actor_user_id INTEGER,

    quality_status TEXT NOT NULL DEFAULT 'RAW'
        CHECK (quality_status IN ('RAW', 'VALIDATED', 'INVALID', 'OUTLIER')),

    instrument_identifier TEXT,

    session_reference TEXT,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (actor_user_id)
        REFERENCES erp_users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CHECK (
        quality_status = 'INVALID'
        OR value IS NOT NULL
    )
);

CREATE INDEX IF NOT EXISTS idx_measurements_type ON measurements(measurement_type);
CREATE INDEX IF NOT EXISTS idx_measurements_actor ON measurements(actor_user_id);
CREATE INDEX IF NOT EXISTS idx_measurements_session ON measurements(session_reference);
