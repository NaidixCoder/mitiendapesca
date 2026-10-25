# RESULTADO 06 — Facturación y ARCA — QA LOCAL
Fecha: 25/10/2026. HEAD observado: `6063ae7`. Estado: **PARCIAL LOCAL**.

## Alcance y aislamiento
Plan vigente: `PLAN_QA_LOCAL_05_10.md`. Se leyeron mandato 06, CONTROL_VIVO, COORDINACION y documentación del módulo. No se utilizaron certificados, credenciales comerciales, servicios externos ni base comercial. No se modificó código, vistas, Ajustes ni CONTROL_VIVO. Sin commits.

## Ejecución real
- **PASS LOCAL — 40/40**: `C:\xampp\php\php.exe scripts/dev/facturacion_domain_smoke_private.php` (exit satisfactorio, 40 líneas OK observadas). SQLite en memoria: fechas/meses inválidos, estados de compras, anulación repetida, snapshot y UNIQUE de cierre, CSV seguro, rangos, totales y paginación.
- **PASS LOCAL — diff**: `git diff --check -- app/Services/Billing app/Controllers/Admin/FacturacionControlController.php`, sin incidencias.
- **EVIDENCIA HISTÓRICA REUTILIZADA — 95/95**: responsive previamente comunicado; no se reejecutó ni se atribuye cobertura fiscal.
- **NO EJECUTADO**: emisión/NC/numeración simultánea sobre MariaDB real privada, reintentos con fallos ARCA simulados, autorización/CSRF HTTP autenticado, persistencia y concurrencia fiscal integral. No existe en esta ejecución evidencia de clon privado fiscal autorizado con autenticación; el smoke SQLite no sustituye estos casos.

## Matriz de pendientes
| Caso | Resultado | Escenario / esperado | Riesgo |
|---|---|---|---|
| F06-01 numeración | NO EJECUTADO | Dos solicitudes simultáneas simuladas; número único, secuencia coherente y rollback correcto | Alto, duplicados |
| F06-02 NC | NO EJECUTADO | NC sintética de comprobante válido; impedir doble reversión y exceso de importe | Alto, integridad |
| F06-03 IVA | PARCIAL | Validaciones de importes/CSV existentes PASS; falta contraste de alícuotas y redondeos por comprobante | Medio/alto |
| F06-04 estados y reintentos | NO EJECUTADO | Timeout, rechazo y éxito ARCA simulados; estados idempotentes, sin emisión duplicada | Alto |
| F06-05 permisos/CSRF | NO EJECUTADO | Usuario no autorizado y POST sin token; denegación sin mutación | Alto |
| F06-06 SQL fiscal | NO EJECUTADO | Clon MariaDB descartable; transacciones, bloqueo, concurrencia y recuperación | Alto |
| F06-07 ARCA externa | PENDIENTE EXTERNO | Homologación autorizada con credenciales de prueba, CAE y validación de respuesta | No habilita producción |
| F06-08 visual | PASS HISTÓRICO limitado | 95/95 responsive; no sustituye navegación funcional ni permisos | Medio |

## Teardown y coordinación
Smoke exclusivamente en memoria; sin servicios ni procesos propios persistentes. `git status --short` mostró cambios ajenos en Ajustes y evidencias visuales de Facturación: no tocados. No hay defecto P0/P1 **confirmado por esta ejecución**; tampoco se afirma ausencia de defectos en escenarios no probados.

## Próxima acción MAIN
Coordinar reserva y clon fiscal privado autorizado, o aceptar formalmente límites locales y trasladar casos que requieran infraestructura a pendiente de entorno. No activar ARCA real. Estado: **PARCIAL LOCAL**.
