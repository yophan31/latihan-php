-- ...existing code...
CREATE TABLE IF NOT EXISTS todos (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    is_finished BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    pos INTEGER NOT NULL DEFAULT 0
);

-- unikkan judul (case-insensitive)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint c
        JOIN pg_class t ON c.conrelid = t.oid
        WHERE c.conname = 'todos_title_unique_ci'
    ) THEN
        ALTER TABLE todos DROP CONSTRAINT IF EXISTS todos_title_key;
        CREATE UNIQUE INDEX IF NOT EXISTS todos_title_unique_ci ON todos ((lower(title)));
    END IF;
END
$$;