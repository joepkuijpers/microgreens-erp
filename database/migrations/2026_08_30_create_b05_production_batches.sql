CREATE TABLE production_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    batch_code TEXT NOT NULL UNIQUE,

    status TEXT NOT NULL
        CHECK (
            status IN ('PLANNED', 'STARTED', 'COMPLETED')
        ),

    planned_quantity REAL
        CHECK (
            planned_quantity IS NULL
            OR planned_quantity >= 0
        ),

    actual_quantity REAL
        CHECK (
            actual_quantity IS NULL
            OR actual_quantity >= 0
        ),

    quantity_unit TEXT
        CHECK (
            quantity_unit IS NULL
            OR length(trim(quantity_unit)) > 0
        ),

    started_at TEXT,

    completed_at TEXT,

    notes TEXT,

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (
        planned_quantity IS NULL
        OR (
            quantity_unit IS NOT NULL
            AND length(trim(quantity_unit)) > 0
        )
    ),

    CHECK (
        actual_quantity IS NULL
        OR (
            quantity_unit IS NOT NULL
            AND length(trim(quantity_unit)) > 0
        )
    ),

    CHECK (
        status = 'PLANNED'
        OR started_at IS NOT NULL
    ),

    CHECK (
        status <> 'PLANNED'
        OR started_at IS NULL
    ),

    CHECK (
        status <> 'PLANNED'
        OR completed_at IS NULL
    ),

    CHECK (
        status <> 'PLANNED'
        OR actual_quantity IS NULL
    ),

    CHECK (
        status <> 'STARTED'
        OR (
            started_at IS NOT NULL
            AND completed_at IS NULL
            AND actual_quantity IS NULL
        )
    ),

    CHECK (
        status <> 'COMPLETED'
        OR (
            started_at IS NOT NULL
            AND completed_at IS NOT NULL
            AND actual_quantity IS NOT NULL
        )
    )
);
