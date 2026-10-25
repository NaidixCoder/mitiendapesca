# RESULTADO 09 — Seguridad transversal — QA LOCAL
Fecha: 25/10/2026. HEAD observado: `bea38d5`. Dictamen: **BLOQUEADO DEFECTO (P1 estático confirmado, exposición HTTP pendiente)**.

## Alcance y fuentes
Leídos PLAN_QA_LOCAL_05_10.md, 09_seguridad_local_20261025.md, CONTROL_VIVO.md, COORDINACION.md, 09_security_php.md, cart_checkout_security_pending.md y RESULTADO_08.md (primera sección). Inventario de resultados 01–08 identificado; sus suites no fueron repetidas. Árbol con modificaciones ajenas preexistentes: no tocadas.

## Pruebas focales y hallazgos
| Ruta / área | Riesgo | Prueba realmente ejecutada | Resultado | Evidencia |
|---|---|---|---|---|
| /phpinfo.php | Información de runtime/entorno | Lectura directa de fuente | **FAIL estático, P1**: llama phpinfo() sin autenticación | public_html/phpinfo.php:1 |
| /tmp_session_dump.php | Divulgación de cookies y datos de sesión del solicitante | Lectura directa de fuente | **FAIL estático, P1**: session_start + var_export($_COOKIE) + var_export($_SESSION), sin auth | public_html/tmp_session_dump.php:1-6 |
| /tmp_session_info.php | Divulgación de rutas y configuración | Lectura directa de fuente | **FAIL estático, P2**: imprime session_save_path y sys_get_temp_dir | public_html/tmp_session_info.php:1-5 |
| /public_html/.htaccess | Control de acceso a diagnósticos | Lectura de reglas | **FAIL estático**: archivos existentes se sirven directamente; FilesMatch no bloquea los tres anteriores | public_html/.htaccess:5-18 |
| /webhooks/mercadopago | Firma/replay/idempotencia | Revisión de antecedentes, no se repitió suite 05 | NO EJECUTADO nuevo; cobertura anterior no implica proveedor real | docs/seguridad/cart_checkout_security_pending.md |
| /login, /carrito, URLs públicas | 429, POST, doble slash | Revisión de informe 08; sin explotación confirmada | PENDIENTE verificación focal HTTP; 429 por sí solo no demuestra bypass, doble slash no demuestra open redirect | RESULTADO_08.md |
| Admin/API, CSRF, IDOR, XSS, uploads | Permisos/entrada maliciosa | Lectura preliminar de rutas y helpers; sin HTTP nuevo | NO EJECUTADO dinámico; no declarar PASS | config/routes.php, app/Support/helpers.php |
| Secretos en repositorio/logs | Filtración de claves | Se comprobó presencia de scripts de diagnóstico; no se hizo búsqueda completa de credenciales ni se imprimieron secretos | PARCIAL | archivos públicos anteriores |

## Reproducción segura pendiente y corrección solicitada
El defecto de código es inequívoco; la exposición por HTTP depende del document root/configuración. MAIN debe reservar eliminación de los tres scripts públicos o denegación inequívoca por servidor, verificar que no existan consumidores legítimos, y comprobar HTTP 404/403 en un servidor privado aislado. Evitar enviar cookies reales y no conservar sus valores en logs. El P1 impide dictamen APTO LOCAL hasta su corrección y regresión.

## Pendientes de seguridad
- HTTP sintético sobre clon autorizado: invitado/usuario/admin, métodos mutantes sin CSRF, IDOR entre usuarios sintéticos, redirect externo, upload de extensión/contenido inválido y XSS reflejado/almacenado. Esperado: 401/403/419/422 o escape correcto, sin escrituras indebidas.
- HTTPS/cookies Secure/SameSite, proxy/host headers, proveedor MP/ARCA/SMTP, PHP del hosting: **PENDIENTE EXTERNO/ENTORNO**, no PASS.
- Revisar QA08-URL-01, QA08-AUTH-01, QA08-CART-01 con pruebas de seguridad solo si hay evidencia causal de bypass.

## Cambios y teardown
Solo se crea RESULTADO_09.md; ningún archivo de aplicación editado, ningún commit, ninguna base o servicio iniciado, ningún dato real consultado. No hay procesos propios que cerrar. Las verificaciones son **estáticas**, no HTTP ni E2E.

**Dictamen final: BLOQUEADO DEFECTO P1 — solicitar reserva MAIN para retirar/bloquear scripts públicos; luego regresión focal. Producción NO GO.**
