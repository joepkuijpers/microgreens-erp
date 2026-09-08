-- B10: Quality & Lab Results
-- Koppelt metingen aan processen of verpakkingen met automatische Pass/Fail logica.

CREATE TABLE IF NOT EXISTS lab_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- LINK (Polymorf: naar Proces of Verpakking)
    source_type TEXT NOT NULL CHECK (source_type IN ('PROCESS', 'PACKAGING')),
    source_id INTEGER NOT NULL,
    
    -- METINGEN
    test_type TEXT NOT NULL,          -- Bijv. 'WATER_ACTIVITY', 'BRIX', 'MICROBIOLOGY'
    test_value REAL NOT NULL,
    unit TEXT NOT NULL,               -- Bijv. 'aw', '%', 'CFU/g'
    
    -- GRENSWAARDEN (Op het moment van testen)
    limit_min REAL,
    limit_max REAL,
    
    -- OORDEEL
    result_status TEXT NOT NULL CHECK (result_status IN ('PASS', 'FAIL', 'PENDING')),
    tested_at DATETIME NOT NULL DEFAULT (datetime('now')),
    lab_technician_id INTEGER,
    notes TEXT
);

CREATE INDEX IF NOT EXISTS idx_lab_source ON lab_results(source_type, source_id);
CREATE INDEX IF NOT EXISTS idx_lab_status ON lab_results(result_status);
