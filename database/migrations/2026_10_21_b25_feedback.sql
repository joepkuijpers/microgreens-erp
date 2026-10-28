-- B25 Klantfeedback: licht register, klacht terugkoppelbaar naar sale->batch
-- sale_id optioneel -> via sales.batch_id traceerbaar naar productie (B09).
-- Bewust licht: geen e-mail/ticketsysteem, wel statusflow + traceerbaarheid.
CREATE TABLE IF NOT EXISTS customer_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'COMPLAINT',
    severity TEXT NOT NULL DEFAULT 'MEDIUM',
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'OPEN',
    sale_id INTEGER,
    resolution TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    resolved_at TEXT,
    resolved_by TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);
CREATE INDEX IF NOT EXISTS idx_feedback_customer ON customer_feedback(customer_id);
CREATE INDEX IF NOT EXISTS idx_feedback_status ON customer_feedback(status);
CREATE INDEX IF NOT EXISTS idx_feedback_sale ON customer_feedback(sale_id);
