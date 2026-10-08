CREATE TABLE IF NOT EXISTS customer_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    order_date DATETIME DEFAULT (datetime('now')),
    status TEXT DEFAULT 'PENDING',
    items_json TEXT NOT NULL,
    total_amount REAL,
    notes TEXT
);
