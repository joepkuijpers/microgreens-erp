CREATE TABLE IF NOT EXISTS financial_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_type TEXT NOT NULL,
    category TEXT NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'EUR',
    related_entity_id INTEGER,
    related_entity_type TEXT,
    description TEXT,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by TEXT,
    is_skal_relevant INTEGER DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_fin_trans_date ON financial_transactions(transaction_date);
CREATE INDEX IF NOT EXISTS idx_fin_trans_category ON financial_transactions(category);
