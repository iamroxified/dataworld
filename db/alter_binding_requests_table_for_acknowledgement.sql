ALTER TABLE binding_requests
ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER copies,
ADD COLUMN acknowledged_at DATETIME NULL AFTER status,
ADD COLUMN acknowledged_by INT NULL AFTER acknowledged_at,
ADD COLUMN admin_note TEXT NULL AFTER acknowledged_by;
