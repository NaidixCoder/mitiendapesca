# RESULTADO 03 — Inventario, proveedores, compras e importaciones
**Fecha:** 2026-10-25 (Argentina). **Commit inspeccionado:** `2a1f692`. **Responsable:** especialista 03. **Dictamen:** PARCIAL / NO GO del circuito integral; no se hicieron commits ni cambios al código.

## 1. Alcance y fuentes
Mandato `docs/preproduccion/especialistas/03_inventario.md`; Plan Maestro §7 OWASP ASVS 5.0/PHP; checklist áreas 06, 07, 19; matriz E2E; inventario CSV; COORDINACION; protocolos Git/V2/V3; protocolo de MariaDB privada/navegadores; documentación Proveedores, Importaciones, QA transversal y cierre SQL. Los antecedentes no se convierten automáticamente en PASS vigentes. Se respetaron las reservas: ningún archivo compartido ni de otros chats se editó.

## 2. Inventario verificado contra rutas y código
| Superficie | Rutas reales | Componentes y efectos | Estado |
|---|---|---|---|
| Existencias Admin | GET `/admin/inventario` | `InventoryController@index`, `InventoryItemsRepository::listItems`, ViewModel/vista; UNION ALL de productos simples e inventario por variante, búsqueda por SKU/nombre. Lectura SQL | Contrato inspeccionado; HTTP específico por roles PENDIENTE |
| Existencias dashboard | GET `/admin/dashboard/low-stock.json` | `DashboardDataController@lowStock`; consumidor de umbrales | PENDIENTE funcional/roles |
| Proveedores | GET `/admin/proveedores`, `/new`, `/edit`; POST `/save`, `/delete` | `ProveedoresController`, `ProveedorService`, `ProveedorRepository`, vistas y CSRF de mutación; tablas `suppliers`, FK `purchase_invoices.supplier_id` | 36/36 HTTP/MariaDB autenticados en clon PASS, incluye responsive |
| Compras fiscales | GET `/admin/facturacion/compras`, `/new`, `/edit`; POST `/save`, `/validate`, `/void`; POST upload/delete de adjunto y GET view/download | `ComprasController`, `PurchaseInvoiceService`, `PurchaseInvoiceRepository`, `PurchaseInvoiceAttachmentService`; facturas de compra, proveedores, adjuntos. **No identificar automáticamente como recepción física/entrada de stock.** | FK del proveedor probada; circuito fiscal completo/adjuntos pendientes de especialista Facturación |
| Importaciones | GET `/admin/imports`, `/show`, `/template`, `/export`; POST `/preview`, `/apply`, `/create`, `/process`, `/rollback` | `ImportsController`, `ImportService`, ImportJobRepository, importadores productos/variantes/atributos/inventario, snapshots y revert. CSV, DB y filesystem | Contratos actuales PASS; HTTP/MariaDB I05 76 casos y SQL E2E 40 casos **históricos**, sin reejecución integral actual |
| Consumidores compartidos | `POST /checkout/stock/preflight`, creación de pedido, pago, importación/publicación | Guard de elegibilidad, reserva/descuento de stock, concurrencia e idempotencia dependientes de Checkout/Pedidos | NO ACREDITADO como compra HTTP en una misma sesión |

**Diferencia funcional:** el controlador actual de Inventario expone únicamente `index()`; `InventoryService` implementa `get/upsert/adjust/setStock/lowStockList` sin introducir rutas de recepción. El servicio `PurchaseInvoiceService` opera facturas fiscales; no afirmar recepción parcial, doble recepción o actualización automática de costo/stock hasta encontrar e identificar su implementación concreta. Es una **brecha de cobertura/alcance por verificar**, no defecto probado.

## 3. Matriz de pruebas y seguridad (ASVS 5.0: familias aplicables, IDs precisos pendientes de mapeo)
| Caso/checklist | Ruta/superficie | Amenaza/ASVS aplicable | Evidencia | Resultado / defecto |
|---|---|---|---|---|
| 06.02 CRUD proveedor y FK compras | `/admin/proveedores/*` | Control de acceso, validación, integridad de datos | `python scripts/dev/proveedores_http_mariadb_private.py` 2026-10-25, exit=0, `PROVEEDORES_AUTHENTIC_HTTP_MARIADB_PASS 36/36` | PASS; sin defecto nuevo |
| 06.02/07.10 permiso y CSRF | `/admin/proveedores/save`, `/delete` | Autenticación, autorización vertical, CSRF | Misma suite: anónimo rechazado, cliente no puede GET/POST, CSRF inválido rechazado, DB intacta | PASS |
| 06.02 responsive | Listado/formulario proveedores | UI y robustez | Misma suite: Edge headless real, 363/390/768/1024/1600, 10/10 vistas sin scroll horizontal | PASS |
| 06.03 integridad proveedor-compra | Factura vinculada a proveedor sintético | FK, baja lógica y idempotencia | Misma suite: prueba 25/28/29/32; factura FK persiste tras baja/reintento/reactivación | PASS para FK; no prueba recepción física |
| 07.04 rollback I05 | `/admin/imports/apply` | Atomicidad, integridad, transacciones | `php scripts/build/imports_i05_atomic_contract.php`: 21/21, exit=0 | PASS contrato, **no** HTTP/SQL nuevo |
| 07.05 atributos y variantes | ImportService | Asociación y rollback | `imports_attribute_relations_contract.php` 30/30; `imports_variant_parent_snapshot_contract.php` 25/25, exit=0 | PASS contratos |
| 07.01–07.10 superficie Importaciones | Admin imports, controladores, snapshots | Subidas, idempotencia, fallos, permisos | 10 suites `scripts/build/imports_*contract.php` ejecutadas actualmente: 71+30+30+21+19+13+21+19+25+35 = **284 comprobaciones**, todas exit=0 | PASS contratos; HTTP de importación pendiente |
| 06.01/03.06 stock-publicación | Producto/variante | Integridad de publicación e inventario | `php scripts/build/transversal_publication_stock_contract.php`: 16/16, exit=0, SQLite in memory | PASS aislado; no MariaDB concurrente |
| 07.04 / 19.01 E2E SQL | ImportService ↔ atributos ↔ B1 ↔ stock | SQL transaccional | `docs/EJECUCION_E2E_MARIADB_PRIVADA.md` + evidencia 2026-10-22 40/40 validada anteriormente por MAIN | **HISTÓRICO** reutilizado, no reejecutado; no pedido HTTP |
| 07.04 I05 HTTP completo | `/admin/imports/*` | Autenticación, CSRF, rollback, archivo malicioso | `docs/modulos/importaciones.md` cierre 2026-10-22; `scripts/dev/imports_i05_http_isolated.py` 76/76 reportado | **HISTÓRICO**; no PASS actual autónomo |
| 06.04–06.08 /19.02–19.05 | recepción, stock y pedido concurrente | Doble ejecución, bloqueo, sobreventa, rollback | No se identificó recorrido completo integrado ni se ejecutó MariaDB concurrente actual | **PENDIENTE**, impacto potencial alto |
| 07.09 exportaciones | `GET /admin/imports/export` | Divulgación/CSV injection/autorización | Ruta inspeccionada, sin descarga HTTP verificada | PENDIENTE |

## 4. Ejecución y aislamiento verificados
- CLI `C:\xampp\php\php.exe`: PHP **8.2.12**. Es un bloqueo de plataforma preproducción ya conocido por Plan Maestro §7, no un hallazgo nuevo del módulo; versión web/hospedaje pendientes de medir. No se modificó Apache/XAMPP.
- Runner real Proveedores: clon privado sin `.env` ni storage comercial, MariaDB nueva puerto **18979**, validación `@@datadir` contra carpeta TEMP del propio runner, PHP privado **18879**. Login y CSRF reales, proveedor/compra y usuarios sintéticos, 36/36 PASS; 10 verificaciones browser headless; exit=0. Final: procesos propios (PID documentados por runner) detenidos, directorio `%TEMP%\mi_proveedores_http_qa_20261023` eliminado, comprobación de LISTENING en puertos QA vacía.
- Contratos actuales importación: `imports_admin` 71, `imports_apply_outcome` 30, `imports_attribute_relations` 30, `imports_i05_atomic` 21, `imports_lifo_uncertain` 19, `imports_parent_cache` 13, `imports_revert` 21, `imports_snapshot` 19, `imports_variant_parent_snapshot` 25, `imports_variant_ship` 35; **284/284**, exit=0 en las diez suites. Estos verifican contratos sin reemplazar ensayos E2E.
- Evidencias previas de Importaciones y SQL transversal tienen teardown acreditado en sus propios documentos. No se recrearon sus instalaciones porque son suites ya acreditadas y no hay cambio material observado en este pase.
- No se inició navegador adicional fuera del runner, no se efectuaron pagos, mail, ARCA, migraciones, SQL comercial ni commits.

## 5. Riesgos, decisiones y tareas abiertas
1. **P1, cobertura crítica pendiente (no defecto confirmado):** completar una transacción real HTTP autenticada que conecte catálogo/importación, pedido y decremento único de stock (incluye dos compradores concurrentes, doble clic, fallos, rollback, variante y stock insuficiente). Coordinar archivos y fixtures con especialistas 02 (Catálogo) y 04 (Pedidos); no editar rutas/Checkout/stock compartidos sin reserva.
2. **P1 si existe recepción comercial:** localizar contrato de recepción física y demostrar compra→recepción parcial→saldo→stock y costo, repetición idempotente, error a mitad de operación y carreras. La ruta `/admin/facturacion/compras` es fiscal; no atribuirle efectos de recepción sin evidencia.
3. **P1 de plataforma transversal:** actualizar y validar PHP parcheado antes de producción; CLI 8.2.12 encontrado, sin demostrar aún versión de PHP del servidor web/destino.
4. **P2 pruebas faltantes:** HTTP autenticado vigente de importaciones/exportaciones, CSRF y límites de archivos, GET inventario por rol, bajo stock, paginación/carga, EXPLAIN, revisión de seguridad de adjuntos fiscales por especialista de Facturación.
5. **ASVS:** se mapearon familias de control con amenazas y rutas; falta asignar IDs numéricos exactos de ASVS 5.0 y evidencia por requisito antes de certificación L2.
6. **No se confirmaron defectos nuevos en el código auditado y no se aplicaron fixes.** Esto no significa ausencia de vulnerabilidades en el perímetro pendiente.

**Cierre:** aprobado únicamente el subconjunto reproducido (Proveedores HTTP/MariaDB/responsive y contratos de importaciones/stock); E2E comercial de inventario/recepción/pedido y seguridad integral siguen **NO GO**. MAIN decide la aceptación y la coordinación de las pruebas compartidas.

## 6. Observación adicional confirmada por lectura de código
**P2 rendimiento (sin medición de impacto):** `app/Repositories/Admin/InventoryItemsRepository.php::listItems()` construye un `UNION ALL` entre productos simples y variantes, ordena todos los resultados y hace `fetchAll()` sin `LIMIT/OFFSET`; `InventoryController@index` no recibe parámetros de paginación. Con un catálogo voluminoso puede crecer consumo de memoria, tiempo de respuesta y HTML. **No hay latencia ni volumen medidos**, por lo cual es riesgo de escalabilidad a ensayar con dataset sintético, no incidente productivo demostrado. Implementación de paginación requiere reserva específica al afectar contrato de vista/ViewModel. No se editó el código.

## 7. Adenda MAIN — integración y stock comercial (25/10/2026, commit bffd58c)
**Mandato:** `docs/preproduccion/REVISION_MAIN_RESULTADOS_01_04_20261025.md`. Se preservan 36/36 Proveedores, 284 contratos Imports, 16/16 contrato stock y 40/40 SQL transversal: **no reejecutados**. Único archivo nuevo/editado del proyecto: este resultado. Ningún commit. No se editaron imports, checkout ni controladores compartidos.

### 7.1 Mapa exacto de escrituras: fiscal no es recepción
- `/admin/facturacion/compras` → `ComprasController` → `PurchaseInvoiceService` → `PurchaseInvoiceRepository`: inserta/actualiza/valida/anula `purchase_invoices`, adjuntos fiscales; **no llama InventoryRepository ni actualiza `inventory`**. Es facturación de compras, no recepción/OC.
- `/admin/inventario` → `InventoryController@index` → `InventoryItemsRepository`: **solo SELECT**, ningún ajuste. `/admin/dashboard/low-stock.json` solo consulta umbrales.
- `InventoryService`: `upsert` inserta/actualiza `inventory`; `adjust` aplica delta sin condicionar disponibilidad; `setStock` fija el entero. Verificado por ejecución aislada.
- `InventoryRepository`: `updateByProductId/insert/updateByVariantId/insertVariant` fijan cantidades y sincronizan publicación; `decrementStock/decrementVariantStock` restan sin `stock >= qty` en el UPDATE; `incrementStock/incrementVariantStock` suman; `lockStockForUpdate/lockVariantStockForUpdate` hacen SELECT FOR UPDATE. La seguridad al descontar depende del consumidor y transacción (no de la sentencia UPDATE sola).
- `ImportService` con `InventoryImporter` y `VariantImporter`: importación CSV escribe productos, variantes, `inventory`/`inventory_variants`, con modo `suma` o `fija` y control de rollback I05 + snapshots. Importador de producto actualiza publicación; importación no representa una recepción formal de compra.
- `CheckoutOrderService` líneas aprox. 173/345–347 y `OrderFromIntentService` 123/242–244: consumidores de lock y decremento; `AdminOrderService` 555–558 realiza incrementos ante operaciones de pedidos. **Estos archivos pertenecen al especialista 04/MAIN; no editados ni validados en nuevo flujo completo por este chat.**
- El `database/schema.sql` consultado tiene `inventory` e `inventory_variants` sin CHECK de stock >= 0. No se identificó tabla de recepción física ni libro dedicado de movimientos de inventario; requiere especificación comercial si se espera historial de cada ajuste.

### 7.2 Nuevas pruebas funcionales SQLite del servicio REAL y microbenchmark
**Comando reproducible** (fixture temporal sin tocar comercial): `C:\xampp\php\php.exe %TEMP%\mi_inventory_specialist03_qa.php`. Base `PDO sqlite::memory:`, esquema mínimo sintético, servicios reales `InventoryService` y `InventoryItemsRepository`. **11/11 verificaciones**: stock inexistente=0, upsert nuevo/existente, delta, setStock, rollback transaccional, producto simple+variante, stock correcto, filtros SKU/ausente y test negativo. El test negativo confirma `adjust(1,-10)` desde 9 => **-1 permitido**; comportamiento real de `InventoryService`, no afirmación de explotación desde HTTP. Sin efectos fuera de la BD en memoria.

Benchmark del **repositorio real**, una muestra por tamaño (no p95, no MariaDB, no navegador):
| Filas obtenidas por UNION ALL/fetchAll | Tiempo de consulta | Incremento pico memoria PHP |
|---:|---:|---:|
| 1.002 | 1,55 ms | 0 MiB |
| 6.002 | 9,12 ms | 4 MiB |
| 21.002 | 31,10 ms | 12 MiB |
El listado carece de LIMIT/OFFSET y crece proporcionalmente con el catálogo. **P2** de escalabilidad confirmado por lectura, impacto productivo/no SQL MariaDB aún desconocido. Coordinar reserva antes de implementar paginación en controlador, repositorio y ViewModel/vista.

### 7.3 Nueva MariaDB 10.4.32 aislada: concurrencia/rollback (SQL real, no Checkout)
**Runner privado temporal:** `%TEMP%\mi_inventory_specialist03_mariadb.py`. MaríaDB independiente en 127.0.0.1:**18983**, datadir específico en TEMP, puerto y `@@datadir` estrictamente confirmados tras normalizar separadores Windows. DB `mi_inventory03_private`, InnoDB con tablas mínimas sintéticas. Primer intento abortado por comparar separadores duplicados en `@@datadir`; **sin escrituras SQL**, teardown confirmado. Se corrigió normalización del guard en runner temporal, no se omitió validación, y se repitió.
- **7/7 aserciones funcionales PASS y una comprobación adicional de teardown PASS, exit 0**: stock 5; dos solicitudes SQL independientes concurrentes con UPDATE guardado (`stock >= 3`) ⇒ exactamente 1 éxito y 1 rechazo, stock 2; rollback restauró stock 5; doble UPDATE sin guard ⇒ stock -1; índice UNIQUE de token de operación sintética bloqueó duplicado (un INSERT aceptado y otro ignorado).
- **Límite:** se midió comportamiento SQL InnoDB; el `UPDATE` guardado y la tabla de tokens son *controles de prueba*, no evidencia de que el código de Checkout utilice esos SQL/índices. No certificar idempotencia real de pedidos con esta prueba.
- Teardown: MariaDB privada detenida, puerto 18983 libre, datadir y credenciales sintéticas descartados, exit=0.

### 7.4 Nuevo E2E HTTP autenticado/CSRF/roles y descubrimiento de defecto en exportación
**Runner temporal:** `%TEMP%\mi_inventory03_http_private.py` (generado desde bootstrap de `scripts/dev/proveedores_http_mariadb_private.py`, sin repetir su lógica de CRUD). Clon aislado sin `.env` comercial, MariaDB 10.4.32 en 127.0.0.1:**18985**, `@@datadir` confirmado, DB de ensayo creada con `database/schema.sql`; PHP real en 127.0.0.1:**18885**; cuentas admin/customer sintéticas, login y CSRF **originales**, no stubs.

**Ejecución 1 — árbol ORIGINAL sin modificaciones:** login admin y `GET /admin/inventario`, `GET /admin/imports`, formulario CSRF y descarga `GET /admin/imports/template` PASS; **`GET /admin/imports/export` devuelve HTTP 500** en BD real privada vacía, aunque emite cabeceras CSV y comienza a escribir columnas. Error exacto en `site/storage/logs/app.log`: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'v.ship_weight' in 'field list'` en `ImportsController.php:719`. `database/schema.sql` declara `ship_*` para `products`, pero **no para `product_variants`**. Defecto reproducible incluso sin variantes: PDO prepara SELECT de variantes. **P1 funcional: exportación falla sistemáticamente sobre esquema de referencia**; flujo export/import y respaldo manual CSV bloqueados. No se tocó el archivo compartido original.

**Ejecución 2 — corrección experimental SOLO EN CLON QA:** se sustituyeron únicamente en la copia `site/app/Controllers/Admin/ImportsController.php` los cuatro campos inexistentes del SELECT (`v.ship_weight`, `v.ship_height`, `v.ship_width`, `v.ship_length`) por `NULL AS ship_*`. Se obtuvo **HTTP 200 y CSV** con cabeceras válidas. Test focal acumulado: `INVENTORY_IMPORTS_AUTH_HTTP_PASS 29`, exit 0, incluyendo 3 POST CSRF inválidos ⇒ HTTP 419 (`preview/apply/rollback`), upload TXT inválido sin import_job nuevo, GET inventario/imports/export/template anónimos denegados, GET admin de cliente denegados, login cliente y persistencia SQL sin escrituras de accesos prohibidos.

**IMPORTANTE:** 29 PASS corresponden a clon modificado experimentalmente; el árbol ORIGINAL permanece con HTTP 500. **No se atribuye PASS de exportación al código no modificado**. La sustitución por NULL evita 500, pero la decisión funcional (campos de envío por variante realmente soportados o vacío explícito) requiere MAIN/Catálogo; reservar `app/Controllers/Admin/ImportsController.php` antes de integrar. Alternativa: mapear dimensiones de padre si negocio lo decide; no inventar datos en CSV.

**Pruebas aún no nuevas:** Apply/rollback completo de CSV válido en el mismo servidor (evidencia histórica I05 76/76 reutilizada), archivo grande, importación maliciosa más allá del upload inválido, descarga con productos/variantes poblados, navegador responsive de Importaciones y pedido autenticado concurrente. No confundir los 29 PASS de endpoints focales con E2E comercial integral.

**Teardown:** primera ejecución detuvo ambos procesos propios y retuvo el clon para diagnóstico, con puertos libres; tras capturar causa raíz, se eliminó antes de la ejecución focal de clon parcheado. Segunda ejecución: PHP y MariaDB privados detenidos por runner, directorio y DB TEMP eliminados; ambos puertos liberados, exit 0. No se usó base comercial ni ningún proveedor externo.

### 7.5 Defectos y coordinación con MAIN
| ID | Severidad | Confirmación | Acción/propietario |
|---|---|---|---|
| INV03-EXP-01 | **P1** | HTTP 500 export CSV sobre esquema oficial; SQLSTATE 42S22 `v.ship_weight` ausente; aislado y reproducido. Clone-only workaround HTTP 200 | MAIN debe reservar `ImportsController.php` y acordar semántica de dimensiones variante; parche y regresión GET export con producto simple, variante y posterior reimportación |
| INV03-NEG-02 | **P2 / potencial P1 si caller omite guard** | `InventoryService::adjust()` y decrements repo no protegen físicamente stock negativo; servicio SQLite y SQL MariaDB confirman; no se detectó uso HTTP explotable directo | Especialista 04/MAIN audita lock+validación transaccional en cada consumidor Checkout/Orders/Cancelación. No cambiar repositorio compartido unilateralmente |
| INV03-PERF-03 | **P2** | Sin paginación SQL; 21.002 items ≈31,1 ms y +12 MiB en SQLite memoria | MAIN reserva repositorio/controlador/vista Inventario para paginación y verifica con MariaDB real/HTML |
| INV03-E2E-04 | P1 cobertura | El SQL concurrente guardado pasó pero faltan E2E compra→pedido→stock con sesión, CSRF e idempotencia bajo error secuencial B1 del especialista 04 | MAIN coordina fixture y sesión compartida con especialista 04; reservar `CheckoutOrderService`, `OrderFromIntentService` y pruebas. **Sin modificación** |
| INV03-REC-05 | Pendiente de decisión funcional | No se encontró recepción física/OC ni historial de movimientos dedicado; Compras es fiscal | MAIN define si se requiere funcionalidad nueva después del congelamiento; no declararla defectuosa sin requisito |

**Estado:** PARCIAL, bloqueo funcional **P1 INV03-EXP-01** abierto en el árbol original. No se realizaron modificaciones de código productivo, migraciones ni commits. El trabajo independientemente ejecutable quedó demostrado; MAIN conserva coordinación de rutas/archivos compartidos y decide cierre.

## 8. Fase final 25/10/2026 — exportación/reimportación focal, reserva pendiente

**Mandato MAIN:** corregir el P1 CSV antes de cerrar. **Dictamen actual BLOQUEADO por reserva + corrección no integrada**. Commit base observado `bffd58c`. Se comprobó `git status`: el archivo `app/Controllers/Admin/ImportsController.php` no tiene modificaciones locales de otras sesiones, pero `docs/COORDINACION.md` no contiene una reserva vigente y explícita para que el especialista 03 edite el método `export()`. El mandato solicita **coordinar esa reserva antes de escribir**; no se presume concedida por ausencia de conflictos. Se dejó el código productivo intacto y este informe documenta la solicitud concreta a MAIN.

### 8.1 Semántica contractual confirmada
`database/schema.sql` declara `ship_weight/ship_height/ship_width/ship_length` en `products`, pero **ninguna** de esas cuatro columnas en `product_variants`. `app/Services/Import/VariantImporter.php` ya rechaza las dimensiones de envío por variante si `update_ship_dims` está habilitado y la fila trae valores. Por lo tanto, en `GET /admin/imports/export` las cuatro columnas oficiales del CSV de **filas variante** deben existir pero estar **vacías**. No heredarlas del producto padre ni inventar valores; las filas de producto simple sí deben conservar sus dimensiones reales.

**Patch mínimo a reservar:** `app/Controllers/Admin/ImportsController.php::export()`, exclusivamente en el SELECT de variantes que hoy referencia `v.ship_*`: eliminar esas cuatro referencias inexistentes y, por ejemplo, proyectar `NULL AS ship_weight, NULL AS ship_height, NULL AS ship_width, NULL AS ship_length`, manteniendo intactos `$headers`, `$rowv`, roles, CSRF y demás SQL. No se solicita migración ni modificación de repositorios, formulario o controlador de checkout.

### 8.2 Nueva ejecución HTTP auténtica con productos y variante sintéticos (sin modificar árbol original)
Runner reproducible: `python %TEMP%\mi_inventory03_export_builder.py` seguido de `python %TEMP%\mi_inventory03_export_private.py`. Ambos scripts propios de QA están exclusivamente en TEMP del equipo, generados a partir del bootstrap privado existente, y aplican el patch anterior **solo dentro del clon TEMP**. MariaDB 10.4.32 **127.0.0.1:18986**, datadir TEMP verificado vía `@@datadir`; PHP privado **127.0.0.1:18886**. Clon sin `.env` comercial, usuarios sintéticos admin/customer, login real, sesiones y CSRF originales.

Fixtures sintéticos: categoría 901; producto simple id 901, SKU `QA-SIMPLE-901`, stock 12, dimensiones **500/20/10/8**; producto padre id 902, SKU `QA-PARENT-902`; variante id 903, SKU `QA-VAR-903`, stock **7**, vinculada a ese padre.

**Resultado:** `INVENTORY_IMPORTS_AUTH_HTTP_PASS 35` (35/35 PASS, exit=0). Incluye exportación CSV **HTTP 200**, cabeceras y BOM, exactamente **2 filas producto + 1 fila variante**, dimensiones del simple preservadas, las cuatro dimensiones de variante vacías, SKU padre y stock 7 correctos; `POST /admin/imports/preview` autenticado sobre CSV exportado responde HTTP 200 sin alterar conteos ni sumas de stock de productos/variantes; CSRF inválido HTTP 419 para `preview/apply/rollback`, upload TXT no creó job, anónimo y cliente sin privilegios denegados. **Se probó previsualización de reimportación, NO Apply/commit de ese mismo CSV**; no exagerar roundtrip completo. El HTTP 200 del clon parcheado **no implica que el original haya dejado de devolver 500**.

Teardown: runner reporta procesos propios PHP/MariaDB detenidos y eliminación completa de `%TEMP%\mi_inventory03_export_qa_20261025`; no se tocaron XAMPP comercial, envíos, pagos, ARCA ni Git. QA previamente acreditado Proveedores/Imports no se repitió.

### 8.3 Integridad de stock y pendientes de cierre
Conservan validez los ensayos independientes ya documentados en §7: servicios reales SQLite (upsert/setStock/adjust/rollback y negativos), SQL InnoDB privado con dos operaciones paralelas condicionadas (`stock 5→2`, una sola acepta cantidad 3), rollback, duplicidad de operación sintética e insuficiencia; E2E de stock/pedido con datos del especialista 04 queda bajo responsabilidad de ese especialista y MAIN, sin reejecutar sus suites ni editar sus archivos. Los decrementos de repositorio sin condición requieren que el consumidor efectúe lock+comprobación en la misma transacción: esto es **dependencia a validar** en el flujo E2E, no afirmar explotación sin prueba.

**Poslanzamiento:** inventario sin paginación SQL (benchmark SQLite con 21.002 filas: 31,1 ms y +12 MiB pico) y ausencia de circuito nuevo de recepción física; ambos fuera del hotfix P1 y sin cambios.

### 8.4 Solicitud formal de reserva a MAIN
Reservar expresamente para especialista 03: **solo `app/Controllers/Admin/ImportsController.php::export()`, SELECT de dimensiones de variante**, excluyendo cualquier otro método. Tras concederse reserva: aplicar corrección mínima al árbol principal, ejecutar lint focal, repetir exportación original con producto simple/variante en MariaDB clonada y preview/Apply+rollback cuando sea seguro, más `git diff --check` focal y teardown. MAIN conserva único derecho de commit. Hasta entonces: **BLOQUEADO**, defecto P1 `INV03-EXP-01` abierto en la fuente original; el resto del ámbito no demuestra P0/P1 nuevo confirmado.

## 9. CIERRE LOCAL AUTORIZADO MAIN — 25/10/2026, commit base `2291527`

**La sección 9 reemplaza los bloqueos históricos de INV03-EXP-01 presentes en §7 y §8. Estado de este encargo: APTO PARA REVISIÓN MAIN (LOCAL).** No es GO de producción ni aprobación global de preproducción.

### 9.1 Reserva y edición focal realizada
El propietario/MAIN autorizó expresamente una reserva exclusiva para `app/Controllers/Admin/ImportsController.php::export()` **únicamente SELECT de dimensiones de variantes**. Se comprobó ausencia de cambios locales previos en dicho archivo (`git status --short -- ...` vacío) y no surgió reserva incompatible vigente con este tramo. Se respetaron las reservas históricas de `apply()`.

Cambio **único de código del proyecto** en `app/Controllers/Admin/ImportsController.php`, SELECT interno `$stmtVars` (aprox. línea 723):
- Antes: `v.ship_weight, v.ship_height, v.ship_width, v.ship_length,` — columnas inexistentes en `product_variants`, error HTTP 500.
- Después: `NULL AS ship_weight, NULL AS ship_height, NULL AS ship_width, NULL AS ship_length,`.
- `products.ship_*` se conserva sin cambios; los campos CSV oficiales `peso_envio_g`, `alto_envio_cm`, `ancho_envio_cm` y `largo_envio_cm` salen vacíos únicamente en filas variante. Es coherente con `VariantImporter`, que no acepta dimensiones de variante bajo el esquema actual.
- **Sin cambios** en rutas, schema, resto de controller, importador, checkout, proveedores ni paginación.

**Revisión de diff:** un solo renglón de código sustituido. `C:\xampp\php\php.exe -l app/Controllers/Admin/ImportsController.php` ⇒ `No syntax errors detected`; `git diff --check -- app/Controllers/Admin/ImportsController.php` ⇒ exit 0 (solo aviso no bloqueante de normalización futura LF/CRLF).

### 9.2 E2E HTTP real focal tras el cambio integrado en el árbol de trabajo
**Comando:** `python %TEMP%\mi_inventory03_export_private.py`. Se adaptó el runner QA TEMP para **eliminar el parche experimental del clon**: ahora `robocopy` clona el archivo ya corregido del repositorio y NO altera ningún controlador en el clon. Entorno autónomo sin `.env` comercial, MariaDB descartable 10.4.32 **127.0.0.1:18986**, puerto y `@@datadir` físico verificados, PHP privado **127.0.0.1:18886**, usuarios sintéticos, sesiones y CSRF reales. Datos: dos productos QA y una variante real QA con stock inventario.

**Salida observada:** `INVENTORY_IMPORTS_AUTH_HTTP_PASS 35`, **35/35 PASS**, **exit 0**. `GET /admin/imports/export` ⇒ **HTTP 200, Content-Type CSV**, 3 filas de negocio (2 productos y 1 variante): simple conserva `500/20/10/8`; variante presenta cuatro dimensiones vacías, `sku_padre` correcto y stock 7. `POST /admin/imports/preview` con CSV recién exportado ⇒ **HTTP 200 y stock/conteos de productos y variantes invariantes**. También pasaron CSRF inválido 419 (preview/apply/rollback), upload inválido no crea import_job, y accesos anónimos/cliente denegados. **NO se hizo Apply/COMMIT de ese CSV:** el roundtrip queda acreditado hasta Preview; Apply/rollback transaccional conserva su evidencia previa y nuevas integraciones complejas se difieren a STAGING.

**Teardown verificado:** los dos procesos privados fueron detenidos; el runner eliminó `%TEMP%\mi_inventory03_export_qa_20261025`, exit 0. No hubo DB comercial, correos, pagos, ARCA, Correo Argentino ni cambios en XAMPP productivo.

### 9.3 Integridad comercial y alcance del cierre
Se reutilizan las pruebas focales anteriores documentadas en §7–§8: persistencia del inventario con servicio real SQLite, MariaDB privada InnoDB con stock insuficiente y carrera de actualización SQL condicionada (exactamente una de dos operaciones fue aceptada), rollback y duplicación de token sintético; prueba de previsualización de CSV sin mutaciones sobre stock real sintético. La creación de pedido concurrente HTTP y cancelación/restore administrados por especialista 04/MAIN quedan para STAGING; no afirmar que las guardas probadas en SQL son guardas implícitas de todos los consumidores.

**Riesgos diferidos:** `INV03-PERF-03` paginación de listado de 21.002 registros y `INV03-NEG-02` guardas en métodos básicos de inventario (los consumidores deben usar lock+verificación); circuito nuevo de recepción física fuera del alcance autorizado. No se identificó otro defecto P0/P1 explotable confirmado en este encargo. La corrección P1 `INV03-EXP-01` queda **RESUELTA EN LOCAL, PENDIENTE DE INTEGRACIÓN MAIN Y STAGING**.

### 9.4 Dictamen final
**APTO PARA REVISIÓN MAIN (LOCAL).** Exportación del CSV funcional en código original corregido, lint PASS, HTTP autenticado 35/35 PASS, controles de permisos/CSRF, Preview sin cambios SQL, y teardown completo. **MAIN integra con commit** y valida STAGING según plan general; no se hicieron commits desde este chat.
