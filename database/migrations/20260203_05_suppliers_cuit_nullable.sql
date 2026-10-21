-- FIX537: permitir proveedores sin CUIT (admin completa luego)
-- Nota: UNIQUE en cuit permite múltiples NULL.
UPDATE suppliers SET cuit = NULL WHERE cuit = '';
ALTER TABLE suppliers MODIFY COLUMN cuit VARCHAR(11) NULL;
