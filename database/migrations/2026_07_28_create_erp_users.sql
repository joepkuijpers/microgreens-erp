BEGIN IMMEDIATE;

CREATE TABLE erp_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL
        COLLATE NOCASE
        UNIQUE
        CHECK (
            length(trim(username)) BETWEEN 3 AND 64
        ),
    display_name TEXT NOT NULL
        CHECK (
            length(trim(display_name)) BETWEEN 1 AND 100
        ),
    password_hash TEXT NOT NULL
        CHECK (
            length(password_hash) > 0
        ),
    role TEXT NOT NULL DEFAULT 'admin'
        CHECK (
            role IN ('admin', 'operator', 'viewer')
        ),
    is_active INTEGER NOT NULL DEFAULT 1
        CHECK (
            is_active IN (0, 1)
        ),
    failed_login_attempts INTEGER NOT NULL DEFAULT 0
        CHECK (
            failed_login_attempts >= 0
        ),
    locked_until TEXT,
    last_login_at TEXT,
    password_changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_erp_users_active
ON erp_users(is_active);

COMMIT;
