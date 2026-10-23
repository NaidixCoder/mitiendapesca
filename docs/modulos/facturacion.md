# Facturación — mandato integral Admin V3

Estado: asignado al especialista. Main coordina; no solapar archivos.

## Alcance
Todas las rutas /admin/facturacion/*: control, comprobantes/detalle/PDF, consultas y reintentos, libro IVA ventas/compras y exportaciones, compras/adjuntos, reportes fiscales, desvíos, pendientes, duplicados, alertas, cierres mensuales. Controladores: FacturacionControlController, ComprobantesFiscalesController, LibroIvaController, LibroIvaComprasController, ComprasController, ReportesFiscalesController, ReporteMensualIvaController, ReporteSaldoIvaController, FacturacionAlertasController, AccountingClosuresController. Vistas: app/Views/pages/admin/facturacion (20).

## Mandato
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md y docs/ARQUITECTURA_ADMIN_V3.md. Auditar UNA vez rutas, lógica, consultas, seguridad, permisos, integridad fiscal, ARCA, exportaciones y vistas derivadas; implementar correcciones y rediseño integral en ventana grande. Referencia estética /admin/seo/auditoria: cards compactas, botones, tipografías y densidad; reutilizar tokens y componentes Admin V3. HTML bien estructurado, CSS solo de módulo, responsive integral. Prohibido operaciones fiscales reales, modificar shell/shared sin coordinación o tocar BD comercial destructivamente. QA aislado con evidencia y entrega final compacta. Registrar resultados aquí.

## Prompt inicial del especialista
MiTiendaPesca: asumí Facturación de punta a punta bajo este documento. Analizá una sola vez e implementá TODO el módulo y las vistas derivadas, salud de código + rediseño HTML/CSS V3 tomando SEO Auditoría como referencia; QA integral sin microventanas. Respetá coordinación y archivos ajenos. Cierre breve para MAIN CHAT: MÓDULO | ESTADO | CAMBIOS | QA | ARCHIVOS/COMMIT | BLOQUEO Y SOLUCIÓN.


## Intervención del especialista — 23/10/2026

**Estado real: implementación visual parcial; QA técnico inicial aprobado. No habilitar integración como cierre fiscal.**

### Cambios efectivamente aplicados

- Se editó exclusivamente el CSS existente `public_html/assets/css/admin-v3/pages/billing.css`, sin crear una hoja paralela. Se migraron referencias heredadas de colores, espaciados y radios `--v2-*` a tokens de Admin V3; se mantuvieron valores de respaldo para colores semánticos opcionales.
- Se retiraron las compensaciones de offset/gutter lateral asociadas al shell V2 que figuraban en el CSS de Facturación; el shell V3 debe administrar esa dimensión.
- Se añadió un namespace `av3-fiscal-page` a los contenedores iniciales de 18 vistas renderizadas del módulo (incluye control, comprobantes, libros IVA, compras, alertas, cierres y reportes). `reportes/index.php` solamente incluye `home.php`, y `reportes/resumen.php` delega en un parcial; ambos permanecen intactos.
- Se incorporaron reglas de presentación compatibles con Admin V3 para tarjetas, filtros, labels, controles, botones, tablas, foco visible y pantallas estrechas. Se conservan los atributos `name`, métodos de formularios, hooks JS, rutas, tokens CSRF y acciones de ARCA.
- No se modificaron bases de datos, modelos fiscales, servicios de emisión, rutas compartidas ni parámetros comerciales. No se ejecutaron operaciones fiscales reales.

### QA ejecutado

- PHP lint local: **20/20 vistas** sin errores de sintaxis.
- PHP lint local: **10/10 controladores** relacionados con Facturación sin errores de sintaxis.
- Gate transversal invocado con `C:\\xampp\\php\\php.exe scripts/build/gate.php`. Resultado definitivo: **OK, exit code 0**, gate transversal con 894 archivos PHP, 120 JavaScript y todos sus contratos (71,78 s).
- Auditoría parcial de superficie de seguridad: se constató el uso de `admin_required()` en consultas de comprobantes y `verify_csrf_or_403()` en mutaciones identificadas; esto **no equivale a validar permisos y CSRF de todos los flujos**.

### QA pendiente y límites verificables

- No se ha comprobado render auténtico ni interacción con navegador a 363/390/768/1024/1600 px: no declarar aprobación visual.
- Pendiente circuito HTTP autenticado de sólo lectura y pruebas negativas de permisos/CSRF en base aislada, sin llamar ARCA real.
- Pendiente validación del dominio fiscal profundo: integridad, idempotencia y concurrencia de reintentos, cierres y persistencia de comprobantes; no asumir seguridad por lint.
- Debe revisarse la composición de vistas que muestran avisos antes del contenedor principal: el namespace está sobre el primer contenedor y no necesariamente abarca los paneles posteriores. Corregir eso antes de homologación estética.
- Integración Git, commits y publicación reservados al MAIN CHAT. No se efectuó commit.

### Archivos propios modificados

`public_html/assets/css/admin-v3/pages/billing.css`; 18 vistas bajo `app/Views/pages/admin/facturacion/**`; este informe. `docs/ESTADO_DESARROLLO.md` estaba modificado por otra sesión y **no fue editado**.

### Endurecimiento focal posterior al gate

Se corrigió `app/Controllers/Admin/AccountingClosuresController.php` incorporando `admin_required()` explícito al comienzo de `index()`, `show()`, `close()` y `export()`. La acción de cierre ya conserva su chequeo CSRF y sesión, y no se realizó ningún cierre real. En el manejo de excepciones de `close()` se evita devolver al cliente el texto de la excepción de backend; se escribe en el log servidor y se presenta un error genérico. **QA**: `php -l` del controlador sin errores; `git diff --check` de archivos propios sin errores. El gate transversal final fue previo a este parche, por lo que no representa una regresión posterior del cambio de controlador.

**Pendiente específico**: verificar en entorno privado el HTTP 401/403 según la implementación de `admin_required()` y las operaciones con CSRF inválido; no afirmar cierre seguro únicamente a partir de la comprobación estática. No se tocó ninguna parte de `docs/ESTADO_DESARROLLO.md` ni de otras asignaciones.

## Continuación técnica integral — 23/10/2026 (segunda intervención)

**Dictamen:** avance funcional y de seguridad comprobable; pendiente validación integral HTTP/SQL real y navegador autenticado antes de declarar CERRADO. Esta sección complementa y actualiza el informe anterior; no sustituye sus límites.

### Alcance revisado y criterio técnico

- Controladores: 10 controladores del dominio fiscal (compras, comprobantes, libros IVA, reportes, alertas, control y cierres). En controladores heredados de `BaseController` se observó el guard de administración del constructor. Los controladores independientes usan `admin_required()` en sus acciones; se reforzó de forma explícita en cierres y se verificaron los métodos POST principales.
- Servicios: `FacturacionArcaService`, `FacturaAWorkflowService`, `FacturaPdfService`, servicios de libros IVA, compras, adjuntos, reportes, alertas y cierres. Se revisaron validaciones, idempotencia, estados y llamadas a repositorios. `FacturacionArcaService` y `FacturaPdfService` son archivos extensos; refactorización general no realizada sin E2E fiscal aislado por riesgo de regresión.
- Repositorios y contratos SQL: comprobantes fiscales, compras, cierres, libros, PDFs, reportes y alertas. Consultas de filtros examinadas usan parámetros PDO en las rutas revisadas; `groupBy` de series se limita a `day/month`. El esquema versionado define UNIQUE de comprobante de compra y UNIQUE de período de cierre (evidencia de esquema, no garantía sobre la base instalada).
- Presentación: 20 archivos de vistas, incluidos wrappers delegados, parciales de control, compras, cierres y reportes; CSS existente `billing.css`. Se conservó Page Header único y la navegación fiscal.

### Hallazgos corregidos en esta intervención

1. `PurchaseInvoiceService`: fechas estrictas (también rechaza días imposibles), total negativo nunca transformado silenciosamente a positivo, total cero inválido, comprobación de existencia y estado antes y después de actualizar una compra.
2. `PurchaseInvoiceRepository`: actualización impedida para compras anuladas; cambios de estado atómicos con predicados `status` y resultado booleano por `rowCount()`, evitando declarar éxito en validaciones/anulaciones repetidas o inexistentes. Se suprimió un `LIMIT 1` redundante en UPDATE por PK.
3. `ComprasController`: guard CSRF explícito en guardar, validar, anular y gestionar adjuntos; se comprueba que exista la compra antes de manipular o servir archivos. Se informa al usuario cuando no ocurrió una transición o el registro desapareció durante la edición.
4. `PurchaseInvoiceAttachmentService`: prohibido copiar cualquier archivo local como sustituto de `move_uploaded_file`; se exige subida HTTP auténtica.
5. `MonthlyClosureService`: períodos `YYYY-MM` de mes válido, usuario positivo; `AccountingClosuresController` mantiene CSRF y administra acceso, sin devolver detalles internos de excepciones al cliente.
6. `ComprobanteFiscalRepository`: protección en `upsertPendingForOrder()` contra volver a pendiente una factura emitida, también en UPDATE condicional y relectura cuando no hubo filas modificadas; incorporación de import correcto de `Throwable` en el repositorio.
7. Exportaciones: nueva `FiscalExportGuard` para fecha de inicio/fin, componentes seguros en nombres de archivos y neutralización de fórmulas inyectadas en celdas CSV, sin convertir importes negativos numéricos a texto. Adaptados `LibroIvaController`, `LibroIvaComprasController` y `ReportesFiscalesController`.
8. `ReportesFiscalesController`: corregidas las claves de los conjuntos `duplicates_exact` y `non_billable_with_invoice` para exportaciones, que antes no coincidían con los datos de `ReportesFiscalesService`.
9. Estilos: CSS heredado de Facturación migrado a tokens Admin V3, namespace aplicado a 18 vistas y al parcial de resumen, con contenedores, tarjetas, controles, tablas y breakpoints compatibles. Sin alterar shell ni estilos globales.

### Evidencia de pruebas, nivel real de cobertura

- `scripts/dev/facturacion_domain_smoke_private.php`: smoke reproducible, SQLite **en memoria**, sin tocar `.env`, red, ARCA ni bases comerciales. Valida fechas y meses inválidos, usuario nulo, totales, edición/anulación/validación de compras, persistencia de estado, UNIQUE de cierre, datos del snapshot, sanitización de exportaciones y escritura/lectura real de CSV en stream temporal. Incluye una aserción explícitamente estática para la correspondencia de claves de exportación.
- `scripts/dev/facturacion_visual_cdp_private.mjs`: Edge headless con perfil y puerto propios, emulación CDP del viewport CSS **exacto**. Fixture CSS sintético (no sesión real), cinco anchos **363/390/768/1024/1600**: sin overflow horizontal, `innerWidth` exacto y estilos de borde cargados. El script cerró su navegador y eliminó su perfil temporal. No prueba toda la navegación, formularios reales ni accesibilidad operativa.
- `php -l` del controlador y servicios modificados: correcto en verificaciones focales; `git diff --check` de cambios propios: sin errores de whitespace.
- `scripts/build/gate.php`: ejecución integral post-modificación iniciada; el resultado definitivo se registra en la siguiente adenda. No equivaler este gate a SQL/HTTP E2E.

### Pendientes técnicos que impiden declarar el cierre

- La prueba con MariaDB privada y autenticación/CSRF **originales** no puede sustituirse por SQLite o stubs. `docs/EJECUCION_E2E_MARIADB_PRIVADA.md` identifica un runner de ejecución **exclusivamente local**, y señala que el control de seguridad del acceso remoto rechazó la creación del esquema privado. No eludir esta protección: la solución es que el propietario ejecute el runner local autorizado o que MAIN CHAT coordine un entorno privado autenticado equivalente para Facturación.
- Falta QA SQL real con transacciones, bloqueos e idempotencia de emisión/reintentos ARCA, carreras de notas de crédito y cierres bajo escrituras concurrentes. UNIQUE de períodos evita duplicados físicos, pero no prueba consistencia de snapshots contables entre lecturas concurrentes. No llamar probado a lo no ejecutado.
- Falta E2E HTTP autenticado de altas de compras, formularios, CSRF inválido y permisos efectivos, más inspección visual con datos reales/sintéticos renderizados por todas las rutas en el shell Admin V3. Las pruebas CDP actuales son **sintéticas**.
- Deuda de rendimiento/mantenibilidad: `PurchaseInvoiceRepository::list()` tiene límite fijo de 500 sin paginación por offset; el motor ARCA y el generador PDF requieren extraer métodos extensos solamente cuando exista una suite fiscal real que proteja su comportamiento. Revisar permisos administrativos granulares contra la matriz de roles efectiva, sin inventar privilegios.
- Sin autorización para conectarse a ARCA producción, enviar correos reales, ejecutar cierres comerciales o acceder a la base activa. No se realizaron dichas operaciones.
- No se ejecutó commit ni push; integración Git corresponde al MAIN CHAT. Cambios concurrentes ajenos permanecen intactos.

### Archivos modificados o creados por esta continuación

- Controladores: `AccountingClosuresController.php`, `ComprasController.php`, `LibroIvaController.php`, `LibroIvaComprasController.php`, `ReportesFiscalesController.php` bajo `app/Controllers/Admin/`.
- Servicios: `app/Services/Billing/{PurchaseInvoiceService,PurchaseInvoiceAttachmentService,FiscalExportGuard}.php`, `app/Services/Accounting/MonthlyClosureService.php`.
- Repositorios: `app/Repositories/Billing/{PurchaseInvoiceRepository,ComprobanteFiscalRepository}.php`.
- Vistas y CSS: 18 vistas bajo `app/Views/pages/admin/facturacion/**`, `app/Views/partials/admin/facturacion/reportes/resumen/content.php`, `public_html/assets/css/admin-v3/pages/billing.css`.
- QA reproducible: `scripts/dev/facturacion_domain_smoke_private.php`, `scripts/dev/facturacion_visual_cdp_private.mjs`, además de este informe. El primer smoke visual sin emulación CDP exacta queda supersedido y debe retirarse.


### Resultado consolidado de QA al cierre de esta intervención

- **Dominio/persistencia sintética:** `C:\xampp\php\php.exe scripts/dev/facturacion_domain_smoke_private.php` → **37/37 OK**, cero errores. Base SQLite temporal en memoria; cubre transiciones, restricciones UNIQUE, validaciones, negativos, CSV generado y contratos de claves. No es una homologación MySQL o ARCA.
- **Responsive CSS sintético:** `node scripts/dev/facturacion_visual_cdp_private.mjs` → **5/5 OK** con Edge headless y emulación CDP real del ancho de viewport: 363, 390, 768, 1024 y 1600 px. `documentElement.scrollWidth <= innerWidth` y estilos V3 aplicados en todos; perfiles y proceso propios terminados, carpeta temporal eliminada. No se usó una sesión autenticada.
- **Lint/contratos globales:** `scripts/build/gate.php` ejecutado tras los cambios sustantivos: 902 archivos PHP, 120 JS y los contratos de regresión/checkout/importaciones/SEO reportaron paso. **Gate global = FAIL exit 1**, exclusivamente en la etapa `UTF-8 sin BOM`.
- **Diagnóstico de esa falla (origen ajeno a Facturación):** `scripts/audit/encoding_scan.php --bom-only` encontró exactamente **1 BOM**, en `public_html/assets/css/admin-v3/pages/audit.css`, archivo del especialista Auditoría. No modificar un archivo reservado por otro módulo. **Solución:** especialista Auditoría elimina el BOM sin cambiar el contenido CSS, MAIN CHAT reejecuta el gate tras integración. No declarar gate aprobado en el estado actual.
- `git diff --check` sobre los archivos modificados de Facturación no produjo errores; únicamente advertencias de futura normalización LF/CRLF. El script inicial `facturacion_visual_css_smoke_private.py` se retiró porque Edge ajustaba erróneamente el ancho móvil; el test CDP lo reemplaza.
- No quedan servidores, bases de datos, perfiles de navegador o ventanas de ensayo propios persistentes. Sin commit ni push.

**Estado comunicado al MAIN CHAT: PARCIAL (implementación técnica efectiva y QA aislado sustancial, sin aprobación integral fiscal).** Dependencias pendientes verificables: runner SQL privado de ejecución local exclusiva, E2E HTTP autenticado original, evaluación de concurrencia ARCA/SQL y revisión visual real en el shell; además, gate cruzado bloqueado por BOM de Auditoría. No se debe publicar ni activar operaciones fiscales por estas pruebas.

## Homologación final solicitada por MAIN CHAT — 23/10/2026

**Estado: PARCIAL, sin aprobación HTTP/MariaDB real.** Esta adenda reemplaza los resultados históricos del gate/BOM: MAIN CHAT resolvió el BOM y el gate global se volvió a ejecutar exitosamente durante esta intervención.

### Cambios nuevos limitados al cierre
- Compras: se sustituyó `PurchaseInvoiceRepository::list()` con límite fijo de 500 por `filteredSql()` compartido, `count()` filtrado y selección SQL `LIMIT/OFFSET` con valores enteros vinculados mediante PDO. Orden estable `issued_at DESC, id DESC`.
- `PurchaseInvoiceService::count()` y `ComprasController::index()`: 25 registros por página, conteo total, corrección de página fuera de rango, `page` acotado, combinación de filtros conservada. No cambia los endpoints POST ni el esquema SQL.
- `FacturacionComprasIndexViewModel`: metadatos `page/pages/total/from/to`, URLs de navegación con filtros, control de estados sin links inactivos.
- `app/Views/pages/admin/facturacion/compras/index.php`: navegación accesible `aria-label="Paginación de compras"`, enlaces anterior/siguiente y rango visible. Reglas responsive propias en `billing.css`.
- `scripts/dev/facturacion_domain_smoke_private.php`: extendido con 3 pruebas focales SQL SQLite sobre conteo/paginación, total **40/40**. El test del repositorio usa `per_page=2`; el controlador usa 25.

### Evidencia ejecutada
1. `C:\xampp\php\php.exe scripts/dev/facturacion_domain_smoke_private.php`: **40/40 OK**, todas las escrituras limitadas a SQLite en memoria; sin tocar `.env`, la base de XAMPP ni ARCA.
2. `node scripts/dev/facturacion_visual_cdp_private.mjs`: **5/5 OK**, Edge headless CDP, anchos CSS 363/390/768/1024/1600 exactos, sin desbordamiento en el fixture. El navegador y el perfil propios se cerraron/eliminaron correctamente. **Es un fixture sintético; no valida las vistas reales dentro del shell con credenciales.**
3. `php -l`: repositorio, controlador, ViewModel y vista de Compras sin errores.
4. `git diff --check` para el cambio paginado: sin errores.
5. `C:\xampp\php\php.exe scripts/build/gate.php`: **OK, exit 0**, 904 archivos PHP, 120 JS y contratos transversales. El BOM de Auditoría está resuelto. Tiempo del gate ~71 s.

### Casos que requieren entorno del propietario y no se ejecutaron

**Restricción expresa:** `docs/EJECUCION_E2E_MARIADB_PRIVADA.md` reserva el runner de base SQL privada para ejecución local del propietario; no se invocó desde Desktop Commander ni se creó un ejecutor equivalente para sortear ese requisito. El E2E transversal SQL previamente aprobado no equivale a homologación de Facturación.

Para completar una *nueva* homologación fiscal el MAIN CHAT deberá coordinar, con el propietario, una base MariaDB descartable y un servidor HTTP privado autenticado que cumplan esos controles. Casos exactos pendientes:
- HTTP anónimo/cliente/admin sobre GET y POST de compras, cierres y comprobantes; confirmación de denegación por rol y token CSRF inválido; POST correcto con token válido, id manipulado o inexistente y adjuntos maliciosos, comprobando códigos HTTP y ausencia de escrituras no autorizadas.
- MariaDB/InnoDB con datos ficticios: crear/editar/validar/anular compras; verificar UNIQUE proveedor/tipo/PV/número, FK, transacción y persistencia; dos peticiones de cierre del mismo mes con prueba de UNIQUE y snapshot consistente frente a altas simultáneas; dos emisiones/reintentos concurrentes del mismo pedido usando servicios externos ARCA simulados (no llamar ARCA real), conservando CAE/estado emitido y sin duplicación; NC duplicada e idempotencia.
- Navegador Edge headless autenticado contra el shell Admin V3 real con fixtures del servidor privado, para rutas control, comprobantes, compras (incluida paginación), libros IVA, reportes, cierres y alertas, a 363/390/768/1024/1600 px; revisar altura de tablas, interacción táctil, focus y menús reales. Protocolo `docs/pruebas/PROTOCOLO_NAVEGADORES.md` exige teardown por PID propio y perfiles efímeros.
- No se declara homologación ARCA real, ni se realizaron envíos electrónicos, cierres comerciales o accesos a bases activas.

### Entrega para integración

Sin commit ni push, reservado a MAIN CHAT. Modificaciones nuevas: `app/Repositories/Billing/PurchaseInvoiceRepository.php`, `app/Services/Billing/PurchaseInvoiceService.php`, `app/Controllers/Admin/ComprasController.php`, `app/Services/Admin/Facturacion/ViewModels/FacturacionComprasIndexViewModel.php`, `app/Views/pages/admin/facturacion/compras/index.php`, `public_html/assets/css/admin-v3/pages/billing.css`, `scripts/dev/facturacion_domain_smoke_private.php` y esta documentación. Se preservan las modificaciones de otros especialistas.
