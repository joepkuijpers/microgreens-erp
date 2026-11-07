-- 046: zacht archiveren + verwijder-log (B31)
ALTER TABLE production_batches ADD COLUMN archived_at DATETIME;
ALTER TABLE grow_batches       ADD COLUMN archived_at DATETIME;

CREATE TABLE IF NOT EXISTS deletion_log (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    action       TEXT NOT NULL,          -- 'archive' of 'hard_delete'
    system       TEXT NOT NULL,          -- 'production' of 'grow'
    batch_id     INTEGER NOT NULL,
    batch_code   TEXT,
    operator     TEXT,
    related_rows INTEGER,
    created_at   DATETIME NOT NULL DEFAULT (datetime('now'))
);