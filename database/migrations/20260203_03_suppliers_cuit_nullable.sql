-- FIX535b: permitir proveedores sin CUIT (admin completa luego)
-- Convención: CUIT puede ser NULL. UNIQUE permite múltiples NULL.
UPDATE suppliers SET cuit = NULL WHERE cuit = '';
ALTER TABLE suppliers MODIFY COLUMN cuit VARCHAR(11) NULL;
