BEGIN IMMEDIATE;

CREATE TABLE audit_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    event_time TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    actor_user_id INTEGER,

    event_type TEXT NOT NULL
        CHECK (
            length(trim(event_type)) > 0
        ),

    entity_type TEXT NOT NULL
        CHECK (
            length(trim(entity_type)) > 0
        ),

    entity_id INTEGER NOT NULL
        CHECK (
            entity_id > 0
        ),

    action TEXT NOT NULL
        CHECK (
            length(trim(action)) > 0
        ),

    reason TEXT,

    before_data TEXT,

    after_data TEXT,

    reference_type TEXT,

    reference_id INTEGER
        CHECK (
            reference_id IS NULL
            OR reference_id > 0
        ),

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (actor_user_id)
        REFERENCES erp_users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CHECK (
        reason IS NULL
        OR length(trim(reason)) > 0
    ),

    CHECK (
        reference_id IS NULL
        OR (
            reference_type IS NOT NULL
            AND length(trim(reference_type)) > 0
        )
    )
);

CREATE INDEX idx_audit_events_entity
ON audit_events(entity_type, entity_id, event_time);

CREATE INDEX idx_audit_events_actor
ON audit_events(actor_user_id, event_time);

CREATE INDEX idx_audit_events_type
ON audit_events(event_type, event_time);

CREATE INDEX idx_audit_events_reference
ON audit_events(reference_type, reference_id);

COMMIT;
