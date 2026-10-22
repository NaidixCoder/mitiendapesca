# MiTiendaPesca — Decisiones y autorización escalonada: Categorías Fase 3

> Fecha: 2026-10-21 · Responsable de decisiones/archivo: **chat principal**.
> Estado: **reglas de negocio aprobadas para la implementación progresiva del Admin; primera ventana de edición acotada**.
> Fuente de diagnóstico y escenarios: `docs/modulos/categorias.md` (§11–12). Si hay contradicción entre una propuesta anterior y este acuerdo, esta decisión prevalece. El propietario puede modificar estas reglas; cualquier cambio de alcance exige nueva coordinación.

## D1 — Visibilidad administrativa: APROBADA

- Desactivar una categoría **activa** mediante formulario o toggle: transición explícita `1 → 0` que pone en estado inactivo **al nodo y a todos sus descendientes**, en **una transacción**. No se borran productos, stock, relaciones, slugs ni SEO.
- Reactivar nodo `0 → 1`: se reactiva **solo ese nodo**, nunca sus descendientes automáticamente.
- Guardar datos de un nodo ya inactivo sin cambiar visibilidad **no propaga** nuevas desactivaciones; no sanear inconsistencias históricas como efecto colateral.
- El estado deseado debe ser explícito e idempotente dentro del servicio; el toggle existente puede actuar de adaptador HTTP. Formularios y toggle deben llamar la **misma política**.
- Auditoría y confirmación al operador deben informar el alcance de la cascada y ocurrir tras commit exitoso.

## D2 — Jerarquía: APROBADA

- Rechazar reactivación/creación/traslado de un **nodo activo** cuando cualquiera de sus ancestros esté inactivo.
- Rechazar que el traslado de un subárbol bajo ancestros inactivos introduzca descendientes activos incoherentes; no modificar automáticamente flags heredados.
- Permitir nodos inactivos bajo padres inactivos si se preservan ciclos y demás restricciones. No activar ancestros implícitamente.
- Mantener protección contra padres inexistentes, ciclos y descendientes propios. Cualquier saneamiento de datos heredados debe tener vista previa y aprobación independiente.

## D3 — Catálogo público: solo auditoría autorizada, cambio todavía NO autorizado

- Dirección funcional propuesta: categorías ocultas o con ancestro inactivo no deberían publicarse como categorías visibles en directorios/facetas ni permitir páginas de categoría incoherentes.
- **Por ahora:** caracterizar en **solo lectura** URL directa, categorías, búsqueda, home y facetas; documentar cómo afecta a los productos y otras rutas.
- No modificar `products.is_active`, stock, slugs, enlaces públicos, controladores, repositorios o servicios públicos en esta fase. El alcance/semántica de URL de producto y visibilidad requieren decisión posterior del propietario con evidencia de consumidores.

## D4 — Contrato de creación rápida desde Productos: APROBADO para ventana conjunta posterior

- Mantener `slug` **globalmente único**.
- `parent_id` enviado siempre por el formulario Productos; `0`, ausencia y `null` significan **categoría raíz** por compatibilidad, nunca "ignorar padre".
- Slug existente bajo **el mismo padre exacto** y categoría activa: HTTP `200`, `ok:true`, `created:false` y datos explícitos de la categoría reutilizada.
- Slug existente bajo **otro padre** (incluido raíz vs padre): HTTP `409`, `ok:false`, `code:category_slug_parent_conflict`, información mínima para resolver; no reasignar un ID de otra rama.
- Si la categoría coincidente está inactiva: no reutilizarla silenciosamente; informar conflicto/estado para decisión explícita del operador.
- Para nueva categoría válida, **conservar HTTP 200 inicialmente** por compatibilidad con el consumidor actual, incluir `created:true` y `parent_id`; HTTP 201 puede evaluarse después en una migración de contrato coordinada.
- Error o conflicto **no cierra modal**, no cambia selección actual ni borra datos del formulario de Productos. Conservar CSRF y contrato de hooks actual.
- **No implementar backend 409 aisladamente**: requiere ventana simultánea exclusiva para `CategoriesController.php`, `public_html/assets/js/admin/product_form/index.js` y `app/Views/partials/admin/products/tab_basicos.php`.

## D5 — Atomicidad Categoría/SEO/portadas: diseño APROBADO, edición compartida todavía NO autorizada

- Autorizar como **objetivo de etapa posterior** una transacción única (mismo PDO/InnoDB) para categoría + historial de slug + redirecciones + settings SEO + bloques editoriales, con rollback visible y auditoría/éxito solo después del commit.
- No transferir silenciosamente un slug histórico de otra categoría: rechazar por conflicto, preservar propiedad y valorar flujo editorial posterior. Tratar cuidadosamente A→B→A para no producir redirecciones circulares.
- Imágenes requieren staging/compensación explícita (filesystem no es transaccional); no borrar portadas anteriores ni las tres referencias históricas faltantes.
- **No editar por esta autorización** `SeoManagerService`, repositorios SEO, `SeoSlugHistoryRepository`, migraciones ni otros consumidores. Revisar contratos existentes primero y solicitar reserva si resulta necesario.

## D6 — Ventanas exclusivas / responsables

### VENTANA 1: AUTORIZADA para chat de Categorías (solo tras releer esta decisión)

**Solo estos archivos:**
- `app/Services/Catalog/CategoryAdminService.php` — reserva exclusiva **para Categorías** durante esta fase, aunque su ubicación sea compartida.
- `app/Services/Admin/Categories/CategoryHierarchyGuard.php` — propio.
- **Nuevo:** `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php` (o un único servicio equivalente justificado sin duplicar reglas).
- `scripts/build/categories_admin_contract.php` y **pruebas focales de Categorías**, sin editar gate global.
- `docs/modulos/categorias.md` — informe exclusivo del chat.

**Alcance:** implementar y probar contrato de transición explícito, cascada y guardia jerárquica transaccionales sobre **MySQL aislado**. Conservar `toggleActiveCascade()` u ofrecer adaptador compatible para consumidores actuales. Nada de CSS, vistas, frontend público, quick, SEO ni controller en esta primera ventana.

**Puerta de salida:** demostrar V01–V09 de §12.6 según alcance accesible (concurrencia MySQL con dos conexiones donde aplique), errores y rollback. Explicitar qué pruebas HTTP dependen de adaptar controller en siguiente ventana; no declarar V01/V02 HTTP si no se ejecutaron. Build gate y limpieza de navegadores conforme protocolo. Actualizar informe y **detener edición** a la espera de autorización para VENTANA 2.

### VENTANA 2: NO ABIERTA
Adaptar `CategoriesController.php` para enviar save/toggle al caso de uso común, con confirmaciones puntuales; después de aprobar resultados de Ventana 1. No rediseñar CSS.

### VENTANA 3: NO ABIERTA
Crear/usar contrato quick 409 y enviar `parent_id` desde el modal de Productos en una **única ventana coordinada** y con propietario exclusivo de los dos archivos Productos.

### VENTANA 4: NO ABIERTA
Orquestación SEO/historial/archivos con revisión/reserva precisa de servicios compartidos, y tests forzando fallas sobre DB descartable.

### VENTANA 5: NO ABIERTA
Auditoría pública solo lectura ahora; edición pública únicamente tras acuerdo comercial posterior.

## D7 — Verificación: APROBADA

- Matriz y secuencia de pruebas de `docs/modulos/categorias.md` §12.6–12.7, ejecutando solo los casos aplicables por etapa, con entornos MySQL privados y fixtures descartables.
- No realizar escrituras sobre la base habitual ni pruebas destructivas contra instalaciones compartidas.
- Navegador headless por defecto y cierre garantizado de recursos propios según `docs/pruebas/PROTOCOLO_NAVEGADORES.md`.
- QA visual y aprobación del propietario son independientes de build gate. No declarar cierre funcional total hasta completar las fases realmente aprobadas.

## Gobierno

- Una carpeta y rama `main`; **los chats especializados no realizan Git** ni cambian ramas. El chat principal integra selectivamente solo tras pruebas y aprobación.
- Reservas documentadas **no equivalen a bloqueo de filesystem**: el propietario debe comunicar esta autorización al chat especializado; los demás chats deben respetar los archivos asignados.
- No modificar `docs/ESTADO_DESARROLLO.md`, `MANIFEST.md`, archivos de Productos/SEO/Público ni CSS del Admin desde el chat especializado sin nueva autorización.

## Actualización 2026-10-21 — Ventana 1 validada y Ventana 2 AUTORIZADA

**Evidencia:** `docs/modulos/categorias.md` §13 documenta implementación del servicio único, pruebas directas MySQL con fixtures, transacciones propias/externas y dos procesos concurrentes; contrato focal **16/16**, build gate **828 PHP / 120 JS OK** en su ejecución. **No son pruebas HTTP del formulario** y la inconsistencia persiste hasta completar esta ventana.

**VENTANA 1 CERRADA.** Conserva la reserva del servicio de estados y guard jerárquico para el mismo responsable solo si es indispensable un ajuste derivado de la integración.

### Ventana 2: permiso de edición exclusivo al chat de Categorías

**Archivos autorizados:**
- `app/Controllers/Admin/CategoriesController.php`: únicamente métodos `save()` y `toggle()` y dependencias/imports estrictamente necesarios. **No tocar `quick()`**.
- `app/Services/Catalog/CategoryAdminService.php`, `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php`, `app/Services/Admin/Categories/CategoryHierarchyGuard.php`: solo ajustes mínimos derivados del contrato probado.
- `app/Views/pages/admin/categories/form.php` y `app/Views/partials/admin/categories/toggle_action.php`: **solo** mensajes/confirmación de impacto o error funcional, si fueran imprescindibles. Sin cambios CSS.
- `scripts/build/categories_admin_contract.php` y pruebas focales exclusivas de Categorías en `scripts/dev/`.
- `docs/modulos/categorias.md` (informe exclusivo).

**Alcance funcional obligatorio:**
1. Formulario y toggle deben ejecutar el **mismo caso de uso de visibilidad**; en `save` usar estado deseado explícito (no aplicar toggle); en toggle preservar su contrato existente.
2. Validar dentro de una misma unidad de trabajo transaccional el estado y jerarquía final (padre nuevo, hijos y ancestros). Evitar TOCTOU: validar y escribir bajo locks consistentes. Conservar unicidad y validaciones anteriores.
3. Al editar, **no escribir `categories.is_active` por fuera del servicio**. La actualización de los campos propios de categoría y su transición de estado deben ser atómicas **entre sí**; si falla validación/cascada, `ROLLBACK` y **no** emitir flash/auditoría de éxito ni dejar cambios parciales.
4. En creación, aplicar `validatePlacement` con estado pedido y padre elegido antes de escribir; si hay error, no crear registro. En edición, rechazar reparentado/activación inválida sin persistencia parcial.
5. Mantener idempotencia (guardar otro dato de nodo inactivo no vuelve a desactivar descendientes); al activar un padre, no activar hijos. Mostrar mensaje útil sobre alcance cuando desactiva descendientes; auditar estados reales tras commit.
6. La gestión SEO e imágenes actual **no es aún plenamente atómica** y pertenece a **Ventana 4**. No asegurar que esté corregida. Si la integración detecta que el flujo actual deja un riesgo nuevo o intolerable, detenerse e informar, sin ampliar por cuenta propia a servicios SEO ni subir cambios destructivos.
7. Preservar rate limit, CSRF, rutas, nombres POST, uploader y semántica de redirección. No intervenir `quick`, Productos, catálogo público, CSS, layout, `config/routes.php`, `scripts/build/gate.php` ni migraciones.

**Criterio de aceptación de Ventana 2:** pruebas **HTTP autenticadas reales sobre MySQL descartable** con `SELECT DATABASE()` y CSRF para desactivar por save/toggle (estados equivalentes), reactivar nodo solo, guardar otro campo sin cascada, rechazar ancestro inactivo/reparentado/ciclo, rollback ante fallo SQL, auditoría/feedback apropiados y regresión de alta/edición/SEO. Verificar no hubo escrituras en BD habitual. Ejecutar contrato focal y build gate, navegador headless con teardown si aplica. Documentar claramente cualquier limitación de atomicidad SEO/archivos. **Detenerse y solicitar aprobación de Ventana 3 tras cerrar y documentar Ventana 2.**

**Ventanas 3 (quick + dos archivos Productos), 4 (SEO/portadas) y 5 (público): siguen NO ABIERTAS.** No se autoriza editar sus archivos con este permiso.

## Actualización 2026-10-21 — Ventana 2 validada; Ventana 3 AUTORIZADA

**Verificación del principal:** `docs/modulos/categorias.md` §14 registra HTTP autenticado sobre MySQL aislado, cascada consistente save/toggle, rollback de categoría+visibilidad, contrato focal **20/20** y gate **828 PHP/120 JS OK**. Se comprobó en código que `save()` utiliza el servicio común. El SEO continúa postcommit y se reserva para V4.

**Ventana 2 cerrada. Ventana 3: contrato quick + consumidor Productos (D4) autorizada para UN SOLO RESPONSABLE: chat de Categorías.** Antes de editar, confirmar que ningún otro chat tenga abierto el JS o parcial de Productos. Los dos archivos Productos se conceden como **excepción temporal específica**, no como propiedad general del módulo.

**Reserva de edición exacta (y partes autorizadas):**
1. `app/Controllers/Admin/CategoriesController.php`: **solamente `quick()` y imports estrictamente necesarios**, sin rehacer `save()`/`toggle()`.
2. `public_html/assets/js/admin/product_form/index.js`: **solamente** `initCategoryQuickCreate()` y su inicialización indispensable; conservar tabs, marcas, variantes y resto del formulario.
3. `app/Views/partials/admin/products/tab_basicos.php`: **solamente** semántica/mensajes del modal quick de Categorías y selector de padre; sin reforma visual ni CSS.
4. `scripts/build/categories_admin_contract.php`, scripts focales propios en `scripts/dev/`, y `docs/modulos/categorias.md`.
5. Lectura autorizada de `CategoryHierarchyGuard` y `CategoryVisibilityPolicyService` para reaprovechar reglas; **no editar esos servicios** sin justificar por escrito una necesidad nueva y obtener autorización.

**Contrato D4 que debe implementarse de forma conjunta (backend + modal):**
- `parent_id` siempre enviado desde el selector; vacío, `0` y ausente equivalen a raíz (`null`) por compatibilidad.
- Nombre/slug válidos y padre existente **y activo en toda su cadena de ancestros**; si no, rechazo HTTP 422 sin escritura. Nunca crear un nodo activo debajo de uno inactivo.
- Slug libre: insertar bajo padre solicitado, HTTP **200** (compatibilidad), JSON `ok:true,created:true,id,name,slug,parent_id`.
- Slug ya existente y **mismo padre exacto** (incluida raíz), si está activo: HTTP **200**, JSON `ok:true,created:false,id,name,slug,parent_id`. Reutilización explícita; no insertar duplicado.
- Slug existente en **otro padre**: HTTP **409**, `code:category_slug_parent_conflict`, sin reasignar ID ni modificar categoría.
- Slug existente pero **categoría inactiva**: conflicto explícito, sin reutilizar automáticamente.
- Carrera concurrente de slug: mantener UNIQUE como última defensa y convertir fallo duplicado a 200/409/422 según estado/padre actual, nunca 500 genérico.
- Ante 409/422/error: el modal conserva nombre y padre, sigue abierto, mantiene seleccionado el producto original sin cambiar otros campos; mostrar error legible y accesible. Ante éxito seleccionar la categoría y cerrar.
- Preservar CSRF, rate limits, hooks existentes, rutas, compatibilidad de JSON actual y operación de creación rápida de **Marcas**.

**Seguridad y atomicidad:** usar validación y locks/transacción compatibles con la política de visibilidad para no introducir categorías activas bajo padres desactivados concurrentemente. Si el guard existente o el contrato de otros escritores exigen modificar un servicio compartido, detenerse y solicitar nueva reserva. No ampliar esta ventana a SEO/slug histórico/transacción editorial de V4.

**Pruebas de salida:** Q01–Q06 de `docs/modulos/categorias.md` §12.6 con HTTP autenticado en **MySQL descartable**, CSRF y `SELECT DATABASE()`; navegador headless para modal de Productos probando conservar datos ante error y seleccionar al crear/reutilizar; colisión concurrente, padre inactivo/ancestro inactivo, interacción con marca quick y regresión de Productos. Contrato, smoke focal, build gate; teardown de todos los procesos/perfiles propios según protocolo. No escribir BD habitual.

**Cierre:** informar archivos tocados, resultados reales y limitaciones en `docs/modulos/categorias.md`, detenerse y pedir autorización para Ventana 4. **No hay permiso de CSS/layout, SEO compartido, catálogo público, migraciones, rutas, gate global, Git o datos comerciales.**

## Actualización 2026-10-21 — Ventana 3 CERRADA y Ventana 4 AUTORIZADA (ámbito acotado)

**Evidencia Ventana 3:** `docs/modulos/categorias.md` §15; contrato 25/25, prueba HTTP autenticada Q01–Q06, cuatro carreras reales con dos servidores PHP y validación del modal en 390/1280 px, regresión del modal Marcas, teardown confirmado y gate global OK (829 PHP /120 JS) **en su ejecución**. La implementación de quick y su JS queda entregada dentro del alcance, sin declaración de aprobación visual.

**Reserva de Productos LIBERADA:** `public_html/assets/js/admin/product_form/index.js` y `app/Views/partials/admin/products/tab_basicos.php`. **No volver a editarlos desde Categorías en V4**; cualquier intervención nueva exige otra reserva.

### Ventana 4: aprobación para Categorías, un responsable y alcance limitado

**Objetivo:** orquestar en una unidad transaccional y coherente la escritura de categorías + visibilidad + historial de slugs + redirects + `seo_settings` + bloques SEO, con manejo seguro de la portada. Evitar falsos éxitos, reasignación histórica indebida y residuos de archivos ante rollback. No modificar el comportamiento público ni las rutas del admin.

**Reserva de escritura exacta:**
- `app/Controllers/Admin/CategoriesController.php` — **solo `save()` y helper `storeCoverImage()`** en cuanto sea imprescindible para staging/compensación. No tocar `quick()`, `toggle()` ni otros endpoints.
- Nuevo `app/Services/Admin/Categories/CategorySaveService.php` u orquestador local de nombre justificado; extraer validaciones y transacción del controlador sin perder contratos/POST.
- `app/Services/Admin/Categories/CategoryHierarchyGuard.php`, `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php`, `app/Services/Catalog/CategoryAdminService.php`: **solo si la orquestación necesita ajuste mínimo compatible**; documentar la justificación.
- `scripts/build/categories_admin_contract.php`, nuevos scripts focales `scripts/dev/categories_*.php` y `docs/modulos/categorias.md`.

**SEO compartido: SOLO LECTURA en esta ventana**, incluidos `SeoManagerService.php`, `CategorySeoBlocksService.php`, `SeoSlugHistoryRepository.php`, `SeoRedirectRepository.php`, `SeoSettingsRepository.php` y `CategorySeoBlocksRepository.php`. Reutilizar sus APIs actuales dentro del mismo PDO/transacción **solo si no hacen commits internos ni silencian fallos**. Si se requiere cambiar un contrato/repositorio común, detenerse, registrar el bloqueo y pedir reserva específica al principal.

**Punto crítico del historial:** `SeoSlugHistoryRepository::add()` contiene `ON DUPLICATE KEY UPDATE entity_id=VALUES(entity_id)`. **Debe impedirse, con protección transaccional comprobable, que un slug histórico de otra categoría se transfiera silenciosamente**. No alcanza una comprobación previa fuera de transacción; diseñar y probar guardas y concurrencia/unique o detenerse si la seguridad necesita cambiar el repositorio global. A→B→A de la misma categoría no debe generar bucles 301 ni transferir propiedad. No modificar semánticas de slugs de productos o marcas.

**Portada:** preparar/validar nueva imagen bajo ruta temporal privada; promover a destino definitivo con estrategia de compensación bien definida. Ante error de SQL/SEO o fallo de publicación, conservar portada anterior, revertir datos de DB cuando proceda y limpiar solo archivos nuevos de la petición. No eliminar las tres referencias históricas ausentes de la BD habitual. No suponer que el sistema de archivos participa en la transacción.

**Garantías:** mantener CSRF, rate limits, validaciones comerciales, JSON POST/flash, permisos, datos de SEO y categoría actuales. Flash de éxito y auditoría tras commit integral, no antes. Errores con rollback; no tragarse excepciones SEO. No alterar `products.is_active` ni otros consumidores.

**Pruebas obligatorias para cerrar:** T01–T07 y regresiones de `docs/modulos/categorias.md` §12.6, con inyección de fallas en historial, redirect, settings, bloques, archivo y commit, escenarios históricos de slug ajeno y A→B→A, concurrencia/unique, alta y edición normal, CSRF, quick y toggle como regresión, gate global/contrato focal. Todo en copia y **MySQL aislados con fixtures descartables**; verificar `SELECT DATABASE()`, sin datos comerciales. Teardown propio y evitar procesos/browser huérfanos.

**Estado de salida:** al completar, documentar la evidencia, archivos y riesgos, detener las ediciones y solicitar autorización de Ventana 5. Si surge necesidad de editar servicios SEO compartidos, **no hacerlo sin nueva reserva**. Prohibidos CSS, layout, Productos, público, rutas, migraciones, gate global, Git y operaciones en BD habitual.

## Actualización 2026-10-21 — Ventana 4 cerrada; Ventana 5 AUTORIZADA solo para AUDITORÍA PÚBLICA

**Evaluación:** `docs/modulos/categorias.md` §16 documenta orquestador `CategorySaveService` y T01–T07 en MySQL aislado: atomicidad de cinco tablas SQL, A→B→A sin loop, prevención de robo de historial para este camino, compensación de portada, pruebas de inyección de fallas, carreras y gate 830 PHP/120 JS. El chat principal leyó servicio y reejecutó `scripts/build/categories_admin_contract.php`: **28/28 OK**. No se ejecutó nuevamente E2E HTTP completo desde el principal. Archivo huérfano tras caída abrupta y repositorio SEO global no quedan resueltos y se documentan como deuda separada.

### Ventana 5: ÚNICAMENTE auditoría y propuesta de política para visibilidad pública (D3)

**Propietario:** chat de Categorías, pero **sin autorización para editar código de catálogo público**. Como otros chats trabajan en carpeta compartida, esta ventana es de lectura, diagnóstico y entrega de matriz, no de implementación.

**Superficies mínimas a caracterizar:**
- GET `/categoria?slug=...`, `/categorias`, `/productos`, `/producto?slug=...`, búsqueda/sugerencias, filtros/facetas, home/destacadas, links internos, sitemap categories/products y redirecciones SEO. Registrar rutas reales y consumers; no inferir nombres inexistentes.
- `app/Controllers/Catalog/{CategoryController,CategoriesController,ProductController,ProductsController}.php`;
- `app/Repositories/Catalog/{CategoryRepository,ProductRepository}.php`;
- `app/Services/Catalog/FacetService.php`, servicios de filtros/SEO relacionados, `app/Services/Public/{HomePageViewService,HomeSectionsViewService}.php`, `app/Controllers/System/MetaController.php`, plantillas públicas pertinentes.
- Contrastar flags `categories.is_active`, jerarquía de ancestros y `products.is_active`. Revisar la opción `catalog_hide_inactive_category_products`, su valor y consumidores; observar regla para productos sin categoría.
- Analizar categorías heredadas activas bajo ancestro inactivo, categorías directas inactivas, hijas profundas, hijos al reactivar padre, enlaces SEO y navegación. Registrar comportamiento actual observable sin sanear datos.

**Hechos de lectura ya comprobados por principal:** `CategoryRepository::findActiveBySlug()` solo filtra `is_active=1` del nodo; `FacetService::categoriesWithCounts()` carga todos los nodos activos y trata como raíces los de padre no presente; `ProductController::show()` oculta según setting solo cuando **la categoría directa** está inactiva; `MetaController::sitemapCategories()` consulta `categories WHERE is_active=1`. No son todavía resultados de E2E/impacto comercial: probar o reproducir en fixture aislado y distinguir resultados reales de hipótesis.

**Entregables obligatorios en `docs/modulos/categorias.md`:**
1. Tabla por superficie: archivo/consulta, comportamiento actual, riesgo concreto, política candidata, impacto esperado y responsable del archivo.
2. Matriz de casos de estado (activo/inactivo directo y ancestros, producto activo/inactivo, setting ON/OFF, jerarquía profunda), comprobada en tests **solo GET** sobre entorno descartable o lectura SQL, sin falsear cobertura.
3. Propuesta de contrato público con alternativas explícitas para decisión de propietario: (a) ocultar categorías/landings/facetas/sitemap si nodo o ancestro está inactivo; (b) definir **aparte** si productos propios activos de esas categorías deben seguir siendo accesibles por URL/otras búsquedas y bajo qué setting; (c) qué HTTP/SEO emplear para página oculta y redirecciones históricas (404/410/301/noindex según caso fundamentado). No cambiar estos comportamientos sin decisión.
4. Mapa exacto de archivos compartidos para futura ventana EXCLUSIVA de edición; estimación de regresiones y consultas/indexes/performance; plan de pruebas aisladas, sin trabajo de actualización de datos comerciales.
5. **Deuda separada:** señalar pero no corregir `SeoSlugHistoryRepository` global y limpieza ante crash de archivo huérfano; reservar a otro mandato de alcance específico. No expandir V5.

**Prohibiciones:** no editar `app/Controllers/Catalog/**`, `app/Services/Catalog/**`, `app/Repositories/Catalog/**`, SEO compartido, home/SEO/sitemap, settings, controllers admin, JS, CSS, HTML, rutas, DB real, migraciones, gate global ni otros chats. Solo leer y **escribir `docs/modulos/categorias.md`** (entrega). Scripts de investigación se guardan fuera del árbol web/Git y solo usan DB descartable; no ejecutar APPLY/POST/desactivaciones reales. Si se navega automatizado, protocolo de teardown obligatorio.

**Cierre:** registrar resultados y pendientes, liberar las reservas previas de CategoriesController y CategorySaveService al cerrar V4 (sin permiso a otros para tocar sin autorización), **detenerse** para que principal y propietario aprueben política pública y habiliten eventualmente Ventana 6 de implementación. Mantener estado «pendiente de aceptación visual» del admin.

## Actualización 2026-10-21 — Ventana 5 auditada; resolución técnica y Ventana 6A AUTORIZADA

**Evidencia:** `docs/modulos/categorias.md` §17 contiene matriz por ruta, 54 GET en clon sintético (settings ON/OFF), verificaciones focales de breadcrumbs, sitemaps, home y SEO, SELECT sobre BD comercial y teardown. **BD habitual: 18 categorías, ninguna rama activa debajo de ancestro inactivo; 25 productos, 19 activos y ninguno activo asignado directamente a categoría inactiva**, en el corte leído. La inconsistencia es real en código/fixtures, no incidencia comercial vigente demostrada.

### Decisiones técnicas y comercial aún reservada

- **V5-D1 — APROBADA TÉCNICAMENTE:** `category_effectively_visible` requiere `is_active=1` en nodo **y en todos sus ancestros**, cadena válida hasta raíz, sin ciclos ni IDs inexistentes. Aplicar por lectura; no cambiar `categories.is_active`, `products.is_active`, relaciones ni datos históricos. Nunca promover automáticamente una hija activa con padre invisible a raíz.
- **V5-D2 — PENDIENTE DE RATIFICACIÓN COMERCIAL DEL PROPIETARIO:** la recomendación del principal es **B1**, preservar flag `catalog_hide_inactive_category_products` (actualmente **OFF/0**) y hacerlo coherente: OFF = productos activos siguen vendiéndose aun en ramas ocultas, pero sin enlaces rotos de breadcrumb; ON = excluir también desde todos los consumidores a productos de categoría inactiva o ancestro inactivo, sin tocar su bandera. **No se autoriza implementar B1 ni ocultar productos** hasta confirmación y nueva reserva específica.
- **V5-D3 — criterio técnico provisional:** categoría activa y vacía sigue disponible por URL (200); `catalog_show_empty_categories` controla tarjeta. Indexación/SEO editorial queda para ventana SEO exclusiva con pruebas.
- **V5-D4 — criterio técnico para próxima fase:** landing de categoría jerárquicamente oculta debe devolver **HTTP 404 real**; no usar 410 para baja reversible ni hacer 301 genérico a Inicio. Reparación del `head` 404, canonical, sitemap, redirects y JSON-LD quedan sin permiso hasta ventana exclusiva SEO.
- **V5-D5:** respetar profundidad ilimitada, contar solo categorías efectivamente elegibles, sin N+1 por producto/por categoría; contrastar `EXPLAIN`/índices sobre esquema aislado.

### Ventana 6A — Implementación de NÚCLEO DE CATEGORÍAS PÚBLICAS, solo alcance específico

**Autorizada ahora al chat de Categorías.** Reserva temporal exclusiva de edición:
1. **Nuevo** `app/Services/Catalog/CategoryPublicVisibilityService.php` (o denominación autocontenida justificada): método común de elegibilidad jerárquica con detección de ciclos/huérfanos y coste controlado.
2. `app/Repositories/Catalog/CategoryRepository.php` **solo consultas públicas de categoría/featured y nuevas APIs de solo lectura**; preservar interfaces de `create/resolveId/resolveIdPlanned` y cualquier método usado por importador.
3. `app/Services/Catalog/FacetService.php` **solo construcción de árbol visible de categorías/facetas**, sin cambiar la política de elegibilidad de PRODUCTOS ni proveedores/marcas/tags/precios. Las hijas ocultas no se promueven a raíz.
4. `app/Controllers/Catalog/CategoryController.php` y `CategoriesController.php` **solo control de categoría elegible y composición de vista del directorio**; no modificar productos, URL/SEO global.
5. **Nuevo** `scripts/build/categories_public_visibility_contract.php` y pruebas focales exclusivas en `scripts/dev/categories_public_*.php`; `docs/modulos/categorias.md`.

**Incluido:** GET `/categoria?slug=...` → 404 si nodo o ancestro inactivo/huérfano/cíclico, GET `/categorias` sin promover hijos invisibles; facetas de categorías elegibles; lista de categorías destacadas del home mediante `CategoryRepository::listHomeFeatured()`. No alterar UI/estilos. Solo si necesario, datos de breadcrumbs de `CategoryController` para categorías efectivamente visibles, sin modificar partial/layout compartidos.

**NO incluido:** `ProductRepository.php`, `ProductsController.php`, `ProductController.php`, `RecommendedProductsService.php`, servicios Home compartidos, `MetaController.php`, `SeoFrontService.php`, `public_html/index.php`, `head.php`, settings, filtros de productos, sitemap/robots, SEO redirects, `SeoSlugHistoryRepository.php`, importaciones ni Git. **La política de productos B1/B2 permanece comercialmente sin aprobar.** Tampoco cambiar `catalog_hide_inactive_category_products`.

**Pruebas obligatorias de V6A:** HTTP GET reales contra clon MySQL descartable (y selector de BD seguro) para raíz/hija/nieta, nodos inactivos, ancestros inactivos, ciclo/huérfano en fixture no comercial, reactivación solo del nodo, categorías vacías y featured/home/facetas sin huérfanos promovidos; count y performance sin N+1; regresiones de `CategoryRepository` usadas por Importaciones (sin hacer import real), búsqueda/producto **solo observación** para registrar limitaciones de V6A. Contrato focal, lint, gate fuera de ventanas de prueba de otros chats y teardown según protocolo. Sin escribir BD habitual.

**Cierre y aviso:** V6A **NO se considera corrección integral del catálogo público**: productos, menú móvil, breadcrumbs de producto, sugerencias, sitemap, redirects y 404/head siguen pendientes de ventanas de integración posteriores. No hacer commit/push; documentar evidencia, archivos y discrepancias en `docs/modulos/categorias.md`, detener edición. No aprobar publicación pública integral hasta resolver B1/B2 y consumidores/SEO.

## Actualización 2026-10-21 — Ventana 6A validada; Ventana 6B-1 AUTORIZADA: sitemap de categorías y chips móviles

**Evidencia:** `docs/modulos/categorias.md` §18 documenta V6A con 18/18 contratos públicos, 28/28 Admin, 7 GET 200 y 11 GET 404 para estados jerárquicos, 2.200 niveles, performance/teardown, gate general 834 PHP/120 JS. El chat principal leyó `CategoryPublicVisibilityService.php` y `CategoryRepository.php`, y **reejecutó ambos contratos: 18/18 y 28/28 OK**. No reejecutó GET/benchmark completos: evidencia del especialista. **V6A cerrada técnicamente, NO lista para integración pública final**.

**Inconsistencia confirmada por lectura:** `MetaController::sitemapCategories()` selecciona `categories WHERE is_active=1` sin ancestros; `app/Views/partials/header/_mobile_search_sheet.php` aplica igual filtro. Pueden anunciar/enlazar categorías que `CategoryRepository::findActiveBySlug()` ahora oculta con HTTP 404. Estos dos consumidores se corrigen **sin ninguna decisión sobre venta de productos**.

### Ventana 6B-1 — permisos exclusivos para chat Categorías

**Archivos exactos autorizados:**
1. `app/Controllers/System/MetaController.php` **solo método `sitemapCategories()` y los `use` estrictamente necesarios**, sin tocar sitemapProducts, sitemapBrands, pages, index, robots u otros métodos.
2. `app/Views/partials/header/_mobile_search_sheet.php` **solo consulta PHP de categorías sugeridas**, sin alterar markup, controles, scripts o estilos del buscador, ni selección de productos.
3. `app/Repositories/Catalog/CategoryRepository.php` **solo nuevo método de lectura pública reutilizable si hace falta** para el listado móvil/sitemap, y `app/Services/Catalog/CategoryPublicVisibilityService.php` **solo corrección de compatibilidad estrictamente comprobada**. No alterar los métodos históricos `create/resolveId*/idBy*` usados en importadores.
4. `scripts/build/categories_public_visibility_contract.php`, scripts `scripts/dev/categories_public_*.php` del módulo y `docs/modulos/categorias.md`.

**Contrato:**
- `/sitemap_categories.xml` solo debe enumerar categorías jerárquicamente elegibles que respondan como landing pública. Filtrar **antes** del límite/orden si corresponde; respetar XML escaping y feed vigente. No listar categorías con ancestros inactivos/ciclo/huérfano.
- Los chips iniciales del buscador móvil deben mostrar **hasta ocho** categorías elegibles, conservando orden y completando ocho con las siguientes elegibles si las primeras son ocultas; sin enlace a 404.
- Reutilizar única política `CategoryPublicVisibilityService`, no replicar condición `c.is_active` recursiva en dos consumidores; evitar N+1, preservar comportamiento actual de categorías comerciales válidas.
- Conservar el alcance solo categorías. NO cambiar bandera `catalog_hide_inactive_category_products`, `products.is_active`, relaciones, carrito, filtros/listados/fichas/sugerencias de productos, links manuales ni precios/stock.
- Sitemap todavía requiere una **revisión SEO final** para `seo_settings.robots_index/noindex` y canonical en ventana de SEO global. Si está al alcance solo por APIs públicas existentes, documentar el hallazgo, **no ampliar cambios a otros servicios sin permiso**. No presumir cobertura SEO integral.

**Pruebas requeridas:** GET reales en clon MySQL descartable con 1/2/3+ niveles, padre/nieta inactivo, ciclos/huérfanos en fixture, sitemap XML válido y conjunto de slugs igual al de categorías elegibles; chips móviles sin enlaces a landing 404, límite 8, orden y relleno; home y facetas regresión; `categories_public_visibility_contract.php`, `categories_admin_contract.php`, gate en turno exclusivo, SQL EXPLAIN o benchmark razonable, sin DB habitual. Documentar pruebas realizadas y no realizadas. Teardown propios.

**NO AUTORIZADOS:** resto de `MetaController.php`, `public_html/index.php`, `SeoFrontService.php`, `SeoSlugHistoryRepository.php`, head/404 y redirects, `ProductRepository/ProductsController/ProductController`, Recommendations/Home/Settings, CSS/JS/html rediseñado, importaciones, migraciones, rutas ni Git.

**Pendiente comercial V5-D2:** el propietario **todavía no ratificó B1/B2**. La recomendación es B1: setting OFF mantiene productos activos vendibles de categoría oculta (valor comercial actual 0), ON los oculta coherentemente en todos los consumidores, sin cambiar flags de DB. No implementar B1/B2 sin respuesta y ventana exclusiva posterior (6B-2). Los flujos SEO/redirecciones/head requieren otra reserva (6C). No publicar corrección incompleta como final.

**Salida:** cerrar V6B-1 tras pruebas y documentación en `docs/modulos/categorias.md`; detener trabajo y solicitar aprobación para consumidores/SEO. La aprobación visual del Admin de categorías también continúa pendiente.

## Resolución comercial definitiva — V5-D2 APROBADA: B1 (2026-10-21)

El propietario **aprueba expresamente B1**. Esta decisión **reemplaza** todas las menciones previas de «B1/B2 pendiente» en documentos y adendas anteriores. Documento de contrato y matriz de consumidores: `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md`.

- **Categoría visible** solo si su flag y el de todos los ancestros son activos (política ya implementada en V6A).
- **Flag `catalog_hide_inactive_category_products=0` (OFF, configuración actual):** los productos con `products.is_active=1` siguen públicamente disponibles y a la venta aunque su categoría o ancestro esté oculto. Deben evitarse breadcrumbs, filtros y enlaces hacia categorías con 404.
- **Flag = 1 (ON):** los productos de categoría directa inactiva o de cualquier ancestro inactivo dejan de estar disponibles en fichas, listados, búsqueda/sugerencias, cards, recomendados, home, QuickAdd, carrito/checkout y sitemap de Productos. No se modifica `products.is_active`, stock, precio, categorías ni pedidos históricos. Una compra preexistente en carrito deberá validarse al confirmar para bloquear producto no elegible sin borrar el carrito silenciosamente.
- Solo es política de visibilidad/venta PÚBLICA; búsquedas admin, importación, datos históricos y contabilidad no deben filtrar por B1.
- Categorías vacías y señalización SEO en errores siguen políticas diferenciadas; no asumir 410/301 para ocultación reversible.

**Próxima Ventana 6B-2 PREPARADA, NO ABIERTA:** núcleo y consumidores públicos de **lectura** de Productos. Ver `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md` §3 (propuesta de reservas por archivo), §4 (matriz de pruebas ON/OFF). **Requiere primero cierre técnico y liberación de reservas de V6B-1, revisión del principal y autorización expresa**. No comenzar por el mero hecho de aprobar B1. A posteriori: V6B-3 Cart/Checkout (validación transaccional de no-compra ON) y V6C SEO/sitemap Productos/redirect/head, ambas aún sin autorización.

**Coordinación:** mantener MetaController `sitemapCategories()` y bloque de chips móviles exclusivos del chat Categorías en V6B-1. Importaciones trabaja hotfix P0 V1C separado. Los módulos comparten Git worktree; solo principal puede hacer commit/push.

## Adenda 2026-10-21 — Ventana 6B-1 VALIDADA; Ventana 6B-2 AUTORIZADA bajo B1

**Revisión técnica:** sección 19 de `docs/modulos/categorias.md` documenta GET reales, XML sitemap, chips móviles 8/3, 2.200 niveles, rendimiento y teardown; principal verificó por lectura el nuevo método `CategoryRepository::listPublicVisibleOrdered()` y sus dos consumidores, y volvió a ejecutar contratos **24/24 público y 28/28 Admin**. El especialista reportó gate general **836 PHP/120 JS OK**. Se **cierra V6B-1** y se liberan sus reservas de `MetaController::sitemapCategories()` y `_mobile_search_sheet.php`, que no pueden editarse en V6B-2. El `noindex`/canonical editorial sigue pendiente de V6C.

**Política B1 ratificada expresamente por propietario**: `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md` es documento vinculante; los textos anteriores «B1/B2 pendiente» son históricos. OFF (`catalog_hide_inactive_category_products=0`) deja visibles/vendibles los productos activos de categorías ocultas; ON (=1) excluye de **toda** superficie pública y compra a los productos cuya categoría o ancestro no sea visible, sin cambiar `products.is_active`, stock, precio, relaciones ni pedidos históricos.

### V6B-2 AUTORIZADA — exclusivamente LECTURA pública de Productos

Responsable único: chat de Categorías. Implementar una política pública común `ProductPublicVisibilityService` (nuevo) que combina `products.is_active`, setting B1 y `CategoryPublicVisibilityService`. **No duplicar filtros o hacer N+1.** Bajo ON, filtro antes de LIMIT/OFFSET para totales, paginación, facetas, priceBounds, ranking, recomendaciones y resultados consistentes; bajo OFF, producto activo de categoría oculta conserva ficha, búsqueda, recomendación y QuickAdd. Evitar breadcrumb/enlace público a categoría 404 cuando OFF (si requiere HTML compartido, detener y solicitar reserva). Producto huérfano bajo ON debe fallar cerrado; no arreglar datos reales.

**Archivos reservados EXCLUSIVAMENTE para cambios focales descritos:**
- **Nuevo** `app/Services/Catalog/ProductPublicVisibilityService.php` — comprobación, conjunto elegible y cache por request.
- `app/Repositories/Catalog/ProductRepository.php` — **solo selectores SELECT públicos** (`publicVisibilitySql`, `buildCatalogWhere`, `paginate`, `priceBounds`, `searchPreview`, `findRelatedFill`, `findRelatedForCart`, `findVisibleByIdsOrdered`, `findQuickAdd` si procede). No editar insert/update, SKU, stock, precios ni selectores de Admin/Importaciones.
- `app/Controllers/Catalog/ProductsController.php` — solo `index/suggest/suggestFragment`; preservar contratos JSON/HTML y 301 legacy.
- `app/Controllers/Catalog/ProductController.php` — solo elegibilidad GET de ficha y datos de breadcrumb, sin cambiar pricing/SEO global.
- `app/Controllers/Catalog/QuickAddController.php` — solo guard GET; conservar 400/404/409 y pricing.
- `app/Controllers/Catalog/CategoryController.php` — solo selección/lista de productos dentro de landing ya elegible; no alterar guard 6A.
- `app/Services/Catalog/FacetService.php` — solo SELECT/conteos públicos de productos, marcas/tags coherentes con B1; no reestructurar el árbol de categorías.
- `app/Services/Catalog/RecommendedProductsService.php` — solo filtrado visible de resultados relacionados y globales, sin modificar ranking/stock/precios.
- `app/Services/Public/HomePageViewService.php` — solo tarjetas públicas de secciones manuales/YouTube; filtrar después de lectura batch o API pública específica, **sin cambiar el repositorio compartido de Cart**.
- Nuevos `scripts/build/categories_product_visibility_contract.php`, scripts `scripts/dev/categories_product_visibility_*.php`, y `docs/modulos/categorias.md` exclusivamente.

**SOLO LECTURA y SIN EDICIÓN en V6B-2:** `CategoryPublicVisibilityService.php`, `CategoryRepository.php`, `ProductReadRepository.php`, `AppSettingsRepository.php`, `MetaController.php`, `_mobile_search_sheet.php`, `public_html/index.php`, `SeoFrontService.php`, Layout/404, rutas, vistas/HTML/CSS/JS, Cart/Checkout/Payment, Settings, Admin, otros repositorios e Importaciones. Si un requisito exige modificar un archivo no reservado, detener ese punto y pedir permiso; **no ampliar alcance unilateralmente**.

**Pruebas obligatorias:** clon MySQL descartable con `SELECT DATABASE()`, fixture de raíz/hija/nieta/ancestros inactivos, ciclos/huérfanos, categorías vacías, productos activos/inactivos, variantes; repetir todo con flag **OFF y ON**. Verificar por HTTP GET `/productos`, `/producto?slug`, `/categoria?slug`, JSON y fragment search, `/productos/quick-add`, Home (manual/YouTube), recomendados y filtros/facetas. Misma elegibilidad, precio, conteos, rango y paginación, sin enlaces a 404 y sin N+1. Asegurar regresión de catálogo habitual, Admin e Importaciones (sin escrituras comerciales). Ejecutar contratos focales, lint y gate coordinado, limpiar recursos y documentar evidencia real.

**IMPORTANTE:** V6B-2 **no habilita producción**; la política B1 ON todavía necesita una ventana **V6B-3** de guardas transaccionales de Carrito/Checkout/Payment y carritos existentes. **V6C** corregirá sitemap Productos, canonical/noindex, redirecciones, 404/head/JSON-LD bajo reserva SEO global. Ambas ventanas NO están autorizadas. Detenerse al concluir 6B-2 y solicitar aprobación, no hacer commit/push.

## Actualización 2026-10-21 — Ventana 6B-2 VALIDADA; Ventana 6B-3 AUTORIZADA (compra B1)

**Revisión:** `docs/modulos/categorias.md` §20 documenta B1 OFF/ON en GET, JSON/HTML, catálogo, facetas, paginación, precios, Home manual/YouTube, relacionados, QuickAdd, 2.200 niveles y MySQL sintético, teardown completo; gate global reportado **838 PHP / 120 JS OK**. El principal revisó `ProductPublicVisibilityService`, consultas públicas y secciones de Home y **reejecutó 28/28 contrato B1, 24/24 público y 28/28 Admin: todos PASS**. No reejecutó los HTTP completos ni benchmark. **V6B-2 cerrada técnicamente**, sin aprobar producción.

**Superficie de compra inspeccionada:** `CartController::add/index/update` usa `CartWriteService` → `LegacyCartEngine` y `ProductReadRepository` (sin B1); `CartSelectionService` recibe raw cart en checkout; `CheckoutIntentService::createIntent()` arma snapshots reutilizables, `CheckoutStockPreflightService::validate()` revisa stock; `CheckoutOrderService::confirm()` crea pedido para transferencia y pagos pendientes; `OrderFromIntentService::createOrderFromIntent()` crea pedido desde intent cuando Mercado Pago confirma, utilizado tanto por `PaymentController::payment()` como por el webhook. Los locks de stock existentes no verifican categoría B1. **Un bloqueo solo en la interfaz o en CartController NO alcanza.**

### Ventana 6B-3 — AUTORIZADA, responsable único chat de Categorías

**Objetivo no negociable:** cuando B1 está **ON**, un producto activo pero ligado a categoría directa/ancestro invisible NO puede agregarse como nueva compra, seleccionarse para continuar, generar un nuevo intent de cobro ni generar un nuevo pedido; debe verificarse también bajo transacción antes de debitar stock/crear pedido. Cuando B1 está **OFF**, el producto activo conserva todas las vías de compra, con stock y variantes válidos. **No eliminar silenciosamente carritos existentes**; mostrar aviso y permitir quitar/actualizar. No tocar flag/stock/precio/órdenes históricas de manera compensatoria.

**Reservas EXCLUSIVAS y acotadas, sin edición de otros métodos en archivos compartidos:**
- **Nuevo** `app/Services/Checkout/CheckoutProductEligibilityGuard.php` (o denominación equivalente): validador central por lotes de IDs producto/variante, con uso de `ProductPublicVisibilityService` y lectura fresca del flag B1; ruta de orden exige transacción activa y locks consistentes de filas relevantes (productos, categorías/ancestros o equivalente SQL protegido). Sin N+1 ni usar estado de sesión como autoridad.
- `app/Services/Cart/Legacy/LegacyCartEngine.php` — **solo add/bulkUpdate/mergeItems/replaceWithItems y su lectura `getCartView()`** según necesidad de B1, sin alterar reglas de cantidad, moneda, precios, variantes, stock, token anónimo, ownership ni borrar automáticamente ocultos. Se puede introducir campo semántico `is_publicly_available` y causa, sin cambiar `is_active` almacenado.
- `app/Services/Cart/CartViewModelService.php` — **solo** `is_selectable` y mensaje/estado de artículo B1 bloqueado, sin alterar calculadora comercial.
- `app/Services/Cart/CartSelectionService.php` — **solo** comprobación de elegibilidad de ítem para checkout/selección (en raw cart y VM), sin modificar reglas de sesión/merge.
- `app/Controllers/Cart/CartController.php` — únicamente `index/add/update` y feedback funcional si es indispensable, conservando CSRF, JSON y errores actuales.
- `app/Views/partials/carrito/item.php` y `summary.php` — **solo** estado legible de indisponibilidad B1 y bloqueo de continuar cuando aplique; sin rediseño visual, ni CSS/JS nuevo. Conservar acciones de eliminar.
- `app/Controllers/Checkout/CheckoutController.php` — **solo validación server-side B1 en `index()` y redirección/aviso de revisión del carrito**; no editar pasos, fiscalidad, impuestos, logística ni `confirm()` deshabilitado.
- `app/Services/Checkout/CheckoutIntentService.php` — **solo guarda B1 antes de crear o reutilizar intent de pago**, aunque el carrito seleccionado esté desactualizado. No alterar hash, montos, shipping, marketing ni snapshot fiscal.
- `app/Services/Checkout/CheckoutStockPreflightService.php` — **solo B1 y reporte código/error antes de éxito de preflight**, preservando stocks/locks y redirección.
- `app/Services/Checkout/CheckoutOrderService.php` — **solo guarda B1 en transacción de `confirm()` antes de crear order/items y descontar stock**, sin cambiar idempotencia, IVA, pagos, stock o mails.
- `app/Services/Orders/OrderFromIntentService.php` — **solo guarda B1 fresca bajo transacción en `createOrderFromIntent()`**, antes de order/items y stock, para cobros/webhooks que reutilizan intent antiguo. **No bloquear reconciliación idempotente de pedidos YA CREADOS**, ni alterar sus estados.
- `app/Controllers/Checkout/PaymentController.php` — **únicamente** manejo de errores B1 en caminos `preference/pay/payment/stockPreflight` si es requerido para respuesta precisa y evitar enviar cargos nuevos. No cambiar payloads MP, idempotency key, SDK, reintentos ni transición de pago.
- `app/Controllers/Webhooks/MercadoPagoWebhookController.php` — **únicamente** respuesta/registro de casos `payment_approved_but_product_unavailable` ya cobrados, si la validación en OrderFromIntent devuelve bloqueo; sin cambiar verificación del proveedor, auto-facturación, flujos idempotentes ni marcar pago como inexistente.
- Nuevos `scripts/build/categories_checkout_b1_contract.php`, `scripts/dev/categories_checkout_b1_isolated.php` (o equivalentes) y `docs/modulos/categorias.md`. Leer scripts/gate existentes para regresión; **no editar gate global ni contratos ajenos**.

**Borde de pagos externos — requisito de seguridad:** validar B1 ANTES de pedir preferencia, token de cobro o enviar `MercadoPagoService::createPayment`. Si el pago ya fue aprobado por MP y, antes de generar pedido, B1 cambió de ON/OFF o desactivaron categoría, **NO ocultar/descontar el pago ni devolver simplemente “compra realizada”**, y tampoco auto-reembolsar sin una política comercial/financiera aprobada. Persistir el evento real en mecanismos existentes, advertir necesidad de conciliación/atención manual y preservar el dato del pago para un posterior reembolso o resolución. Si garantizar trazabilidad exige modificar repositorios/estado financiero no reservado, **detener esa parte, no desplegar y solicitar reserva**. Webhook no debe confirmar pedido no elegible ni falsear HTTP 200 como éxito comercial. **Nunca crear un pedido post-cobro que incumpla B1 ON.**

**Invariantes/TOCTOU:** revalidar en servidor cada mutación de carrito e intent, Y en la misma transacción/locks que crean order y descuentan stock; IDs producto/variante y categoría deben concordar, sin SELECT obsoleto/caché calentado antes del cambio de estado. Orden de locks consistente con cambios de categoría para evitar deadlocks. Debe haber manejo fail-closed ante error lectura del setting/jerarquía; no usar una lectura no protegida para aprobar un pedido. Mantener comportamientos de pago, stock, descuentos, mayoristas, promociones, CSRF y rate limits previamente existentes.

**SOLO LECTURA (no modificar) en V6B-3:** `ProductPublicVisibilityService.php`, `CategoryPublicVisibilityService.php`, `CategoryRepository.php`, `ProductRepository.php`, `ProductReadRepository.php`, `CartRepository.php`, `AppSettingsRepository.php`, `InventoryRepository.php`, `CheckoutIntentRepository.php`, `OrderRepository.php`, `PaymentRepository.php`, servicios MercadoPago/ARCA, Marketing, atributos, Admin, Importaciones, SEO/sitemap, JS/CSS, rutas, migrations y BD habitual. Si una API insuficiente fuerza su cambio, **detenerse y pedir autorización puntual**.

**Pruebas obligatorias:** MySQL aislado con `SELECT DATABASE()`, dos estados ON/OFF, productos en categoría visible/inactiva directa/ancestro apagado, variantes, sin stock, anónimo/logueado, carrito viejo y merge, reactivación del padre, y errores de jerarquía sintéticos. POST directo de `/carrito/agregar`, actualizar, seleccionar, `/checkout/stock/preflight`, `/checkout/pago/seleccionar`, crear intent, transferencia/orden, pago MP mock/sandbox sin cobros reales y **webhook simulado**; CSRF, JSON/códigos/redirecciones, fraude de IDs, stock/price guard y no cambios comerciales ante bloqueo. Caso TOCTOU: categoría pasa de elegible a inelegible entre GET/carrito, intent, preflight y generación de pedido; caso intent ya pagado, doble webhook e idempotencia. Comprobar `orders/order_items/inventory/payments/intents` antes/después y eventos audit. Regresión OFF completo sin alterar precio/envío/IVA/marketing; nuevos contratos focales B1 + anteriores (28/24/28), gate general solo al terminar y cuando no coincida con Importaciones. Sin usar pasarela externa de dinero real ni datos comerciales. Teardown estricto.

**Cierre de ventana:** `docs/modulos/categorias.md` con resultados reales y riesgos; **NO PRODUCCIÓN** hasta V6C SEO y QA integral. Si hay bloqueo de arquitectura de pagos, documentarlo y detener sin declarar completada la compra. **No commit/push**, no iniciar V6C ni tocar otros módulos.

## Actualización 2026-10-22 — 6B-3 técnicamente validada; Ventana 6C-A SEO AUTORIZADA (sin redirects)

**Verificación principal:** sección 21 de `docs/modulos/categorias.md` registra E2E HTTP autenticado sobre clon + MySQL sintético, CSRF 419/422, B1 OFF/ON, carritos persistentes, guard transaccional de Productos/categorías/variantes, concurrencia con dos PDO, CheckoutIntent, transferencia, webhook y Mercado Pago simulado (ningún cargo real). El principal leyó `CheckoutProductEligibilityGuard`, `CheckoutIntentRepository::findReusable`, controladores de catálogo y servicios SEO y reejecutó **31/31 Checkout B1, 28/28 Productos B1, 24/24 público y 28/28 Admin**. El gate general **844 PHP / 120 JS PASS** y teardown se aceptan como evidencia del especialista, no reejecución del gate en principal. **V6B-3 cerrada técnicamente, no aprobada para producción**.

**Bloqueadores de producción fuera de SEO:**
- `CheckoutIntentRepository::findReusable()` compara `marketing_campaign_id = :m` y `email_campaign_id = :e` vinculando `0`, mientras `create()` guarda `NULL` cuando no existe campaña. **Reutilización rota para NULL** reproducida por especialista y confirmada por código. Corrección focal posterior reservada para **6B-4** (NO AUTORIZADA aún), sin permitir duplicados/alterar attribution.
- Pago de Mercado Pago aprobado que termina sin pedido por B1: snapshot de proveedor/intento queda y webhook señala `manual_reconciliation_required`; **aún falta protocolo operativo y reconciliación verificable** (responsables, respuesta al cliente, comprobación con MP, cuándo reintegrar/regularizar, registros/auditoría, idempotencia ante redelivery). No hay autorización para reembolsos automáticos ni modificar pagos/estados financieros sin evaluación separada. Es requisito de QA final/producción, independientemente de SEO.

**Diagnóstico SEO por código:** `MetaController::sitemapProducts()` usa `products.is_active=1` sin B1 ni `seo_settings.robots_index`; sitemapCategories ya respeta ancestros pero no `noindex`. `app/Views/partials/head.php` imprime canonical y robots por defecto; `app/Views/pages/errors/404.php` añade noindex por `extraHead` y puede duplicar robots/canonical, mientras algunos 404 se renderizan desde controlador. Redirecciones legacy en `ProductsController` y `public_html/index.php` pueden llegar a landing 404. Separar cambios de bajo riesgo de redirects globales.

### VENTANA 6C-A — AUTORIZADA: sitemap, indexabilidad, canonical y 404 HEAD de categorías/productos

**Responsable exclusivo:** chat Categorías, ahora sobre SEO público. **Reserva exacta y temporal de escritura:**
1. `app/Controllers/System/MetaController.php`: **solo `sitemapProducts()` y `sitemapCategories()`**, más `use` necesarios; productos con predicado **B1** (OFF mantener activos de categoría oculta; ON excluir), no indexables excluidos por `seo_settings.robots_index=0` y canonical editorial que no corresponda a URL propia; categorías jerárquicas elegibles y indexables. Aplicar filtro ANTES de salida/paginación; XML correcto sin N+1, no regresión de sitemapBrands/Pages/index/robots. No introducir 301.
2. `app/Services/Seo/SeoFrontService.php`: **solo ajustes mínimos de `buildProduct()`/`buildCategory()`/aplicación de overrides si se demuestran necesarios** para coherencia entre `robots_index`, canonical, breadcrumbs y JSON-LD de fichas públicas visibles. Sin cambiar SEO de Brand/Pages ni cálculos precio/stock, schema overrides legales y settings editoriales existentes.
3. `app/Views/partials/head.php`: **exclusivamente bloque inicial de título/robots/canonical/OG/JSON-LD**, para que el 404 use una sola instrucción robots `noindex,nofollow` y no presente canonical de URL inexistente/Inicio ni JSON-LD de producto/categoría inexistente. No modificar orden/carga de CSS, JS, iconos, assets, identidad o layout. Si el 404 no puede declararse correctamente desde su view, hacer ajuste mínimo bajo el mismo bloque sin depender del orden de inclusión.
4. `app/Views/pages/errors/404.php`: **solo metadatos y coherencia 404 (status, robots, canonical y títulos)**, sin rediseño HTML/CSS, no duplicar robots al pasar por head.
5. `app/Controllers/Catalog/ProductController.php` y `CategoryController.php`: **solo ramas existentes de 404 y construcción/inyección de `$seo` si realmente imprescindible**; mantener guard B1, precio, filtros, facetas, urls, legacy y checkout intactos.
6. Nuevos `scripts/build/categories_seo_b1_contract.php`, `scripts/dev/categories_seo_b1_isolated.php` (o equivalentes) y `docs/modulos/categorias.md`; ejecutar contrato SEO Admin existente, no modificarlo.

**SOLO LECTURA / NO AUTORIZADO:** `public_html/index.php` (redirecciones globales), `ProductsController.php` (301 legacy), `SeoRedirectRepository.php`, `SeoSlugHistoryRepository.php`, `ProductRepository.php`, `CategoryRepository.php`, `ProductPublicVisibilityService.php`, `SeoSettingsRepository.php`, `config/routes.php`, `MetaController::robots/sitemap/sitemapPages/sitemapBrands`, otros layouts/partiales/JS/CSS/Settings/Admin, Cart/Checkout/Payment, importaciones, BD/migraciones y Git. No automatizar slug history ni cambiar historiales; si falta API o permiso solicitar reserva, no editar por intuición.

**Políticas SEO:**
- **B1 OFF:** producto activo de rama invisible puede ser **HTTP 200 e indexable** si no existe `robots_index=0`; sitemapProducts lo conserva y canonical apunta a ficha propia, no a una categoría 404.
- **B1 ON:** mismo producto debe ser **404 y ausente del sitemap**; categorías inactivas/ancestros invisibles 404, ausentes sitemap. Ninguna URL de sitemap debe resolver 404 o `noindex`. Las categorías vacías activas siguen indexables salvo ajuste editorial.
- **Noindex manual:** respetar sin borrar ni modificar la configuración editorial de `seo_settings`; si `robots_index=0` excluir del sitemap aunque GET 200. Canonical de ficha válida debe ser propio o preservar override editorial válido; no crear un 301 implícito. `robots.txt` no se modifica ni reemplaza directivas meta.
- **404 reales:** HTTP 404, un solo `meta robots=noindex,nofollow`, sin canonical/OG/JSON-LD engañosos. No 410 de baja reversible ni redirección genérica a Inicio.
- **Páginas listadas/filtradas:** no alterar paginación del catálogo. Registrar cualquier incongruencia SEO que exigiría editar `ProductsController` o rutas para futura reserva.

**Pruebas requeridas:** HTTP GET de sitemapProducts, sitemapCategories, landing/ficha 200/404, 404 genérico y head metadata; B1 OFF/ON, producto activo categoría directa inactiva/ancestro inactivo, ciclos/huérfanos sintéticos, productos y categorías noindex, canonical override (propio y externo), canonical/case/escapes XML, 2.200 niveles para no N+1, indexación de página vacía, ausencia duplicación robots. Comparar conjunto de slugs de sitemaps con URLs públicas indexables de cada modo, respuestas HTTP y DOM/head realmente serializado. CSRF ni POST comerciales no necesarios en esta ventana. Ejecutar 31/28/24/28 focales, nuevo SEO, contrato Seo Admin, lint, gate coordinado con Importaciones 1D-C, teardown de MySQL/copia/puertos. No operaciones comerciales.

**Pendiente 6C-B, NO ABIERTA:** legacy redirects `/productos?categoria=`, resolución de `seo_redirects` y slug-history en `public_html/index.php`, eliminación de cadenas/ciclos y 301 solo a destino público válido. Reservas del entrypoint y `ProductsController` requerirán evaluación y autorización nueva. **Pendiente 6B-4** reutilización de intents NULL y proceso financiero de pago aprobado sin pedido, también NO ABIERTA.

**Cierre:** reportar implementación/pruebas/limitaciones en `docs/modulos/categorias.md`, detenerse sin commit/push. **No considerar producción, ni integrar SEO/checkout globalmente antes de 6C-B, 6B-4, protocolo de conciliación, QA y aceptación estética.**

## Ratificación del propietario — 2026-10-22: completar hasta Etapa 7, ampliar por seguridad real

**Instrucción expresa del propietario:** «No te digo de detenerlo, hagámoslo bien. Concluyamos hasta la etapa 7. Y sumamos trabajo si algo que afecta la seguridad del sitio». **Esta directiva prevalece sobre la recomendación previa de recortar alcance por duración.** La prioridad es calidad y seguridad, no cerrar artificialmente el módulo.

**Alcance funcional autorizado como objetivo de coordinación (NO permiso indiscriminado para editar archivos):**
1. Terminar **6C-A** ya abierta: sitemap/indexabilidad/canonical/robots/404 coherentes con B1 y filtros editoriales; pruebas HTTP y regresiones.
2. Al recibir cierre técnico de 6C-A, el principal **revisará y abrirá 6C-B** con reserva expresa y exclusiva: redirects históricos/legacy, cadenas/ciclos, slugs y destinos elegibles. No escribir entrypoint o repositorios SEO globales antes de esa reserva.
3. Completar **Etapa 7**: integración y QA integral público/Admin B1 OFF/ON de categoría/producción, SEO/crawling, carrito, checkout, intents, pagos, idempotencia y concurrencia; aceptación visual y regresiones, sobre entornos descartables y sin datos/operaciones comerciales reales.
4. **No dejar de lado la seguridad financiera:** corregir reuse con `marketing_campaign_id/email_campaign_id NULL` (6B-4 focal con reservas de Checkout), y desarrollar protocolo verificable para cobro aprobado sin pedido (responsable, aviso, revisión, conciliación y eventual refund manual autorizado, auditoría, doble webhook e idempotencia). Separado de SEO pero **bloqueante para producción**.
5. **Hallazgos P0/P1 de seguridad o integridad descubiertos en cualquier etapa** (autorización, CSRF, SQL, exposición de datos, manipulación de precios/stock, pagos, reintentos, transacciones, condición de carrera, redirects abiertos, pérdida de datos) **se incorporan al plan con prioridad, evidencia reproducible y reserva focal del principal**. No ignorarlos por haber alcanzado etapa 7; tampoco confundir mejoras cosméticas o refactor sin riesgo con un incidente de seguridad. Evitar cambios no coordinados en archivos compartidos.

**Gobernanza vigente:** mantener el trabajo por ventanas y revisiones para proteger el repositorio compartido donde operan Importaciones, Marcas, Atributos y Categorías; esto **no significa detener el objetivo**, sino coordinar permisos y pruebas. No exigir ratificación comercial redundante de B1 ni nuevas consultas al propietario por cada corrección técnica dentro de esta dirección. El principal abrirá cada ventana con límites precisos tras revisar la anterior y conflictos de archivos. Ningún especialista hará commits/push. No autorizar producción hasta cerrar los riesgos de seguridad e integridad, QA real, SEO, flujo de dinero y revisión visual.

## Adenda 2026-10-22 — 6C-A VALIDADA y VENTANA 6C-B AUTORIZADA: redirects históricos seguros

**Verificación de 6C-A:** `docs/modulos/categorias.md` §22 documenta GET HTTP/SQL aislados para sitemaps B1 OFF (12 productos) y ON (4), 6 categorías indexables, canonical editorial, noindex, 404 con robots único y sin canonical/entity JSON-LD, stress 2.200 niveles, XML parser y teardown. Principal leyó `MetaController::sitemapProducts/sitemapCategories`, `SeoFrontService`, `head.php` y `errors/404.php`; **reejecutó 28/28 SEO B1, 31/31 Checkout B1, 28/28 Productos B1, 24/24 público, 28/28 Categorías Admin y contrato SEO Admin: todos PASS**. HTTP/stress y gate **848 PHP/120 JS OK** se aceptan como evidencia del especialista, **no reejecutados por principal**. **6C-A CERRADA TÉCNICAMENTE**; sin aprobación de producción ni aceptación visual integral.

**Bloqueo 6C-B confirmado por lectura:** `public_html/index.php` aplica `seo_redirects` GET/HEAD y emite 301/302 hacia `to_path` sin validar ruta, estado B1, cadena, ciclo, ni si termina en 404; antes, su limpieza de tracking emite 301. `ProductsController::index()` hace 301 de `/productos?categoria=...` aunque sea categoría jerárquicamente invisible. `SeoRedirectRepository` expone `findTargetByFrom()`, `registerHit()` y métodos de escritura usados por `SeoManagerService`. Existe `seo_slug_history` como histórico de slugs, pero **no es autorización para crear redirects automáticamente, reescribir historia ni mutar datos comerciales**.

### VENTANA 6C-B AUTORIZADA — resolutor de redirecciones público, preservando B1 y SEO 6C-A

**Propietario exclusivo:** chat Categorías. Objetivo: redirecciones 301/302 solo a un destino terminal **interno, público, válido y coherente con B1, SEO y HTTP**. Eliminar cadenas/ciclos a nivel de resolución sin hacer escrituras masivas; evitar open redirects, navegación privada indebida y 301 hacia 404.

**Reservas temporales estrictas:**
1. **Nuevo** `app/Services/Seo/PublicRedirectTargetResolver.php` (o nombre equivalente): resolver seguro de `seo_redirects`, validación/normalización de URLs internas y recorridos con límite de saltos/conjunto visitado, status originales permitidos y comprobación de destino final. Usar `ProductPublicVisibilityService` para **producto** y `CategoryRepository::findActiveBySlug` para **categoría**; no N+1 por catálogo ni tocar flags. Validar existencia pública de destinos `/marca`/páginas soportadas con fuentes actuales, no asumir que cualquier path local es válido. `canonical/noindex` de destino no justifica automáticamente una nueva 301: si hace falta decidir destino alternativo, registrar y pedir reserva.
2. `public_html/index.php`: **solo bloque de redirects SEO por `seo_redirects` en GET/HEAD** y helpers/uso puntuales indispensables del resolver; mantener precedencia de limpieza tracking, capturas UTM, carga de rutas, autenticación, errores, analytics y dispatch exactamente como están. No mover/alterar el ciclo de boot/autoload, CSRF/POST ni el código de login.
3. `app/Controllers/Catalog/ProductsController.php`: **solo bloque legacy al comienzo de `index()`** para `/productos?categoria=` y `?marca=`; confirmar existencia/visibilidad/slug válido antes de 301 y que los filtros preservados no creen redirect hacia 404. Categoría invisible: sin 301, respuesta pública coherente (404 real noindex, o ruta de listado original sin canonical engañoso solo si avalado por GET); B1 OFF conserva fichas de productos activos aunque categoría oculta. No tocar `suggest`, JSON, facets, priceBounds, rankings, analytics, HTML ni catálogo B1.
4. `app/Repositories/Seo/SeoRedirectRepository.php`: **solo** helper(s) SELECT/read para resolver cadenas y/o registrar hits de redirects válidos, estrictamente necesarios; NO tocar `upsert()`, `retargetTo()`, datos de `seo_redirects` ni esquema.
5. `app/Repositories/Seo/SeoSlugHistoryRepository.php`: **solo lectura; NO EDITAR**. Si hace falta integrar lectura histórica adicional, detener ese subcaso y solicitar reserva; no activar autoredirección de slugs por suposición.
6. Nuevos `scripts/build/categories_seo_redirects_contract.php`, `scripts/dev/categories_seo_redirects_isolated.php` (nombres alternativos equivalentes); `docs/modulos/categorias.md` para registro y QA.

**No autorizados (SOLO LECTURA):** `SeoManagerService.php` y editores/gestión Admin de redirects; `SeoFrontService.php`, `head.php`, `errors/404.php`, `MetaController.php` y sitemaps, `CategoryRepository`, `ProductRepository`, `ProductPublicVisibilityService`, `CategoryPublicVisibilityService`, `SeoSettingsRepository`, `config/routes.php`, otros controladores (ProductController/CategoryController), Cart/Checkout/Payment, importaciones, BD y migraciones, JS/CSS, Git. Los cambios deben estar acotados a bloques autorizados, sin una refactorización global del router.

**Contrato de seguridad/semántica:**
- Solo rutas app **relativas de origen** con un slash inicial y destino final **mismo origen**; prohibir esquemas `http(s)://` externos, `//host`, `\\`, credenciales/CRLF/control chars, encoding ambiguo, `%2f%2f`, path traversal, puertos/origen ajenos y query strings que reintroduzcan riesgos. Nunca pasar valores sin verificar al header `Location`. Fallar cerrado o continuar a 404 de ruta original sin emitir 301 si destino dudoso; loguear motivo de forma segura, no revelar secrets.
- Saltos A→B→C: resolución final sin loop/cadena expuesta al visitante cuando se pueda probar; ciclos A→B→A, auto-redirect, destinos que coinciden con path fuente, exceso de saltos y mapping de estado incoherente: no emitir 301. **No mutar tablas de redirects ni autogenerar equivalencias**. Registrar `hits` solo cuando realmente se emite redirect permitido.
- Destino `/categoria?slug=...`: jerarquía completa activa, categoría válida (404 si no). Destino `/producto?slug=...`: `products.is_active=1` más política B1 del flag actual (OFF permite producto activo de rama oculta; ON no), sin cache cross-request obsoleta. Validar slugs y parámetros `slug` sin arrays/inyección. Para marcas y otras páginas públicas permitidas, comprobar ruta/fila/método; NUNCA redirigir a `/admin`, `/checkout`, `/cuenta`, `/webhooks` o URL no reconocida.
- Respetar canonical/noindex editoriales de 6C-A sin convertir sin justificación destinos de canonical alternativo en redirect automático. Una URL de sitemap debe mantenerse elegible; no introducir 301 hacia URL 404, loop o 5xx en casos probados.
- No alterar tracking UTM, normalización de query, preservación de filtros `q/min/max/orden/page/perPage`, encadenamiento con la normalización existente, secuencia GET/HEAD ni procesamiento de POST. Al faltar destino válido la respuesta debe ser 404 real (u otra respuesta documentada segura según ruta), **no** 301 a Inicio ni 410 para desactivación reversible.
- No modificar historiales SEO ni productos/categorías comerciales, datos reales o pagos. El status 301/302 original se conserva solo cuando semánticamente seguro; no persistir reemplazos.

**Pruebas obligatorias privadas:** con `SELECT DATABASE()` y clon MySQL aislado: redirects `A→B`, `A→B→C`, `A→A`, `A→B→A`, más que máximo de saltos, destino no existente, en 404, producto inactivo, categoría inactiva directa/ancestro inactivo, huérfana/cíclica sintética, slugs renombrados y colisiones. B1 OFF y ON: producto activo de rama oculta solo 301 válido en OFF; categoría oculta nunca 301 a landing 404. Legacy `/productos?categoria` visible/invisible, `?marca` visible/ausente, `sub`, filtros, tracking UTM, HEAD, status 301/302, percent-encoding/Unicode, queries array, ruta `//evil`, `https://evil`, CRLF, `%2F`, `../`, rutas privadas, no open redirect. Verificar `Location`, HTTP de destino, meta robots/canonical, ausencia de falsos hits, ausencia de ciclos, preservación SEO 6C-A. Pruebas de concurrencia/TOCTOU donde sea posible (cambio de B1/jerarquía entre requests) sin prometer atomicidad across requests. Reejecutar SEO B1 28, Checkout 31, Productos 28, público 24, Admin 28, SEO Admin, contrato nuevo, lint, gate coordinado con Importaciones 1E (sin solapar). Reportar resultados reales y teardown completo. No utilizar BD habitual para escrituras o redes externas.

**Cierre:** actualizar `docs/modulos/categorias.md`, detenerse para revisión del principal; **sin commits/push**. 6B-4 para idempotencia `NULL` y conciliación MP siguen separados **NO autorizados**. Etapa 7 QA integral solo tras validar 6C-B y resolver deudas de seguridad/pagos; producción no aprobada.

## Adenda 2026-10-22 — 6C-B núcleo GET validado; subventana 6C-B2 HEAD + tracking AUTORIZADA

**Revisión principal:** §23 de `docs/modulos/categorias.md` registra redirecciones GET 301/302 seguras, resolver explícito sin open redirect, ciclos, saltos y 301→404, B1 OFF/ON, categorías con ancestros ocultos, canonical/noindex, códigos, contador de hits, escala 2.200 niveles, SQL y HTTP privados; 6C-B reporta gate **853 PHP / 120 JS OK** y teardown, que el principal NO reejecutó íntegramente. El principal leyó `PublicRedirectTargetResolver`, `public_html/index.php`, `ProductsController`, `config/routes.php` y **reejecutó**: redirects **35/35**, SEO B1 **28/28**, Checkout B1 **31/31**, Productos B1 **28/28**, público **24/24**, Admin Categorías **28/28**, SEO Admin **PASS**. **NÚCLEO GET 6C-B VALIDADO; 6C-B NO CERRADA** hasta HEAD/tracking y pruebas.

**Hallazgos reproducidos en entorno privado por especialista y confirmados por estructura de código:**
1. `config/routes.php` registra fichas públicas solo con `GET`; `public_html/index.php` despacha por clave literal `HEAD /ruta` y devuelve 404. SEO histórico fail-closed en HEAD no emite Location por esta razón. Debe ofrecer HEAD válido consistente con GET **solo para recursos públicos SEO seguros**, sin exponer endpoints GET mutadores (pixels, email/campañas/click, promo, cuenta, auth, admin, checkout, AJAX, webhooks) ni introducir escritura comercial.
2. El bloque UTM se ejecuta ANTES del resolver y emite 301 no condicionado a que URL limpia exista: `/old-cat-hidden?utm_source=...` → 301 a `/old-cat-hidden` → 404; `/old-direct?utm_*` añade otro 301 antes de SEO válido. Los datos `analytics_utm` first-touch deben capturarse ANTES de limpiar para GET, sin degradar atribución ni crear Location insegura o hacia 404. Evitar tratar arrays UTM como string (se usa `trim((string)`), query duplicada y encoding malicioso.

### VENTANA 6C-B2 — AUTORIZACIÓN FOCAL EXTRAORDINARIA, solo chat Categorías

**Objetivo simultáneo:** HEAD de fichas/categorías/marcas/listado e índices públicos debe devolver status/headers semánticamente coherentes con GET y **cuerpo vacío**; HEAD de redirects históricos/legacy válidos debe emitir Location seguro, y el resto fallar cerrado; canonicalización de parámetros no debe generar 301 hacia 404 ni cadenas UTM→301→301 SEO, preservando first-touch legítimo. No modificar marketing/payments ni autorizar redirecciones abiertas.

**Reservas permitidas, métodos/bloques exactos:**
- `public_html/index.php` **solo** (a) traducción/selección de dispatch HEAD para rutas GET **whitelist pública y segura**, dejando método HTTP original intacto para guards/telemetría; (b) bloque HEAD del resolver histórico GET/HEAD; (c) **bloque previo de limpieza tracking/UTM**, únicamente el orden, validación/canonicalización y emisión de Location, conservando capturas first-touch; (d) ajuste imprescindible al bloque de analytics/page_view para NO registrar HEAD como visita GET si el nuevo dispatch provoca ese efecto. Sin cambiar boot, auth, CSRF, rutas POST/PUT/PATCH/DELETE, sesiones comerciales, UTM reales, handlers, cookies o dispatch global distinto de la whitelist.
- `config/routes.php` **solo si resulta estrictamente indispensable**, para declaraciones HEAD explícitas de rutas públicas SEO seguras (p.ej. `/`, `/productos`, `/producto`, `/categoria`, `/marca`, `/categorias`, `/marcas`, legal e índices sitemap/robots si son soportados). **NO** añadir HEAD global para todo GET ni HEAD a `/admin`, `/cuenta`, `/checkout`, `/carrito`, `/login`, `/promo`, tracking `/email/*`, APIs AJAX, descargas con efecto lateral, ni rutas POST.
- `app/Services/Seo/PublicRedirectTargetResolver.php` **solo** adaptación mínima para validar el destino limpio y coordinar resoluciones UTM/HEAD, sin aflojar validaciones de same-origin, rutas, B1, canonical/noindex y ciclos ni ampliar whitelist a rutas privadas. Preferir reutilizar API actual y **no editarlo** si alcanza.
- `app/Controllers/Catalog/ProductsController.php` **solo bloque legacy al inicio de `index()`**, exclusivamente coordinación HEAD/tracking con redirect `categoria/marca` sin cambiar facetas, estado B1, filtros ni respuestas GET ya validadas. Si se resuelve por entrypoint, no editar.
- **Nuevos** `scripts/build/categories_seo_redirects_head_tracking_contract.php`, `scripts/dev/categories_seo_redirects_head_tracking_isolated.php` (o equivalentes); ampliación de contrato redirects existente estrictamente para estos casos; `docs/modulos/categorias.md`.

**En SOLO LECTURA:** `SeoRedirectRepository`, `SeoSlugHistoryRepository`, `SeoManagerService`, `SeoFrontService`, `MetaController`, `head.php`, `errors/404.php`, `ProductController`, `CategoryController`, repos Catalog/Settings, `MarketingCampaignService`, `AnalyticsService` y otros servicios, Customer/Auth/Payments/Checkout, Importaciones, JS/CSS, DB/schema/migraciones y Git. Si el HEAD real revela mutación de negocio, telemetría inadecuada dentro de controladores o exige otro archivo: **detener subcaso y solicitar reserva focal**, no convertir el router entero a GET. Ningún acceso comercial en fixture.

**Casos de aceptación E2E HTTP/SQL reales:**
- Comparar `GET` vs `HEAD` de cada ruta pública SEO autorizada: **mismo estado y Location/cabeceras relevantes, cero bytes de body HEAD**; producto/categoría/marca visibles e invisibles, B1 OFF/ON, inexistentes 404; HEAD de legacy `/productos?categoria/marca` y de `seo_redirects` con uno/dos saltos, 301/302 seguros, ciclos/invalid 404 sin Location. HEAD para API/privados y GET con efectos comerciales **no** despachar accidentalmente ni escribir datos.
- UTM `/old-cat-hidden?utm_source=...`: nunca 301 a página inexistente. `/old-direct?utm_source=...` y cadena: **un solo 301/302 final** cuando se puede resolver y probar destino; campaña first-touch GET preservada. `/productos?categoria=visible&utm_medium=...` / invisible, `?marca=...`, filtros `q/min/max/orden/page/perPage`, `sub`, coexistencia de UTM y `fbclid/gclid/ref`, URL con tracker repetido o malicioso, HEAD equivalente sin fijar first-touch espurio, no open redirect ni 301→404.
- Verificar que HEAD ni canonicalización disparen checkout, websockets, pago, newsletter, email-open/click, counters comerciales, facturación, modificaciones al producto ni APIs mutadoras. Contadores SEO `hits` de GET se conservan; para HEAD adoptar política explícita documentada, sin contar doble por cadena y sin requerir escritura comercial; compatibilidad de analytics first-touch, idempotencia, CSRF y POST no regresan.
- Reejecutar redirects 35, SEO B1 28, Checkout B1 31, Productos B1 28, público 24, Admin 28, SEO Admin, nuevo contrato, lint y gate **coordinado con Importaciones 1F-A**, base/clon sintéticos, `SELECT DATABASE()`, teardown completo. PHP built-in y/o Apache pueden tratar HEAD distinto: comprobar realmente `curl -I` con respuesta body 0, headers correctos y status.
- Reportar evidencia HTTP real antes de declarar solucionado; pruebas textuales no bastan, y no modificar DB comercial.

**Cierre:** actualizar `docs/modulos/categorias.md` §23 con subventana 6C-B2, resultados y limitaciones; detenerse **sin commits/push**. V6C-B integral aún requiere revisión final del principal. **6B-4** `CheckoutIntentRepository` campañas NULL, protocolo Mercado Pago approved sin pedido, y **Etapa 7 QA** siguen fuera de esta autorización y sin producción. Directiva: corregir cualquier seguridad/integridad comprobada, solicitando nueva reserva para archivos ajenos.
