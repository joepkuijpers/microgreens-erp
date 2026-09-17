BEGIN IMMEDIATE;

-- B07: Production Output & Branching
-- Supports multiple output paths from a single production batch (Fresh, Freeze-dry, Waste, etc.)

CREATE TABLE production_outputs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    batch_id INTEGER NOT NULL,

    output_type TEXT NOT NULL
        CHECK (
            output_type IN (
                'FRESH',
                'FREEZE_DRY_INPUT',
                'FREEZE_DRIED_PRODUCT',
                'WASTE',
                'SAMPLE',
                'OTHER'
            )
        ),

    quantity REAL NOT NULL
        CHECK (quantity > 0),

    unit TEXT NOT NULL
        CHECK (length(trim(unit)) > 0),

    status TEXT NOT NULL DEFAULT 'REGISTERED'
        CHECK (
            status IN (
                'REGISTERED',
                'PROCESSED',
                'SOLD',
                'DISCARDED',
                'STORED'
            )
        ),

    produced_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (batch_id)
        REFERENCES production_batches(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Ensure we don't allocate more than physically possible (checked in app logic mostly, but basic FK here)
    CHECK (batch_id > 0)
);

CREATE INDEX idx_production_outputs_batch ON production_outputs(batch_id);
CREATE INDEX idx_production_outputs_type ON production_outputs(output_type);
CREATE INDEX idx_production_outputs_status ON production_outputs(status);

COMMIT;
