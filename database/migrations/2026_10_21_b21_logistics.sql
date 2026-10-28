-- B21 Logistiek: hybride model (rit + leveringen), beide licht
-- delivery_runs = rit (datum + chauffeur + status). Bewust licht: geen geo/afstanden.
-- deliveries = levering, gekoppeld aan rit + sale -> via sales.batch_id traceerbaar naar batch.
CREATE TABLE IF NOT EXISTS delivery_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    run_date TEXT NOT NULL,
    driver TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'PLANNED',
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id INTEGER,
    sale_id INTEGER,
    destination TEXT,
    delivery_status TEXT NOT NULL DEFAULT 'PENDING',
    delivered_at TEXT,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (run_id) REFERENCES delivery_runs(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);
CREATE INDEX IF NOT EXISTS idx_deliveries_run ON deliveries(run_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_sale ON deliveries(sale_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_status ON deliveries(delivery_status);
