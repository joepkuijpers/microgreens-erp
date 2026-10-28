CREATE TABLE IF NOT EXISTS entity_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    source_entity_type TEXT NOT NULL,
    source_entity_id INTEGER NOT NULL,

    relationship_type TEXT NOT NULL,

    target_entity_type TEXT NOT NULL,
    target_entity_id INTEGER NOT NULL,

    quantity REAL,
    quantity_unit TEXT,

    event_time TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    actor_user_id INTEGER,

    reference_type TEXT,
    reference_id INTEGER,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (length(trim(source_entity_type)) > 0),
    CHECK (source_entity_id > 0),

    CHECK (length(trim(relationship_type)) > 0),

    CHECK (length(trim(target_entity_type)) > 0),
    CHECK (target_entity_id > 0),

    CHECK (
        quantity IS NULL
        OR quantity >= 0
    ),

    CHECK (
        quantity IS NULL
        OR (
            quantity_unit IS NOT NULL
            AND length(trim(quantity_unit)) > 0
        )
    ),

    CHECK (
        reference_id IS NULL
        OR (
            reference_type IS NOT NULL
            AND length(trim(reference_type)) > 0
        )
    ),

    FOREIGN KEY (actor_user_id)
        REFERENCES erp_users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_entity_links_source
    ON entity_links (
        source_entity_type,
        source_entity_id
    );

CREATE INDEX IF NOT EXISTS idx_entity_links_target
    ON entity_links (
        target_entity_type,
        target_entity_id
    );

CREATE INDEX IF NOT EXISTS idx_entity_links_relationship
    ON entity_links (
        relationship_type
    );

CREATE INDEX IF NOT EXISTS idx_entity_links_event_time
    ON entity_links (
        event_time
    );

CREATE UNIQUE INDEX IF NOT EXISTS uq_entity_links_direct
    ON entity_links (
        source_entity_type,
        source_entity_id,
        relationship_type,
        target_entity_type,
        target_entity_id,
        event_time
    );
