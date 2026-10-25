# Especialista 10 — Infraestructura — QA exclusivamente LOCAL

Leer PLAN_QA_LOCAL_05_10.md, CONTROL_VIVO.md, COORDINACION.md, protocolos Git y ejecución E2E privada, scripts/build/gate.php y documentación de backup/restauración. Objetivo: revisar únicamente que el repositorio es desplegable y recuperable, sin preparar staging ni modificar XAMPP comercial.

## Ejecutar
1. Inventario reproducible de PHP/JS/Composer/extensiones, rutas públicas, configuración y variables requeridas sin revelar secretos. Registrar que PHP CLI 8.2.12 local NO certifica runtime productivo.
2. Revisar migraciones y orden, idempotencia, errores y coherencia de esquema con validaciones estáticas/SQLite cuando se pueda, sin alterar BD habitual. Si hay fixture MariaDB descartable y autorizado, probar migración o restauración únicamente ahí; no crear infraestructura compleja.
3. Revisar procedimientos de backups, protección de secretos, logging y borrado de temporales en el código. Ejecutar test de backup/restore solo con datos ficticios y contra instancia propia verificada; nunca usar BD comercial.
4. Inspeccionar el gate global ya aprobado (909 PHP/120 JS, exit 0 al cierre 01–04) y ejecutar solo regresiones afectadas por cambios posteriores, no auditoría global redundante.
5. Elaborar checklist de deployment para servidor futuro (HTTPS, PHP soportado, DB, permisos, cron, SMTP, sandbox, rollback) como PENDIENTE EXTERNO, sin contratar ni desplegar.

Entrega docs/preproduccion/especialistas/RESULTADO_10.md con ejecución/resultado real y matriz de preparación, riesgos y límites. No cambiar XAMPP, no editar archivos de otras sesiones sin reserva MAIN, no hacer commits. Cierre APTO LOCAL CONDICIONADO / PARCIAL / BLOQUEADO DEFECTO.
