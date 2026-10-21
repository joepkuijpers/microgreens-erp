-- B22 Onderhoud: licht model (apparaatregister + onderhoudslogboek)
-- Bewust GESCHEIDEN van de energie-table 'equipment' (B13) en 'cleaning_logs' (B12).
-- equipment_items = technisch apparaatregister. is_critical voedt B28/B16.
CREATE TABLE IF NOT EXISTS equipment_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'OTHER',
    is_critical INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
-- maintenance_logs = technisch onderhoud per apparaat (geen reiniging -> B12).
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    maintenance_date TEXT NOT NULL,
    type TEXT,
    description TEXT,
    performed_by TEXT,
    next_due_date TEXT,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (item_id) REFERENCES equipment_items(id)
);
CREATE INDEX IF NOT EXISTS idx_maint_item ON maintenance_logs(item_id);
CREATE INDEX IF NOT EXISTS idx_maint_due ON maintenance_logs(next_due_date);
