-- Data migration: reconstruye home_sections_config si estaba guardado en chunks
-- y elimina claves auxiliares usadas durante la transición.

-- Reconstruye payload chunked (si existe)
SET @home_sections_payload := (
  SELECT GROUP_CONCAT(
           setting_value
           ORDER BY CAST(SUBSTRING(setting_key, LENGTH('home_sections_config__p') + 1) AS UNSIGNED)
           SEPARATOR ''
         )
    FROM app_settings
   WHERE setting_key REGEXP '^home_sections_config__p[0-9]+$'
);

-- Si hay payload reconstruido, lo persiste en la clave principal
UPDATE app_settings
   SET setting_value = @home_sections_payload,
       updated_at = NOW()
 WHERE setting_key = 'home_sections_config'
   AND @home_sections_payload IS NOT NULL
   AND @home_sections_payload <> '';

-- Si no existía la clave principal pero sí chunks, crea la clave principal
INSERT INTO app_settings (setting_key, setting_value, updated_at)
SELECT 'home_sections_config', @home_sections_payload, NOW()
 WHERE @home_sections_payload IS NOT NULL
   AND @home_sections_payload <> ''
   AND NOT EXISTS (
     SELECT 1 FROM app_settings WHERE setting_key = 'home_sections_config'
   );

-- Limpia claves auxiliares de chunking
DELETE FROM app_settings
 WHERE setting_key = 'home_sections_config__parts'
    OR setting_key REGEXP '^home_sections_config__p[0-9]+$';

