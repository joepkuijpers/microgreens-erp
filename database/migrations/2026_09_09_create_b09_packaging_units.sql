-- B09: Packaging Units Architecture
-- Ondersteunt verpakkingen vanuit zowel Fresh Outputs als Freeze-Dry Processen.

CREATE TABLE IF NOT EXISTS packaging_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- FLEXIBELE LINK NAAR BRON
    source_type TEXT NOT NULL CHECK (source_type IN ('OUTPUT', 'PROCESS')),
    source_id INTEGER NOT NULL,
    -- Als source_type='OUTPUT' -> source_id = production_outputs.id
    -- Als source_type='PROCESS' -> source_id = freeze_dry_processes.id
    
    -- VERPAKKING DETAILS
    package_type TEXT NOT NULL,       -- Bijv. 'CLAMSHELL', 'JAR', 'BAG'
    weight_per_unit_g REAL NOT NULL,  -- Gewicht per individuele verpakking
    quantity_units INTEGER NOT NULL,  -- Aantal geproduceerde eenheden
    total_weight_g REAL NOT NULL,     -- Totaal gewicht (quantity * weight_per_unit)
    
    -- LABEL & TRACEERBAARHEID
    label_code TEXT UNIQUE NOT NULL,  -- Unieke barcode/label voor deze batch verpakking
    best_before_date DATE,
    packed_at DATETIME NOT NULL DEFAULT (datetime('now')),
    
    -- AUDIT
    operator_id INTEGER,
    notes TEXT,

    -- INDEXEN VOOR SNELLE ZOEKOPDRACHTEN
    FOREIGN KEY (source_id) REFERENCES production_outputs(id) ON DELETE CASCADE -- Note: SQLite handhaaft FK niet strikt op polymorfe links, maar we documenteren het intentie.
);

CREATE INDEX IF NOT EXISTS idx_pkg_source ON packaging_units(source_type, source_id);
CREATE INDEX IF NOT EXISTS idx_pkg_label ON packaging_units(label_code);
CREATE INDEX IF NOT EXISTS idx_pkg_type ON packaging_units(package_type);
