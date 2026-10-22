-- B27: licht register voor EXTERNE koppelingen (OrganicXseeds, sensoren, export).
-- Start leeg: er is nu geen actieve externe bron. Vult zich pas bij echte aansluiting.
-- Eis: externe data houdt bron + timestamp + sync-status.
CREATE TABLE IF NOT EXISTS integration_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    source_type TEXT NOT NULL DEFAULT 'EXTERNAL',
    endpoint_url TEXT,
    sync_status TEXT NOT NULL DEFAULT 'INACTIVE',
    last_sync_at TEXT,
    source_note TEXT,
    is_active INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_integration_active ON integration_sources(is_active);
