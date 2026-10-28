ALTER TABLE cleaning_agents ADD COLUMN supplier_id INTEGER REFERENCES suppliers(id);
