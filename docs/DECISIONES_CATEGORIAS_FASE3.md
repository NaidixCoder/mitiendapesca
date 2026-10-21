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
