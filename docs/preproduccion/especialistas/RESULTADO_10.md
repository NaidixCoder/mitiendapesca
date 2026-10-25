# RESULTADO 10 — Infraestructura QA LOCAL
Fecha: 2026-10-25. HEAD observado: bea38d5. Dictamen: PARCIAL LOCAL.

## Evidencia ejecutada
- Lectura de PLAN_QA_LOCAL_05_10, mandato 10, CONTROL_VIVO y COORDINACION; antecedentes de gate global 909 PHP / 120 JS exit 0 reutilizados, NO reejecutados.
- `C:\xampp\php\php.exe -v`: PHP CLI 8.2.12 ZTS Windows; no certifica runtime del hosting.
- `C:\xampp\php\php.exe -m`: bcmath, curl, dom, fileinfo, gd, mbstring, mysqli, openssl, PDO, pdo_mysql, pdo_sqlite, soap, xml, zip NO figura, entre otras. Revisar requisitos Composer/funcionalidades del despliegue.
- Inventario `git ls-files *migrat* *backup* *deploy* *restore*`: migraciones SQL numerosas en database/migrations, deploy.sample.sh y scripts/dev/audit_backup_restore.php. Presencia de vendor/composer. No se ejecutaron migraciones.
- Intento `php scripts/dev/audit_backup_restore.php`: FAIL de conexión PDO SQLSTATE HY000 2002, exit 1, antes de dump/creación de clon. NO acredita restauración. No repetir sin aislamiento confirmado: script usa DB::pdo() de configuración actual, vuelca la base origen y crea clon en mismo servidor; NO garantiza origen sintético. La falla es de entorno/conectividad, no defecto funcional demostrado.
- Git status muestra modificaciones ajenas en Ajustes/Auditoría/Facturación y evidencias; no se editaron. No se ejecutó gate global por no corresponder nueva auditoría general.

## Matriz de cierre
| Caso | Estado | Evidencia / límite |
|---|---|---|
| INF10-01 Runtime PHP | PASS LOCAL inventario | CLI 8.2.12, módulos enumerados; hosting PENDIENTE EXTERNO |
| INF10-02 Composer/JS | PARCIAL LOCAL | vendor/composer presente; versiones/resolución completa no verificadas |
| INF10-03 Migraciones | NO EJECUTADO | Archivos identificados; orden, idempotencia y compatibilidad esquema no certificados |
| INF10-04 Backup/restore | NO EJECUTADO | Intento abortó antes de operaciones por conexión rechazada; requiere origen y destino sintéticos verificados |
| INF10-05 Rollback | NO EJECUTADO | No se alcanzó ensayo transaccional; pendiente entorno aislado |
| INF10-06 Secretos/permisos/logs/temporales | PARCIAL LOCAL | Regla de aislamiento y riesgos del script identificados; permisos reales del servidor no comprobables |
| INF10-07 Gate | PASS HISTÓRICO | 909 PHP / 120 JS según CONTROL_VIVO; no nueva corrida |
| INF10-08 Despliegue | PENDIENTE EXTERNO | HTTPS, PHP parcheado, DB, permisos, cron, SMTP, sandbox y rollback |

## Defectos y riesgos
- INF10-R01: script de auditoría no impone origen sintético; podría respaldar la BD habitual si se ejecuta con configuración normal. No se modificó: requiere revisión y reserva MAIN para cualquier corrección. Prioridad preventiva alta para ejecución de QA; no se afirma pérdida de datos.
- INF10-R02: PHP 8.2.12 local antiguo; elegir versión soportada y parcheada para hosting. No confundir con certificación de producción.
- INF10-R03: restauración y rollback sin prueba reproducible aislada. Riesgo operativo abierto.
- No se ha confirmado P0/P1 de aplicación mediante estas pruebas.

## Checklist futuro de despliegue (no ejecutado)
1. Fijar versión PHP soportada y extensiones requeridas por Composer; verificar composer install --no-dev y frontend construido en entorno de release.
2. Servir exclusivamente public_html, negar acceso web a .env, backups, logs, vendor, scripts y archivos temporales; permisos mínimos, secretos por entorno y rotación.
3. Validar orden de migraciones y compatibilidad de schema en BD sintética, con snapshot previo, procedimiento de reversión y comprobación posterior.
4. Probar dump/restauración integral en instancia independiente descartable; cotejar tablas, filas, índices, integridad y tiempos de recuperación; documentar RPO/RTO.
5. HTTPS/certificado, cookies seguras, configuración de errores, logging sin datos sensibles, rotación/retención, limpieza de temporales y uploads.
6. Verificar cron/colas, SMTP de prueba, MP/ARCA solo sandbox autorizado, Correo Argentino productivo prohibido en QA.
7. Checklist go/no-go, monitoreo, healthchecks, rollback de código y DB, responsable de recuperación y simulacro antes de producción.

## Teardown / alcance
No se levantaron servicios ni clones propios; no se tocaron bases comerciales ni XAMPP. No commits. Solo este informe de especialista.

**DICTAMEN: PARCIAL LOCAL.** No aprobar INF10-03/04/05 sin ensayo sintético verificado; MAIN decide reserva para endurecer script y coordina entorno privado, o acepta expresamente el límite como pendiente de entorno. Producción NO GO.
