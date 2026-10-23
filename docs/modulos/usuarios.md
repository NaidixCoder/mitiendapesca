# Usuarios — mandato integral Admin V3 (23/10/2026)

Estado: asignado a especialista, MAIN CHAT coordina e integra.

## Inventario (confirmar con código)
/admin/users, /show, POST /role, /force-logout; UsersController; app/Views/pages/admin/users (2 vistas).

## Alcance
Lista/perfil, roles y permisos, CSRF, cierre de sesiones, impedir perder último administrador, auditoría de cambios, validaciones, confirmaciones, estados y responsive. Probar con usuarios sintéticos.

## Reglas de ejecución
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md, docs/ARQUITECTURA_ADMIN_V3.md. Una auditoría inicial completa y una ventana grande de implementación: reparar lógica, seguridad, SQL/servicios y todas las vistas; rediseño HTML/CSS Admin V3 con /admin/seo/auditoria como referencia (cards, botones, jerarquía, peso de fuente, tablas, responsive). Reutilizar tokens/componentes, CSS acotado al módulo; sin Tailwind ni overrides globales. No modificar shell/sidebar, CSS compartido ni archivos de otros especialistas sin reserva MAIN CHAT. Probar en DB aislada las operaciones destructivas; verificar desktop/tablet/móvil. Registrar QA real y riesgos, sin microventanas.

## Primer prompt para el especialista
MiTiendaPesca — especialista Usuarios. Leé docs/modulos/usuarios.md y toda la documentación obligatoria. Auditá UNA vez el módulo entero con sus vistas derivadas; implementá integralmente calidad de código, seguridad, HTML/CSS Admin V3 con referencia visual Auditoría SEO y QA responsive/funcional. Sin microventanas ni solicitudes de autorización por cada cambio propio. Actualizá este documento con evidencia. **Terminá en una única respuesta breve:** MÓDULO | ESTADO (CERRADO/PARCIAL) | CAMBIOS | QA con resultados | ARCHIVOS/COMMIT | BLOQUEO + SOLUCIÓN. Ese resumen será auditado por MAIN CHAT.

## Intervención integral del especialista — 23/10/2026

**Estado técnico: PARCIAL para cierre global**. La lógica crítica de Usuarios y el QA privado pasaron, pero el gate global está rojo por un archivo de otro módulo, y la recomputación manual de estadísticas necesita un POST autorizado (rutas globales reservadas). No declarar aprobación global ni producción.

### Salud del código y correcciones
- `UsersController::index`: query limitado a 120 caracteres y aceptado solo si es texto, dos placeholders SQL independientes, total y paginación recortados al último número válido, orden estable por fecha+ID.
- `UsersController::show`: paginación de pedidos normalizada; SELECT paginado por usuario conserva las relaciones con pedidos, perfiles y direcciones; eliminado efecto de escritura en GET (`rebuild=1` o ausencia de caché). La lectura de `customer_stats` permanece. Las estadísticas todavía no existentes quedan vacías hasta recalcularlas por una operación segura futura.
- `UsersController::role`: roles no admitidos se rechazan explícitamente, sin conversión automática a cliente; validación de parámetros de entrada, redirección `next` restringida a Usuarios, CSRF y rate-limit existentes.
- Servicio nuevo `UserAccessService`: transacción InnoDB, bloqueo `SELECT ... FOR UPDATE` de administradores, verificaciones del actor real en BD y del usuario objetivo, guard del último administrador, protección contra mutación de la cuenta actual, actualización del rol con invalidación de sesión en la misma transacción y rollback ante error. Cambios sin modificación de rol no incrementan versión.
- `forceLogout`: verifica actor admin en SQL, rechaza cuenta propia e inexistente, incrementa `COALESCE(session_version,0)+1`, conserva auditoría y retorno flash; no se modificaron auth/middleware globales ni datos comerciales.
- Auditoría de cambio de roles y cierre de sesiones conserva `Audit::log` y `cache_forget` ya existentes.

### Admin V3
- Listado: nuevo panel de búsqueda y tabla operativa, estados vacíos, confirmación de cambio de rol y cierre forzado, CSRF, acciones ocultas para la propia cuenta, paginación anterior/siguiente y lectura móvil.
- Detalle: encabezado oficial, bloques de actividad, perfil, direcciones, acciones y pedidos rediseñados con `av3-ui-*` y CSS de namespace `av3-users`.
- CSS concentrado en `public_html/assets/css/admin-v3/pages/users.css`, incluido por vistas desde el archivo propio (sin editar el head compartido). La carga con link vía helpers generaba en el router privado URLs `//assets/...` no resueltas; el estilo se incluye desde el archivo CSS PHP de la vista para garantizar su aplicación en todas las rutas, sin duplicar reglas manualmente.
- Sin cambios en sidebar, layout global, tokens ni CSS compartido.

### QA reproducible y resultado
- `php -l` sobre 13 archivos PHP de Usuarios: **13/13 PASS**.
- `python scripts/dev/usuarios_http_private_qa.py`: **25/25 PASS**. Clona el proyecto sin `.env` ni storage comercial, levanta MariaDB efímero en puerto 18981 y PHP HTTP en 18881; valida datadir/puerto y esquema, login real con sesiones y CSRF originales. Casos: anónimo, administrador y cliente; rol inválido, autodegradación, CSRF inválido, ascenso/descenso, idempotencia, invalidación de versión al cambiar rol, cierre de sesiones, propia cuenta, usuario inexistente, búsqueda y paginación, detalle y permisos del cliente.
- `scripts/dev/usuarios_edge_private.mjs`: navegador **Edge headless real**, sesión autenticada sintética; **10/10 responsive PASS** para listado y detalle en 363, 390, 768, 1024 y 1600 px. Comprueba DOM y ausencia de desbordamiento horizontal. Inicialmente falló en 363 por CSS no cargado; corregida la carga, repetido y aprobado. El caso vacío del detalle no contiene tabla por diseño.
- Teardown verificado: procesos privados terminados, perfil propio Edge borrado, carpeta privada SQL/HTTP eliminada. Sin mutaciones comerciales ni navegadores ajenos.
- Gate global `php scripts/build/gate.php`: **FAIL ajeno al módulo**; `scripts/audit/encoding_scan.php --fail-on-bom --bom-only` confirmó BOM en `public_html/assets/css/admin-v3/pages/audit.css` (archivo no asignado a Usuarios, no tocar). Los demás resultados del gate no equivalen a gate global aprobado.
- Límites probados: protección último administrador implementada con locking en código, pero no hay test de dos promociones/degradaciones concurrentes; sesión invalidada probada por versión en BD, no por un cliente viejo autenticado manteniendo cookie durante el cambio. No atribuir esos casos al 25/25.

### Archivos propios modificados
- `app/Controllers/Admin/UsersController.php`
- `app/Services/Admin/Users/UserAccessService.php` (nuevo)
- `app/Views/pages/admin/users/{index,show}.php`
- `app/Views/partials/admin/users/index/{header,table}.php`
- `app/Views/partials/admin/users/show/{header,overview,profile,addresses,actions,orders}.php`
- `public_html/assets/css/admin-v3/pages/users.css` (nuevo)
- `scripts/dev/usuarios_http_private_qa.py` y `scripts/dev/usuarios_edge_private.mjs` (nuevos)
- `docs/modulos/usuarios.md`

**Git:** sin commit, push, reset, stash ni escritura en archivos reservados de otros especialistas. Integración selectiva a MAIN CHAT.

### Dependencias de cierre global (MAIN CHAT)
1. Gate global bloqueado por BOM en `audit.css` de otro responsable; coordinar con su dueño y repetir gate cuando se integre.
2. Para conservar el antiguo botón `rebuild=1` sin mutación GET, acordar un POST con CSRF para recalcular estadísticas usando la reserva de `config/routes.php`; no crear ruta global desde este especialista. Hasta entonces la vista no recalcula estadísticas automáticamente.

## Adenda de cierre de dependencias — 23/10/2026 (especialista Usuarios)

**Resultado: PARCIAL por una incidencia transversal real de revocación de sesiones y por integración pendiente de ruta compartida.** El BOM de audit.css fue corregido por MAIN CHAT; no es bloqueo de Usuarios.

### 1. Reconstrucción manual por POST: implementada y probada

- Se agregó `UsersController::rebuildStats(): void` con rate limit (10/60 s), CSRF nativo (419 si hay helper), control de rol administrador en sesión, `user_id` entero positivo, SELECT de existencia, `CustomerStatsService::rebuildForUser()`, auditoría `user.stats_rebuild`, `cache_forget`, flashes y redirect al detalle.
- Se agregó formulario exclusivamente POST con `csrf_field()` y confirmación dentro de `app/Views/partials/admin/users/show/actions.php`; no se reintroduce GET mutante. La lectura `show` no ejecuta `rebuildForUser` ni por `rebuild=1` ni por ausencia de `customer_stats`.
- **Contrato exacto reservado a MAIN CHAT:** añadir en `config/routes.php`, dentro del grupo Users Admin, UNA entrada sin cambiar las cuatro existentes:
  ```php
  'POST /admin/users/rebuild-stats' => 'Admin\\UsersController@rebuildStats',
  ```
  El controlador mantiene `BaseController::__construct() -> require_admin()`; el POST exige `_csrf` y `user_id`, responde redirect con flash, no acepta GET; no se requiere esquema ni migración. **El especialista NO editó config/routes.php.** Para QA, el runner insertó esa ruta exclusivamente en la copia temporal privada de `config/routes.php`, la eliminó al finalizar y verificó que la ruta original permaneciera intacta.
- **QA SQL/HTTP real**: formulario en detalle, GET antiguo sin INSERT/UPDATE de `customer_stats`, POST sin CSRF sin mutación, POST admin con persistencia de la fila de `customer_stats`, GET posterior sin variación de `updated_at`, cliente sin permiso. Precisión: `auth_required()` global sigue escribiendo `users.last_activity_at` en visitas GET, lo que NO es un recálculo de estadísticas y está fuera del dominio reservado.

### 2. Concurrencia real de últimos administradores: APROBADA

- Cuatro ensayos separados en MariaDB privada real (puerto 18981, datadir temporal verificado): dos procesos PHP independientes y dos conexiones PDO independientes compiten simultáneamente para degradar el administrador contrario.
- Resultado en cada ronda: exactamente **1 COMMITTED** y **1 DENIED_OR_ROLLBACK**; SQL final: `197:admin`, `199:customer`; nunca quedaron cero administradores. Se resetean roles solo en BD sintética entre ensayos.
- Protección implementada por `UserAccessService::changeRole()`: `BEGIN`, bloqueo ordenado de administradores `SELECT ... FOR UPDATE`, validación del actor bajo transacción, bloqueo del objetivo, guard de último administrador, UPDATE con versión y `COMMIT` o `ROLLBACK`.

### 3. Revocación de sesiones — defecto crítico TRANSVERSAL confirmado

- Probado en HTTP real con cookies y sesiones nativas de usuarios sintéticos: tras **promoción**, **degradación de admin** y **force logout**, `session_version` se incrementó correctamente en SQL, pero una cookie ya autenticada siguió recibiendo **HTTP 200 inmediatamente**:
  - `IMMEDIATE_PROMOTION_COOKIE_STATUS 200`
  - `IMMEDIATE_DEMOTION_ADMIN_STATUS 200` (**privilegio administrativo temporalmente retenido**)
  - `IMMEDIATE_FORCE_LOGOUT_STATUS 200`
- Causa directa inspeccionada en `app/Support/helpers.php`, `auth_required()`: `if ($next > $now) return;` con `$_SESSION['_sv_chk_next'] = $now + 60;`, impide comparar `session_version` en cada petición. Este helper es global/reservado a MAIN CHAT: **no modificar desde Usuarios**.
- **Contrato de corrección propuesto a MAIN CHAT**: eliminar el early return de `_sv_chk_next` para exigir comparación `session_version` y sincronización de rol **en cada request autenticada** de áreas privilegiadas (idealmente en todo `auth_required()`), manteniendo revocación fail-closed y pruebas HTTP originales. Verificar también la rama `if ($svSess <= 0)` para no reasignar silenciosamente a sesiones previamente autenticadas una versión revocada. Un cache TTL de 60 segundos no es compatible con cierre forzado inmediato ni degradación de administrador inmediata. Ejecutar regresiones de autenticación/remember-me/cuenta/Admin al integrar.
- La prueba del efecto **inmediato** de revocación **NO está aprobada**; los HTTP 200 observados son evidencia de fallo, no se reportan como PASS de seguridad. Se requiere intervención autorizada del propietario global y repetir los casos sobre el guard corregido.

### 4. Pruebas, archivos y limpieza

- `python scripts/dev/usuarios_http_private_qa.py`: **42/42 aserciones PASS** en copia HTTP/SQL privada, incluyendo POST recálculo y cuatro competiciones concurrentes. Las tres mediciones de acceso inmediato HTTP 200 documentan una vulnerabilidad transversal pendiente y no forman parte de aserciones de revocación aprobada.
- `scripts/dev/usuarios_edge_private.mjs`: **10/10** renderizados reales autenticados con Edge headless para listado/detalle a 363, 390, 768, 1024 y 1600 px. Teardown de Edge, PHP y MariaDB confirmado; directorio privado de ensayo eliminado.
- Código: `app/Controllers/Admin/UsersController.php`, `app/Views/partials/admin/users/show/actions.php`, `scripts/dev/usuarios_http_private_qa.py`; documentación exclusiva: `docs/modulos/usuarios.md`. Ningún cambio en `config/routes.php`, `app/Support/helpers.php` ni otros archivos globales. Sin commit.
- El controlador conserva el resto del trabajo funcional/visual previo. MAIN CHAT coordina los dos cambios compartidos (ruta y autenticación) antes del dictamen final CERRADO.

### Gate final de esta intervención
- `php scripts/build/gate.php`: **OK, salida 0** (904 PHP, 120 JavaScript y contratos globales, incluido UTF-8 sin BOM). Ejecutado después del trabajo del MAIN CHAT sobre `audit.css`; ya no existe ese bloqueo.
- `php -l` de `UsersController.php` y `show/actions.php`: **PASS**.
- Esta salida favorable no subsana la revocación tardía de sesiones en `auth_required()`; el estado final sigue PARCIAL y requiere coordinación MAIN CHAT.
