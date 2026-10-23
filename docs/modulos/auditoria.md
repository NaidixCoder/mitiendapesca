# Auditoría — mandato integral Admin V3 (23/10/2026)

Estado: asignado a especialista, MAIN CHAT coordina e integra.

## Inventario (confirmar con código)
/admin/audit, /show, /logins y POST /logins/cleanup; AuditController; app/Views/pages/admin/audit (3 vistas).

## Alcance
Historial y detalle, filtros/paginación, auditoría de logins, roles/privacidad, limpieza controlada, retención, integridad e imposibilidad de ocultar acciones reales. Probar limpieza solo en DB aislada.

## Reglas de ejecución
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md, docs/ARQUITECTURA_ADMIN_V3.md. Una auditoría inicial completa y una ventana grande de implementación: reparar lógica, seguridad, SQL/servicios y todas las vistas; rediseño HTML/CSS Admin V3 con /admin/seo/auditoria como referencia (cards, botones, jerarquía, peso de fuente, tablas, responsive). Reutilizar tokens/componentes, CSS acotado al módulo; sin Tailwind ni overrides globales. No modificar shell/sidebar, CSS compartido ni archivos de otros especialistas sin reserva MAIN CHAT. Probar en DB aislada las operaciones destructivas; verificar desktop/tablet/móvil. Registrar QA real y riesgos, sin microventanas.

## Primer prompt para el especialista
MiTiendaPesca — especialista Auditoría. Leé docs/modulos/auditoria.md y toda la documentación obligatoria. Auditá UNA vez el módulo entero con sus vistas derivadas; implementá integralmente calidad de código, seguridad, HTML/CSS Admin V3 con referencia visual Auditoría SEO y QA responsive/funcional. Sin microventanas ni solicitudes de autorización por cada cambio propio. Actualizá este documento con evidencia. **Terminá en una única respuesta breve:** MÓDULO | ESTADO (CERRADO/PARCIAL) | CAMBIOS | QA con resultados | ARCHIVOS/COMMIT | BLOQUEO + SOLUCIÓN. Ese resumen será auditado por MAIN CHAT.


## 23/10/2026 — Continuación de cierre integral (especialista)

**Estado: PARCIAL — sin aprobación de cierre/producción.**

### Cambios efectivos
- `AuditController.php`: redirect de POST cleanup con allowlist de rutas de Auditoría; navegación de detalle restringida a rutas locales del módulo; validación estricta de id de detalle; CSRF fail-closed si el helper no existe; inclusión única de la hoja visual por vista mediante `extraHead`.
- `AuditSearchRepository.php`: se preservan en el listado agrupado los eventos con `request_id IS NULL` (histórico pre-migración), sin agruparlos ni confundirlos con actualizaciones de stock. Se aplicó en COUNT y SELECT.
- `AuditLoginsRepository.php`: paginación con orden estable fecha descendente, ID descendente.
- `AuditIndexService.php` y `AuditLoginsService.php`: cotas de página y búsqueda, restricciones de request ID y fechas. Limpieza manual respeta retención mínima de configuración: solicitudes POST ya no pueden acortarla.
- `public_html/assets/css/admin-v3/pages/audit.css` (nuevo): composición localizada, tokens existentes, layout adaptable de feed, formularios y tabla; las tres vistas comparten `av3-audit`. No se tocó CSS global ni shell.
- Parciales de actividad/logins: navegación visual, superficies y accesibilidad de paginación, confirmación explícita de eliminación; detalle comparte estilos sin alteración de su lógica.

### QA y evidencia real
- PHP CLI `-l`: 12 archivos propios revisados sin errores de sintaxis (antes del último endurecimiento de ID/CSRF; repetir tras integración).
- `git diff --check` ejecutado sin errores de whitespace; advertencias Git de conversión LF/CRLF sobre working tree concurrente, no fallas de contenido.
- SQLite privada en memoria, 5 fixtures sintéticos: `AuthLoginEventsMaintenanceService::cleanup(dryRun=true)` conservó 5/5; cleanup real eliminó 3/3 vencidos de tipos admitidos; preservó el login reciente y un evento ajeno, PASS. Archivo temporal de prueba eliminado.
- Verificadas las cuatro rutas declaradas en `config/routes.php` por inspección; NO se realizó HTTP autenticado.
- Se lanzó `scripts/build/gate.php`; el proceso reportó inicio con 899 archivos PHP. Resultado final no confirmado aún.
- Responsive: reglas @media construidas para móvil/tablet/desktop; **no** se ejecutó navegador real ni captura en 363/390/768/1024/1600. No equivale a QA visual aprobado.

### Pendientes impeditivos de CERRADO
- HTTP E2E autenticado con guardia de esquema MariaDB privado, permisos reales, CSRF válido/inválido, paginaciones/filtros/errores y persistencia.
- QA real de limpieza con rutas POST y trazabilidad de `Audit::log` en ambiente privado.
- Capturas y revisión responsive real de 3 vistas, consolidar markup legacy aún presente en parciales de detalle.
- Confirmar gate completo y repetir linter tras cambios finales.
- Posibles riesgos de paginación de request agrupado y de retención/backoffice requieren E2E bajo SQL MySQL.
- Ningún registro comercial tocado; sin commits ni push (responsabilidad MAIN CHAT).


### Adenda de verificación final de esta intervención
- Después de los ajustes finales: **PHP -l PASS en 18 archivos** del módulo (controller, services, repositorios, páginas y todos los parciales de audit); **git diff --check PASS** en el dominio, con advertencias Git no bloqueantes de CRLF.
- Gate global: la salida observada incluye **FAIL: nombre export saneado** en la batería de Facturación `facturacion_domain_smoke_private.php` (archivo de otro especialista); su proceso terminó con exit code 0, lo que **no equivale a gate global aprobado**. No se corrigió el código ajeno.
- Nuevos parciales de detalle afectados: `show/header_card.php`, `show/snapshot_card.php`, `show/receipt_card.php`, `show/details_card.php`. Se usan tarjetas Admin V3 del módulo, JSON adaptativo e iframe de comprobante con título accesible. Mantienen información funcional.
- Evidencia disponible: 18/18 sintaxis, diff-check, limpieza sintética SQLite 5 fixtures; ausencia de HTTP MariaDB autenticado y de visual browser se mantiene. No declarar CERRADO.


## 23/10/2026 — QA E2E HTTP, MariaDB y navegador: resultado final

**Resultado técnico del dominio: 105/105 PASS**, ejecución reproducible con:
`python -u scripts/dev/auditoria_http_mariadb_private.py`.

- Base MariaDB privada descartable, puerto 18983, directorio temporal y verificación expresa de `@@datadir` y `@@port`. Clon sin `.env`, almacenamiento comercial, uploads ni credenciales comerciales. Servidor HTTP PHP privado en 18883, sesiones y CSRF **originales**; no hay sustitutos de autenticación.
- 30 controles HTTP y SQL iniciales: acceso anónimo denegado en 3 rutas; autenticación admin/cliente real; CSRF login y cleanup inválidos denegados; detalle con ID válido y 4 inválidos; consulta de eventos históricos con `request_id NULL`; búsqueda de logins; limpieza no confirmada sin cambios, limpieza confirmada borrando solo 3 eventos vencidos, 2 fixtures conservados (uno reciente y otro de evento fuera de política); 2 filas de `admin_audit_log` preservadas; nueva entrada `logins_cleanup_executed` persistida; usuario sin rol admin sin acceso GET/POST.
- Playwright real con Microsoft Edge headless: las 3 vistas (/admin/audit, /admin/audit/show, /admin/audit/logins) a 363, 390, 768, 1024 y 1600 px = **15 combinaciones**, HTTP 200, contenido autorizado, sin overflow de documento y hojas CSS del módulo y shell realmente analizadas mediante CSSOM. **15 capturas**: `%TEMP%\mi_auditoria_visual_qa_20261023\audit_<ancho>_<ruta>.png`. Revisadas visualmente capturas 363 logins, 390 actividad y 1600 logins: estilos cargados, navegación, tarjetas, filtros y tablas. La tabla de accesos utiliza desplazamiento horizontal interno en móvil para conservar todas sus columnas.
- En la primera ejecución de navegador se detectó CSS sin carga real en el router QA. Corregida normalización de assets **solo en el clon privado**, se añadió comprobación CSSOM compartido y se repitió el recorrido completo. La ejecución definitiva pasó **105/105**, no 89/89. La normalización del `head.php` privado no modifica el `head.php` original.
- **Teardown PASS**: dos procesos privados terminados; carpeta del clon y base temporal eliminadas; las capturas quedan como evidencia. Ningún registro comercial tocado.
- Build lint: 18 archivos PHP sin errores; `git diff --check` sin errores en dominio Auditoría; `python -m py_compile scripts/dev/auditoria_http_mariadb_private.py` sin errores.
- Gate global de otros especialistas: la ejecución previa informó `FAIL: nombre export saneado` dentro del smoke de Facturación. No constituye fallo de Auditoría y se remite a MAIN CHAT. No hay commit/push por regla multichat.

**Estado para integración selectiva:** Auditoría entregada con pruebas técnicas/funcionales y revisión visual de evidencia; aprobación estética final del propietario y gate global transversal son decisiones independientes del MAIN CHAT. Archivos propios: `AuditController.php`, repositorios AuditSearch/AuditLogins, servicios AuditIndex/AuditLogins, páginas/parciales audit, CSS `admin-v3/pages/audit.css`, script `scripts/dev/auditoria_http_mariadb_private.py`, este informe.
