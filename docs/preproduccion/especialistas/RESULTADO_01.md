# RESULTADO 01 — Usuarios y seguridad de acceso
Fecha: 25/10/2026. Responsable: especialista 01. Estado: PARCIAL; no equivale a GO de preproducción.

## Entorno y ejecución nueva verificable
Comando: `python C:\xampp\htdocs\mi-pesca\scripts\dev\usuarios_http_private_qa.py`.
Resultado observado: `USUARIOS_HTTP_PRIVATE_PASS 42/42`, exit code 0, duración 31.29 s. El runner confirmó clon privado sin .env ni almacenamiento comercial, instancia MariaDB privada con datadir `c:/users/matii/appdata/local/temp/mi_usuarios_http_qa_20261023/data`, y servidor HTTP privado. El runner informó `TEARDOWN processes: [(17440, 1), (15016, 1)]` y `TEARDOWN private dir removed`. Edge headless: `USUARIOS_AUTHENTIC_EDGE_RESPONSIVE_PASS 10/10`, perfil propio eliminado; anchos 363/390/768/1024/1600 en listado y detalle. No se abrió navegador personal.

## Matriz inicial caso → ruta → ASVS → evidencia → resultado → defecto
| Caso | Ruta/acción | ASVS (familia) | Evidencia | Resultado | Defecto |
|---|---|---|---|---|---|
| Login y CSRF | login; /admin/users | V2 autenticación, V3 sesión, V4 acceso | PASS 6-8 | PASS privado | ninguno observado |
| Acceso admin/cliente | /admin/users y /show | V4 control de acceso | PASS 9,21,27-29 | PASS privado | ninguno observado |
| Rol inválido, auto-degradación | POST /admin/users/role | V4, V5 validación | PASS 10-11 | PASS privado | ninguno observado |
| CSRF en roles | POST /admin/users/role | V4/V5 CSRF | PASS 12 | PASS privado | ninguno observado |
| Ascenso/degradación/idempotencia | POST /admin/users/role | V3/V4 | PASS 13-16 | PASS privado | ninguno observado |
| Revocación inmediata | rol, force-logout, cookies anteriores | V3 sesiones | HTTP 401 tras promoción, degradación y force logout; PASS 30-36 | PASS privado | incidencia histórica de TTL corregida, no reabrir |
| Recalcular estadísticas | /admin/users/show, POST /admin/users/rebuild-stats | V4/V5 | PASS 22-26,37 | PASS en clon; confirmar ruta global integrada | sin defecto demostrado |
| Paginación y detalle | /admin/users, /show | V4/V5 | PASS 19-21 | PASS privado | ninguno observado |
| Último administrador concurrente | POST /admin/users/role | V4 acceso, concurrencia | CONCURRENCY_RESULT 1-4; PASS 39-42 | PASS MariaDB privada | ninguno observado |
| Responsive | /admin/users, /show | accesibilidad/UI | Edge 10/10, perfil eliminado | PASS navegador privado | ninguno observado |
| Registro/validación email | rutas públicas por inventariar | V2 | sin ejecución nueva | PENDIENTE | no determinado |
| Recuperación contraseña | rutas públicas por inventariar | V2/V3 | sin ejecución nueva | PENDIENTE | no determinado |
| IDOR/BOLA transversal | perfil, direcciones, pedidos, API | V4 | control admin/cliente parcial | PENDIENTE ampliación | no determinado |
| Cookies seguras/rate limiting | auth y endpoints públicos | V3/V2 | solo pruebas focales existentes | PENDIENTE | no determinado |

ASVS indicado por familia orientativa; falta vincular requisitos identificados por versión específica. No afirmar cobertura total.

## Evidencia histórica reutilizada
`docs/PLAN_MAESTRO_PREPRODUCCION.md` §0.1: MAIN CHAT documentó 42/42 HTTP/MariaDB, revocación HTTP 401 y último administrador 4/4, integración `6c852bd`. `docs/modulos/usuarios.md` contiene historia de vulnerabilidad TTL y corrección posterior; prevalece el resultado actualizado. `docs/preproduccion/MATRIZ_E2E.md` mantiene pendientes de recuperación y permisos por registro.

## Pendientes y riesgos
1. Inventariar rutas públicas de registro, login, recuperación, perfil, direcciones, sesiones remember-me, AJAX/API y consumidores; cruzar inventarios CSV y controles efectivos en código.
2. Ejecutar E2E HTTP+SQL+navegador para recuperación y aislamiento entre clientes, con cuentas sintéticas y sin correo real; verificar expiración/replay de tokens, cookies y límites.
3. Validar ruta POST rebuild-stats en árbol integrado y regresiones después del congelamiento del candidato.
4. Leer y cruzar exhaustivamente documentación de seguridad y §7 ASVS/PHP con requisitos identificados. No atribuir PASS a casos no ejecutados.
5. Confirmar commit actual del árbol en una lectura Git no destructiva. El commit `6c852bd` es referencia histórica, no SHA verificado de esta corrida.

P0/P1 nuevos confirmados en esta corrida: ninguno. Riesgos sin probar: pendientes de evaluación, no equivalen a ausencia de vulnerabilidad.

## Seguridad, coordinación y teardown
Solo se ejecutó el runner privado existente; sin SQL comercial, proveedores externos, emails, pagos ni facturas. Sin commits, push, ramas, resets ni edición de código compartido. Runner informó cierre de dos procesos propios, eliminación de directorio privado y perfil Edge. La prueba no constituye E2E integral de las áreas 01, 08 y 17.

## Segunda intervención — 25/10/2026; árbol `2a1f692`
**Auditoría de código ampliada (verificación efectiva):** rutas existentes revisadas en `config/routes.php`: GET/POST `/login`, POST `/logout`, GET/POST `/registro`, GET `/verificar-email`, POST `/verificar-email/reenviar`, GET/POST `/olvide-clave`, GET/POST `/reset-clave`, GET `/cuenta/seguridad`, POST `/cuenta/seguridad/cambiar-clave`, POST `/oauth/google`, GET `/cuenta`, GET/POST `/cuenta/perfil`, GET/POST `/cuenta/direcciones*`, GET `/cuenta/pedidos*`, POST `/cuenta/sessions/clear`, y cinco rutas `/admin/users*` (incluye `POST /admin/users/rebuild-stats` ahora integrado). Este es un inventario focal, no inventario total de consumidores JS, OAuth y pedidos.

**Componentes inspeccionados:** `LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController`, `EmailVerificationController`, `PasswordResetService`, `EmailVerificationService`, `ProfileController`, `AddressesController`, `AddressService`, `app/Support/helpers.php::auth_required` (parcial), rutas y runner Usuarios privado. `php -l` ejecutado hoy en nueve archivos PHP (cinco controladores Auth, dos servicios Auth, ProfileController y AddressesController): **9/9 PASS**, solo sintaxis, no pruebas funcionales.

**Inspección controles:** login, registro, recuperación y mutaciones de perfil/direcciones llaman `verify_csrf_or_abort()`; login, registro y solicitudes/reset tienen límites de tasa/antibot. Direcciones utiliza `auth_required()` y repositorios restringidos por usuario (`findForUser`, update/delete/setDefault con `userId`). `auth_required()` consulta `session_version` y rol en BD por petición (sin TTL visible en el fragmento comprobado). Todos estos son contratos de código, no pruebas de explotación verificadas para rutas públicas.

**Hallazgo de seguridad potencial H-01 (P1 sujeto a prueba, NO confirmado mediante concurrencia):** `PasswordResetService::resetPassword()` invoca `validateToken()` ANTES de comenzar transacción y luego marca `used_at` con `UPDATE password_resets SET used_at=NOW() WHERE id=?` sin predicado `used_at IS NULL` ni bloqueo en lectura. Dos peticiones concurrentes podrían validar el mismo token y terminar ambas cambiando hash; la última contraseña ganaría. Debe reproducirse con dos conexiones privadas, corregirse dentro del servicio Auth bajo reserva y repetirse una prueba de token one-time en carrera.

**Hallazgo potencial H-02 (P1 sujeto a pruebas):** reset de contraseña en `PasswordResetService` actualiza `password_changed_at` y borra `user_tokens` pero no incrementa `users.session_version`. El guard global `auth_required()` consultado comprueba `session_version`, no se verificó que controle `password_changed_at`. Riesgo de mantener cookies nativas anteriores tras recuperación; reproducir HTTP con sesión anterior activa. El helper es compartido y pertenece a MAIN. La corrección conservadora esperada en Auth es invalidación de versión dentro de la misma transacción; no modificar sin reserva.

**Hallazgo potencial H-03 (P2 sujeto a pruebas):** `EmailVerificationService::verifyToken()` realiza SELECT fuera de transacción y marca token usado sin predicado condicional; dos validaciones concurrentes pueden responder éxito, aunque email quede verificado correctamente. Evaluar idempotencia semántica y test de replay concurrente.

**Intento de ampliar QA:** se solicitó crear un runner adicional para login, dirección, recuperación, CSRF, replay y cookie vieja en copia privada; la herramienta rechazó la operación con mensaje explícito de control de seguridad. **No se alteró el comando ni se intentó eludir el bloqueo.** No se ejecutaron estos casos; permanecen **BLOQUEADOS**, NO PASS. Se conservó la regresión previa 42/42 realmente ejecutada con exit 0 y teardown.

**Seguridad de plataforma (referencia Plan Maestro §7):** documentación de MAIN indica PHP CLI 8.2.12 y `session.upload_progress.enabled=1`, ambos incompatibles con su criterio de plataforma parcheada para lanzamiento. No se cambió php.ini, Apache ni entorno compartido; registrar como dependencia transversal bloqueante de producción, no vulnerabilidad explotada aquí.

**Límites pendientes:** no hay E2E público de registro/validación real, recuperación con token/cookie/carrera, Google OAuth sandbox, direcciones de dos usuarios con IDOR real, roles mayoristas, headers/cookies en servidor final, rutas API/cron, responsive público ni pruebas de emails (prohibidos sin sandbox). No se atribuyen PASS sin captura/SQL/HTTP. No se detectaron P0/P1 nuevos *confirmados* en esta intervención, pero H-01/H-02 requieren tratamiento prioritario de MAIN.

**Cambios:** únicamente este informe `docs/preproduccion/especialistas/RESULTADO_01.md`; sin modificación de aplicación, migraciones, pruebas reservadas o rutas, sin commit. La segunda intervención no inició procesos HTTP/SQL ni navegadores nuevos; sin recursos adicionales para desmontar.

## Verificación complementaria del riesgo H-02
Búsqueda de consumidores PHP de `password_changed_at`/`_pwd_changed_at` en `app/**` (PowerShell `Get-ChildItem ... | Select-String`): aparecen asignaciones en LoginController, GoogleController y ChangePasswordController, lecturas de presentación y escrituras de PasswordResetService; no apareció validación de marca temporal en `app/Support/helpers.php`. Esto **refuerza la hipótesis** de cookie nativa anterior no revocada por restablecimiento, aunque sigue faltando prueba HTTP en privado. `C:\xampp\php\php.exe -v` confirmó directamente PHP **8.2.12 CLI** en esta intervención. El intento de obtener ini por comando corto falló por quoting de PowerShell, por lo que el valor de `session.upload_progress.enabled` citado arriba sigue siendo únicamente evidencia documentada de MAIN, no revalidación propia.

**Confirmación directa del guard:** lectura íntegra de `helpers.php` líneas 1284–1410: el SELECT de `auth_required()` incluye `session_version,role,name,email,avatar_url`, **no** `password_changed_at`; ante `sv` distinto revoca, pero si `sv` de sesión es <=0 adopta la versión de BD (migración legacy). La actualización de password en el servicio no cambia versión: H-02 queda como **defecto de diseño estático confirmado**, sin explotación HTTP acreditada; prioridad P1 propuesta por exposición de sesiones anteriores. Requiere corrección autorizada del servicio Auth + regresiones de reset y remember-me. Evitar considerar el 42/42 de cambios de rol como cobertura de reset.

## Intervención prioritaria MAIN — 25/10/2026, commit base observado `bffd58c`

**Reserva/propiedad:** MAIN autorizó modificar exclusivamente servicios Auth necesarios. Se comprobó `git status --short` focal antes de editar: `PasswordResetService.php` no tenía cambios previos en ese momento. Se dejó intacto `helpers.php`, `config/routes.php`, controladores y otros módulos. Documento rector: `docs/preproduccion/REVISION_MAIN_RESULTADOS_01_04_20261025.md`.

### Correcciones reales implementadas
Archivo modificado: `app/Services/Auth/PasswordResetService.php`.
- H-01: `resetPassword` ahora abre transacción ANTES de consultar el hash del token, toma la fila con `SELECT ... FOR UPDATE` y verifica `used_at IS NULL AND expires_at > NOW()`. Consumo condicionado mediante `UPDATE ... WHERE used_at IS NULL AND expires_at > NOW()`, con `rowCount() === 1`, rollback al perder carrera.
- H-02: reset actualiza `users.session_version = COALESCE(session_version,0)+1` junto con hash y `password_changed_at` dentro de la MISMA transacción que marca el token usado y elimina `user_tokens`; comprueba fila de usuario actualizada.
- `changePassword` también incrementa `session_version` dentro de la misma transacción que actualiza contraseña y revoca `user_tokens`. **Comportamiento intencionado a revisar en UX:** el propio dispositivo que efectuó el cambio conserva cookie con versión antigua y deberá reautenticarse en la siguiente petición; `ChangePasswordController` no se modificó al estar fuera de los servicios autorizados.
- No se modificó `EmailVerificationService` por tratarse de hallazgo independiente no priorizado ni comprobado.

### QA efectivamente ejecutado después del cambio

| Tipo | Ejecución / ambiente | Resultado verificable |
|---|---|---|
| Sintaxis | `C:\xampp\php\php.exe -l app\Services\Auth\PasswordResetService.php` | PASS |
| Revisión diff | `git diff --check`, `git diff --stat` focal | Sin errores de diff; 1 archivo, 37 inserciones/13 eliminaciones al corte |
| Usuarios + roles + cierre sesiones | `python scripts/dev/usuarios_http_private_qa.py`, copia sin .env, MariaDB privada puerto 18981 y PHP HTTP 18881 | **42/42 PASS**, exit 0, 31.49s; Edge 10/10, último admin 4/4, cookies rol/force logout HTTP 401 |
| Auth/recuperación + IDOR | `python scripts/dev/usuarios_auth_security_private.py`, copia sin .env, MariaDB privada puerto 18987 + PHP HTTP 18887 | **21/21 PASS**, exit 0, 16.18s |
| Restablecimiento por HTTP | `GET/POST /reset-clave` con token generado en DB privada y CSRF original | Token válido renderiza, CSRF inválido 419, reset correcto persiste y consume token, replay no cambia versión: PASS |
| Revocación cookie tras reset | Cookie cliente autenticada ANTES del reset, GET /cuenta inmediatamente después | `RESET_OLD_COOKIE 401`: PASS |
| Concurrencia real token | Dos procesos PHP distintos y dos PDO independientes, mismo token privado, dos passwords diferentes | `RESET_CONCURRENCY [('REJECTED',''),('SUCCESS','')]` (otra corrida orden inverso), 1 éxito/1 rechazo y una sola fila usada: PASS |
| Perfil y direcciones | GET autenticado /cuenta/perfil y /cuenta/direcciones; POST sin CSRF | Renderizados 200; perfil sin CSRF 419; dirección sin CSRF 419: PASS |
| Aislamiento direcciones | Dos usuarios sintéticos, registro domicilio para el otro titular en SQL privado; GET editar y POST eliminar desde sesión cliente | GET denegado; POST no borra fila de otro usuario, SQL comprueba que permanece: PASS |

**Fixture:** `database/schema.sql` no incorpora `password_resets`; primera ejecución del runner nuevo llegó a login, falló con tabla ausente y terminó con procesos parados/temporal conservado. Se comprobó que los puertos privados estaban libres, se eliminó solo el directorio temporal propio y se ajustó el runner para aplicar la migración existente `database/migrations/20251219_01_password_reset.sql` únicamente a MariaDB privada; las dos ejecuciones posteriores culminaron PASS con teardown. Las credenciales son aleatorias del runner; no se consulta .env comercial, no se envía email externo ni se utilizan pagos/ARCA/MP.

**Scripts nuevos:** `scripts/dev/usuarios_auth_security_private.py` (generador de ejecutor aislado sobre bootstrap conocido); el runner dinámico en TEMP se elimina/reemplaza en corridas futuras, no forma parte del código comercial. Su alcance exacto es 21 aserciones, no un E2E completo de cuenta.

### Pendientes y criterios de cierre
1. **Cambio autenticado y remember-me aún NO acreditados HTTP/SQL**: se intentó ampliar el runner para cubrir dos cookies, persistencia y eliminación `user_tokens`, pero el control de seguridad de la herramienta rechazó la edición. No se eludió esa restricción. `changePassword` pasó revisión estática/lint, no E2E focal. MAIN debe coordinar la prueba/UX de logout del dispositivo actual.
2. Registro público, validación email/expiración, OAuth Google sandbox, recuperación solicitada por email sin salida externa, creación/edición válida de direcciones, perfil persistido, roles mayorista, privacidad de pedidos/facturas y comparación detallada de cookies/headers quedan pendientes de E2E ampliado.
3. Concurrencia de cambio de password (no solo reset) y casos de token expirado/usuario eliminado no se ejecutaron; la cobertura de reset es concurrencia exitosa de dos PDO reales.
4. H-03 email verification concurrente sigue pendiente. PHP CLI 8.2.12 permanece debajo del criterio del Plan Maestro y requiere intervención de infraestructura MAIN.
5. Antes de producción: regresión post-integración sobre commit candidato congelado y seguridad de sesiones ante remember-me/OAuth. **NO GO / ÁREA PARCIAL** hasta completar.

**Teardown final:** suite Usuarios `TEARDOWN processes [(13280,1),(2000,1)]`, directorio privado eliminado, perfil Edge borrado. Suite Auth `TEARDOWN processes [(7564,1),(23648,1)]`, directorio privado eliminado; no se cerraron navegadores ajenos. Ningún commit, branch change, push o escritura comercial.

## Cierre focal solicitado por MAIN — 25/10/2026 14:06 (continuación)
**Base:** `bffd58c`; no se reinició auditoría. MAIN aceptó previamente 42/42 Usuarios y 21/21 Auth. `git status` confirmó el servicio Auth modificado por esta intervención anterior, runner nuevo y este resultado sin integrar.

**Nueva regresión ejecutada:** `C:\xampp\php\php.exe -l app\Services\Auth\PasswordResetService.php` PASS; `git diff --check -- app/Services/Auth/PasswordResetService.php` sin errores (advertencia Git de conversión LF/CRLF, no fallo); `python scripts/dev/usuarios_auth_security_private.py` **21/21 PASS, exit 0, 21.14 s**, puerto privado 18987/datadir privado coincidentes, HTTP privado 18887, sin .env comercial. Pruebas observadas: login autenticado, perfil/direcciones, CSRF 419, bloqueo IDOR GET/POST, reset con token válido y CSRF nativo, revocación de cookie vieja `RESET_OLD_COOKIE 401`, replay, dos procesos concurrentes `RESET_CONCURRENCY [('REJECTED',''),('SUCCESS','')]`. Teardown: `[(13040,1),(11760,1)]`, directorio privado eliminado.

**Bloqueo focal real:** la operación de ampliación del runner para verificar por HTTP el cambio autenticado, segunda cookie y remember-me fue rechazada por el control de seguridad de Desktop Commander. No se eludió ese bloqueo. Por consiguiente, esos casos **NO tienen PASS HTTP nuevo**. Del mismo modo no se ejecutó el flujo positivo de registro y edición/alta de perfil/dirección; los casos existentes de perfiles/direcciones cubren render, CSRF e IDOR negativo. El servicio cambia `session_version` durante `changePassword()`, pero el controlador actualmente actualiza `_pwd_changed_at` sin sincronizar `$_SESSION['sv']`; como `auth_required()` compara versión en cada request, se espera reautenticación también del dispositivo que cambió la contraseña. No se modificó el controlador, reservado fuera del alcance explícito.

**Cambios nuevos en esta fase final:** únicamente esta adenda en el informe; no hubo nueva modificación de código ni ampliación del runner por el bloqueo indicado. Se mantienen las correcciones ya aceptadas de `PasswordResetService`.

**DICTAMEN: BLOQUEADO**, no APTO PARA REVISIÓN MAIN según el criterio solicitado, porque falta evidencia HTTP/MariaDB reproducible de cambio autenticado, cookie secundaria y remember-me, así como circuitos críticos positivos de registro/perfil/direcciones. No afirmar ausencia total de P0/P1 sin esas comprobaciones; no se confirmó un nuevo P0/P1 en las pruebas efectivamente ejecutadas. MAIN debe coordinar el entorno/ejecutor autorizado para esas pruebas y decidir el contrato UX del dispositivo que cambia la clave. Sin commits, sin SQL comercial, sin uso de servicios productivos.

## Cierre de etapa LOCAL — 25/10/2026 — Estado: CIERRE LOCAL CONDICIONADO

**Marco y prevalencia:** esta sección aplica `docs/preproduccion/PLAN_DOS_ETAPAS_LOCAL_STAGING.md` (Plan Maestro V2) y `docs/preproduccion/CONTROL_VIVO.md`. Sustituye el **dictamen operativo** «BLOQUEADO» de intervenciones anteriores por **CIERRE LOCAL CONDICIONADO** exclusivamente para el alcance local autorizado; **no** transforma pruebas pendientes en PASS ni otorga GO de staging o producción. MAIN conserva integración por commit selectivo, control de regresiones y aceptación final. No se ejecutaron nuevas suites ni se modificó código en esta etapa documental.

### PASS LOCAL con evidencia reproducible

| ID | Alcance probado | Evidencia existente | Estado / límite |
|---|---|---|---|
| LOC-01-A | Autenticación/roles, CSRF, autorización admin/cliente, revocación ante cambio de rol y force logout, último administrador concurrente | `python scripts/dev/usuarios_http_private_qa.py`; `USUARIOS_HTTP_PRIVATE_PASS 42/42`; cookies previas HTTP 401; carrera último admin 4/4; salida 0 y teardown | **PASS LOCAL**. Cubre casos específicos de Usuarios, no cambio de contraseña |
| LOC-01-B | Navegación responsive de listado y detalle Usuarios | Edge headless `USUARIOS_AUTHENTIC_EDGE_RESPONSIVE_PASS 10/10`; 363/390/768/1024/1600; perfil temporal eliminado | **PASS LOCAL** en clon privado |
| LOC-01-C | Reset HTTP con token generado en base privada: formulario y CSRF, consumo, replay | `python scripts/dev/usuarios_auth_security_private.py`; `AUTH_SECURITY_PRIVATE_PASS 21/21`; CSRF inválido 419; un reset válido; replay sin incremento adicional de `session_version`; exit 0, teardown | **PASS LOCAL**; no prueba recepción de emails |
| LOC-01-D | Reset concurrente sobre mismo token | Dos procesos PHP/PDO independientes: `RESET_CONCURRENCY [('REJECTED',''),('SUCCESS','')]`; token persistido como usado una sola vez | **PASS LOCAL** |
| LOC-01-E | Revocación de cookie anterior al reset | Cliente autenticado antes del reset: `RESET_OLD_COOKIE 401`, `session_version` incrementada | **PASS LOCAL**; no cubre cambio desde dos navegadores |
| LOC-01-F | Perfil/direcciones: acceso autenticado, CSRF y controles horizontales negativos | HTTP 200 en vistas; POST CSRF inválido 419; GET editar dirección ajena rechazado; POST eliminar ajena sin borrar registro SQL del otro usuario; casos dentro de 21/21 | **PASS LOCAL focal**; no sustituye CRUD positivo |
| LOC-01-G | Corrección P1 en fuente y sintaxis | `app/Services/Auth/PasswordResetService.php`: token `FOR UPDATE`, consumo condicionado con `rowCount`, actualización transaccional de `session_version` y borrado `user_tokens`; `php -l` PASS y `git diff --check` sin errores | **CORREGIDO LOCAL, PENDIENTE INTEGRACIÓN MAIN**; el cambio autenticado no recibió E2E focal |

**Aislamiento y logs:** suites ejecutadas anteriormente contra copias privadas sin `.env` comercial, MariaDB con datadir/puertos independientes y cuentas sintéticas; cierre de procesos y eliminación de temporales confirmado. Conservar evidencias y fallos documentados en las secciones precedentes; esta reclasificación no los borra.

### DIFERIDO STAGING: escenarios, esperado y evidencia exigida (ninguno es PASS)

| ID | Criticidad / escenario y pasos requeridos en staging | Resultado esperado | Evidencia necesaria | Responsable / gate |
|---|---|---|---|---|
| ST-01-A | **Alta**. Dos navegadores/dispositivos con cookies separadas del mismo cliente; autenticarse en ambos; cambiar clave desde A; intentar petición autenticada y mutación desde B | B pierde acceso inmediatamente (401 JSON o redirección HTML sin mutación); credencial antigua deja de funcionar y nueva funciona | Fecha, commit, PHP/DB staging, trazas HTTP y Set-Cookie/Cookie redactadas, código de respuesta, SQL `session_version`, capturas de ambos navegadores | MAIN + 01 / S |
| ST-01-B | **Alta**. Emitir remember-me en dos sesiones, cambiar contraseña; presentar cookie persistente previa y probar reingreso | Tokens antiguos eliminados de `user_tokens`; cookie remember-me anterior no reautentica; nuevos accesos requieren credencial vigente | Consultas SQL antes/después con identificadores ficticios, headers/cookies redactados, logs de autenticación sin secretos, HTTP observado | MAIN + 01 / S |
| ST-01-C | **Alta**. Observar el propio navegador A después del cambio exitoso, redirección y siguiente navegación; abrir otra pestaña y reintentar | Comportamiento deliberado y consistente (logout con mensaje claro, **o** continuidad si MAIN sincroniza de forma segura versión/sesión); sin aparente éxito seguido de error desconcertante ni sesión obsoleta | Capturas de secuencia, códigos/Location HTTP, versión SQL, decisión UX documentada y verificación funcional | MAIN / S; decisión local AUTH-UX-01 |
| ST-02-A | **Alta**. Registrar cuenta nueva con datos válidos e inválidos, duplicado de email y CSRF incorrecto; probar primer login/verificación, sin correo real | Alta única; validación 422/errores por campo; CSRF 419; datos y aceptación de términos persistidos; verificación por token sintético/sandbox, replay/expiración rechazados | Requests/responses redactadas, filas SQL de usuario/verificación, logs del transporte de correo sandbox, capturas del formulario | MAIN + 01 / S |
| ST-02-B | **Alta**. Solicitar recuperación desde UI por email existente e inexistente en mail sandbox; seguir token recibido, probar caducidad, CSRF, replay y login posterior | Respuesta neutra sin enumeración; token enviado **solo** a sandbox, caduca/se usa una sola vez; clave antigua deja de servir; sesiones anteriores revocadas | Bandeja/captura del correo sandbox redactada, HTTP/SQL antes/después, hash/token nunca expuesto en informe, estado de cookies | MAIN + 01 / S |
| ST-02-C | **Media/alta**. Editar perfil propio válido e inválido, guardar nombre y datos, refrescar/reautenticar; intentar variar `user_id` en POST para afectar a otro cliente | Solo titular actualizado; datos persisten; validaciones y CSRF correctos; otro perfil intacto | UI y HTTP de ambos titulares, SQL `users`/`user_profiles` antes/después, 403/419 cuando corresponda | MAIN + 01 / S |
| ST-02-D | **Alta**. Crear, editar, marcar principal y eliminar direcciones propias; luego intentar leer/editar/eliminar/fijar principal una dirección ajena cambiando `id`; repetir sin CSRF y con entradas inválidas | CRUD propio persistente y consistente; IDOR bloqueado sin alteración ajena, CSRF 419; direcciones default coherentes | Capturas, tráfico HTTP con IDs sintéticos, SQL `user_addresses` para ambos titulares antes/después, validaciones y códigos 403/404/419 según contrato | MAIN + 01 / S |
| ST-02-E | **Alta**. Google OAuth con cuentas y credenciales de sandbox autorizadas, login/retorno/cancelación, vinculación, sesión y cierre; no usar secretos ni usuarios reales | Estado OAuth validado, sin account takeover ni open redirect, rol efectivo correcto, revocación acorde a contrato | Logs redactados, requests/callback autorizados, DB sintética, navegador HTTPS y pruebas de permisos/sesiones | MAIN + 01 / S |

**Precondiciones comunes de todos los diferidos:** staging privado HTTPS con PHP mantenido/parcheado, MariaDB sintética aislada, despliegue de commit congelado, URLs y cookies de prueba, mail/OAuth sandbox cuando proceda, sin llamadas productivas. Cada caso requiere ejecución real antes del gate S; en local permanece **DIFERIDO STAGING**, nunca «aprobado».

### DEPENDENCIA MAIN — AUTH-UX-01
- Revisar el `diff` de `PasswordResetService.php` e integrar de forma selectiva en fuente bajo commit MAIN, conservando bloqueo/consumo único, incremento `session_version` y borrado remember-me.
- Determinar el contrato UX de `ChangePasswordController::update()`: actualmente registra `_pwd_changed_at` y regenera ID, pero no sincroniza `$_SESSION['sv']` con la versión incrementada. Esto puede cerrar la **misma** sesión en la siguiente petición. MAIN debe elegir explícitamente entre reautenticación inmediata con mensaje claro o mantener la sesión actual de forma coherente y segura, sin relajar la revocación de otras sesiones. La prueba multi-sesión definitiva es ST-01.
- MAIN actualiza `CONTROL_VIVO.md`, revisa los gates y gestiona cualquier P0/P1 confirmado en fuente antes de intentar LISTO PARA STAGING. El cierre local condicionado de 01 no resuelve P0/P1 de otros módulos ni autoriza producción.

**DICTAMEN FINAL ESPECIALISTA 01: CIERRE LOCAL CONDICIONADO.** Se cierra exclusivamente la etapa de ejecución local de Usuarios con evidencias declaradas; quedan ST-01/ST-02 diferidos y AUTH-UX-01 a cargo de MAIN. Sin nueva ejecución, sin cambios de código, sin commits. El especialista queda inactivo hasta una petición expresa de regresión focal por MAIN.
