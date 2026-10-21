FIX553 — Retención de importaciones (purga dura 6 meses)
======================================================

Qué hace
--------
- Purga importaciones viejas (por defecto: 183 días ~ 6 meses):
  - Borra archivos del filesystem: .csv, _rollback.json, _errors.json
  - Borra el registro en DB (import_jobs)

Motivo
------
Con FIX552 el revert es LIFO (undo stack). No tiene sentido conservar
archivos/rollback para siempre: llena disco, complica backups y no aporta
valor operativo.

Cómo ejecutarlo
--------------
Desde la raíz del proyecto:

  php cli.php imports:purge 183

Devuelve JSON con resumen y escribe un log simple en:
  storage/logs/import_purge.log

Automatización (Hostinger)
--------------------------
Configurar un Cron 1 vez por día (recomendado). Ejemplo:

  php /home/USUARIO/mi-pesca/cli.php imports:purge 183

Notas / seguridad
-----------------
- El borrado de archivos se limita a storage/imports (guardrail).
- La purga es "dura": si un job supera el umbral, se elimina también de DB.
- Si falta algún archivo, igual se elimina el job en DB y se registra warning.
