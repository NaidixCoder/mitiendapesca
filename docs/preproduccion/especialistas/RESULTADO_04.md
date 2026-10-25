# Especialista 04 — Pedidos, clientes y checkout

Fecha: 2026-10-25 (Argentina). Commit base observado: `2a1f692`. Estado: **EN CURSO / NO GO**. Sin commits, sin cambios de código, sin operaciones sobre base comercial.

## Alcance y evidencia preliminar

Leído el mandato `04_pedidos_checkout.md`; inspeccionados el plan maestro, la coordinación y las rutas reales `config/routes.php`. El plan maestro registra 27/27 contratos SQLite de catálogo/checkout, 40/40 SQL transversal Importaciones–Atributos–B1–Checkout/Stock y pruebas sintéticas de pago e idempotencia. **Son antecedentes declarados, no pruebas HTTP de compra completa ni PASS reejecutados por este especialista.** Queda pendiente correlacionar cada contrato con su script y commit exacto.

Git `status --short` muestra modificaciones de otros especialistas en Ajustes, Auditoría, Facturación y archivos de evidencia; no se tocaron. Los inventarios disponibles incluyen `INVENTARIO_RUTAS_ADMIN.csv` y `INVENTARIO_RUTAS_ADMIN_DETALLADO.csv`.

## Matriz preliminar (sin extrapolar cobertura)

| Caso | Ruta real / componente | ASVS 5 (familia a mapear) | Evidencia actual | Estado |
|---|---|---|---|---|
| 04-C01 | GET /carrito, POST /carrito/agregar, actualizar, quitar, seleccion, vaciar | validación, CSRF, integridad | Rutas verificadas; contrato histórico pendiente de identificación | PENDIENTE HTTP+SQL |
| 04-C02 | POST /carrito/repeat/{merge,replace,cancel}, /carrito/merge/{unify,replace,discard} | autorización, integridad | Rutas verificadas | PENDIENTE |
| 04-C03 | GET /checkout, POST /checkout/confirmar, /checkout/transferencia | sesión, CSRF, lógica de negocio, transacciones | Rutas verificadas; SQL transversal 40/40 histórico no crea pedido | PENDIENTE E2E |
| 04-C04 | POST /checkout/envio/{calcular,seleccionar}, /checkout/direccion/seleccionar | validación, IDOR, integridad | Rutas verificadas | PENDIENTE |
| 04-C05 | POST /checkout/stock/preflight, /checkout/pago/seleccionar | concurrencia, idempotencia, stock | Rutas verificadas; pagos sintéticos históricos | PENDIENTE E2E; coordinar 03/05 |
| 04-C06 | GET /cuenta/pedidos, /ver, /factura; POST /repetir, /direccion/guardar, /factura-a/solicitar | autorización por objeto, CSRF | Rutas verificadas | PENDIENTE |
| 04-C07 | POST /cuenta/pedidos/comprobante/subir; GET /descargar, /ver | archivos, autorización por objeto | Rutas verificadas | PENDIENTE |
| 04-C08 | GET /cuenta/direcciones y formularios; POST /crear, /guardar, /eliminar, /principal | IDOR, CSRF, validación | Rutas verificadas | PENDIENTE |
| 04-C09 | GET /admin/orders, /show; POST /mark-paid, /status, /shipping | RBAC, CSRF, concurrencia, estados | Rutas verificadas | PENDIENTE |
| 04-C10 | POST /admin/orders/arca/emit, /emit-nc; Correo Argentino track/import | integraciones, autorización, idempotencia | Rutas verificadas | FUERA de pruebas externas sin autorización; coordinación fiscal/envíos |
| 04-C11 | POST /checkout/mp/{preference,payment,brick-failed}, /checkout/pagar; GET /pago/resultado | pago, webhook, replay, idempotencia | Rutas verificadas; contrato MP histórico | PENDIENTE; coordinación 05 |

## Ejecución y límites

- Inspección de repositorio: PASS, solo lectura. Commit `2a1f692` y estado Git registrados.
- HTTP autenticado, navegador, SQL privado, concurrencia y teardown: **NO EJECUTADOS**. No se ha levantado todavía un clon descartable ni verificado datadir/puerto privado; por seguridad no se ejecutan escrituras ni pagos en XAMPP activo.
- Ningún defecto explotable confirmado aún; **no** confundir ausencia de hallazgos con ausencia de vulnerabilidades.

## Próximos pasos obligatorios

Leer en profundidad los protocolos, documentación de módulos y seguridad, identificar controladores/servicios/repositorios y todas las rutas indirectas; mapear ASVS con IDs concretos; construir clon sin `.env` comercial, MariaDB privada y cuentas sintéticas; capturar HTTP/UI/SQL para camino feliz, roles, CSRF, IDOR, validaciones, concurrencia, rollback e idempotencia. Coordinar con especialistas 03 y 05 antes de escenarios de stock y pagos. Documentar defectos con severidad y solicitar reserva antes de editar código. Verificar teardown y repetir regresiones afectadas.

**Decisión provisional:** NO GO por falta de evidencia E2E transaccional completa, no por fallo funcional demostrado.

## Ejecución real adicional — 25/10/2026, commit `2a1f692`

### Contratos reejecutados (PHP CLI local; no equivalen a HTTP)

| Script `scripts/build/` | Observado | Salida |
|---|---|---|
| `categories_checkout_b1_contract.php` | PASS | 31 contratos, exit 0 |
| `checkout_intent_reuse_contract.php` | PASS | 12 aserciones, exit 0 |
| `checkout_payment_frontend_contract.php` | PASS | 12 checks, exit 0 |
| `checkout_payment_integrity_contract.php` | PASS | 26 checks, exit 0 |
| `checkout_payment_orphan_contract.php` | PASS | 12 checks, exit 0 |
| `orders_index_contract.php` | PASS | filtros/paginación/vista/CSS, exit 0 |
| `transversal_catalog_checkout_contract.php` | PASS | 27 checks SQLite/normalizador, exit 0 |

### HTTP+MariaDB privada: una prueba completa ejecutada

Comando: `& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1'`. El runner verificó clon sin `.env` comercial, MySQL privado `127.0.0.1:18997` y datadir temporal, PHP privados `18897/18896`, sesiones y cuenta sintéticas. Fixture inicial: 0 órdenes. **Resultado global exit 1**, por falla de la segunda suite; no registrar PASS general.

`checkout_http_real_authenticated.py`: **19/19 PASS**, marcador `CHECKOUT_AUTHENTIC_HTTP_MARIADB_PASS 19`. Evidencia observada: checkout anónimo denegado, login y token CSRF genuino, CSRF inválido rechazado en login/preflight, IDOR de dirección rechazado, B1 inactivo, stock insuficiente, recuperación de stock, POST real de transferencia 302, orden e ítem persistidos, stock 5→3, estado `pending_payment`, replay sin segunda orden ni segundo descuento. Esto acredita HTTP+SQL real de este flujo **con entorno sintético**; no acredita MP ni toda la experiencia de navegador.

`checkout_http_real_concurrent.py`: **FAIL** antes de la operación concurrente efectiva. Dos sesiones diferentes reciben HTTP 302 hacia `/checkout`, ambas con mensaje `B1_UNAVAILABLE`; snapshot SQL final: `orders=0`, `order_items=0`, `inventory.stock=5` (esperado `1,1,3,pending_payment`). El fixture `race_reset` restablece órdenes, ítems, stock y línea del carrito, pero el test de concurrencia no pasó. Evidencia original: `docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log` (no incluir automáticamente: revisar datos antes de integrar). Este resultado **no demuestra doble descuento ni corrupción**; existe defecto o precondición insuficiente en la suite que impide acreditar concurrencia HTTP. Clasificación temporal **P1 de cobertura/gate** hasta distinguir problema del fixture frente a funcionalidad. Diagnóstico: revisar estado completo de carrito/visibilidad B1 al reiniciar carrera y su lectura por sesiones nuevas; agregar aserción de preflight B1 antes del `Barrier`; no modificar código compartido sin reserva.

**Teardown reportado:** `[QA] Private test folders and ports cleaned.` Runner detuvo procesos e indicó directorios y puertos liberados. No se realizaron cobros, ARCA ni correos reales. No se ejecutó navegador responsive.

### Dependencias y controles verificados por inspección

- `CheckoutProductEligibilityGuard::checkItems()` valida id/cantidad, producto activo, árbol de categorías si flag habilitado y correspondencia variante-producto; con `lock=true` exige transacción y utiliza `FOR UPDATE`.
- `CheckoutIntentService::createIntent()` verifica disponibilidad y que dirección pertenezca al usuario antes de crear el intento.
- `CheckoutOrderService::confirm()` vuelve a cargar carrito/selección y valida dirección propia. **Inspección parcial de implementación, no certificación total de todos sus métodos.**
- Riesgo adicional: el mensaje `B1_UNAVAILABLE` del diagnóstico de sesiones presenta secuencias `est\u00c3\u00a1n` (mojibake) en la evidencia HTTP; corroborar codificación en origen versus visualización del log antes de registrar defecto UI.

### Limitaciones actualizadas y coordinación

Aún pendientes: HTTP para altas/ediciones de direcciones, seguimiento/cuenta, estados/admin, carga de comprobantes, controles de rol admin/cliente, responsive en navegador, concurrencia HTTP corregida y regresión, pago MP/sandbox autorizado (especialista 05), stock transversal (03), y auditoría ASVS requisito por requisito. No hubo corrección de código: el mandato vigente reserva solamente este resultado; cualquier cambio funcional o de fixture compartido se eleva a MAIN con archivo y justificación concreta. **NO GO** hasta resolver corrida concurrente y gates integrados restantes.

### Repetición focal independiente — concurrencia HTTP privada

Comando `& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -RaceOnly`, mismo commit y fixture nuevo, **exit 0**. Marcador `CHECKOUT_REAL_HTTP_CONCURRENCY_PASS 3`: dos sesiones autenticadas independientes, HTTP paralelo real mediante dos servidores PHP, una sola orden/ítem y decremento de stock 5→3 sin duplicados. Limpieza de directorio temporal y puertos confirmada por runner. **Contraste importante:** concurrencia PASS cuando se prueba sola, pero FAIL por B1 tras las 19 pruebas previas en una misma secuencia integral. Esto reduce sospecha de fallo intrínseco de bloqueo de órdenes y eleva la necesidad de investigar contaminación de estado del fixture o regresión inter-escenario. No afirmar que la corrida integral quedó aprobada.

La ejecución focal sobrescribe `docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log` con marcador PASS; por eso la evidencia textual del fallo de la primera corrida queda preservada arriba en este informe. Es imprescindible guardar logs separados por ejecución al modificar el runner, previa reserva. En la segunda ejecución no se creó archivo de código nuevo ni hubo commits.

### Validación estática adicional y teardown independiente

Se ejecutó `php -l` sobre 20 archivos PHP de controladores Checkout/Cart/Account Orders/Addresses/Admin Orders, servicios Checkout/Orders y repositorios Orders: **20/20 sin errores de sintaxis**, ejecución CLI PHP 8.2.12; no se extrapola a ausencia de defectos de seguridad o lógica. Verificación separada: puertos QA `18997`, `18897`, `18896` sin listeners y `%TEMP%\mi_checkout_http_private_qa` inexistente tras la repetición. Estado de infraestructura QA: teardown **PASS**.


## 25/10/2026 — Revisión MAIN: diagnóstico exhaustivo, defecto P0 y ampliación HTTP

**Corte de código:** HEAD observado `bffd58c` (MAIN integró documentación mientras corrían las pruebas; origen inicial `2a1f692`). Sin commits ni push del especialista. Revisión leída: `docs/preproduccion/REVISION_MAIN_RESULTADOS_01_04_20261025.md`. Todos los ensayos de escritura ejecutados exclusivamente en clon descartable sin `.env` comercial, MariaDB localhost puerto 18997, datadir `%TEMP%/mi_checkout_http_private_qa/data`, PHP localhost 18897/18896, usuarios e imágenes ficticios y correos `.test`. No se tocó MP, Correo Argentino, ARCA ni correo productivos.

### Defecto 04-P0-01 — Doble pedido real y descuento duplicado al confirmar transferencia concurrente (P0, BLOQUEANTE)

**Reproducción:** el runner `scripts/dev/run_checkout_http_private.ps1`, con fixture actualizado, ejecutó 19 pruebas HTTP exitosas seguidas de dos POST reales simultáneos `/checkout/transferencia` con sesiones independientes para el comprador 197. Se obtuvieron dos HTTP 302 a pedidos diferentes (`id=2` e `id=3`), SQL `orders=2`, `order_items=2`, stock **5→1** (esperado 1 pedido y 5→3). Evidencia inmutable: `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_RACE_DUPLICATE.log`; salida global exit 1. Esto **es un defecto real de aplicación**, no solo contaminación de fixture.

**Causa técnica confirmada:** `CheckoutOrderService::confirm()` deriva `checkout_token` desde JSON del array completo `effectiveCart['items']`, que contiene `stock` variable; el primer POST reduce stock y cambia el hash calculado por el segundo. Aunque hay bloqueo `SELECT users FOR UPDATE` y reconsulta de orden por token, los dos hashes difieren; la transacción confirma ambos pedidos. `orders.checkout_token` en `database/schema.sql` aparece sin UNIQUE, defensa adicional pendiente de MAIN previa migración compatible.

**Corrección candidata probada exclusivamente en CLON DESCARTABLE**, sin editar el archivo fuente compartido `app/Services/Checkout/CheckoutOrderService.php`: en `run_checkout_http_private.ps1 -CandidateTokenFix`, al clonar sustituye el hash mutable por lista ordenada de `[product_id,variant_id,qty,price]` y conserva user/address/total. Con el mismo comprador y carrito, dos solicitudes generan token estable. **Regresión integral 19/19 HTTP+SQL + 3/3 HTTP concurrencia PASS, exit 0**, una orden, un solo descuento stock 5→3. Archivo evidencia independiente `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_PASS_20261025.log`. El P0 continúa **ABIERTO EN CÓDIGO FUENTE/PRODUCCIÓN CANDIDATA**: MAIN debe reservar/autorizar la integración en `CheckoutOrderService.php`, evaluar unicidad física y compatibilidad histórica, y ejecutar nuevamente sin `-CandidateTokenFix`.

### Defecto 04-QA-01 — Repro inicial B1_UNAVAILABLE: fixture de producto incompleto

Inicialmente el primer checkout despublicaba el producto sintético (`products.is_active=0`), porque no tenía imágenes y faltaba `catalog_allow_publish_without_images=1`; `race_reset` restauraba órdenes/carrito/stock, pero no la publicación. Se verificó con `diagnose` incluyendo estado SQL del producto/categoría/settings. Corregido **solo fixture** `scripts/dev/checkout_http_real_fixture.php`: setting explícito en base temporal, estado de producto/categoría restaurado en `race_reset`. Evidencia de fallo anterior `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_02.log`. No se cambió política comercial.

### Defecto 04-P2-02 — Actualización de dirección ajena respondía éxito falso (P2, CORREGIDO)

`AddressRepository::update()` filtraba correctamente `id AND user_id`, pero retornaba el booleano de `PDOStatement::execute()`, que vale true aunque no actualice ninguna fila. Con dos clientes sintéticos, el POST `/cuenta/direcciones/guardar` sobre dirección ajena 198 respondía HTTP 200, `ok:true`, **sin mutación SQL**. Se corrigió `app/Repositories/Account/AddressRepository.php` para requerir ejecución y al menos una fila afectada o dirección que efectivamente pertenezca al usuario (admite guardados idempotentes). **Regresión HTTP 422 para ajena + SQL intacto PASS; CRUD propio PASS**, PHP lint PASS. La protección de datos frente a IDOR ya era correcta, pero la semántica HTTP/UX estaba defectuosa.

### Circuito nuevo 04-A01 — Cliente, direcciones, admin y estados

Comando: `& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -CandidateTokenFix -AccountAdmin`. Script nuevo `scripts/dev/checkout_04_account_orders_private.py` (cuentas sintéticas 197/198/admin199, tokens CSRF reales). Último resultado **55/55 PASS HTTP+SQL, exit 0**, secuencial después de 19/19 checkout + 3/3 concurrencia, evidencia `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_55PASS_20261025.log` (y log actual más reciente). Cobertura efectiva: login real de tres roles, listado/detalle pedido propio, detalle ajeno denegado; listado/direcciones, formulario, validación negativa 422, CSRF 419, alta/edición/selección principal/eliminación SQL, edición/consulta/eliminación/selección de dirección ajena sin cambios, cliente a `/admin/orders` y mutación 403, admin listado/detalle, CSRF admin en estado/marcar pagado/envíos, estado inválido 422, comprobante inexistente sin acceso a archivos, carga comprobante sin CSRF 419, cancelación administrativa `pending_payment→cancelled` con flags para **evitar ARCA**, stock 3→5 exactamente una vez, segunda cancelación idempotente, transición prohibida `cancelled→shipped` 422 sin alterar SQL. Pruebas de upload válido de archivo y mark-paid positivo pendientes para no invocar integraciones externas fiscal/financiera sin control adicional.

### Logs preservados y teardown

Logs independientes: `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_02.log` (B1); `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_RACE_DUPLICATE.log` (P0); `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_PASS_20261025.log` (19+3); `docs/QA_CHECKOUT_HTTP_PRIVATE_ACCOUNT_FAIL_20261025.log` (fixture HTTP JSON/form inicialmente incorrecto); `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_55PASS_20261025.log` (19+3+55). Estas evidencias no autorizan publicación automática de archivos `.log`; revisar antes de commit. Todos los runs finalizaron con el runner confirmando cierre PHP/MariaDB, ausencia de carpeta temporal y listeners QA. No se terminó ningún proceso ajeno.

### Responsive — captura preliminar NO certificada

Se ejecutó Edge headless propio vía CDP, con sesión sintética autenticada, 4 páginas × 5 widths (363,390,768,1024,1600), capturas `docs/evidencias/preprod_04_responsive_20261025_v2/*.png` y métricas `summary.json`. Aunque el script anunció 20/20, **la inspección posterior de PNG reveló páginas SIN ESTILOS CSS**, por lo que **20/20 NO ES PASS VISUAL**; además `admin_orders` reportó `window.innerWidth=408` para targets 363 y 390. Es obligatorio corregir rutas de assets del runner privado, corroborar carga de hojas CSS y reejecutar, sin tocar CSS de producción si el problema reside en la configuración de clon/URL. Primer login adicional de navegador chocó con HTTP 429; se corrigió el harness para reutilizar cookie sintética previamente autenticada sin desactivar rate limiting. Edge fue cerrado por PID propio y perfil descartado.

**Estado actual:** área 04 **PARCIAL / NO GO** hasta integración del P0 en código fuente + regresión sin patch inyectado; QA funcional significativo comprobado pero sin cierre de cargas de comprobantes válidas, pago real, emisión fiscal y responsive CSS. Coordinar bloqueo P0 con MAIN y especialista 03 (stock), 05 (MP), 06 (ARCA), sin ensayar proveedores reales.


## Adenda de cierre técnico focal — ejecución integral 25/10/2026 (prevalece sobre estados preliminares anteriores)

### RESULTADO GLOBAL REPRODUCIBLE DEL CLON CON CORRECCIÓN CANDIDATA

Comando exacto ejecutado en Windows remoto:

`& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -CandidateTokenFix -AccountAdmin -Responsive`

**Exit 0. Suites encadenadas en la MISMA MariaDB privada:** checkout autenticado HTTP+SQL **19/19 PASS**; concurrencia HTTP con dos servidores/sesiones **3/3 PASS**; circuito cuenta/direcciones/pedidos/Admin HTTP+SQL **55/55 PASS**; Edge real headless responsive **20/20 PASS** con estilos CSS cargados y comprobados (CSSOM: ~2235 reglas pública, ~2093 Admin). El runner verifica datadir privado, puerto privado y ausencia de órdenes antes de crear fixtures; `-CandidateTokenFix` parchea exclusivamente `CheckoutOrderService.php` de la **copia descartable**, no la fuente compartida. Se obtuvo una sola orden y stock 5→3 bajo carrera HTTP; admin canceló con stock 3→5 una sola vez; la transición prohibida mantuvo estado. Las 55 verificaciones incluyen login y CSRF, 403 por rol, aislamiento IDOR por pedido/dirección, CRUD real de direcciones y su persistencia, comprobante inexistente, CSRF de uploads/admin, estados y cancelaciones.

**Log final preservado fuera de carpeta temporal:** `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_19_3_55_20PASS_20261025.log`, SHA256 `499495AC7D0D4E186FCF38DF60697D920DF2995FB8718F19AE56717CB3CA83CD`. Log de reproducción P0 (dos pedidos/stock 5→1): `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_RACE_DUPLICATE.log`, SHA256 `5CBDFAE342D3428905CE07A5D7A412EB8106F6A0ADB76C2D2E63869C1F314BC8`. Otros logs de runs previos tienen nombres distintos; no se sobrescribió evidencia de fallos preservada.

### Responsive real (corrige el falso positivo visual original)

El primer harness produjo capturas sin CSS: se identificó URL de assets incorrecta `//assets/...` en servidor PHP efímero. **Corrección exclusiva del entorno QA:** agregar `ASSET_URL=http://127.0.0.1:18897/assets` al `.env` generado en clon (`scripts/dev/run_checkout_http_private.ps1`), sin cambiar APP_URL/config comercial. La suite JavaScript vía Edge CDP (`scripts/dev/checkout_04_visual_private.mjs`) ahora comprueba ancho solicitado, `documentElement.scrollWidth`, `innerWidth`, ruta y **CSSOM con reglas cargadas**, no solo `link` presente. Se usan cookies sintéticas de cuentas previamente autenticadas, preservando el rate limit; motor Edge headless con perfil de uso único, teardown por PID propio. Capturas y JSON de 4 páginas × 5 tamaños: `docs/evidencias/preprod_04_responsive_20261025_v5/`, incluyendo `/cuenta/direcciones`, `/cuenta/pedidos`, `/admin/orders` y `/checkout` para **363, 390, 768, 1024, 1600 px**. **20/20 PASS** sin overflow horizontal y con 2000+ reglas CSS reales; fueron inspeccionadas visualmente muestras de Admin 363, direcciones 390 y checkout 390. El falso ancho 408 de Admin desapareció al cargar el CSS correcto, por lo que NO es un defecto CSS productivo demostrado. Capturas v2/v4 son históricas de QA defectuoso, no certifican responsive.

### Teardown final independiente

Tras la corrida, `%TEMP%/mi_checkout_http_private_qa` **inexistente**, puertos `18997`, `18897`, `18896`, `18998` sin listeners, ningún proceso Edge con `edge_profile`/puerto QA. El runner emitió `Private test folders and ports cleaned.` y `CHECKOUT_04_BROWSER_TEARDOWN completed ...` y terminó exit 0. No se cerraron navegadores comerciales.

### Matriz de resultado por familia / OWASP ASVS 5.0 (mapeo por control, no certificación ASVS exhaustiva)

| Familia y checklist | Endpoints | Controles ASVS aplicables por familia | Evidencia concreta | Resultado |
|---|---|---|---|---|
| 09.01-09.03, 09.05, 09.07, 09.10 | `/checkout`, `/checkout/transferencia`, `/checkout/stock/preflight` | Validación de entradas, transacciones/integridad, sesiones e idempotencia | `...CANDIDATE_19_3_55_20PASS...log` 19+3; snapshot 1 pedido stock 5→3 | PASS **solo con fix P0 inyectado al clon** |
| 09.08, 08.06 | preflight, direcciones de checkout | CSRF, autorización por objeto | 419 inválido, dirección ajena rechazada, SQL aislado | PASS alcance probado |
| 08.02, 08.06, 08.08 | `/cuenta/direcciones/*` | BOLA/IDOR, entrada, CSRF, no divulgación | 55 casos, creación/edición/default/baja SQL; ajena 422+sin escritura | PASS con corrección `AddressRepository` |
| 08.03, 08.09 | `/cuenta/pedidos`, `/cuenta/pedidos/ver` | Autorización por usuario, aislamiento entre propietarios | pedido propio 200, ajeno redirección 302; 2 compradores | PASS focal |
| 10.01, 10.02, 10.04, 10.07, 10.08, 10.10 | `/admin/orders`, `/show`, POST `/status` | RBAC, CSRF, transición, atomicidad e idempotencia | admin 200, cliente 403, CSRF 419, invalid 422, cancelar repone stock una vez | PASS focal |
| 10.03, 10.05, 10.06 | carga/descarga/ver comprobante, mark-paid y shipping | carga de archivos, acceso por objeto, CSRF | comprobante ausente con respuesta acotada, POST inválido 419 | PARCIAL; falta upload positivo/negativo de MIME y marcar pago/enviar válidos |
| UX 363/390/768/1024/1600 | cuenta/direcciones, pedidos, admin pedidos, checkout | UX responsive / accesibilidad adicional pendiente | `.../preprod_04_responsive_20261025_v5` 20 PNG + summary JSON, CSSOM real | PASS **responsive por anchura; accesibilidad exhaustiva pendiente** |
| MP/ARCA/Correo | pagos, webhooks, fiscal/envíos | autenticación webhook, firma, trazabilidad, integridad | no se invocaron proveedores externos; colaboración especialistas 05/06/03 | PENDIENTE / FUERA DE ALCANCE EXTERNO |

### Defectos, código propio y decisión para MAIN

**P0 04-P0-01 NO RESUELTO EN REPOSITORIO:** corrección candidata probada y reproducible, pero `app/Services/Checkout/CheckoutOrderService.php` sin modificar por reserva de MAIN. Solicitar integración del token estable y segunda ejecución del runner **sin** `-CandidateTokenFix`, además de evaluar índice UNIQUE `orders.checkout_token` con migración compatible e implicaciones de transacciones. Ante dos sesiones/reintentos con distinta reserva de stock sigue riesgo de doble pedido hasta esa integración. No declarar GO de ventas.

**P2 04-P2-02 CORREGIDO EN PROPIO DOMINIO:** `app/Repositories/Account/AddressRepository.php`, `update()` ahora verifica fila afectada o pertenencia existente, no éxito de execute sin filas; 422 ajena, CRUD propio y regresión 55 PASS.

**QA-01 CORREGIDO:** `scripts/dev/checkout_http_real_fixture.php` setting sintético sin imágenes y race_reset de publicación; se añadieron clientes 198/admin199 y snapshot sin datos comerciales. `scripts/dev/run_checkout_http_private.ps1` incluye clon con patch solo cuando se solicita explícitamente, exportación de suite cuenta, opcional responsive y ASSET_URL privado. Nuevos scripts `scripts/dev/checkout_04_account_orders_private.py` y `scripts/dev/checkout_04_visual_private.mjs` (no publicados). Se recomienda separar y firmar los logs de cada ejecución de manera automática en futuras corridas, sin repetir nombre de salida compartido.

**Pendientes auténticos:** integración P0 MAIN con prueba completa sin candidata; carga y previsualización de comprobantes válidos/maliciosos en sandbox y permisos por objeto; mutaciones administrativas positivas de marcado de pago/envío y regresiones sin homologación externa; análisis accesibilidad UI adicional; coordinación stock especialista 03, Mercado Pago 05, ARCA 06; pruebas de performance/seguridad ASVS exhaustiva sobre staging definitivo y PHP corregido. La suite HTTP/SQL es amplia pero no demuestra todos los webhooks ni el flujo comercial MP externo. NO GO global hasta MAIN y propietario aprueben gates restantes. Ningún commit efectuado por especialista 04.


## 25/10/2026 — Fase final solicitada por MAIN: archivos/comprobantes, permisos y cierre condicionado

**Mandato:** no reabrir investigación P0; no tocar `app/Services/Checkout/CheckoutOrderService.php`, no añadir otro arreglo de tokens; mantener evidencias históricas intactas; operaciones externas MP/ARCA/Correo Argentino productivas PROHIBIDAS. Se respetó. El archivo `CheckoutOrderService.php` figura sin modificaciones locales del especialista. No commits.

### Pruebas nuevas ejecutadas y resultado

Se amplió exclusivamente `scripts/dev/checkout_04_account_orders_private.py` para HTTP multipart, IDOR de comprobantes, archivo válido y malicioso, y pedido ajeno. Comando real seguro: `& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -CandidateTokenFix -AccountAdmin`. Uso de `-CandidateTokenFix` SOLO mantiene la corrección antigua en el clon descartable, **NO** se alteró su algoritmo ni código fuente. El resultado encadenado es **19/19 checkout + 3/3 concurrencia + 75/75 clientes/admin/comprobantes PASS, exit 0**, MariaDB sintética 18997; adicionalmente responsive 20/20 con CSS real ya acreditado en `...CANDIDATE_19_3_55_20PASS...log` y capturas `docs/evidencias/preprod_04_responsive_20261025_v5`.

**Nuevo log independiente:** `docs/QA_CHECKOUT_HTTP_PRIVATE_20261025_19_3_75_CANDIDATE_PASS.log` SHA256 `BFAEC388F7095DA4B7D5E8F6E674BD50A25EDDE5B4326E9C7449CBAF2849BAC2`. Históricos conservados sin ediciones: `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_RACE_DUPLICATE.log` hash `5CBDFAE342D3428905CE07A5D7A412EB8106F6A0ADB76C2D2E63869C1F314BC8`, y `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_19_3_55_20PASS_20261025.log` hash `499495AC7D0D4E186FCF38DF60697D920DF2995FB8718F19AE56717CB3CA83CD`.

Nuevas verificaciones HTTP/SQL reales, *dentro del clon privado*:
- Multipart con CSRF inválido: HTTP 419, sin archivo. Archivo PHP `<?php ... ?>` rechazado por MIME; vacío y mayor a 6 MiB rechazados; verificado directorio sin nuevos ficheros tras cada rechazo. Todos PASS.
- PDF sintético con firma `%PDF-1.4`, upload HTTP y redirección `comprobante=1`; persistencia solo bajo `storage/receipts/order_<id>` del clon, bytes exactos comparados; previsualización/descarga propias HTTP 200, MIME `application/pdf`, bytes exactos. PASS.
- Usuario cliente 198 no pudo cargar comprobante en pedido 197, ni ver/descargar comprobante existente de 197. No se alteró el archivo propietario. PASS.
- Intento `/cuenta/pedidos/repetir` sobre pedido ajeno redirige; SQL mantiene 1 pedido, stock 3. PASS.
- Intento de ruta relativa `../../not-a-real-file` fue redirigido sin entrega del archivo. PASS de ese caso, no sustituye fuzzing exhaustivo de rutas.
- Continúan PASS las pruebas previas de permisos 403, CSRF 419, dos clientes, direcciones CRUD/IDOR, estado inválido 422, cancelación administrada con restitución exacta 3→5 y replay que no duplica stock.

**Teardown:** `%TEMP%/mi_checkout_http_private_qa` inexistente tras ejecución; puertos `18997`, `18897`, `18896`, `18998` sin listeners; salida de runner confirma limpieza. No se usaron proveedores ni se borraron logs preservados.

### Ejecución DEFINITIVA lista para MAIN, sin parche candidato

**EJECUTAR SOLAMENTE cuando MAIN confirme integración del P0 en código fuente, reserva liberada y validación de esquema:**

`& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -AccountAdmin -Responsive`

**Es intencional que NO incluya `-CandidateTokenFix`.** El runner clona el código fuente realmente integrado y prueba 19+3+75+20, con verificación de una orden y un único descuento bajo concurrencia. `-Responsive` exige `-AccountAdmin` y ejecuta 20 capturas con CSS real. **Observación operativa:** `checkout_04_visual_private.mjs` rehúsa sobrescribir el directorio de evidencia `preprod_04_responsive_20261025_v5`; para la nueva corrida MAIN debe preparar una ruta nueva única de capturas en ese script antes de lanzar `-Responsive`, preservando la evidencia anterior. Asimismo el log principal `docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log` se sobrescribe por el runner: MAIN debe copiarlo con un nombre nuevo ANTES de ejecutar y archivar inmediatamente el resultado con nombre distinto y hash, sin tocar los logs históricos anteriores. Si la integración no está confirmada, **no ejecutar el gate sin patch como prueba de cierre**.

### Estado y dependencias al entregar

**BLOQUEADO POR INTEGRACIÓN MAIN.** La funcionalidad y la cobertura privada están listas para revisión; no se puede declarar APTO definitivo mientras `CheckoutOrderService.php` no incorpore el arreglo P0 por MAIN y no se observe PASS integral sin parche. No se detectaron nuevos P0/P1 en las operaciones probadas, pero no equivale a asegurar que no existan en flujos externos no ejecutados. Quedan especialmente para coordinación los positivos de marcado de pago que pueden activar autoemisión fiscal, ARCA, webhooks/pagos MP y despacho con Correo Argentino: no se ejecutaron por prohibición de proveedores productivos. No refactorización ni commits. MAIN conserva autoridad de integración, regresión final y GO/NO GO.

**Ajuste final al preparativo MAIN (reemplaza la observación operativa de directorio fijo):** se actualizó **solo** el harness `scripts/dev/checkout_04_visual_private.mjs` para usar un directorio de capturas nuevo por marca temporal UTC (`preprod_04_responsive_<timestamp>`), con su guardia de no sobrescritura. Así el comando exacto anterior con `-AccountAdmin -Responsive` ya no requiere editar manualmente la ruta ni pisará los PNG `v5`. Es un ajuste de QA; no se modificó HTML/CSS ni checkout. MAIN sigue debiendo preservar el log resultado de su futura ejecución con otro nombre.


## CIERRE LOCAL CONDICIONADO — Plan Maestro V2 / CONTROL_VIVO — 25/10/2026

**Fuentes de decisión vigentes leídas:** `docs/preproduccion/PLAN_DOS_ETAPAS_LOCAL_STAGING.md` (Plan Maestro V2) y `docs/preproduccion/CONTROL_VIVO.md`. Estado oficial de producción: **NO GO**. Corte Git observado para esta actualización documental: `2291527`. No se ejecutaron nuevas pruebas, no se modificó código, fixtures ni scripts y no se hicieron commits. Conservar íntegros los logs de FAIL y PASS previos y las capturas responsive.

| Clasificación V2 | Alcance y evidencia previa | Responsable y condición |
|---|---|---|
| **PASS LOCAL** | Checkout HTTP/MariaDB sintético **19/19**, concurrencia **3/3**, cuentas/direcciones/pedidos/Admin/comprobantes **75/75**, responsive Edge headless CSS real **20/20**. Los primeros 22 casos transaccionales fueron ejecutados **sobre un clon con `-CandidateTokenFix`**, NO sobre la corrección ya integrada en fuente. Evidencias: `docs/QA_CHECKOUT_HTTP_PRIVATE_20261025_19_3_75_CANDIDATE_PASS.log`, `docs/QA_CHECKOUT_HTTP_PRIVATE_CANDIDATE_19_3_55_20PASS_20261025.log` y `docs/evidencias/preprod_04_responsive_20261025_v5/`. | Evidencia local aceptada por MAIN; no repetir. No equivale a GO ni PASS de staging. |
| **PASS LOCAL — corrección dirección** | `AddressRepository::update()` ya corregido dentro del perímetro local; edición ajena devuelve 422, no altera SQL, y CRUD propio permanece PASS. Código pendiente de revisión/integración selectiva por MAIN. | MAIN revisa el diff e integra. |
| **BLOQUEADO POR MAIN — 04-P0-01** | Clon sin arreglo produjo dos pedidos y stock 5→1 bajo concurrencia; el candidato produjo una orden y stock 5→3. `CheckoutOrderService.php` quedó reservado a MAIN; no fue editado por especialista 04. Log del fallo: `docs/QA_CHECKOUT_HTTP_PRIVATE_FAIL_20261025_RACE_DUPLICATE.log`. | MAIN integra el token estable en **código fuente**, evalúa unicidad/compatibilidad y ejecuta la regresión privada existente **sin `-CandidateTokenFix`**: `& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1' -AccountAdmin -Responsive`. MAIN conserva nuevo log con nombre y hash únicos. **Gate L no superado** mientras falte esta integración y PASS real. |
| **DIFERIDO STAGING — ST-05** | Checkout en HTTPS de hosting, dos sesiones reales, reintentos, cancelación, seguimiento de stock, comprobantes y sus permisos sobre infraestructura definitiva. El clon local acredita casos parciales, no reemplaza el ensayo externo. | MAIN en staging privado HTTPS, SQL sintético y trazas HTTP; exigencia: una orden, un decremento, estados y permisos válidos. |
| **DIFERIDO STAGING — ST-06/ST-07** | Mercado Pago sandbox/webhooks firmados y replay, ARCA homologación autorizada o deshabilitación verificada, Correo Argentino **sin ninguna llamada productiva**. | MAIN y responsables de integraciones; no considerar PASS hasta evidencia sandbox o deshabilitación requerida. |
| **DIFERIDO STAGING — ST-08/ST-09** | PHP web actualizado, HTTPS, cookies, carga/descarga de archivos con límites reales de servidor, control de acceso, backup/restore, navegación y responsive con assets HTTPS. | MAIN durante habilitación del hosting; los 20/20 responsive locales no son aprobación HTTPS. |

**Dictamen de especialista 04: CIERRE LOCAL CONDICIONADO — BLOQUEADO POR INTEGRACIÓN MAIN.** El trabajo local independiente y las evidencias quedan entregados; 04 no ejecutará más suites, no modificará `CheckoutOrderService.php` ni introducirá correcciones de token. MAIN tiene la reserva, la integración y la repetición final sin parche de clon. Los casos diferidos siguen **PENDIENTE**, nunca PASS. No quedan nuevos P0/P1 demostrados por los casos locales adicionales, pero **04-P0-01 continúa abierto en fuente** hasta que MAIN lo cierre. No GO producción.
