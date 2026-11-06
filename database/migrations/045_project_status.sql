-- 045: tabel voor projectvoortgang (B30)
CREATE TABLE IF NOT EXISTS project_status (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    phase_label TEXT    NOT NULL,
    phase_num   INTEGER NOT NULL,
    phase_total INTEGER NOT NULL DEFAULT 7,
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO project_status (phase_label, phase_num, phase_total)
VALUES ('features', 5, 7);