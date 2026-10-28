-- B16 Taakplanning: tasks-tabel (single source of truth)
-- NULLable, geen dataverlies. Nieuwe tabel, geen wijziging op bestaande rijen.
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT NOT NULL DEFAULT 'GENERAL',
    priority TEXT NOT NULL DEFAULT 'NORMAL',
    status TEXT NOT NULL DEFAULT 'OPEN',
    due_date TEXT,
    source_type TEXT NOT NULL DEFAULT 'MANUAL',
    source_id INTEGER,
    batch_id INTEGER,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    completed_at TEXT,
    completed_by TEXT,
    cancelled_reason TEXT,
    notes TEXT
);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_due ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_tasks_source ON tasks(source_type, source_id);
