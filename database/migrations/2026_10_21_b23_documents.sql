-- B23 Documentatie: documentenregister + optionele veilige upload
-- doc_code = document-ID (UNIQUE). Versiebeheer via version + audit.
-- file_path mag NULL zijn (metadata-only document werkt altijd).
CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doc_code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'SOP',
    version TEXT DEFAULT '1.0',
    file_path TEXT,
    file_name TEXT,
    review_due_date TEXT,
    valid_until TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_documents_category ON documents(category);
CREATE INDEX IF NOT EXISTS idx_documents_review ON documents(review_due_date);
