CREATE TABLE IF NOT EXISTS batch_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER,
    inventory_id INTEGER,
    quantity_used REAL
);

CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS equipment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expense_date TEXT,
    description TEXT,
    amount REAL
);

CREATE TABLE IF NOT EXISTS finished_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    quantity REAL DEFAULT 0,
    unit TEXT
);

CREATE TABLE IF NOT EXISTS grow_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crop TEXT NOT NULL,
    sow_date TEXT,
    expected_harvest_date TEXT,
    harvest_date TEXT,
    tray_count INTEGER,
    tray_type TEXT,
    status TEXT
);

CREATE TABLE IF NOT EXISTS harvests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER,
    harvest_date TEXT,
    weight_grams REAL,
    quality_notes TEXT
);

CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name TEXT NOT NULL,
    category TEXT,
    quantity REAL DEFAULT 0,
    unit TEXT,
    unit_cost REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT,
    unit TEXT,
    sale_price REAL,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    sale_date TEXT,
    product TEXT,
    quantity REAL,
    amount REAL,
    status TEXT,
    customer_id INTEGER,
    product_id INTEGER
);

CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);
