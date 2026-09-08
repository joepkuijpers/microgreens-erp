-- B08: Freeze-Dry Process Architecture
-- Creëert de tabel voor specifieke vriesdroog-procesdata (Layer 3).
-- Deze tabel linkt aan een BESTAANDE 'FREEZE_DRY_INPUT' output uit B07.
-- Het wordt alleen gevuld als het vriesdroogproces daadwerkelijk start.

CREATE TABLE IF NOT EXISTS freeze_dry_processes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- LINK NAAR LAYER 2 (B07 Output)
    -- Verwijst naar het specifieke output-record dat als input dient voor deze machine.
    production_output_id INTEGER NOT NULL UNIQUE, 
    
    -- MACHINE & CYCLUS IDENTITEIT
    machine_id TEXT NOT NULL,             -- Bijv. 'FD-MACHINE-01'
    cycle_code TEXT NOT NULL UNIQUE,      -- Unieke code voor deze draai (bijv. 'FD-20260908-001')
    
    -- PROCES TIMING
    started_at DATETIME NOT NULL,         -- Start moment cyclus
    ended_at DATETIME,                    -- Eind moment (NULL als nog bezig)
    
    -- PROCES DATA (Layer 3 specifiek)
    input_weight_g REAL NOT NULL,         -- Gewicht bij start (kopie van B07 output voor snapshot)
    final_weight_g REAL,                  -- Gewicht NA drogen (cruciaal voor yield berekening)
    
    -- PARAMETER LOGGING (Optioneel uitbreidbaar)
    avg_temperature_c REAL,               -- Gemiddelde temperatuur tijdens cyclus
    min_pressure_mbar REAL,               -- Minimale druk (vacuüm) bereikt
    
    -- STATUS & AUDIT
    status TEXT NOT NULL DEFAULT 'RUNNING', -- 'RUNNING', 'COMPLETED', 'FAILED'
    operator_id INTEGER,                  -- Wie startte de machine?
    notes TEXT,                           -- Bijzondere observaties tijdens proces
    
    -- FOREIGN KEY CONSTRAINT
    FOREIGN KEY (production_output_id) REFERENCES production_outputs(id) ON DELETE CASCADE
);

-- Index voor snelle zoekopdrachten op machine of status
CREATE INDEX IF NOT EXISTS idx_fd_machine ON freeze_dry_processes(machine_id);
CREATE INDEX IF NOT EXISTS idx_fd_status ON freeze_dry_processes(status);
CREATE INDEX IF NOT EXISTS idx_fd_output ON freeze_dry_processes(production_output_id);
