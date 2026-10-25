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
