-- B08-QC: Quality Checks voor Freeze-Dry Process
-- Maakt een tabel voor specifieke kwaliteitsmetingen na het drogen.

CREATE TABLE IF NOT EXISTS freeze_dry_quality_checks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freeze_dry_process_id INTEGER NOT NULL,
    
    -- METINGEN
    moisture_percent REAL,           -- Restvochtpercentage (cruciaal voor houdbaarheid)
    water_activity_aw REAL,          -- Water activity (aw) waarde
    texture_score INTEGER,           -- Optionele score 1-10
    color_deviation TEXT,            -- Beschrijving van kleurverandering
    
    -- AUDIT
    measured_at DATETIME NOT NULL DEFAULT (datetime('now')),
    operator_id INTEGER,
    device_id TEXT,                  -- Bijv. vochtmeter serienummer
    notes TEXT,

    FOREIGN KEY (freeze_dry_process_id) REFERENCES freeze_dry_processes(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_fdqc_process ON freeze_dry_quality_checks(freeze_dry_process_id);
