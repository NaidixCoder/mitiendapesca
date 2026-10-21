# MiTiendaPesca — Auditoría y plan de Importaciones Admin V3

> Auditoría del chat principal · 2026-10-21 · Código fuente: `C:\xampp\htdocs\mi-pesca` (único árbol, rama `main`).
> **Estado:** auditoría estática terminada; **VENTANA 1 de implementación visual autorizada al nuevo chat de Importaciones** conforme a `docs/COORDINACION.md`. Cambios de motor, DB, reversión y otros módulos requieren nuevas reservas.
> **No es aprobación de producción ni certificación E2E:** GET sin sesión a `https://localhost/mi-pesca/public_html/admin/imports` respondió 401; HEAD respondió 404 por tratamiento de método. No se accedió a la vista autenticada, no hubo escrituras comerciales ni render real en navegador en esta auditoría.

## 1. Historia y decisiones del propietario que deben preservarse

Estas reglas resumen decisiones tomadas durante el desarrollo, relevantes para recuperar contexto del nuevo chat; verificar siempre contra contratos actuales, sin recuperar código viejo:

1. `/admin/imports` es **pantalla operativa única**: subir y configurar arriba; historial **secundario y plegable**, últimas **10** importaciones; `/admin/imports/new` redirige ahí. No agregar paso redundante «Nueva importación».
2. **Flujo seguro**: archivo y opciones → **vista previa sin escritura de catálogo** → confirmación si corresponde → aplicar → detalle/auditoría. La pantalla inicial no presenta «Aplicar» antes del preview.
3. Preview: **una card principal** «Vista previa de seguridad», con «Detalle por fila» *dentro* y desplegable. Filtros Todos/Errores/Warnings/OK/Con cambios, búsqueda por SKU; si hay errores, filtro inicial Errores. Diferenciar errores globales de filas.
4. Detalle `/admin/imports/show?id=...`: quién modificó, estados, diferencias **reales de costo, precio, lista, stock y umbral** (antes → después). No ocultar cambios efectivos bajo mensajes genéricos.
5. Historial **Revertir** con confirmación, CSRF y criterio **LIFO**; proteger modificaciones manuales posteriores, compatibilidad de snapshot y condiciones de rollback. Nunca simplificar a «deshacer» sin garantías.
6. CSV oficial con separador **`;`**, UTF-8/BOM para Excel Windows; headers y orden **no deben modificarse silenciosamente**. El historial tuvo una plantilla anterior de 24 columnas; **el código actual `template()`/ `export()` usa 32 columnas** (incluye proveedor, moneda, mayoristas, envíos). La versión actual es fuente del contrato, no volver atrás.
7. CUIT del proveedor **opcional** si hay nombre; no crear proveedor inválido por falta de CUIT. Mantener compatibilidad con import/export y la UI de Productos.
8. El import CSV **debe funcionar con DB de catálogo vacía**, incluidas creaciones permitidas de marca, categoría, proveedor, producto y variantes, sin confundir resultado dry-run con persistencia.
9. Por decisión de UI se eliminaron opciones de separador y slug visibles: no reintroducirlas en formularios. Mantener detección interna del separador por compatibilidad con archivos existentes.
10. La identidad oficial del Admin V3 es **Tabler/Dashboard/Pedidos como referencias**, no el CSS anterior de Importaciones. El módulo **no gana aprobación estética** por pasar lint/gate.

## 2. Superficie técnica inventariada

**Rutas reales:** GET `/admin/imports` (index), GET `/admin/imports/new` (redirige), POST `/admin/imports/preview`, POST `/admin/imports/apply`, POST `/admin/imports/create` (alias preview), POST `/admin/imports/process` (redirige), GET `/admin/imports/show`, POST `/admin/imports/rollback`, GET `/admin/imports/template` y GET `/admin/imports/export`. Revisar `config/routes.php` sin editarlo.

**Controlador:** `app/Controllers/Admin/ImportsController.php` (**787 líneas**, index, preview, apply, subida/opciones, gate anti-autocompletado, show, template, export, rollback). Combina HTTP, almacenamiento, preparación del preview, CSV export y coordinación.

**Motor/derivados:** `app/Services/Import/{ImportService,CsvReader,RowNormalizer,RowValidator,ProductImporter,VariantImporter,InventoryImporter,AttributeImporter,ImportResult,ImportRevertService,ImportPurgeService}.php`; `app/Repositories/Imports/{ImportJobRepository,ImportRevertRepository,ProductVariantImportRepository}.php`. `ImportReader.php` está marcado **DEPRECATED**: analizar referencias antes de eliminar. Dependencias Catalog/SEO/Variants compartidas: **solo lectura** sin reserva.

**Presentación:** `app/Views/pages/admin/imports/{index,view}.php` y `app/Views/partials/admin/imports/{index,view}/*.php`; `app/Services/Admin/Imports/{ImportsViewPresenterService,ViewModels/ImportsIndexViewModel,ViewModels/ImportsViewViewModel}.php`; JS `public_html/assets/js/admin/imports/preview.js`, cargado desde `public_html/assets/js/app.js` para `pageId=admin-imports`.

**CSS actual:** **no hay `admin-v3/pages/imports.css`**, ni reglas de módulo `av3-imports`; predominan utilidades `av3-pad-*`, `av3-border-*`, `btn-*`, `badge-pill`, `av3-card`, y clases heredadas que dificultan densidad/consistencia. Crear una única hoja propia `public_html/assets/css/admin-v3/pages/imports.css`, sin editar `head.php` compartido; cargarla una sola vez por vista siguiendo el patrón local existente y verificar `index` y `show`.

**Pruebas:** JS `preview.js` está conectado en app.js. No se encontró suite focal propia `scripts/build/imports_*contract.php` / `scripts/dev/imports_*smoke.php`. Existen gate global y utilidades de limpieza/legado no equivalentes a pruebas de importación. En auditoría se ejecutó `php -l` de Controller, ImportService, RevertService, normalizador, validador e index/view: **todos OK**. No se ejecutó gate global (otras sesiones editan módulos).

## 3. Hallazgos verificados y prioridades

| ID | Severidad | Hallazgo / evidencia | Acción recomendada |
|---|---|---|---|
| I01 | **P0 visual** | Upload: formulario con múltiples contenedores/checkboxes densos; textos extensos, botones `btn-*` y clases legacy; `preflight` y `show` repiten patrones. | Rediseño mobile-first con `av3-ui-*`, panel único por estado, composición operativa, sin KPIs decorativos. |
| I02 | **P0 legibilidad** | UTF-8 válido pero **carácter literal U+FFFD** en vistas principales: `index/header.php` 1, `history.php` 10, `preflight.php` 27, `upload_form.php` 29; total **67**. | Corregir manualmente texto según contexto en esas vistas; NO reemplazo masivo inventado; regresión que no contenga U+FFFD. |
| I03 | **P1 funcional/UX** | Formulario anuncia «Archivo (CSV o Excel)» pero `accept=.csv` y subida backend solo acepta `.csv` hasta 10 MB. | Mostrar **CSV** de forma inequívoca. XLS/XLSX real no se promete ni agrega sin una fase de lector/mapeo autorizada. |
| I04 | **P1 datos UI** | `ImportService` escribe `level='warning'`, mientras `ImportsIndexViewModel` y `preview.js` filtran `warn`; los warnings pueden quedar como OK en filtros/contadores. | Normalizar estados semánticos de punta a punta con test contrato para warning/warn, sin cambiar resultados comerciales. |
| I05 | **P0 integridad a verificar** | `ImportService::run(false)` hace BEGIN y COMMIT aunque durante el bucle algunas filas sumen errores; `apply()` muestra «aplicada con errores». El preflight previo no impide problemas surgidos al aplicar. | Diseñar política precisa all-or-nothing vs parcial, probar rollback/error en BD descartable. **No cambiar la semántica sin aprobación.** |
| I06 | **P0 reversibilidad a verificar** | `ImportService` guarda `_rollback.json` **después de DB commit**, con `@file_put_contents` y excepciones silenciadas; un import terminado podría carecer de snapshot válido. | Hacer fallar/cambiar estado o checkpoint seguro ante ausencia de snapshot, con E2E de fallos antes de autorizar modificación. |
| I07 | **P1 auditoría** | `ImportsController::apply()` arma `Audit::log(...file_name => $job['file_name'] ?? '')` con `$job` no definido; `rollback()` usa `$_SESSION['user_id']` y Audit usa `$_SESSION['uid']`. | Tests de audit actor/file_name y corrección focal compatible con sesión real; verificar persistencia y sin cambios silenciosos. |
| I08 | **P1 validaciones** | `RowValidator` dice «costo final», pero `ImportService` declara costo CSV **neto sin IVA**; los mensajes pueden llevar a errores comerciales. | Unificar documentación, ayuda y mensaje con la regla realmente vigente, no recalcular costos sin pruebas. |
| I09 | **P1 seguridad** | El APPLY recibe opciones serializadas en `opt_b64` y ruta temporal `existing_file` mediante POST; `validateExistingFile` compara prefijo de paths. | Auditar integridad de preview→apply, pertenencia exacta a directorio, expiración, manipulación, doble envío e idempotencia; proteger sin romper gate de seguridad. |
| I10 | **P1 escalabilidad** | `export()` incluye consultas a variantes y atributos dentro del foreach por producto; controller prepara/exporta todo, carga `fetchAll` de productos. | Proponer export por lotes/streaming y servicio dedicado; comprobar equivalencia exacta de 32 headers antes de refactor. |
| I11 | **P1 coherencia** | Duplicación de header template/export; consultas de esquema/guard proveedor replicadas; ViewModel/presenter devuelven clases CSS `badge-pill` y utilidades como strings. | Contrato central de headers + presentación semántica; extracción gradual a servicios, no reescritura big-bang. |
| I12 | **P1 rollback/rendimiento** | Revert LIFO con snapshots v2, conflictos por `updated_at` y restauración best-effort; purga por antigüedad (183 días por defecto). | Caracterizar LIFO, escrituras posteriores, advertencias, snapshot ausente/legacy y retención antes de cambios. Nunca eliminar sin auditoría. |
| I13 | **P2 CSS/accesibilidad** | Tablas de preview e historial con scroll interno, toggles checkbox/peer, filtros solo estilizados y métricas repetidas; texto/acciones difíciles en 363–390px. | Filas móviles reales para tablas, disclosure accesible, filtros por estado y mensajes visible/teclado, manteniendo datos y notas de cambios. |

**Estos riesgos son hallazgos de lectura de código, NO incidencias reproducidas en E2E.** I05/I06/I09/I12 requieren pruebas de DB/seguridad antes de asignar correcciones. Códigos existentes de SKU, stock, IVA, moneda, precios, mayoreo, proveedor y variantes son contratos comerciales, no permiso de refactor destructivo.

## 4. Contrato UX objetivo (aplicar patrón Admin V3 sin reinterpretarlo)

**Estado A — /admin/imports sin preview:** Page Header existente «Importaciones» y descripción breve; **panel operativo principal** con archivo CSV protagonista (dropzone funcional basada en input file estándar), acciones secundarias «Plantilla CSV» y «Exportar catálogo»; bloques compactos para a) creación automática opcional, b) campos a actualizar agrupados por Operativo/Catálogo/Variantes, c) cálculo de precios opcional. Una acción dominante «Analizar archivo» (no «Importar», porque este POST solo hace dry-run). Historial últimas 10, **plegable y secundario** debajo. Sin selector visual de separador ni slug.

**Estado B — POST preview, misma ruta/página:** Page Header; **una card «Vista previa de seguridad»** con resumen compacto de lo que se creará/actualizará, errores y advertencias; «Detalle por fila» expandible **dentro**, filtros y búsqueda integrados, estados semánticos consistentes; tabla desktop y filas móviles. Si hay error, acción aplicar deshabilitada/ausente con explicación; cuando proceda, confirmación sensible en la misma card; Cancelar vuelve a /admin/imports. No introducir wizard de rutas innecesarias.

**Estado C — /admin/imports/show?id=…:** cabecera compacta, estado/autor/fechas, cambios aplicados con números pertinentes y tabla/filas con **campo: antes → después**; información de reversión segura según elegibilidad LIFO, alertas claras de snapshot/ediciones manuales/legacy; una única acción primaria coherente con el contexto. Historial/resultado escaneable en móvil.

**Composición:** colores y tokens de `docs/GUIA_VISUAL_ADMIN_V3.md`; Page Header **20px** del shell; texto operativo 14px, títulos secundarios 16px, controles 36–40px; `av3-ui-card/field/control/table/badge/btn`; namespace `av3-imports__*` y clases `data-*` para JS. No Tailwind, no nuevos `-v2`, no overrides acumulados, nada de estilos globales/otro shell. Preservar el HTML funcional `name`, CSRF, actions, IDs/hooks e interacción de confirmación.

## 5. Implementación escalonada y reservas

### VENTANA 1 — **AUTORIZADA AL NUEVO CHAT: UI y contratos presentacionales**

- Archivos propios permitidos: `app/Views/pages/admin/imports/index.php`, `view.php`, **solo** `app/Views/partials/admin/imports/**`, `public_html/assets/js/admin/imports/preview.js`, **nuevo** `public_html/assets/css/admin-v3/pages/imports.css`, pruebas/contrato focal nuevos `scripts/build/imports_admin_contract.php` y `scripts/dev/imports_readonly_smoke.php` (si es seguro), `docs/modulos/importaciones.md`.
- `ImportsIndexViewModel.php` / `ImportsViewViewModel.php` / `ImportsViewPresenterService.php`: **solo ajustes de datos presentacionales / normalización de estados**, comprobando contratos; **sin cambio de negocio, consultas o repositorios**. Para ellos primero documentar diff previsto en informe propio.
- Alcance: HTML+CSS completos en **index / preview / show**, textos U+FFFD corregidos, accesibilidad y filtros/warnings, etiquetas CSV claras, responsive y densidad. Integrar hoja CSS una sola vez por pantalla sin editar `head.php`, `app.js`, componentes globales. Evitar modificar el código vivo de Marcas/Categorías/Atributos.
- **Prohibido en V1:** editar `ImportsController.php`, `app/Services/Import/**`, `app/Repositories/Imports/**`, otros `Catalog/SEO/Variants`, `config/routes.php`, migraciones/BD, `head.php`, `app.js`, gate global, rutas públicas. No realizar APPLY/ROLLBACK reales en BD habitual.
- **Puerta:** contrato focal y JS/PHP lint, gate cuando no coincida con pruebas pesadas de otros chats, navegador autenticado seguro o entorno aislado sobre 363/390/768/1024/1600 con capturas y navegación por teclado; validar upload form `name`, CSRF, preview→apply *sin aplicar datos comerciales*, filtros/warnings, historial/revert controls, filas de diff. Marcar **PENDIENTE DE APROBACIÓN VISUAL** hasta revisión del propietario. Documentar y detenerse para ventana 2.

### VENTANA 2 — NO AUTORIZADA AÚN: refactorización backend NO SEMÁNTICA

- Extraer preparación de `ImportsController` a casos de uso propios (Upload/Preflight/Apply/Export/Template) preservando rutas, opciones y resultados.
- Unificar plantilla/export en contrato de 32 headers, evitar N+1 bajo pruebas, separar DTO / ViewModel / clase CSS; validar que CSV exportado pueda reimportarse sin pérdida de variantes/SKU/precios/stock/IVA/moneda/proveedor.
- Archivo `ImportReader.php` DEPRECATED: únicamente caracterizar y decidir retirada cuando `rg`/smokes prueben cero consumidores. Nunca borrar por apariencia.

### VENTANA 3 — NO AUTORIZADA AÚN: integridad de aplicación/reversión y seguridad

- Resolver I05/I06/I07/I08/I09/I12 mediante tests MySQL descartables, errores forzados, concurrencia, replay y rollback; definir si APPLY permite parcialidad o exige all-or-nothing. Confirmar con propietario antes de cambiar semántica.
- No invadir `ProductImporter`, `VariantImporter`, repositorios de marcas/categorías/atributos/proveedores compartidos sin reserva por archivo; Categorías está ejecutando Ventana 4 con su propio controlador/SEO.

### VENTANA 4 — NO AUTORIZADA AÚN: validación final y revisión visual de aceptación

- E2E autenticado sin datos comerciales: CSV 32 headers, variantes/atributos, BD vacía, proveedores opcionales, dry-run/no-write, apply, cambios antes/después, rollback LIFO/conflictos, archivo inválido, límites 10 MB, export roundtrip, SEO/categorías sin efecto secundario, rate limits, CSRF, contraste y teclado. Capturas responsive antes/después.
- Protocolo de navegadores `docs/pruebas/PROTOCOLO_NAVEGADORES.md`; headless, PIDs propios, finally/teardown, no matar Firefox ni reiniciar XAMPP. Chat principal hace integración/commits selectivos tras autorización.

## 6. Criterios de coordinación y entrega

- Nuevos archivos de Importaciones **reservados exclusivamente** para el chat especializado. Como todos comparten carpeta XAMPP y `main`, verificar `git status` antes y después, **no hacer Git** desde el chat nuevo, no editar globales ni invadir otro módulo.
- Antes de programar V1, leer `docs/COORDINACION.md`, `docs/GUIA_VISUAL_ADMIN_V3.md`, `docs/ARQUITECTURA_ADMIN_V3.md`, `CONTRATO_VISUAL_ADMIN_2026.md`, `docs/PROTOCOLO_GIT_MULTICHAT.md`, `docs/pruebas/PROTOCOLO_NAVEGADORES.md` y las vistas Dashboard/Pedidos. **No repetir esta auditoría desde cero**, verificar únicamente diferencias respecto a este corte.
- Registrar en `docs/modulos/importaciones.md` qué se hizo, qué se probó realmente, qué no se probó y qué requiere autorización, con capturas guardadas fuera del repositorio.
- El éxito de pruebas técnicas es necesario pero **no otorga aprobación estética**. Al cerrar V1, detenerse para evaluación visual del propietario y autorización de V2.

## Adenda 2026-10-21 — Ventana 1 implementada: verificación del principal y autorización VENTANA 1B

El chat principal leyó `docs/modulos/importaciones.md` (bitácora V1), inspeccionó las tres vistas y la hoja `imports.css`, y **revisó personalmente cinco capturas sintéticas** (`upload/preview/show`, anchos 390 y 1600 px). Además **re-ejecutó** `scripts/build/imports_admin_contract.php`: **68/68 OK**; `node --check admin/imports/preview.js`: OK; `git diff --check` focal: sin errores. El chat especialista documentó gate global **831 PHP/120 JS OK**, sintéticos 363/390/768/1024/1600 y teardown. **No equivale a prueba autenticada ni aprobación estética final**. La carga y el flujo de confirmación no se probaron con la sesión real.

**Dictamen:** implementación visual V1 **aceptada técnicamente**; se considera bien encaminada estéticamente, pero **sin aprobación visual definitiva**. Para reducir cambios no probados, NO se autoriza todavía la V2 de refactor backend. Se autoriza **Ventana 1B (QA autenticado y corrección visual/accesibilidad)** exclusivamente al chat de Importaciones.

### Ventana 1B — autorización concreta

**Archivos de escritura:** exactamente los **mismos de V1**: vistas y parciales exclusivos `app/Views/pages/admin/imports/{index,view}.php` y `app/Views/partials/admin/imports/**`; `public_html/assets/css/admin-v3/pages/imports.css`; `public_html/assets/js/admin/imports/preview.js`; presenter/ViewModels de Importaciones solo para estado/payload presentacional; `scripts/build/imports_admin_contract.php`, scripts focales de test exclusivos, y `docs/modulos/importaciones.md`. **Ninguna edición backend de Controller, Service, Repository, shared assets/JS/CSS, shell, otras vistas ni datos comerciales.**

1. **QA real:** navegar, con sesión admin legítima y aislada, las tres superficies sobre código real con shell/sidebar/page header. Si no se dispone de autenticación propia, montar **copia aislada de código y BD con solo estructura/fixtures sintéticos**, usando el procedimiento de categorías, y reportar explícitamente la diferencia respecto del navegador comercial. No falsificar una sesión real con maqueta HTML. No acceder/tocar sesión del Firefox del propietario.
2. **Interacciones:** upload CSV 32 columnas/10MB, dry-run sin escritura de DB, preview de éxito y de errores, nombres largos y unicode, filtros `warning/warn`, conteos, búsqueda, detalle expandible, scrolling, tabla/filas móvil, historial 10, estado vacío, rollback elegible/inelegible y diálogos de confirmación; probar apply/rollback **solo en BD aislada y con fixture**, sin editar el backend. Verificar CSRF y acciones POST. Distinguir pruebas de lectura de ejecución.
3. **Diseño:** contrastar con Dashboard y Pedidos **en el mismo navegador y con el shell realmente cargado**. Corregir, si hace falta, densidad, paddings, márgenes, alineación de textos/controles/botones, disposición del gran formulario, barra de filtros y metadatos del detalle. Priorizar 363, 390, 768, 1024 y 1600 px; evitar huecos de tarjetas, doble scrolling, dropdown cortado y formularios largos sin jerarquía. La captura sintética desktop actual sugiere revisar el extenso bloque de checks y las métricas del detalle (sin agregar KPI decorativos).
4. **Fidelidad:** conservar `name` POST, CSRF, ruta, upload, `opt_b64`, `confirm_phrase`, `data-confirm`, semántica LIFO y estructura CSV actual. No inventar XLSX ni cambiar all-or-nothing del motor.
5. **Salida:** capturas antes/después **reales o aisladas autenticadas** y especificar cuál, casos de error y éxito, ejecuciones de scripts focales y gate cuando no esté ocupado por otros chats; certificar teardown (no matar Firefox/XAMPP), archivar fuera de repo screenshots. Marcar `EN REVISIÓN VISUAL`, no autodeclarar diseño aprobado. Detenerse para visto bueno del propietario antes de V2.

**Pendientes funcionales de V2/V3:** I05/I06/I07/I08/I09/I10/I12 no autorizados; si se reproducen fallos graves con fixtures, documentar P0 y bloquear cambios funcionales hasta autorización puntual.

## Adenda 2026-10-21 — Ventana 1B revisada; HOTFIX P0 de reversión AUTORIZADO (Ventana 1C)

**Verificación del chat principal:** `docs/modulos/importaciones.md` registra E2E HTTP autenticado contra la aplicación completa y MySQL descartable, 50 capturas con sidebar en 363/390/768/1024/1600, preview sin escritura, apply, LIFO, rollback, CSRF y teardown. El principal leyó `ImportRevertService.php`, `ImportRevertRepository.php` y `ImportService.php`, examinó capturas reales de carga y detalle a 390 y 1600 px, y **re-ejecutó `imports_admin_contract.php`: 71/71 OK**. V1B **completada técnicamente**, el diseño es consistente con la identidad pero **aceptación visual definitiva sigue a cargo del propietario**. No se integra en Git de manera prematura.

### Riesgo P0 reproducido y confirmado por código

- El E2E aislado demuestra que un precio modificado manualmente después del import (`2480→2499`) fue restaurado incorrectamente al precio anterior (`2000`) y el job se marcó `reverted`.
- `ImportRevertRepository::getUpdatedAt()` retorna null si falta `updated_at` (la tabla real `products` no lo tiene) y `ImportRevertService::detectConflicts()` **omite** ese caso. El v2 `_rollback.json` guarda `before` pero **no tiene valores `after` verificables**.
- Otros riesgos existentes I05/I06/I07/I09/I12 siguen pendientes de un ciclo propio. Este hotfix solo debe evitar la **destrucción silenciosa de ediciones posteriores**, sin prometer que resuelve toda la integridad de la importación.

### Autorización V1C — seguridad funcional de rollback, excepcional y acotada

Se **pospone Ventana 2** (refactor general) y se habilita **Ventana 1C exclusivamente al chat de Importaciones**, con reserva temporal de escritura en:
- `app/Services/Import/ImportRevertService.php`: comprobar estado y bloquear antes de cualquier RESTORE/DELETE.
- `app/Repositories/Imports/ImportRevertRepository.php`: API de lectura de estado actual **con listas blancas de tabla/columnas**, bloqueo transaccional y operaciones del mismo módulo; no afectar repositorios Catalog.
- `app/Services/Import/ImportService.php`: **solo generación/registro del snapshot de rollback de ejecuciones futuras**, para incluir postestado verificable de campos o entidades afectados. Si se necesita modificar ProductImporter/VariantImporter/InventoryImporter u otro servicio, parar y solicitar ampliación concreta antes de editar.
- `app/Services/Admin/Imports/ImportsViewPresenterService.php`: **solo** detectar formatos seguros/incompatibles y reflejar disponibilidad real de Revertir. Si hace falta, `app/Views/partials/admin/imports/view/notices.php` **solo** mensaje de bloqueo, sin cambios estéticos amplios.
- `scripts/build/imports_admin_contract.php`, nuevos `scripts/build/imports_revert_contract.php` y `scripts/dev/imports_rollback_integrity_isolated.php` (o equivalentes), `docs/modulos/importaciones.md`.

**Contrato de seguridad obligatorio:**
1. **Nunca sobrescribir/borrrar** datos si la última versión del registro no se puede confirmar; **fail-closed** ante falta de `updated_at`, `after`, información incompleta o snapshot legacy. El timestamp es señal adicional, no única defensa. Para snapshots v2 anteriores sin `after`, **bloquear revert automático con explicación precisa** y conservar opción de recuperación manual revisada; jamás asumir ausencia de conflicto.
2. Para nuevos imports registrar un **postestado verificable** de todas las operaciones destructivas (updates y deletes por creación), incluyendo producto, variante, stock/inventario y entidades auxiliares cuando aplique. Elegir formato versionado explícito (v3 o extensión compatible), adaptar consumidor y UI **en conjunto**. No suponer seguridad si solo se compara precio y no SKU, stock, variante o atributo. Si hay una operación no contrastable, bloquear el lote completo.
3. Realizar **lectura/comparación con locks dentro de la misma transacción PDO de rollback** que ejecuta RESTORE/DELETE; detectar alteraciones manuales y carreras **antes de la primera escritura**. Sin verificación fuera de transacción que permita TOCTOU. Rechazo = **cero alteraciones del catálogo** y estado/feedback explícito; audit y LIFO coherentes con el bloqueo.
4. Preservar compatibilidad de snapshots previos sin convertirlos ni eliminarlos; para v1/v2 no verificable bloquear por defecto en lugar de ofrecer botón engañoso. Evitar que bloquear el último import permita revertir uno anterior indebidamente (revisar cómputo LIFO).
5. **No migraciones** de tablas ni columnas en BD comercial. No reescribir precio, inventario, márgenes o reglas de negocios al crear el snapshot; no cambiar la semántica de Apply salvo lo mínimo necesario para registrar estado de verificación.
6. Proteger el caso de varias filas/importaciones que tocan el mismo registro, valor NULL/0/decimales, updates no-op, entidad nueva luego editada, variante/stock, snapshot ausente/incompleto y dos reversions concurrentes. Evitar restauración parcial cuando se detecta conflicto. No confiar en comparaciones meramente textuales de floats/fechas.
7. **No ampliar** a reparación de `_rollback.json` después del commit, all-or-nothing de importación, refactor de controller/export, cambios visuales adicionales, SEO, Productos/Categorías, Settings o Git. Registrar esos problemas por separado; si impiden una solución segura, detener y pedir autorización.

**Aceptación V1C:** replicar primero el bug P0 en clon descartable (precio 2000→2480, edición posterior 2499; rollback debe quedar bloqueado y 2499 preservado); éxito normal de reversión segura sin ediciones; bloqueo para snapshot v2 sin after; alteración del precio/costo/stock/variante/creados/entidades auxiliares; ausencia de postestado/archivo; LIFO y intentos simultáneos, CSRF, audit; E2E autenticado con asserts SQL antes/después; contrato focal y gate cuando otros chats no lo estén ejecutando. Verificar explícitamente DB del fixture y teardown de procesos/credenciales; sin escritura en BD habitual.

**Cierre:** documentar pruebas y limitaciones, pedir revisión del chat principal. **Ventana 2 y Ventana 3 generales siguen NO AUTORIZADAS**. El usuario aún debe aprobar el diseño final antes de integrar el módulo o publicarlo.
