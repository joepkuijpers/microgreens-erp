CREATE TABLE IF NOT EXISTS labor_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    work_date TEXT NOT NULL,
    hours_worked REAL NOT NULL CHECK (hours_worked > 0),
    activity TEXT NOT NULL,
    batch_id INTEGER,
    gross_hourly_rate REAL NOT NULL DEFAULT 0 CHECK (gross_hourly_rate >= 0),
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES grow_batches(id)
);

CREATE INDEX IF NOT EXISTS idx_labor_entries_work_date
ON labor_entries(work_date);

CREATE INDEX IF NOT EXISTS idx_labor_entries_batch_id
ON labor_entries(batch_id);