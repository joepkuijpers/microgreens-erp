CREATE TABLE IF NOT EXISTS b02_findings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'DETECTED',
    description TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (entity_id > 0),

    CHECK (
        status IN (
            'DETECTED',
            'CONTEXT_LOADED',
            'REVIEWED',
            'CORRECTED',
            'VERIFIED',
            'RESOLVED'
        )
    )
);

CREATE INDEX IF NOT EXISTS idx_b02_findings_status
    ON b02_findings(status);

CREATE INDEX IF NOT EXISTS idx_b02_findings_entity
    ON b02_findings(entity_type, entity_id);

CREATE INDEX IF NOT EXISTS idx_b02_findings_status_entity
    ON b02_findings(status, entity_type, entity_id);
