# MiTiendaPesca — Control vivo de preproducción
Versión 2.0 · 25/10/2026 · MAIN CHAT · **GO producción: NO**

## Protocolo de mantenimiento
Cada entrega/corrección: MAIN verifica resultado real y actualiza este tablero en la misma intervención, con fecha, commit, entorno, casos ejecutados, evidencias, defectos y responsable. No sustituir PASS de staging por PASS local. Nunca se consideran aprobadas pruebas diferidas. Los informes individuales mantienen sus antecedentes; no reescribir para borrar fallos.

| ID | Responsable | Estado actual | Próxima acción / condición de cierre |
|---|---|---|---|
| 01 Auth | 01 + MAIN | PARCIAL; 42/42 + 21/21 PASS reportados | Integrar servicio y decidir UX. Multi-sesión/remember-me y alta/perfil positivo -> ST-01/ST-02 |
| 02 Catálogo | 02 | PARCIAL; contratos reutilizables | Fijar solo defectos locales evidenciados; CRUD HTTP/UI -> ST-03 |
| 03 Inventario | 03 + MAIN | BLOQUEADO DEFECTO P1 INV03-EXP-01 | Corregir export CSV en código fuente + probar roundtrip; ST-04 para límites hosting |
| 04 Checkout | MAIN + 04 | BLOQUEADO DEFECTO P0 04-P0-01 | Integrar token idempotente en fuente; correr suite privada sin parche de clon; ST-05 posterior |
| 05–10 | Sin activar | SIN INFORMES | No asignar trabajo en esta fase; planificar más adelante |
| MP / ARCA / Correo | MAIN | DIFERIDO STAGING | Sandbox autorizado o deshabilitación verificada ST-06/ST-07; jamás API Correo productiva para QA |
| PHP / Hosting | MAIN | DIFERIDO STAGING | Hosting contratado, PHP parcheado, backup/restore y seguridad ST-08 |
| UI navegador | MAIN / especialistas | DIFERIDO STAGING parcial | Ejecución real y capturas representativas ST-09 |

## Registro de hitos
- 25/10/2026: cambio aprobado de estrategia por propietario: dos etapas, solo especialistas 01–04. No se han ejecutado pruebas nuevas por la actualización documental. Se conservan estados de informes; pendientes de staging NO son PASS.
- 25/10/2026: persistentes P0 Checkout y P1 Exportaciones; ninguno puede diferirse al hosting.

## Formato obligatorio de actualización
Fecha/Hora | HEAD/commit | Especialista | ID caso | Entorno local/staging | Comando/acción | PASS/FAIL/BLOQUEADO/DIFERIDO | Evidencia | Defecto/severidad | Fix/commit | Próximo responsable | Gate. Añadir nueva entrada por cada auditoría; conservar fallos originales.

## Auditoría MAIN — Cierre local tras entregas finales 01–04 (25/10/2026)

**Evidencia inspeccionada:** `RESULTADO_01.md` (116 líneas), `RESULTADO_02.md` (139), `RESULTADO_03.md` (132), `RESULTADO_04.md` (196), `git status` y códigos implicados identificados en informes. Las cifras son resultados reportados por especialistas, **no suites reejecutadas por MAIN en esta pasada**. Árbol HEAD al corte `2e1ef1c`.

- **01 — CIERRE LOCAL CONDICIONADO:** Usuario 42/42 + Auth 21/21 PASS reportado. Servicio Auth modificado aún no integrado. MAIN revisa UX sesión actual tras changePassword y corrección existente. Casos HTTP multi-browser y remember-me → ST-01; registro/alta de perfil/direcciones → ST-02. No reintentar runner bloqueado.
- **02 — PENDIENTE LOCAL FOCAL:** sin E2E nuevo HTTP/MariaDB. Hallazgo P2 de redirección externa potencial en `ProductsController::jsonOrRedirect()`; otro P2 potencial: `per/page` sin máximo; fijar seguro y probar contratos/funciones focales sin abrir un runner complejo. CRUD/imagen/variantes/navegador → ST-03 y ST-04.
- **03 — P1 BLOQUEADO EN FUENTE:** 35/35 PASS HTTP sobre clon con columnas ship_* NULL; fuente ImportsController sigue consultando v.ship_* inexistentes. MAIN autoriza **reserva exclusiva a 03 de `app/Controllers/Admin/ImportsController.php::export()`**, únicamente SELECT de dimensiones variante, con NULL AS ship_*. No expandir cambios. Repetir sobre fuente corregida en clon seguro, registrar PASS y MAIN integra selectivamente. Previsualización de CSV NO significa reimportación completa.
- **04 — P0 BLOQUEADO EN FUENTE:** nueva cadena 19/19+3/3+75/75 PASS con CandidateTokenFix y responsive previo 20/20. Archivo CheckoutOrderService fuente no modificado. **Reserva MAIN exclusiva sobre CheckoutOrderService y algoritmo de checkout_token**; aplicar solución idempotente estable en fuente, revisar implicaciones y ejecutar harness existente SIN CandidateTokenFix. El resto de positivos con terceros y navegación de hosting → ST-05/ST-06/ST-07.

**No se autoriza GO ni staging-ready hasta la integración de 03-P1 y 04-P0.** Pruebas trasladadas a staging continúan PENDIENTE, no PASS. No hacer commits de archivos de otros especialistas. Mantener solo especialistas 01–04 activos, 01 y 04 sin nuevas ejecuciones hasta que MAIN concluya dependencias.


## MAIN — Integración y ejecución real de cierre local (25/10/2026)
- **Commit 65990b9:** integrados selectivamente `CheckoutOrderService.php` (token estable sin stock mutable), `ImportsController.php` (dimensiones de variante NULL), `PasswordResetService.php` (token un solo uso transaccional, session_version) y `AddressRepository.php` (rechazo real de edición ajena). Los cuatro archivos pasaron `php -l` y `git diff --check` antes del commit. Importaciones 35/35 HTTP con código fuente corregido fue ejecutado por especialista 03, no reejecutado por MAIN tras commit.
- **P0 04-P0-01 — RESUELTO LOCAL en fuente:** MAIN ejecutó `powershell -File scripts/dev/run_checkout_http_private.ps1 -AccountAdmin -Responsive` **SIN -CandidateTokenFix** sobre clon privado; resultado exit 0: Checkout 19/19, concurrencia 3/3 con una orden/descuento único (stock 5→3), cuenta/admin/comprobantes 75/75, responsive CSS 20/20, procesos/puertos/temporales liberados. Log preservado `docs/QA_CHECKOUT_HTTP_PRIVATE_MAIN_SOURCE_PASS_20261025.log`, SHA256 `aba64e6933634870f4734fdb4d32e5386c7ba13eba9b04ae2b62d68d0629597a`. **No equivale a aprobación HTTPS staging ni cubre MP/ARCA/Correo real.**
- **P1 INV03-EXP-01 — RESUELTO LOCAL en fuente:** un solo cambio SELECT y 35/35 HTTP MariaDB del especialista 03 sin parche de clon, CSV export con simple+variante HTTP 200 y preview, no Apply de CSV recién exportado. Pendiente ST-04.
- **Commit afb1f4b:** correcciones focales MAIN en `ProductsController.php`: destino seguro por allowlist en respuesta no JSON de imágenes (sin confiar en Referer), tope 100 items por página y page clamp. `php -l` y `git diff --check` PASS. **NO se ha ejecutado prueba HTTP/MariaDB posterior del controlador**, se conserva ST-03 para E2E completo y regresión del redirect/paginación.
- **01 cierre condicionado:** 42/42+21/21 previos aceptados, cambios Auth integrados. `AUTH-UX-01` aún requiere decisión e inspección/corrección del controlador para experiencia coherente de la sesión que cambia contraseña; multi-navegador y remember-me siguen ST-01 y ST-02.
- **Puerta L:** bloqueantes P0 checkout y P1 export corregidos; faltan cierre UX Auth, revisión regresión focal de Catálogo y formalización final del conjunto de scripts/informes QA. **AÚN NO MARCAR LISTO PARA STAGING** hasta comprobar esos puntos o documentar excepciones aceptables. Producción NO GO.


## MAIN — Corrección AUTH-UX-01 (25/10/2026)
- **Commit 52c94d1:** `ChangePasswordController::update()` ya no redirige a `/cuenta` con sesión de versión obsoleta. Si el servicio confirma cambio, invoca `Auth::logout()`, crea sesión limpia para flash y redirige a `/login` con mensaje de reautenticación. Es decisión explícita: todas las sesiones, incluida la actual, requieren nuevo login; `PasswordResetService` ya incrementa `session_version` y revoca `user_tokens`. `php -l` y diff check PASS. **Prueba HTTP end-to-end de cambio de clave sigue ST-01, NO PASS LOCAL** por restricción del ejecutor.
- Se registran commits MAIN de correcciones focales: `65990b9`, `afb1f4b`, `52c94d1`. Cuatro informes especialistas y tablero integrados en `c5a7584`. Al cierre de este turno no se detectan P0/P1 locales conocidos abiertos en estas cuatro áreas, sujeto a que staging no descubra otros. Faltan validación HTTP focal de redirect/paginación de Catálogo y regresión Auth/change-password; ambas permanecen diferidas a staging en caso de no poder ejecutarse con harness permitido. **Estado: CIERRE LOCAL CONDICIONADO / PREPARACIÓN STAGING**, no GO producción ni PASS staging.


## CIERRE FINAL PUERTA L — MAIN (25/10/2026)
**LISTO PARA STAGING en el alcance LOCAL 01–04; PRODUCCIÓN NO GO.** Acta `ACTA_CIERRE_LOCAL_20261025.md`. Checkout real 19+3+75+20 PASS sin parche, Importaciones 35/35 reporte sobre fuente corregida, Auth regresiones históricas válidas con límites explícitos, Catálogo correcciones lint pero HTTP diferido. Gate global `scripts/build/gate.php`: 909 PHP, 120 JS, contratos regresión, `[gate] OK`, exit 0 tras corrección límite 375 líneas de ProductsController commit `cdc14a0`. Ejecución inicial exit 1 por 377 líneas preservada en acta. QA scripts `bb7aadf`. **No abrir nueva ronda local** salvo regresión focal por cambio. Deudas HTTP y hosting ST-01..ST-09 continúan PENDIENTE. Cambios ajenos de Ajustes/Auditoría/Facturación no integrados.


## Inicio tanda QA LOCAL 05–08 — 25/10/2026
**Cambio de dirección aprobado por propietario:** no activar staging ni contratar/usar hosting en esta etapa. Documento vigente `docs/preproduccion/PLAN_QA_LOCAL_05_10.md`; anteriores planes staging se conservan solo como referencia. Especialistas 01–04 conservan cierre local ya acreditado y limitaciones. **05 Pagos, 06 Facturación, 07 Ajustes/Ops, 08 Tienda pública: ACTIVADOS, SIN RESULTADOS TODAVÍA; informes esperados RESULTADO_05.md...RESULTADO_08.md.** 09 y 10 quedan pendientes para próxima tanda. No se ejecutaron pruebas nuevas al redactar mandatos. No usar proveedores reales; Correo Argentino productivo prohibido incluso consultas. MAIN único integrador.


## Auditoría MAIN de primera entrega 05–08 — 25/10/2026
**Dictamen general: TANDA 05–08 NO CERRADA / PARCIAL LOCAL.** HEAD de especialistas `6063ae7`. Se revisaron íntegramente `RESULTADO_05.md` a `RESULTADO_08.md` y `git status`. Cuatro informes nuevos sin commits ni cambios de implementación; NO hay evidencias de que se hayan ejecutado HTTP/MariaDB integrales ni navegación pública nueva.
- **05 Pagos:** 77/77 contratos PHP reejecutados, PASS contrato; transiciones persisted approved/pending/rejected, webhook duplicado, replay, conciliación/rollback SQL no ejecutados. Runner destructivo requiere DB privada específica, no correr sin verificar aislamiento. PARCIAL LOCAL.
- **06 Facturación:** 40/40 smoke SQLite en memoria PASS; 95/95 responsive histórico. Sin prueba fiscal de concurrencia, NC real sintética en MariaDB, permisos/CSRF HTTP ni flujo de errores ARCA simulado. PARCIAL LOCAL.
- **07 Ajustes/Ops:** sólo inspección de código/documentación, mapeo y reutilización de suite 155/155, navegación 132/132, Auditoría 105/105; **0 nuevas pruebas funcionales**. No tocar cambios ajenos pendientes; Correo Argentino prod prohibido. PARCIAL LOCAL.
- **08 Público:** 197 contratos/checks estáticos/sintéticos PASS; prueba HTTP localhost falló al conectar, sin navegador ni UI comprobada. Verificar aislamiento/entorno antes de atribuir defecto producto; PARCIAL LOCAL.
**Causa de desvío:** los mandatos permiten detenerse al carecer de infraestructura local, por lo que no garantizan completar los casos practicables. MAIN debe diagnosticar disponibilidad de runner/servidor privado autorizado; asignar ensayos concretos, con límite de complejidad y sin evadir controles. Si realmente no viable, diferir expresamente; nunca rebautizar contrato PASS como HTTP/E2E. No activar 09–10 ni dar L05–08 hasta resolución o decisión formal sobre los casos faltantes.


## MAIN — Diagnóstico de entorno QA local 05–08 (25/10/2026)
Herramientas presentes: PHP 8.2.12 CLI (NO apto prod), mysqld/mysql, vendor, schema, Python, Node. No listeners QA 188xx/189xx detectados al corte. Existen ejecutores privados Checkout 18897/18997, Ajustes 18887/18987, Auditoría 18883/18983, smoke Facturación SQLite y script webhook sintético potencialmente destructivo. Ninguno fue ejecutado por MAIN en este diagnóstico. Ver `DIAGNOSTICO_ENTORNO_QA_05_08_20261025.md`. Segunda ronda será focal, condicionada a aislamiento y sin cambios en Apache comercial. Estado 05–08 continúa PARCIAL LOCAL.


## MAIN — Auditoría de entrega final 05–08 (25/10/2026)
- **05 Pagos: CIERRE LOCAL CONDICIONADO.** Resultado 77/77 contratos ejecutados, sin nueva cobertura HTTP/SQL. Transiciones persistidas, conciliación y webhooks HTTP quedan NO EJECUTADOS (no PASS); no autorizar MP productivo. Sin defecto P0/P1 nuevo probado.
- **06 Facturación: CIERRE LOCAL CONDICIONADO.** Smoke 40/40 en SQLite y responsive histórico 95/95; transacciones y carreras fiscales MariaDB/HTTP, permisos y ARCA simulada integral NO EJECUTADOS (no PASS). ARCA productiva prohibida.
- **07 Ajustes/Ops: CIERRE LOCAL CONDICIONADO.** Se reutilizan pruebas históricas 155/155 Ajustes, 132/132 navegación, 105/105 Auditoría, sin nuevos ensayos propios de la tanda. Cambios ajenos sin commit siguen intactos y no se integran. Correo Argentino productivo prohibido incluso GET.
- **08 Tienda pública: CIERRE LOCAL CONDICIONADO, con evidencia nueva real.** Informe ampliado con HTTP aislado y Edge 55 vistas responsive (363,390,768,1024,1600), CSS real, sin overflow, variantes/foco/carrito sintético y acceso Checkout 1/4 sin comprar; además corrida privada de 40 vistas con incidencia final de arnés exit=1. Se preserva distinción: 55 vistas no prueban todos los clics ni funcionamiento real de login. Pendientes focales QA08-URL-01 (`//login` y form actions), QA08-AUTH-01 (429 login), QA08-CART-01 (POST carrito no acreditado) y QA08-ASSET-01 (imágenes de fixtures excluidas). No alterar helpers/rutas sin prueba causal y reserva; no inventar PASS.
- **Resultado MAIN:** cierres **del alcance local razonablemente viable**, con limitaciones y riesgos expresos. NO APROBACIÓN de tienda para producción ni certificación de esos flujos HTTP. No abrir otra tanda general 05–08; retomar solo defectos concretos o pruebas cuando exista servidor autorizado. Se continúa con especialistas 09 Seguridad y 10 Infraestructura, EXCLUSIVAMENTE en QA local. MAIN debe preparar mandatos 09 y 10 y auditar su entrega. Los cambios no confirmados de Ajustes/Auditoría/Fiscal permanecen separados.
