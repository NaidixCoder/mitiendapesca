# Categorías — informe vivo del chat especializado

> **Estado vigente: CIERRE INTEGRAL V2 DE CATEGORÍAS IMPLEMENTADO Y VALIDADO TÉCNICAMENTE (ver §24). HEAD de Producto y búsquedas autenticadas corregidos, QA HTTP/SQL/seguridad/visual completado. ENTREGADO para revisión del principal; PRODUCCIÓN NO aprobada por CheckoutIntent NULL, conciliación MP y aceptación transversal pendiente.**
> **Actualización:** 2026-10-22 · **Responsable:** chat especializado de Categorías.
> **Proyecto:** `C:\xampp\htdocs\mi-pesca` · **Alcance integral V2:** Categorías Admin/público, jerarquía, B1, SEO, redirects, HEAD, UTM y UX propios; guards GET-only focales autorizados. Sin edición de CheckoutIntent, pagos, conciliación, Importaciones, Atributos o AnalyticsService global.
> **Documento de entrega:** únicamente este archivo. El chat principal coordina integración, archivos globales y Git.

## 1. Alcance real y dependencias

Se inspeccionaron rutas, controlador, servicio de activación, repositorio público, ViewModel, dos vistas admin, JavaScript, CSS, esquema SQL y consumidores del catálogo público. Las rutas reales (no traducidas) son:

| Método | Ruta | Acción |
|---|---|---|
| GET | `/admin/categories` | Listado, búsqueda, filtros y paginación |
| GET | `/admin/categories/new` | Alta |
| GET | `/admin/categories/edit?id=N` | Edición |
| POST | `/admin/categories/save` | Alta/actualización y datos SEO |
| POST | `/admin/categories/toggle` | Activación individual y desactivación en cascada |
| POST | `/admin/categories/quick` | Alta rápida consumida desde Productos |

No existe endpoint de **eliminación** en el módulo; no se añadió uno sin decisión de negocio. Se conservaron CSRF, rate limits, nombres de campos, integración con `CategorySeoBlocksService`, `SeoManagerService` y el panel SEO compartido. Se inspeccionaron consumidores públicos (`Catalog/CategoryController`, `Catalog/CategoriesController`, `CategoryRepository`), pero **no** se modificaron.

## 2. Hallazgos priorizados y estado real

| Prioridad | Hallazgo comprobado | Estado y tratamiento |
|---|---|---|
| **Crítica** | El guardado anterior impedía `parent_id = id`, pero permitía trasladar una categoría debajo de un descendiente, creando ciclos. | **Corregido** en backend con `CategoryHierarchyGuard::validateParent()` y excluyendo descendientes de las opciones seleccionables. |
| **Alta** | El guardado completo no exigía que el padre existiera y convertía identificadores inválidos a entero. | **Corregido**: valida entero positivo, existencia y cadena de ancestros; rechaza referencias imposibles. |
| **Alta** | La desactivación de categoría y descendientes se ejecutaba sin transacción explícita ni bloqueo de la raíz. | **Corregido parcialmente**: transacción y `SELECT ... FOR UPDATE` sobre raíz, comprobación de actualización y rollback ante error. **Pendiente** estudiar concurrencia con reparentado de descendientes. |
| **Alta** | Guardado de categoría, cambios de slug, ajustes SEO y bloques editoriales se realizan en varias operaciones separadas; excepciones SEO se registran sin deshacer la categoría. | **Pendiente**: diseñar atomicidad y propagación de errores junto con los servicios SEO compartidos. |
| **Alta** | El upload anterior comprobaba tipo real, pero sin límite explícito de bytes/dimensiones. | **Corregido**: máximo 4 MB; JPG, PNG, WEBP; lados de hasta 6000 px; se mantiene validación de archivo subido. **Pendiente** política de limpieza de archivo si falla la escritura DB. |
| **Alta** | El formulario permite cambiar `is_active` directamente, mientras `toggle` desactiva descendientes en cascada. La misma operación de negocio podría dejar descendientes activos según la vía usada. | **Pendiente**: definir política de visibilidad única y aplicarla de forma coordinada. No se cambió esa semántica sin autorización. |
| **Media** | Listado sin paginación; recuperaba todas las categorías, sin cantidad de productos/subcategorías. | **Corregido**: consulta dedicada con `COUNT`, agregaciones SQL, `LIMIT/OFFSET` 25/50 y rango mostrado. Conteos **directos**, no recursivos. |
| **Media** | Selector de padre sin ruta jerárquica; también mostraba categorías descendientes válidas visualmente. | **Corregido**: etiqueta jerárquica `Padre › Hija`, ordenada; destinos cíclicos deshabilitados. |
| **Media** | Slugs duplicados y nombres inválidos podían producir errores o datos ambiguos; faltaban límites alineados con esquema. | **Corregido en guardado completo**: duplicados de slug, caracteres permitidos, límites de nombre/slug/meta, categoría a editar existente. La unicidad sigue respaldada por el índice DB. No se introdujo unicidad de nombre. |
| **Media** | Las dos vistas combinaban utilidades genéricas, estilos inline, tabla ancha y formularios sin composición específica responsive. | **Corregido en las vistas propias** con diseño Admin V3 y una hoja `categories.css`; el panel SEO compartido mantiene sus clases por contrato. |
| **Media** | Las imágenes de portada faltantes aparecían rotas en la UI. | **Mitigado en presentación**: reemplazo por placeholder mediante JS ante carga fallida. Los archivos reales ausentes **no se restauraron**. |
| **Media** | Al abrir un alta, `CategoryLabel::sanitize()` podía generar un nombre inicial no solicitado; breadcrumb nuevo usaba clave de edición. | **Corregido**: campo nuevo vacío y `admin_crumbs('categories.new')`. |
| **Media** | `quick` devuelve una categoría existente por slug aunque pertenezca a otro padre; alta rápida no comparte todas las validaciones del formulario completo. | **Pendiente**: acordar si debe reutilizar, advertir o crear slug alternativo, sin romper selector de Productos. |
| **Media** | URLs manuales de portada no tienen una política explícita de orígenes/formatos de ruta, y el upload puede quedar huérfano si falla el guardado posterior. | **Pendiente**: contrato de imágenes y limpieza sin afectar recursos existentes. |
| **Baja** | No hay eliminación administrativa; el sistema usa activación/desactivación. | **Sin cambio** por protección de referencias de productos y datos editoriales. |

**Integridad real de DB al auditar (consulta solo lectura):** 18 categorías, 9 activas, 14 principales, 0 ciclos, 0 categorías huérfanas, 0 productos con categoría inexistente, 0 nombres duplicados, 0 categorías activas bajo padre inactivo.

**Portadas locales ausentes detectadas (3):** Reeles, Señuelos blandos y Accesorios. Las rutas exactas están verificadas en la sección 7; no se repararon los datos.

## 3. Correcciones e implementación efectiva

**Listado** (`CategoriesIndexService` + `categories/index.php`):
- Búsqueda por nombre/slug; opción de incluir inactivas; 25/50 por página, navegación anterior/siguiente y total real.
- Resumen de total/activas/principales; categorías con portada o placeholder, slug, ID, categoría padre, subcategorías directas, productos directos, orden y estado legible.
- Una consulta paginada con agregaciones de productos/subcategorías, más conteo y resumen; no N+1 de filas.
- Acción Editar y mismo POST de toggle en escritorio/móvil, protegida por CSRF. Confirmación de cambio de visibilidad y retorno a filtros.

**Formulario** (`CategoriesFormViewModel` + `categories/form.php`):
- Secciones Identificación/Jerarquía, Imagen de portada y SEO/Editorial en `details` nativo.
- Nombre requerido y vacío en alta; slug editable con auto-generación backend cuando se deja vacío; categorías padre en ruta jerárquica.
- Entradas de orden y visibilidad; preview de portada; carga de archivo; labels con `for`, ayudas contextualizadas, botones Guardar/Cancelar.
- Se conserva completo el parcial `partials/admin/seo/entity_panel` y sus `name`/`data-seo-*`.

**Backend focal** (`CategoriesController`, `CategoryHierarchyGuard`, `CategoryAdminService`):
- Rechazo de padre inexistente, propio o descendiente; rechazo de edición de ID ausente; validación de límites y slug; protección de imagen.
- Toggle transaccional con lock y rollback ante error; sin alterar la regla existente «desactivar desactiva descendientes; activar solo activa la elegida».
- Se acota la URL de retorno de toggle a la ruta administrativa del módulo; se preservan auditoría y rate limit previos.
- **No implementado:** transacción total categoría + SEO, cambios de DB/esquema, eliminación, modificaciones de catálogo público.

## 4. Inventario de archivos afectados por esta implementación

Rutas relativas a `C:\xampp\htdocs\mi-pesca`:

| Tipo | Archivo | Modificación |
|---|---|---|
| Editado | `app/Controllers/Admin/CategoriesController.php` | Listado delegado; jerarquía/errores/validaciones; subida de imágenes; retorno toggle; formulario |
| Editado | `app/Services/Catalog/CategoryAdminService.php` | Transacción, bloqueo, rollback, búsqueda de descendientes con conjunto de visitados |
| **Nuevo** | `app/Services/Admin/Categories/CategoriesIndexService.php` | Búsqueda, filtros, agregaciones y paginación |
| **Nuevo** | `app/Services/Admin/Categories/CategoryHierarchyGuard.php` | Existencia, ascendencia y prevención de ciclos |
| Editado | `app/Services/Admin/Categories/ViewModels/CategoriesFormViewModel.php` | Alta vacía, breadcrumb correcto y datos del formulario |
| Reescrito | `app/Views/pages/admin/categories/index.php` | Tabla y filas móviles, filtros, métricas, paginación |
| Reescrito | `app/Views/pages/admin/categories/form.php` | Formulario semántico y SEO desplegable |
| **Nuevo** | `app/Views/partials/admin/categories/toggle_action.php` | POST reutilizable para ambas representaciones |
| **Nuevo** | `public_html/assets/css/admin-v3/pages/categories.css` | CSS aislado y responsive del módulo |
| **Nuevo** | `public_html/assets/js/admin/categories/index.js` | Confirmación y fallback ante error de imágenes |
| **Nuevo** | `scripts/build/categories_admin_contract.php` | Contrato estático focal de Categorías |
| **Nuevo** | `scripts/dev/categories_readonly_smoke.php` | Consultas y render sintético + DB read-only |

**Archivos compartidos deliberadamente NO modificados:** `config/routes.php`, `app/Views/layouts/admin.php`, `app/Views/partials/head.php`, `app/Views/partials/admin/page_header.php`, `app/Views/partials/admin/seo/entity_panel.php`, `app/Support/helpers.php`, `public_html/assets/css/admin-v3/components/*`, `scripts/build/gate.php`, `MANIFEST.md` y `docs/ESTADO_DESARROLLO.md`.

**Carga CSS/JS:** las dos vistas incluyen la hoja de ámbito `admin-v3/pages/categories.css` y el módulo `js/admin/categories/index.js` sin registrar nuevas dependencias globales. El contrato focal **todavía no** se invoca desde el gate maestro; fue ejecutado separadamente.

## 5. Identidad Admin V3, CSS y mobile-first

- Respeta primario `#006B6B`, fondo `#F6F8FA`, superficies `#FFFFFF`, texto `#182B39`, secundario `#647786`, borde `#DFE5E9`, mayormente vía tokens `--av3-color-*`.
- Namespace único `av3-categories__*`, tipografía operativa 14 px, controles ~38–40 px, radios 8/11 px y densidad equilibrada. Sin Tailwind nuevo, sin CSS por segunda capa u overrides globales, sin modificar aside/Page Header.
- Mobile-first: dos métricas en primera fila y tercera completa en espacios estrechos; tres métricas cuando hay ancho útil; filtros ajustables; tarjetas/filas móviles y tabla desktop con cambio a partir de 840 px del **contenedor**, no únicamente del viewport.
- Formulario de una columna en móvil; orden/visibilidad en dos columnas y portada junto a campos al ampliar el contenedor; el SEO se despliega para no sobrecargar el primer plano.
- `data-categories-confirm` y `data-categories-image` son hooks JS; presentación separada de comportamiento.
- **Deuda visual:** acciones del módulo usan clases propias de botón en vez de adoptar por completo `av3-ui-btn`; evaluar consolidación con la biblioteca común **sin tocar CSS compartido**. El editor SEO compartido conserva estilos/utilidades previas por coordinación.

## 6. Pruebas ejecutadas (y qué demuestran)

| Ejecución / evidencia | Resultado | Alcance real |
|---|---|---|
| PHP `-l` en controladores/servicios/vistas propios + `node --check` | **OK** | Solo sintaxis. |
| `php scripts/build/categories_admin_contract.php` | **OK 9/9** | Contrato estático focal de listado, SQL, jerarquía, SEO, toggle, CSS, upload. |
| `php scripts/dev/categories_readonly_smoke.php` | **OK** | MySQL **solo lectura**; filtros, resultados vacíos, jerarquía, render HTML de listado/form y hooks SEO. |
| `php scripts/build/gate.php` | **OK** (822 PHP, 119 JS en ejecución registrada) | Gate global completado durante implementación. El contrato propio no está integrado al gate. |
| Auditoría de integridad MySQL desde script de respaldo | **OK**, con 3 portadas ausentes | 18 categorías; ausencia de ciclos, referencias huérfanas y duplicados de nombre. No mutó DB. |
| Capturas Edge headless: **363, 390, 768, 1024 y 1600 px**, listado + formulario | **OK: 10 capturas** | Render en navegador con HTML sintético (consultas reales para listado), carga CSS, shell y verificación de `scrollWidth`; sin desbordamiento horizontal ni errores JS. NO representa sesión admin autenticada. |
| Interacción Edge headless: desplegar SEO a 363 y 1600 px; cancelar diálogo toggle | **OK**, confirmación 1, **0 POST** | Estado DOM responsive y confirmación JS. No prueba guardar ni alternar realmente en DB. |
| Revisión visual manual de capturas 363/1600 | Realizada | Se corrigieron nombre inicial y fallback de imágenes rotas; la observación de screenshots no equivale a aprobación visual autenticada. |

**Evidencia privada en la PC:** `C:\xampp\mi-pesca-backups\categories_v3_20261020` (respaldo previo de cinco archivos, render y capturas `categories_index_*.png` / `categories_form_*.png`, scripts auxiliares de browser e integridad). Ningún ZIP de entrega.

**Limitación decisiva:** NO se hicieron POST de creación/edición, subida de portada, toggle real ni guardado SEO desde navegador autenticado; NO se ejecutaron migraciones ni escrituras sobre la base de datos.

## 7. Verificación de datos / portadas faltantes

La auditoría de integridad de categorías detectó **tres referencias locales a archivos que no existen** en la ruta de la aplicación:

- **Reeles:** `/uploads/categories/ed286e04277e1695c95326def9cf30c4.webp`
- **Señuelos blandos:** `/uploads/categories/ccff98d9cfa8571d74bdd0951ce2c87b.jpg`
- **Accesorios:** `/uploads/categories/e1b94b011b78774fa80be940b2880699.webp`

No se modificaron registros ni uploads para resolverlas. El nuevo JS aplica placeholder si la imagen no carga. Verificar y recuperar imágenes originales con el propietario antes de reemplazar datos.

## 8. Pendientes para coordinación y próxima etapa

1. **Alta:** acordar e implementar guardado atómico categoría + SEO + bloques, junto a manejo de errores y archivos ya cargados. Puede requerir revisión de servicios compartidos; **solicitar reserva al chat principal** antes de modificarlos.
2. **Alta:** unificar semántica al desactivar desde el formulario frente al toggle en cascada; definir coherencia de categoría activa bajo padre inactivo. No alterar negocio unilateralmente.
3. **Alta:** prueba autenticada, segura y controlada de alta/edición, errores de slug, padres inválidos/cíclicos, actualización SEO, carga de portada y toggle con descendientes; verificar persistencia/rollback y catálogo público afectado sin editarlo.
4. **Media:** revisar `quick`: slug existente bajo padre diferente, mensajes y compatibilidad con selector de Productos. Coordinar cualquier cambio con responsable de Productos.
5. **Media:** recuperar las tres portadas faltantes; definir URL/orígenes admitidos y limpieza de uploads huérfanos.
6. **Media:** evaluar consistencia bajo concurrencia (edición jerárquica simultánea con toggles); bloqueo de raíz actual no cubre necesariamente cambios concurrentes de descendientes.
7. **Media:** en una ventana coordinada, registrar `categories_admin_contract.php` en `scripts/build/gate.php`. **No se tocó el gate compartido.**
8. **Media:** revisión final de UI en Firefox/Edge **autenticados**, prueba teclado/labels/carga fallida y comparación de botones del módulo con `av3-ui-btn`; no modificar biblioteca global por este chat.
9. **Baja:** definir si se requiere eliminación, reordenación avanzada o paginación de selector de padres para volúmenes grandes; funcionalidades no existentes y no incorporadas de oficio.

## 9. Entrega al chat principal

- **Estado:** **EN CURSO**, aunque la implementación visual y los contratos focales están listos para revisión técnica. **No** marcar como “cerrado/aprobado en producción” hasta completar la validación funcional autenticada y resolver decisiones de integridad.
- **Código nuevo/editado:** exclusivamente los 12 archivos identificados en §4; sin cambios globales ni en módulos ajenos.
- **Pruebas:** gate integral OK, smoke MySQL read-only OK, contrato propio OK y browser headless con 10 capturas y confirmaciones canceladas; 0 POST real.
- **DB:** sin modificaciones por esta ejecución; tres rutas de portadas inválidas previamente existentes.
- **Riesgos que requieren decisión:** atomicidad SEO, diferencia entre vías de desactivación, `quick` y limpieza de uploads.
- **Git:** sin commits, push, cambios de rama, worktrees ni operaciones de integración realizados por este chat.
- **Regla continua:** actualizar **este mismo archivo** tras cada etapa relevante, cada cambio técnico/visual y cada nuevo resultado de pruebas. El chat principal puede consultarlo mediante Desktop Commander sin pedir un resumen por chat.

---

## 10. Segunda pasada — 2026-10-21 (guía Admin V3 v2.0)

> **Estado de esta etapa:** LISTO PARA REVISIÓN TÉCNICA Y DECISIÓN COMERCIAL. Persisten decisiones funcionales **pendientes de autorización**, por lo tanto el módulo general continúa **EN CURSO**. Se consultó `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0 **antes** de editar estilos. Única documentación editada: este archivo.

### 10.1 Migración real de componentes Admin V3

Archivos modificados en esta pasada, exclusivamente del módulo:

1. `app/Views/pages/admin/categories/index.php`: botones `av3-ui-btn`, filtros con `av3-ui-filters`, `av3-ui-field`/`av3-ui-control`, badges `av3-ui-badge`, acciones `av3-ui-actions`, tabla `av3-ui-table`/`av3-ui-table-scroll`, vacío `av3-ui-empty`.
2. `app/Views/pages/admin/categories/form.php`: `av3-ui-btn` primario/secundario, `av3-ui-field`, `av3-ui-control`, `av3-ui-field-label`, `av3-ui-field-hint`; permanecen names, IDs, CSRF y hooks `data-seo-*` compartidos.
3. `app/Views/partials/admin/categories/toggle_action.php`: activar con `av3-ui-btn`; desactivar con variante `av3-ui-btn--danger`; sin modificar método/ruta/datos del POST ni la confirmación.
4. `public_html/assets/css/admin-v3/pages/categories.css`: eliminadas definiciones duplicadas de botones, controles, badges y estilos base de tabla. Se conservan solo disposición de paneles/filtros, tabla vs. filas móviles, indicadores y composición del formulario; ajustes de legibilidad del DS heredado (labels 13 px, ayudas/badges/encabezado tabla 12 px) acotados a Categorías. No se agregó Tailwind, `!important` ni CSS global.
5. `scripts/build/categories_admin_contract.php`: contrato ampliado de **9 a 12 verificaciones**, añade adopción `av3-ui-*`, ausencia de botón local duplicado y límite de ancho de formulario.

**Condición técnica singular:** `master-shell.css` tiene una regla más específica que fuerza `width:100%;max-width:none` en `.av3-wrap` del contenido. Para cumplir el **formulario de hasta 860 px** sin editar ese archivo transversal, la única regla local específica se corrigió en origen a `body[data-page^="admin"] .av3-master-content > .admin-surface.av3-categories > .av3-wrap.av3-layout--form`; no hay capas de override sucesivas.

**No se modificaron** los componentes CSS `admin-v3/components`, tokens, shell, sidebar, Page Header, el panel SEO compartido, rutas, `app.js`, la base habitual ni el catálogo público. Los dos JS de interacción previos mantienen sus hooks. Las tres referencias de portadas faltantes (§7) se preservaron **sin cambiar registros ni archivos**.

### 10.2 Diagnóstico reproducido: edición vs. toggle de visibilidad

Se revisaron `CategoriesController::save()`, `CategoriesController::toggle()`, `CategoryAdminService::toggleActiveCascade()`, jerarquía y el consumo público:

| Situación | Formulario `save` actual | Toggle `/admin/categories/toggle` actual |
|---|---|---|
| Desactivar padre con hijas activas | Solo `UPDATE categories SET is_active=0 WHERE id=?`; las hijas **siguen activas** | Desactiva raíz y todos los descendientes en una operación transaccional |
| Reactivar padre | Solo activa el padre | Solo activa el padre |
| Padre elegido inactivo | Guardado admite jerarquía activa debajo de inactiva | No valida antecesores al reactivar |
| Histórico/avisos | `category.save`; sin recuento de descendientes | `category.toggle`; recuentos y aviso de productos |
| Seguridad de datos | CSRF + validaciones y control de ciclos | CSRF + lock de raíz + transacción, sin lock completo de jerarquía |

**Evidencia de HTTP aislado:** con padre activo e hija/nieta activas, POST `save` de padre a `is_active=0` produjo estado **0/1/1**; POST de `toggle` produjo **0/0/0**; nueva activación por toggle produjo **1/0/0**. Es una inconsistencia de negocio confirmada, **no corregida** sin autorización.

#### Política única propuesta (REQUIERE APROBACIÓN)

- **Desactivar cualquier categoría:** desactivar ella y **todos** los descendientes, de forma atómica, en ambas vías (formulario/listado). No eliminar productos, asignaciones, contenido, slugs ni referencias.
- **Activar una categoría:** activar **solo ese nodo**; rechazar la operación si algún antecesor está inactivo. No activar hijas automáticamente: evitar publicar contenido que se desactivó voluntariamente.
- **Reparentar:** bloquear padre inexistente/descendiente (ya implementado). Además, rechazar mover una categoría activa debajo de antecesores inactivos. Las categorías inactivas podrían reorganizarse bajo padres inactivos, pero continuarían ocultas.
- **Crear rápidamente:** no permitir que la selección de un padre inactivo produzca una categoría nueva activa sin advertencia. Discutir si `quick` puede crear inactivas o si debe exigir padre activo.
- **UX:** confirmar cantidad de descendientes afectados al desactivar; en formulario indicar que el estado se propagará; mensajes específicos si la activación no puede realizarse; auditar IDs alcanzados.
- **Concurrencia:** validar la jerarquía dentro de una transacción y lock ordenado de filas afectadas (no solo lock de raíz), más pruebas de dos solicitudes simultáneas. La implementación actual puede competir contra `save`/reparentado sin lock.
- **Catálogo público:** verificar efecto real de una hija marcada activa con padre inactivo; `CategoryRepository::findActiveBySlug()` valida únicamente `is_active` del nodo, por lo que no basta ocultar el padre en el admin. Cambiar ese contrato exige coordinación con el equipo del público.

**Ninguno de esos cambios comerciales se implementó en esta pasada.** El usuario/chat principal debe validar política y autorizar servicio único para las dos rutas; mantener ambas reglas distintas sería un riesgo operativo permanente.

### 10.3 Atomicidad Categoría + SEO + bloques editoriales

**Diagnóstico del código real:**
1. `CategoriesController::save()` inserta/actualiza `categories` y establece flash de éxito.
2. Si tuvo éxito, llama a `SeoManagerService::registerSlug()`, `recordCategorySlugChange()` (redirige/reorienta historial), `saveSettings()` y `CategorySeoBlocksService::saveForCategory()`.
3. Todo se ejecuta mediante la misma instancia `PDO`, pero **sin transacción externa**: si una llamada SEO falla, el `catch (Throwable)` solo registra log y redirige; la categoría ya persistió. También puede quedar un historial SEO parcial.
4. El archivo de portada se mueve a `public_html/uploads/categories` **antes** del guardado DB; la escritura de archivo no participa en rollback. Una posterior falla SQL puede dejar un archivo huérfano.
5. `Audit::log()` es best-effort y no debe confundirse con garantía de atomicidad.

**Diseño propuesto (NO implementado, necesita coordinación SEO):**

- Extraer el caso de uso de guardado a un servicio orquestador de Categorías, **sin duplicar repositorios SEO**; validar inputs, slug y jerarquía antes de tocar DB.
- Subida con límites y ubicación temporal privada; permitir limpieza exacta si falla persistencia. Mover a destino definitivo en el punto controlado del flujo, sin borrar jamás una portada anterior hasta estar referencialmente seguro.
- `beginTransaction()` → `INSERT/UPDATE categories` → registrar slug/historial/redirecciones → persistir `seo_settings` → insertar/actualizar/eliminar `category_seo_blocks` → `commit()`; **no suprimir excepciones SEO**. Ante error: `rollBack()`, liberar archivo nuevo, mostrar error sin flash falso de éxito. Solo después del commit registrar auditoría/flash.
- Verificar que todas las tablas SEO tienen motor transaccional, que los repositorios no abren/cierran sus propias transacciones ni cambian PDO, y definir compensación cuando el movimiento físico de archivo falle después de DB.
- Garantizar slug único en DB además del precheck; capturar violación de unique bajo concurrencia.
- Tests de fallo inyectado en fase SEO y bloques: comprobar rollback del nombre/slug, settings, historial/301, y eliminación de uploads temporales. Probar ida/vuelta del slug y redirecciones sin encadenamientos.

No se editaron servicios globales `SeoManagerService`, repositorios SEO, `CategorySeoBlocksService`, migraciones ni contratos compartidos.

### 10.4 Creación rápida desde Productos — problema real reproducido

`CategoriesController::quick()` recibe `name,parent_id`, construye `str_slug(name)`, valida existencia del padre solicitado y luego busca **únicamente `slug=?`**. Si existe, devuelve `ok:true,id,name,slug` sin comprobar `parent_id`, sin mostrar advertencia ni mover el registro. El cliente de Productos usa ese ID: **puede reutilizar una categoría bajo otro padre sin que el usuario lo advierta**.

**Prueba autenticada aislada:** `Duplicada` existe debajo de `Raíz de pruebas` (ID 19). Se solicitó alta rápida del mismo nombre debajo de `Padre inactivo` (ID 22). El endpoint respondió **HTTP 200 / ok:true / id:23**, correspondiente a la categoría cuyo padre sigue siendo 19. Ninguna fila se movió.

**Opciones para decisión comercial:**
1. **Recomendación:** devolver **HTTP 409** con error claro `slug existente en otra categoría superior`, datos del existente, y requerir selección explícita en Productos. Conserva unicidad global y evita relaciones ambiguas. Hay que revisar la UI/JS de Productos con su responsable antes de hacerlo.
2. Reutilizar solo si `parent_id` solicitado coincide exactamente, y en caso contrario mostrar decisión al operador.
3. Introducir slug único alternativo por jerarquía; necesita contrato de URL pública/SEO y no se recomienda sin diseño de navegación.

**Sin cambios de código en `quick` ni en Productos** (área compartida/otro responsable).

### 10.5 Pruebas HTTP autenticadas, DB aislada y evidencia de interfaz

Se preparó fuera del proyecto `C:\xampp\mi-pesca-backups\categories_phase2_20261021` con:
- Copia privada de aplicación + vendedor/activos sin `uploads` reales; `.env` exclusivo con `DB_NAME=mi_pesca_cat_e2e_20261021`. No se copió contenido de las tablas del comercio: **solo estructura** MySQL. Datos sintéticos: un admin de prueba, padre, hija, nieta, padre inactivo y duplicado.
- Servidor PHP integrado **solo en 127.0.0.1:18124**, sesión de prueba protegida con token privado, HTTP autenticado con CSRF y verificación de DB destino en scripts de fixtures.
- Scripts privados `prepare_isolated.py`, `seed_fixture.php`, `reset_and_fix.py`, `query_fixture.php`, `http_e2e.py`, `authenticated_visual.js`; no se sumaron a la carpeta del proyecto.
- Ajuste **solamente en la copia privada** de `app/Support/helpers.php` para normalizar el separador de directorio `dirname('/index.php')` del servidor PHP integrado sobre Windows, que inicialmente generaba rutas `\\/` e impedía cargar CSS/JS. **No se editó el helper global del proyecto**.

| Prueba real HTTP / escenario | Resultado | Evidencia |
|---|---|---|
| Sesión admin fixture + GET listado, nuevo, editar | **OK**, HTTP 200 | Respuestas reales sobre servidor aislado |
| Crear y editar categoría con slugs/metadatos | **OK**, ambos HTTP 302 y filas verificadas | `http_e2e.py` y consultas DB aisladas |
| Rechazar slug duplicado | **OK**, redirección y sin fila nueva | MySQL aislada |
| Rechazar ciclo padre→nieta y padre inexistente | **OK**, estado sin cambio | MySQL aislada |
| CSRF inválido | **OK**, HTTP 403 y sin insert | HTTP aislado |
| Subida inválida | **OK**, rechazada y sin categoría creada | HTTP aislado |
| PNG real válido | **OK**, ruta y archivo en `isolated_site/public_html/uploads/categories` | HTTP multipart real |
| Desactivar padre vía formulario | **INCONSISTENCIA CONFIRMADA**: 0/1/1 | MySQL aislada |
| Desactivar con toggle | **OK**: 0/0/0 | MySQL aislada |
| Reactivar con toggle | **OK**: 1/0/0 | MySQL aislada |
| Alta rápida con slug igual pero padre distinto | **RIESGO CONFIRMADO**: HTTP 200, se reutiliza ID ajeno al padre | JSON y DB aislada |
| Bloque editorial SEO | **OK**, `bullets_json` persistido | DB aislada |
| Edge headless HTTP autenticado 363/390/768/1024/1600 | **OK**, **10 capturas** (listado+formulario), CSS/JS cargados, cero excepciones JS, sin scroll horizontal | `auth_index_*.png`, `auth_form_*.png`; mobile solo filas y desktop solo tabla |
| Contrato específico `categories_admin_contract.php` | **OK, 12 verificaciones** | Ejecutado después de migración `av3-ui-*` |
| `categories_readonly_smoke.php` | **OK**, 18 registros habituales y ninguna escritura | DB habitual solo lectura |

La revisión manual de capturas confirmó legibilidad, estados, controles y acciones coherentes con Admin V3. Se corrigió durante esta pasada el ancho del formulario de escritorio (**máximo 860 px**), sin modificar el shell. **Las 10 capturas finales** se regeneraron tras el ajuste.

**Importante:** las pruebas HTTP fueron auténticas sobre la base **aislada**, no sobre la DB habitual ni una sesión real del negocio. No se ejecutó persistencia sobre el sitio original. El servidor de prueba y la base aislada son temporales y deben quedar fuera de Git. El código del módulo en el proyecto sí contiene los cambios visuales de §10.1.

### 10.6 Entrega y restricciones de integración

- **Implementado realmente:** migración visual Admin V3 de controles y botones, simplificación CSS, formulario 860 px, contrato específico extendido, diez vistas HTTP autenticadas de fixture sin scroll lateral.
- **Confirmado pero NO modificado por decisión comercial:** diferencias de desactivación, reparentar dentro de un padre inactivo, concurrencia jerárquica, consistencia del catálogo público, atomicidad de SEO/archivos, creación rápida por slug bajo padre distinto.
- **Sin modificaciones globales:** `config/routes.php`, `scripts/build/gate.php`, `app/Support/helpers.php` del proyecto, servicios/repositorios SEO compartidos, migraciones, BD habitual, uploads del comercio, `docs/ESTADO_DESARROLLO.md`, `MANIFEST.md`, otros informes de módulos y Git.
- **Siguiente decisión necesaria del chat principal/propietario:** aprobar política de visibilidad, contrato de colisión de quick y reserva temporal del orquestador transaccional/servicios SEO implicados. Solo entonces implementar esas reglas y repetir pruebas de regresión aisladas.

### 10.7 Verificación final de esta pasada

- `php scripts/build/gate.php` **OK**, ejecución completa: **826 PHP, 119 JavaScript**, contratos globales, sidebar/drawer, SEO, Marketing/Email, CSS unificado y regresiones. El contrato nuevo específico **no se añadió al gate central** (archivo compartido reservado); se ejecutó aparte con **12/12 OK**.
- `categories_readonly_smoke.php` **OK** sobre la DB habitual, sin modificaciones: 18 categorías (9 activas), filtros y formulario.
- CSS del módulo validado con `tinycss2`: **sin errores de sintaxis**, 7.255 bytes tras consolidación, sin clases duplicadas de botón.
- Comprobación de integridad sobre DB habitual tras el conjunto de pruebas: **18 categorías, 0 ciclos, 0 huérfanas, 0 nombres duplicados** y exactamente las **mismas tres referencias de portada ausentes** (§7). Ningún registro fue corregido por estas pruebas.
- Servidor privado PHP de pruebas **detenido al concluir**; no se alteró XAMPP del proyecto. La base sintética aislada y los scripts privados se mantienen fuera de Git en `C:\xampp\mi-pesca-backups\categories_phase2_20261021` para reproducción controlada.
- **Archivos de código efectivamente editados en fase 2:** dos vistas PHP, un parcial PHP de toggle, un CSS de Categorías, y el contrato focal del módulo. **No se modificaron lógica comercial ni servicios SEO globales.**

**Cierre de fase 2:** **LISTO PARA REVISIÓN TÉCNICA** con evidencia funcional HTTP y visual real en entorno **aislado**. **No apto para declarar cierre operativo** hasta aprobar y aplicar política de visibilidad, resolver `quick` y atomicidad SEO, y validar las acciones finales con la nueva política.

---

## 11. Cierre funcional — diagnóstico para autorización (2026-10-21)

> **Estado: BLOQUEADO POR DECISIONES COMERCIALES Y RESERVAS DE ARCHIVOS.** Esta etapa **es únicamente auditoría de lectura y propuesta técnica**: no hubo cambios de código, CSS, esquema, base de datos ni catálogo público. Sigue vigente el diseño aprobado de la segunda pasada; no corresponde rehacerlo. La siguiente implementación requiere aprobación expresa del chat principal. Se releyeron `docs/COORDINACION.md`, el protocolo multichat, la guía V3, este informe y el código fuente actual de los consumidores.

### 11.1 Alcance verificado: vías de cambio de estado

**Implementación actual (lectura directa):**

- `POST /admin/categories/save` — `app/Controllers/Admin/CategoriesController.php::save()`: el `INSERT/UPDATE categories` incluye `is_active` directamente. No invoca `CategoryAdminService` ni calcula descendientes. Desactivar padre **no** cambia hijas/nietas. Si se edita una categoría inactiva manteniendo `is_active=0`, tampoco vuelve a aplicar ninguna política a hijas preexistentes.
- `POST /admin/categories/toggle` — `CategoriesController::toggle()` delega a `app/Services/Catalog/CategoryAdminService.php::toggleActiveCascade()`. Con raíz activa → inactiva, inicia transacción, bloquea raíz con `FOR UPDATE`, calcula descendientes y actualiza `is_active=0` para todos. Con raíz inactiva → activa, solo modifica raíz. El servicio no comprueba estado de ancestros ni bloquea el conjunto completo de la jerarquía antes de recorrerlo.
- `app/Services/Admin/Categories/CategoryHierarchyGuard.php`: impide padre inexistente, autoparentesco y ciclos, pero **no** comprueba visibilidad de ancestros ni concurrencia de ediciones. La vista permite elegir padres inactivos.
- Evidencia **HTTP autenticada previa, DB aislada** (§10.5): desactivar desde formulario → raíz/hija/nieta **0/1/1**; desde toggle → **0/0/0**; reactivar vía toggle → **1/0/0**. Esta etapa **no repitió** los POST.

**Riesgo de aplicar una cascada ingenua al formulario:** se debe comparar `is_active` anterior y solicitado **dentro de la misma transacción**; no propagar de forma incidental cada vez que se guarda un nombre/SEO de un padre ya inactivo. Determinar aparte si se sanea el estado heredado de hijas activas bajo padres inactivos.

### 11.2 Catálogo público: evidencia y alcance real

**Lectura, sin cambios públicos:**

- `app/Repositories/Catalog/CategoryRepository.php::findActiveBySlug()` consulta `WHERE slug=? AND is_active=1` **solo del nodo**. `app/Controllers/Catalog/CategoryController.php::show()` lo consume en `/categoria?slug=...`, sin recorrer/validar ancestros. Consecuencia: **una hija activa bajo padre inactivo puede seguir resolviendo su página pública por URL directa**.
- `CategoryRepository::listHomeFeatured()` también filtra solo `is_active=1`; puede mostrar una hija que perdió su padre visible.
- `app/Services/Catalog/FacetService.php::categoriesWithCounts()` obtiene categorías activas; si un padre inactivo no está en `catsById`, una hija activa se clasifica como **raíz** en el árbol público. `CategoriesController::index()` utiliza este servicio en el directorio `/categorias`.
- `app/Repositories/Catalog/ProductRepository.php::buildCatalogWhere()` inicia con visibilidad de **producto**; el filtro `c.is_active=1` se aplica solo cuando `enforce_active_category=1`. Hay otros filtros con `cp.slug` y `c.slug`. No está garantizada una ocultación completa de productos por desactivar categorías, y **no** se propone alterar automáticamente `products.is_active`, inventario o asociaciones.

**Decisión de alcance:** el primer cierre puede imponer integridad de estados en las **escrituras Admin** para impedir nuevos casos, pero **no garantiza por sí solo** corregir todas las inconsistencias públicas heredadas o ventanas de carrera. Antes de declarar la política completa, confirmar si el catálogo debe exigir `is_active=1` para **toda la cadena de ancestros** en rutas, facetas, home, filtros y cards. Cualquier endurecimiento de consultas públicas requiere **reserva específica**, auditoría de consumidores y pruebas de regresión; no editar esas rutas por cuenta del módulo.

### 11.3 Política uniforme propuesta — NO APROBADA / NO IMPLEMENTADA

| Operación | Semántica recomendada | Aprobación |
|---|---|---|
| Desactivar categoría activa (formulario o toggle) | Desactivar esa categoría y **todos** sus descendientes, de manera transaccional; sin eliminar asociaciones, stock, productos, slugs o SEO | **Pendiente** |
| Reactivar categoría inactiva | Activar **solo** el nodo, sin reactivar descendientes | **Pendiente** |
| Reactivar hija si tiene algún ancestro inactivo | **Rechazar** con explicación y enlace/indicación del ancestro, en vez de activar parcialmente la rama | **Pendiente** |
| Crear o mover un nodo activo bajo padre/ancestro inactivo | **Rechazar** y pedir activar previamente la cadena, o elegir padre activo; nunca reactivar ancestros implícitamente | **Pendiente** |
| Crear o mover un nodo inactivo bajo padre inactivo | Permitir manteniendo inactivos nodo y descendientes, previa validación de ciclos | **Pendiente** |
| Guardar otro dato de nodo ya inactivo, sin cambio de visibilidad | Preservar `is_active` de descendientes **salvo** política explícita de saneamiento de datos heredados | **Pendiente** |
| Hijas activas bajo padres inactivos ya existentes | Auditoría y **plan de saneamiento separado** con vista previa; no hacer cascada silenciosa en cada edición | **Pendiente** |
| Visibilidad de productos asociados / páginas públicas | Decidir ocultación por categoría y ancestros **sin tocar flags de productos**, verificando buscador, home, URLs y filtros | **Pendiente y reserva pública** |

**Arquitectura propuesta:** `CategoryVisibilityPolicyService` u otro caso de uso **del módulo** con entrada explícita `setActive(categoryId, desiredState)`, en lugar de depender exclusivamente de `toggle`. El mismo servicio se invocaría desde el guardado del formulario y desde `toggle`, con estados explícitos/idempotentes; mantener `toggle` como adaptador de UI que interpreta la intención, pero resolver bajo bloqueo y transacción. Para reducir carreras, construir el conjunto de afectadas y adquirir bloqueos de manera consistente/ordenada, volver a validar jerarquía y ancestros y actualizar en la misma transacción. Documentar estrategia frente a reparentados concurrentes; un `SELECT FOR UPDATE` únicamente de la raíz no basta. Registrar una sola auditoría fiable tras commit y conservar CSRF/rate limits.

### 11.4 Creación rápida desde Productos — alcance exacto y contrato propuesto

**Backend:** `CategoriesController::quick()` recibe `name` y opcionalmente `parent_id`; calcula slug **globalmente único**. Busca `SELECT id,name FROM categories WHERE slug=?` y responde `200, ok:true, id` sin comparar el padre existente con el solicitado ni si está activo.

**Consumidor REAL:** `public_html/assets/js/admin/product_form/index.js::initCategoryQuickCreate()` abre un modal con `#category-quick-parent` y lo reinicia, pero su `FormData` **solo envía `name` y CSRF**, no `parent_id`. Al guardar un slug existente, selecciona el ID recibido en `#product-category-select`. Por tanto el problema tiene **dos capas**: (a) discrepancia en contrato HTTP para solicitudes que sí incluyen padre y (b) **selector de padre no transmitido** desde Productos, lo que hace que el servidor reciba padre `null` incluso si se eligió otro. La **reutilización silenciosa HTTP 200** reproducida en la fase anterior se verificó mediante un POST directo aislado con `parent_id`; **HTTP 409 es la conducta propuesta, aún no implementada**, y el selector de Productos todavía no se probó enviando ese campo.

**Contrato de API recomendado, sujeto a aprobación:**
- `201` y `{ok:true,id,name,slug,parent_id,created:true}` si se crea; mantener respuesta compatible o acordar versión por consumidor.
- Si existe slug **y padre exacto coincide**, decidir si devolver existente `200` con `created:false` y `parent_id` (reutilización explícita sin insertar) o un `409` uniforme incluso en el mismo padre.
- Si el **slug existe bajo otro padre**, devolver **HTTP 409** JSON no ambiguo: `{ok:false,code:"category_slug_parent_conflict",error:"Ya existe una categoría con ese nombre bajo otra categoría superior.",existing:{id,name,parent_id,slug}}`. No modificar el registro ni asignar el ID automáticamente.
- Si `parent_id` está ausente, definir si significa «categoría principal» (`null`) o «no se especificó jerarquía»; **no** aplicar un conflicto de padre ambiguo antes de resolverlo con Productos.
- Ante padre inexistente/inactivo, respuesta validada y coherente con la política de visibilidad; contemplar colisión simultánea con índice UNIQUE del slug, y no devolver HTTP 500 genérico por carrera.

**Reserva requerida antes de implementar integración:** `public_html/assets/js/admin/product_form/index.js` (equipo/propietario de Productos), `app/Views/partials/admin/products/tab_basicos.php` (modal con `#category-quick-parent`), `app/Controllers/Admin/CategoriesController.php` (Categorías) y contrato específico del endpoint. Mantener formularios y UX de Productos sin cambios hasta coordinar.

### 11.5 Atomicidad de Categoría + historial de slug + SEO + editorial

**Ruta actual verificada:** `CategoriesController::save()` actualiza/crea `categories` fuera de transacción, audita y emite flash positivo **antes** de invocar `SeoManagerService::registerSlug()`, `recordCategorySlugChange()`, `saveSettings()` y `CategorySeoBlocksService::saveForCategory()`; si falla cualquier operación SEO, el `catch(Throwable)` la registra sin deshacer categoría, 301 o cambios previos. `storeCoverImage()` guarda el archivo definitivo **antes** de escribir la DB.

**Dependencias SEO exactas y reservas** (TODAS **solo lectura** hasta autorización):
- `app/Services/Seo/SeoManagerService.php`
- `app/Services/Seo/CategorySeoBlocksService.php`
- `app/Repositories/Seo/SeoSettingsRepository.php` → `seo_settings`
- `app/Repositories/Seo/SeoSlugHistoryRepository.php` → `seo_slug_history`
- `app/Repositories/Seo/SeoRedirectRepository.php` → `seo_redirects`
- `app/Repositories/Seo/CategorySeoBlocksRepository.php` → `category_seo_blocks`

Las tablas `categories`, `seo_settings`, `seo_redirects`, `seo_slug_history` y `category_seo_blocks` están definidas con InnoDB en `database/schema.sql`; comprobar el motor de **la DB real** antes de asumir garantía transaccional (no se corrió consulta DDL en esta etapa). Los servicios/repositorios inspeccionados comparten PDO sin commits explícitos propios, lo que **habilita conceptualmente** orquestación transaccional desde Categorías sin necesariamente editar sus contratos. Es imprescindible confirmar ausencia de efectos laterales externos/captura interna de excepciones en toda la cadena.

**Riesgo adicional confirmado por código:** `SeoSlugHistoryRepository::add()` tiene `UNIQUE(entity_type,slug)` y ejecuta `ON DUPLICATE KEY UPDATE entity_id=VALUES(entity_id)`. Reutilizar un slug histórico de **otra categoría** puede **reasignar la propiedad de ese historial** silenciosamente. La atomicidad por sí sola no elimina este defecto semántico: decidir si rechazar reutilización, impedir reasignación o permitirla con auditoría/migración de redirects. **Requiere reserva SEO global** y pruebas específicas.

**Orquestación propuesta a aprobar:** caso de uso local `CategorySaveService`, validación previa y manejo de unicidad, registro de archivo temporal, `BEGIN` → escritura `categories` + política de visibilidad/jerarquía acordada → slug history/301 → `seo_settings` → bloques editoriales → `COMMIT`; excepciones deben llegar al controlador para `ROLLBACK`. Mover auditoría/flash de éxito al **después del commit**. Para portada, definir staged upload y compensación si falla SQL o la publicación del archivo (no hay rollback de FS). Evitar borrar/reemplazar archivos previamente referenciados y preservar expresamente las **tres referencias históricas ausentes** (§7).

### 11.6 Mapa de archivos: propiedad, reserva y cambio previsto

| Archivo / dominio | Estado para siguiente fase | Alcance puntual |
|---|---|---|
| `app/Controllers/Admin/CategoriesController.php` | **Propio Categorías, requiere autorización comercial** | Unificar guardado/visibilidad, errores, respuesta 409 y transacción SEO |
| `app/Services/Catalog/CategoryAdminService.php` | **Servicio con ubicación Catalog; confirmar reserva exclusiva** | Extraer servicio `setActive`, locks, cascada y reactivación |
| `app/Services/Admin/Categories/CategoryHierarchyGuard.php` | **Propio Categorías** | Validación de antecesores activos y ciclos bajo transacción |
| **Nuevo** `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php` (nombre propuesto) | **Propio Categorías, no creado** | Única política de estados |
| **Nuevo** `app/Services/Admin/Categories/CategorySaveService.php` (nombre propuesto) | **Propio Categorías, no creado** | Orquestación de guardado categ./SEO/archivo |
| `app/Views/pages/admin/categories/form.php`, `app/Views/partials/admin/categories/toggle_action.php` | **Propios, solo ajustes UX acotados si se aprueba** | Mensajes/confirmaciones por cascada, sin rediseñar |
| `public_html/assets/js/admin/product_form/index.js` y **`app/Views/partials/admin/products/tab_basicos.php` (modal y `#category-quick-parent`)** | **RESERVAR CON PRODUCTOS** | Transmitir `parent_id`, manejar 409/selección sin reemplazo automático |
| `CategoryRepository.php`, `CategoryController.php`, `CategoriesController.php`, `FacetService.php`, `ProductRepository.php`, consumidores Home/SEO públicos | **RESERVAR CON RESPONSABLE PÚBLICO** | Estudio y, solo si se aprueba, filtro uniforme de cadena de ancestros |
| `SeoManagerService.php`, `CategorySeoBlocksService.php`, repositorios SEO listados en §11.5 | **RESERVA SEO GLOBAL** | Revisar compatibilidad transaccional e historial, sin modificaciones ahora |
| `config/routes.php`, `scripts/build/gate.php`, migraciones/schema, otros contratos globales | **RESERVA CHAT PRINCIPAL si fuesen necesarios** | No se prevén nuevas rutas ni migraciones por defecto |
| `scripts/build/categories_admin_contract.php` y pruebas nuevas aisladas del módulo | **Propios Categorías** | Matriz de invariantes, 409 y rollback de fallos |
| `docs/modulos/categorias.md` | **Exclusivo de este chat** | Diagnóstico y aprobación (esta sección) |

**No se debe reservar automáticamente todo el catálogo público:** solicitar al chat principal reservas *precisas por archivo* después de aprobar la semántica. Ningún archivo compartido se modificó durante este cierre documental.

### 11.7 Plan de pruebas y criterios de aceptación ANTES del cierre

**Ejecutar POST autenticados solo sobre DB aislada y fixtures descartables**, igual que la evidencia de §10.5, con snapshot de estados, historial y uploads; realizar `finally`/teardown del navegador privado según `docs/pruebas/PROTOCOLO_NAVEGADORES.md`. No tocar sesiones ni datos reales.

1. **Transiciones desde ambos endpoints:** padre activo con hija/nieta activas → desactivar por formulario y toggle da **0/0/0**; reactivar raíz da **1/0/0**; desactivar solo hoja no altera hermanos/padres; repetición de guardado sin cambio de estado no repropaga; confirmar avisos y auditoría.
2. **Integridad de jerarquía:** activar hija con padre inactivo = rechazo explícito sin escrituras; alta activa bajo padre inactivo = rechazo acordado; mover activo debajo de inactivo = rechazo; mover inactivo sin ciclos permitido; IDs inexistentes, hijos recursivos y mismo padre preservan árbol.
3. **Concurrencia:** activar y desactivar dos miembros de una misma rama simultáneamente, editar `parent_id` contra cascada, lock timeout/deadlock y reintentos controlados; comprobar ausencia de ramas activas bajo ancestro inactivo, todas las operaciones con `commit/rollback` coherentes.
4. **Quick + Productos:** `POST /admin/categories/quick` mismo slug bajo **otro padre** retorna 409 y no altera/selecciona categoría; mismo padre opera según decisión; `parent_id` real llega desde modal; `null`/omisión definido; padre inexistente/inactivo, slug concurrente y HTTP 422/403; JS conserva modal y muestra error sin cerrar ni seleccionar ID.
5. **Atomicidad:** alta y edición completas, cambio de slug con historial/301/canonical, metadatos y bloques FAQ/especificaciones/bullets; inyectar fallo **después** de cada escritura SEO (historial, redirect, settings, bloques) y comprobar revertidos **todos** los registros; slug histórico ajeno no cambia `entity_id` inesperadamente; flash positivo/auditoría solo tras commit.
6. **Archivos:** subida válida/inválida, límite 4 MB/6000 px, fallo DB después de staging, cleanup exacto de archivo recién subido; no eliminar portadas antiguas ni las tres referencias históricas.
7. **Público, en entorno aislado y sin editarlo antes de aprobación:** URL directa hija activa/padre inactivo, directorio `/categorias`, facetas, home destacadas, listado y búsqueda de productos, breadcrumbs/SEO. Comprobar que la política acordada no filtra productos que deben seguir visibles por otras categorías o enlaces.
8. **Regresión:** CSRF, permisos admin, rate limits, duplicados de slug, SEO existente, build gate global y contrato focal, 363/390/768/1024/1600 px con capturas del formulario y lista sin variación de diseño.

**Evidencia previa reutilizable (NO reejecutada en esta etapa):** en §10.5–10.7, HTTP autenticado sobre `mi_pesca_cat_e2e_20261021`, 10 capturas, 12 contratos y build gate 826 PHP/119 JS OK. **Esta etapa solo realizó lectura de código y análisis de contratos; no declarar las nuevas pruebas aprobadas.**

## DECISIONES PENDIENTES DE APROBACIÓN

**Solicitud concreta al chat principal/propietario — responder ANTES de modificar servicios compartidos:**

- **D1 — Visibilidad:** ¿Se aprueba que desactivar por **cualquiera de los dos endpoints** desactive toda la rama, y que reactivar solo habilite el nodo? ¿El formulario debe aplicar cascada **solo al pasar 1→0**? Recomendación: **sí**.
- **D2 — Ancestros:** ¿Se prohíbe activar/reubicar/crear nodo **activo** bajo algún ancestro inactivo? Recomendación: **sí**, rechazando sin cambios y mostrando la cadena que debe corregirse.
- **D3 — Datos heredados / público:** ¿Se sanea mediante operación explícita cualquier hija activa con padre inactivo? ¿Debe además protegerse la visibilidad pública por ancestros en URL directa/home/facetas, aunque no se cambie el flag de productos? Recomendación: **auditoría y saneamiento explícitos; definir contrato público antes de editarlo**.
- **D4 — Creación rápida:** ¿Se acepta **HTTP 409** si el mismo slug existe bajo **otro padre**, preservando slug globalmente único? Recomendación: **sí**. Definir si mismo padre devuelve ID existente y qué significa `parent_id` omitido desde el modal. Resolver simultáneamente el **selector de padre no enviado** por el JS.
- **D5 — Atomicidad SEO:** ¿Se autoriza orquestador PDO por categoría para guardar categoría + slug history + redirects + SEO settings + bloques con una transacción y errores visibles? Recomendación: **sí**, previa reserva de servicios SEO. Decidir política de slug histórico usado por otra entidad y estrategia de staging/compensación de imágenes.
- **D6 — Reservas:** confirmar responsable y ventana exclusiva para **CategoryAdminService**, **Productos** (`product_form/index.js` + modal), **SEO** (servicios y repositorios nombrados) y **público** (`CategoryRepository`, `FacetService`, controladores/servicios consumidores). Sin reservas, **no implementar** modificaciones transversales.
- **D7 — Aceptación:** confirmar que el cierre solo se declarará cuando se repitan los casos de §11.7 por HTTP autenticado en DB aislada y el contrato público acordado se compruebe sin regresiones.

> **Entrega de esta etapa:** informe técnico y mapa de reservas preparados, **sin implementación funcional nueva**. **Estado: BLOQUEADO POR APROBACIÓN**. No hubo commits, push, worktrees, migraciones ni modificaciones de registros/portadas. Esperar autorización explícita del chat principal.

---

## 12. Fase 3 — propuesta final para autorización y reserva de archivos (2026-10-21)

> **ENTREGABLE DE PLANIFICACIÓN, NO IMPLEMENTACIÓN. Estado: PENDIENTE DE APROBACIÓN DEL CHAT PRINCIPAL.** Se releyeron `docs/COORDINACION.md`, `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0, `docs/PROTOCOLO_GIT_MULTICHAT.md`, `docs/pruebas/PROTOCOLO_NAVEGADORES.md` y este informe; se contrastaron seis rutas y los archivos PHP/JS actuales (fecha y tamaño verificados). Se mantienen §10 como evidencia histórica y §11 como diagnóstico. **Esta sección 12 es el plan operativo propuesto vigente, pero no modifica las decisiones D1–D7 hasta su aprobación expresa.** Única escritura de esta fase: el propio `docs/modulos/categorias.md`.

### 12.1 Política funcional exacta propuesta (contrato de negocio por aprobar)

**Invariante recomendada**: una categoría activa no puede tener ningún ancestro inactivo. El estado de `products.is_active` y la asignación `products.category_id` son independientes; **jamás modificar esos datos por desactivar categorías**. La política regula `categories.is_active` y jerarquía, no elimina productos, slugs, SEO ni contenidos.

| Solicitud / estado previo | Resultado propuesto | Observaciones de idempotencia |
|---|---|---|
| Activa A → desactivar A por `save` o `toggle` | `A=0` y **todos** sus descendientes `=0`, independientemente de profundidad | Se debe operar como una sola transacción; mismos resultados y auditoría coherente |
| Inactiva A → activar A | `A=1`; hijas y nietas **permanecen como están** | Solo posible si todos los ancestros de A están activos; no publicar descendientes implícitamente |
| Activa A → guardar nombre/SEO sin cambiar estado | Sin variación de `is_active` en la rama | **No** ejecutar cascada cada vez que se guarda el formulario |
| Inactiva A → guardar sin solicitar activación | Preservar estados actuales; no sanear datos heredados silenciosamente | Auditoría de anomalías aparte, decisión D3 |
| Inactiva hija B bajo padre inactivo A → activar B | **Rechazar**, sin escritura, indicando qué ancestro debe habilitarse primero | Mismo criterio en ambos endpoints |
| Alta activa bajo cualquier ancestro inactivo | **Rechazar**, sin inserción ni SEO/archivo definitivo | Alta inactiva bajo padre inactivo podrá permitirse |
| Reparentar activa A bajo rama inactiva | **Rechazar** operación completa | Comprobar nueva cadena de ancestros y descendientes actuales |
| Reparentar inactiva A con hijas activas bajo rama inactiva | **Rechazar hasta resolver anomalías de la subrama**; nunca publicar ni desactivar implícitamente | Requiere regla para árboles heredados; alternativa aceptable: saneamiento expreso previo |
| Reparentar rama inactiva íntegramente bajo padre inactivo | Admitir si no produce ciclo ni invalida invariantes | No activar ningún nodo |
| Repetir estado solicitado `setActive(A,estadoActual)` | Éxito idempotente, sin efectos colaterales; resultado informativo | El toggle de UI deberá determinar estado objetivo bajo lock, no confiar en un valor antiguo del cliente |
| Slug repetido en otra rama vía `quick` | **HTTP 409 JSON**, sin inserción ni selección automática | El índice `UNIQUE slug` continúa siendo global |

**No proponer** un “arreglo” que cambie automáticamente `products.is_active`, elimine asociaciones o haga masiva la reactivación de toda una subrama.

**Distinción esencial de reglas**: (a) **integridad de escritura Admin** — debe ser obligatoria en el servicio que guarde/mueva/active; (b) **visibilidad efectiva pública** — en el código actual una hija activa con padre inactivo puede tener página accesible, mostrarse como raíz en `FacetService`, y hay filtros de productos que no exigen ancestros activos. Decidir si deben ocultarse páginas, facetas, cards y productos filtrados por ramas inactivas **sin cambiar estados de productos**. La consistencia de escritura futura por sí sola no sanea anomalías heredadas ni garantiza que ninguna ruta pública filtre indebidamente.

### 12.2 Arquitectura propuesta: un caso de uso, múltiples entradas

**Separación de responsabilidades**, pendiente de autorización:

1. `CategoriesController::save()`: adaptador HTTP para validar CSRF, cuota, parámetros, devolver errores, conservar valores del formulario y redirigir **solo cuando el servicio informa commit exitoso**. No ejecutar SQL, subir archivos definitivos ni emitir flash de éxito anticipado.
2. `CategoriesController::toggle()`: adaptador HTTP del mismo caso de uso; lee estado bajo lock, solicita el **estado objetivo** a la política y conserva contrato de navegación/auditoría (sin volver a implementar su propio algoritmo de cascada).
3. **Nuevo `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php`** (nombre propuesto): validar cadena de ancestros, ciclo, transición e invariantes; definir `setActive(categoryId,desiredState)`, conjunto recursivo afectado, diferencia anterior/nueva y motivo de rechazo. Usado desde **ambas** entradas; no guarda SEO ni archivos.
4. **Nuevo `app/Services/Admin/Categories/CategorySaveService.php`** (nombre propuesto): orquestar alta/edición/reparentado y política de visibilidad, SEO/historial/301/bloques y recurso de portada en una unidad de trabajo. Define quién **posee una única transacción PDO** y devuelve resultado tipado (`ok`, error semántico, IDs afectados, mensaje); no intenta hacer `commit()` desde cada dependencia.
5. `CategoryHierarchyGuard`: lectura/validación de árbol (padre existente, ciclos, ancestros y elegibilidad del destino). No duplicar la validación en Controller ni en dos servicios. Revalidar dentro de la transacción antes de actualizar.
6. `CategoryAdminService` (ubicado en `app/Services/Catalog/`): revisar si conviene convertirlo en adaptador del servicio único o consolidar su lógica allí, **sin mantener dos políticas activas**; archivo de ruta compartida, requiere turno exclusivo del principal.
7. SEO existente: conservar llamadas a `SeoManagerService`, `CategorySeoBlocksService` y repositorios con **la misma conexión PDO**. No duplicar las consultas ni crear repositorios SEO paralelos. Verificar que propagan excepciones y que no tienen commits propios.
8. `CategoryRepository`/público: **solo consumidores** en esta fase; contrato de visibilidad por toda la cadena de ancestros pendiente, sin asumir que el cambio Admin autoriza a editarlos.

**Orden operacional recomendado para guardado:** validar campos y archivo → preparar nueva portada en staging aislado → comenzar transacción y fijar locks → recargar categoría/jerarquía y estado actual → validar invariantes de rama (incluida transición 1→0) → INSERT/UPDATE categoría y descendientes según caso → historial de slug/redirects/SEO settings/bloques → verificar/publicar archivo staged antes del commit, con compensación ante error → COMMIT → auditoría/flash de éxito. Si falla cualquiera: ROLLBACK, no mostrar éxito, eliminar **solo** el recurso nuevo creado por ese intento, preservar portada anterior y valores editados. El filesystem no es transaccional: queda riesgo de archivo huérfano si el proceso muere después de publicar y antes de commit; considerar limpieza por identificador/antigüedad fuera del flujo comercial.

**Observación de concurrencia:** `SELECT ... FOR UPDATE` sobre la raíz actual no protege por sí solo un reparentado de otra sesión que entra/sale del subárbol; bloquear ancestros y nodos afectados en orden consistente, validar nuevamente el árbol y definir protección contra *phantoms* de categorías creadas/movidas mientras se construye el conjunto. Si InnoDB no brinda invariantes suficientes con ese patrón sin migración, evaluar mecanismo de serialización **acotado** con el chat principal; no adoptar lock advisory/global sin acordar sus timeouts, liberación y compatibilidad. Capturar deadlocks/reintentos limitados y asegurar operación idempotente donde sea viable.

### 12.3 Contrato de creación rápida: resolución del caso de Productos

**Estado actual confirmado en código:** el endpoint `POST /admin/categories/quick` acepta `name,parent_id`, pero ante slug existente consulta **solo** slug y devuelve `200,ok:true,id`, sin comparar padres. `public_html/assets/js/admin/product_form/index.js::initCategoryQuickCreate()` posee `parentSel=#category-quick-parent` pero su `FormData` agrega únicamente `name` y CSRF; omite `parent_id` aunque el modal esté diseñado para seleccionarlo. El DOM vive en `app/Views/partials/admin/products/tab_basicos.php`. Es una **doble falla** de contrato, no se resuelve con backend aislado.

| Condición | HTTP / payload propuesto | Comportamiento de Productos |
|---|---|---|
| Slug libre + padre elegible | Éxito JSON con `id,name,slug,parent_id,created:true` (HTTP 201 **si se aprueba**, o 200 por compatibilidad) | Insertar opción, seleccionarla y cerrar modal |
| Slug existente bajo **mismo** `parent_id` | **Recomendado:** HTTP 200 JSON `ok:true,created:false,id,parent_id`; reutilización explícita solo dentro de la misma rama | Mostrar selección existente; no insertar duplicado |
| Slug existente bajo **otro** padre, incluido `null` vs ID | **HTTP 409** `{ok:false,code:"category_slug_parent_conflict",error:"...",existing:{id,name,slug,parent_id}}` | Mostrar conflicto; **no cerrar modal, no borrar nombre/padre, no seleccionar otra categoría, no perder formulario de producto** |
| Padre inválido/inexistente/inactivo para alta activa | HTTP 422 con mensaje estable; rechazo completo | Mostrar error local, mantener entradas |
| Petición concurrente con slug insertado entre SELECT e INSERT | Convertir excepción UNIQUE en respuesta estable según padre real; no devolver 500 genérico | Misma UX de 409/200 según caso |
| `parent_id` omitido | **Recomendado:** compatibilidad `null`/principal; precisar semántica antes de activarlo | JS debe enviar **siempre** `parent_id` del selector, incluso vacío como `0` o acuerdo definido |

**Nota:** cambiar únicamente `quick` a 409 mientras Productos todavía omite el padre puede generar conflictos falsos para categorías existentes bajo cualquier padre. Implementar endpoint + consumidor **coordinadamente en una misma ventana**; probar conservación del formulario. No ampliar a edición de Productos fuera de esas líneas/DOM sin acuerdo.

### 12.4 Atomicidad SEO e historial: contratos y riesgos

- El Controller confirma éxito/audita **antes** de SEO; luego atrapa errores de SEO sin revertir la categoría. Un fallo tras actualizar `categories` puede dejar `seo_settings`, `seo_slug_history`, `seo_redirects` y `category_seo_blocks` desalineados.
- Las tablas están **declaradas InnoDB en `database/schema.sql`**; **verificar `information_schema` de la copia aislada y de DB habitual en solo lectura** antes de comprometer atomicidad; el esquema documental no prueba motor real.
- `SeoSlugHistoryRepository::add()` ejecuta `ON DUPLICATE KEY UPDATE entity_id=VALUES(entity_id)` bajo unique `(entity_type,slug)`. Esto **puede transferir a otra categoría un slug histórico ajeno sin error**, incluso dentro de una transacción exitosa. **Decisión previa obligatoria**: proponer rechazar el slug reservado por otra categoría (HTTP 409/validación) y preservar su historial; valorar cómo permitir reutilización explícita bajo decisión editorial. El guardado actual no detecta ese conflicto histórico.
- Riesgo distinto: volver a slug antiguo **de la misma categoría** debe evitar redirecciones circulares y preservar historial legítimo. Probar A→B→A, cadenas 301 y canonicals.
- `SeoManagerService`, servicios de bloques y repositorios existentes fueron inspeccionados; no se verificó inyección de fallos de toda la cadena en esta fase. **No modificar contratos SEO globales** hasta reservar archivos precisos. Evaluar si basta orquestador en Categorías que usa su API existente y captura errores correctamente; reservar edición global solamente si pruebas demuestran necesidad.
- Audit/error reporting: un éxito solo **después** del commit; log de rechazo/fallo sin informar “creada/actualizada”. Para auditoría best effort fuera de transacción, documentar si fallar el log debe impactar operación (recomendado: no revertir commit por fallo no crítico de log).

### 12.5 Propiedad y reservas precisas antes de cualquier edición

| Archivo exacto | Ámbito/propietario a coordinar | Motivo y límites |
|---|---|---|
| `app/Controllers/Admin/CategoriesController.php` | **Categorías, autorizado solo tras D1–D6** | Delegar save/toggle/quick, CSRF/errores/409, mantener rutas |
| `app/Services/Admin/Categories/CategoryHierarchyGuard.php` | **Categorías** | Validación de ancestros/ciclos bajo guardado transaccional |
| **Nuevo** `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php` | **Categorías, propuesto** | Regla única para save/toggle |
| **Nuevo** `app/Services/Admin/Categories/CategorySaveService.php` | **Categorías, propuesto** | Orquestador transacción categoría+SEO+portada |
| `app/Services/Catalog/CategoryAdminService.php` | **Servicio en carpeta compartida: RESERVA** | Evitar dos algoritmos de transición; bloqueo/cascada |
| `app/Views/pages/admin/categories/form.php`, `app/Views/partials/admin/categories/toggle_action.php` | **Categorías** | **Solo** confirmaciones/mensajes derivados de cambio funcional, sin retoques de CSS |
| `public_html/assets/js/admin/product_form/index.js` | **PRODUCTOS: RESERVA OBLIGATORIA** | Enviar `parent_id`, manejar 409 y estado local del formulario |
| `app/Views/partials/admin/products/tab_basicos.php` | **PRODUCTOS: RESERVA OBLIGATORIA** | Confirmar opciones del selector/modal, sin reorganizar Productos |
| `app/Services/Seo/SeoManagerService.php` | **SEO GLOBAL: REVISIÓN/RESERVA si se edita** | Slug history/redirect/settings compatibles con transacción |
| `app/Services/Seo/CategorySeoBlocksService.php` | **SEO GLOBAL: REVISIÓN/RESERVA si se edita** | Persistencia de bloques |
| `app/Repositories/Seo/SeoSlugHistoryRepository.php` | **SEO GLOBAL: RESERVA ESPECIAL** | Problema semántico de transferencia de slug histórico |
| `app/Repositories/Seo/SeoRedirectRepository.php` | **SEO GLOBAL: REVISIÓN/RESERVA si se edita** | 301 y retarget en misma transacción |
| `app/Repositories/Seo/SeoSettingsRepository.php` | **SEO GLOBAL: REVISIÓN/RESERVA si se edita** | Upsert settings |
| `app/Repositories/Seo/CategorySeoBlocksRepository.php` | **SEO GLOBAL: REVISIÓN/RESERVA si se edita** | Upsert/delete de bloques |
| `app/Repositories/Catalog/CategoryRepository.php` | **CATÁLOGO PÚBLICO: RESERVA aparte** | Resolución de categoría activa con ancestros |
| `app/Controllers/Catalog/CategoryController.php` | **CATÁLOGO PÚBLICO: RESERVA aparte** | URL directa de categoría |
| `app/Controllers/Catalog/CategoriesController.php` | **CATÁLOGO PÚBLICO: RESERVA aparte** | Listado/directorio |
| `app/Services/Catalog/FacetService.php` | **CATÁLOGO PÚBLICO: RESERVA aparte** | Árbol y counts; hijas huérfanas visuales |
| `app/Repositories/Catalog/ProductRepository.php` | **CATÁLOGO PÚBLICO: RESERVA aparte** | Verificar filtros de visibilidad, **sin modificar flags de productos** |
| `app/Services/Public/HomePageViewService.php` y `app/Services/Public/HomeSectionsViewService.php` | **CATÁLOGO PÚBLICO: revisar consumidores antes de reservar edición** | Home/destacadas; revisar necesidad real sin modificar |
| `scripts/build/categories_admin_contract.php`, pruebas propias nuevas | **Categorías** | Contract tests estados, 409, transacción |
| `config/routes.php`, `scripts/build/gate.php`, `app/Support/helpers.php`, migraciones, `docs/ESTADO_DESARROLLO.md`, `MANIFEST.md` | **CHAT PRINCIPAL/INFRAESTRUCTURA** | No se solicitan cambios por defecto; reservar solo si imprescindibles |
| `public_html/assets/css/admin-v3/pages/categories.css`, `public_html/assets/css/admin-v3/components/*`, layout y Page Header | **NO TOCAR** | Rediseño existente conservado; aprobación visual por propietario/principal |

**Pedir ventana de edición exclusiva por archivo, no solo “permiso SEO” genérico.** Primero intentar integrar usando contratos SEO de solo lectura, y abrir reserva de escritura global únicamente si el fallo existe o el comportamiento del slug histórico debe cambiarse. No confundir ubicación `app/Services/Catalog/` con permiso exclusivo de Categorías.

### 12.6 Matriz de pruebas de aceptación (a ejecutar DESPUÉS de aprobar código)

**Entorno:** copia privada fuera de `C:\xampp\htdocs\mi-pesca`, MySQL exclusiva y descartable **con estructura y fixtures sintéticos**, `.env` propio apuntando a DB no habitual, servidor PHP loopback, sesión admin fixture + CSRF auténtico y verificación `SELECT DATABASE()` en cada prueba; no usar datos, imágenes ni credenciales de clientes. Utilizar como referencia scripts de §10.5 pero **no ejecutar antiguos fixtures a ciegas**. Navegador automatizado headless, `try/finally` cierra tabs/contexto/browser y procesos propios; comprobar cierre, no cerrar ventanas de usuario ni XAMPP.

| ID | Caso / precondición | Resultado objetivo verificable |
|---|---|---|
| V01 | Activa padre + hija + nieta: desactivar desde `save` | Todos `=0`, una transacción, productos/asociaciones intactos |
| V02 | Fixture idéntica: desactivar desde `toggle` | Estados, IDs afectados y auditoría **equivalentes** a V01 |
| V03 | Reactivar padre después de V01/V02 | `1/0/0`, ninguna hija reactivada automáticamente |
| V04 | Editar nombre/SEO de padre que ya estaba inactivo | Ninguna propagación nueva de estados; mismo árbol |
| V05 | Intentar activar hija bajo ancestro inactivo en ambos endpoints | Rechazo explícito, DB sin escrituras |
| V06 | Crear/mover activa bajo padre o abuelo inactivo | Rechazo; sin insert/reparentado/SEO/archivo definitivo |
| V07 | Mover subárbol inactivo con descendiente activo bajo inactivo | Rechazo o saneamiento **solo** según regla D3 aprobada |
| V08 | Padre inexistente, propio, nieto y ciclos profundos | Rechazo conservando jerarquía |
| V09 | Dos requests paralelos: cascada vs reparentado/activación | Sin hijos activos bajo ancestros inactivos, rollback si hay conflicto, sin deadlocks sin tratar |
| Q01 | Slug libre, padre activo existente | Creación válida en padre solicitado; índice slug global único |
| Q02 | Mismo slug, **otro padre** | **HTTP 409** con código semántico; no cambia DB |
| Q03 | Mismo slug, mismo padre | Reutilización visible `created:false` o 409 según D4, nunca duplicado |
| Q04 | Modal Productos selecciona padre antes de `quick` | Request contiene `parent_id`; categoría elegida no se reemplaza ante 409 |
| Q05 | Error 409/422 de Productos con formulario de producto parcialmente completado | Modal abierto, nombre/padre conservados, campos del producto y categoría previa intactos |
| Q06 | `parent_id` omitido/0/inválido y carrera slug UNIQUE | Contrato D4, status claro, sin reuso ajeno ni 500 genérico |
| T01 | Crear categoría con SEO settings + FAQ/bullets/specs | Todos los registros aparecen juntos después del commit |
| T02 | Editar slug A→B y de B→A | Historial, 301, canonical coherentes, sin bucles |
| T03 | Slug histórico ya registrado para **otra categoría** | Rechazo/política D5; NO reasignar `entity_id` silenciosamente |
| T04 | Fallo inyectado tras INSERT/UPDATE categoría | Todo revertido, sin flash de éxito |
| T05 | Fallo inyectado en historial, redirect, settings y bloques (cada punto) | ROLLBACK de **todas** las tablas, sin registros parciales |
| T06 | Subida válida PNG/JPEG/WEBP y límites 4MB/6000px | Persistencia válida, errores claros, limpiar staging |
| T07 | Falla DB tras subir, falla publish del archivo, falla commit | Solo archivo recién creado se limpia/compensa; portada anterior intacta |
| P01 | Fixture hija activa con padre inactivo, URL directa `/categoria` | **Caracterizar** visibilidad actual sin tocar público; criterio final según D3 |
| P02 | Misma fixture en `/categorias`, facetas, home, búsqueda, productos | Documentar visibilidad por consumidor y ausencia de cambios en flags de productos |
| R01 | Acceso sin sesión, con rol insuficiente y CSRF inválido | Rechazo 401/403 según contrato vigente, sin escrituras |
| R02 | PHP/JS sintaxis, contrato de Categorías, regresión SEO y `scripts/build/gate.php` | Todos OK; registrar cantidades actuales, no repetir cifras antiguas como nuevas |
| R03 | Listado y formulario 363/390/768/1024/1600 px | Sin regresión CSS/overflow y sin cambios de identidad; aprobación visual manual fuera de automatización |

**No ejecutar las pruebas T04–T07 ni de concurrencia sobre DB habitual**. Preparar fault injection solo en copia aislada; comparar estado previo/posterior por consulta DB/archivos. Guardar resultados por caso y el ID de DB de prueba; sin reutilizar fixtures con estados contaminados entre escenarios.

**Criterios de salida para marcar CERRADO FUNCIONALMENTE:** D1–D7 firmadas, reservas realizadas, V/Q/T completos sin fallos, P caracterizados y con decisión tomada, R01–R03 correctos, auditoría sin pérdidas y cero escrituras sobre DB habitual durante pruebas. Que el build gate pase **no** equivale a aprobación visual por propietario.

### 12.7 Secuencia segura de implementación por etapas (esperar autorización antes de la primera)

| Etapa | Dependencia / autorización requerida | Cambio permitido cuando se apruebe | Puerta de salida |
|---|---|---|---|
| **0. Gobernanza** | Chat principal aprueba D1–D7 y reservas precisas, confirma archivo responsable de Productos/SEO/público | Solo asignaciones/coordinación; respaldos privados, fixtures descartables | Lista de archivos libres y reglas de negocio firmadas |
| **1. Contrato de estados** | D1/D2, reserva `CategoryAdminService` | Servicio local de transición con target explícito y guard jerárquico, sin SEO ni UI global | V01–V09 en suite aislada; invariantes y concurrencia |
| **2. Entradas Admin** | Etapa 1 OK | Adaptar save/toggle para invocar un servicio común, mensajes de cascada puntuales, sin rediseño | V01–V08 desde HTTP autenticado |
| **3. Creación rápida** | D4, turno **simultáneo** con Productos para dos archivos | 409 JSON en backend + `parent_id`/manejo error en modal | Q01–Q06 y no perder formularios |
| **4. Transacción + portada** | D5, revisión contratos SEO y posible reserva `SeoSlugHistoryRepository` | Orquestador PDO con historial/301/settings/bloques y staging/compensación | T01–T07, incluyendo fallos forzados |
| **5. Compatibilidad pública** | D3, reservas públicas separadas **solo si la decisión requiere editar** | Al principio auditoría read-only, luego cambios mínimos por consumidor autorizados | P01–P02 y regresión home/catálogo |
| **6. Cierre** | Etapas anteriores aprobadas; coordinación con principal | Contrato focal, gate, browser con teardown, documentación final | R01–R03, propietario/principal aprueban; principal integra Git |

**Nota de secuencia:** etapas 1–4 comparten Controller y posiblemente unidad de trabajo. Evitar dos commits o integraciones intermedias en las que el formulario ya despache cascada pero aún no exista rollback SEO. Usar cambios focales y pruebas entre etapas, con integración/publicación ordenada exclusivamente por el chat principal.

### DECISIONES PENDIENTES DE APROBACIÓN — FASE 3

1. **D1 (visibilidad):** aprobar cascada descendente para desactivar en ambos endpoints, pero reactivación solo del nodo; propagar únicamente durante cambio efectivo **1→0**. **Recomendación: aprobar.**
2. **D2 (ancestros/reparentado):** rechazar altas activas, reactivaciones y movimientos de ramas activas hacia ancestros inactivos; decidir cómo manejar subárbol inactivo con descendientes activos heredados. **Recomendación: impedir toda inconsistencia nueva y sanear heredadas aparte.**
3. **D3 (público):** elegir entre (a) integridad del Admin + auditoría pública sin modificar público, o (b) exigir además visibilidad de toda la cadena en páginas, facetas/home/filtros. **Recomendación: (b) como contrato futuro, sujeto a reserva y pruebas específicas; NO modificar `products.is_active`.** Decidir saneamiento de datos históricos solo con vista previa y operación expresamente aprobada.
4. **D4 (quick/Productos):** HTTP 409 entre padres diferentes, semántica de padre omitido, reutilización en mismo padre, código de éxito en creación (200/201) y contrato JSON estable. **Recomendación: 409 otro padre; 200 `created:false` mismo padre; omisión compatible `null`; transmitir campo siempre desde JS.**
5. **D5 (atomicidad/slug histórico):** una transacción por alta/edición con SEO/bloques/historial/301, staging compensable y error visible; slug histórico de otra entidad **no debe cambiar de propietario** sin intervención. **Recomendación: aprobar transacción, rechazar transferencia silenciosa.**
6. **D6 (reservas):** autorizar ventana de escritura `CategoryAdminService`, coordinar **dos archivos de Productos** y revisión SEO; definir si/responsables para público. **No autorizar por defecto cambios de rutas globales, schema ni CSS.**
7. **D7 (aceptación):** aprobar matriz §12.6 y orden §12.7, entorno aislado descartable, teardown estricto, gate general y revisión visual humana final.

**Estado final de esta entrega:** **PROPUESTA ENTREGADA; NO IMPLEMENTADA**. Sin cambios de código, imágenes, uploads, datos reales, tests de escritura, CSS, servicios compartidos ni Git en esta fase. **Esperar autorización del chat principal.**

---

## 13. Fase 3 — VENTANA 1 IMPLEMENTADA (2026-10-21)

> **Estado: VENTANA 1 TERMINADA Y VALIDADA; DETENER CAMBIOS, ESPERAR AUTORIZACIÓN EXPRESA PARA VENTANA 2.** Documento rector: `docs/DECISIONES_CATEGORIAS_FASE3.md`, D1/D2/D7 aprobadas para esta ventana. D4 y objetivo D5 aprobados para **etapas posteriores**, NO fueron implementados. D3 solo lectura. Esta sección reemplaza, únicamente para Ventana 1, la condición de «no implementado» de los planes §§11–12; las restantes decisiones siguen pendientes por ventana.

### 13.1 Archivos realmente modificados / creados

| Tipo | Archivo (relativo al proyecto) | Trabajo efectivo |
|---|---|---|
| **Editado, reservado** | `app/Services/Catalog/CategoryAdminService.php` | Adaptador de compatibilidad sin lógica SQL duplicada: `toggleActiveCascade(id)` delega a la política; se agrega `setActive(id,desiredState)` explícito |
| **Editado, propio** | `app/Services/Admin/Categories/CategoryHierarchyGuard.php` | Snapshot indexado ordenado de jerarquía; modo `FOR UPDATE` solo dentro de transacción; validación de parent/ciclos; nuevo `validatePlacement()` para ancestros inactivos y subárboles incoherentes; recorrido de descendientes desde snapshot |
| **Nuevo, propio** | `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php` | Única implementación de transición de estados, idempotencia, descendientes, validación y transacciones/rollback |
| **Editado, propio** | `scripts/build/categories_admin_contract.php` | Amplía el contrato focal de 12 a **16 verificaciones**, reflejando que la política, no el adaptador, controla locks/transacciones |
| **Nuevo, prueba focal** | `scripts/dev/categories_visibility_window1.php` | MySQL descartable, fixtures sintéticos, transiciones, fallo SQL inyectado, rollback externo y dos procesos PHP concurrentes; destruye su propia DB |
| **Editado, único documento** | `docs/modulos/categorias.md` | Esta sección, evidencias, límites y próxima autorización |

**Respaldo privado, fuera de Git y del proyecto:** `C:\xampp\mi-pesca-backups\categories_phase3_window1_20261021` (copias previas de cuatro archivos reservados/documentales). **No modificados:** `CategoriesController.php`, vistas, JS Productos, SEO, público, CSS, layout, rutas, contratos/gate globales, migraciones, base habitual, portadas y Git.

### 13.2 Contrato técnico implementado, exacto

- `CategoryVisibilityPolicyService::setActive(int $id,int $desiredState)`: acepta solo `0`/`1`; resultado tipado asociativo `ok,from,to,changed,cascade_ids,children_count,products_count` en éxito. Si no existe ID → `ok:false,code:not_found`; estado inválido → `invalid_state`; activación con ancestro inactivo → `inactive_ancestor`. Sin escrituras en rechazos funcionales.
- **Transición 1→0:** determina `[$id, ...todosLosDescendientes]`, emite una única actualización SQL de estados en una transacción PDO y devuelve IDs/cantidades reales; **no toca** `products.is_active`, asociaciones, stock, slugs, SEO ni portadas.
- **Transición 0→1:** solo el nodo; comprueba todos los ancestros mediante `CategoryHierarchyGuard::validatePlacement()`. No reactiva descendientes. La transición inversa del toggle se calcula **después** del lock, no con estado obsoleto del cliente.
- **Transición 0→0 o 1→1:** `changed:false`, sin SQL de modificación ni nueva cascada. Una hija incoherente heredada bajo un padre ya inactivo **no se sanea** por guardar de nuevo el mismo estado (D1).
- `CategoryAdminService::toggleActiveCascade(int $id)` **conserva** el método y los campos de respuesta anteriores utilizados por el Controller. El nuevo `CategoryAdminService::setActive()` permite que el futuro formulario pida un estado explícito. No existen dos implementaciones de cascada.
- `CategoryHierarchyGuard::validateParent()` conserva el uso previo para el formulario (padre/ciclos); `validatePlacement(id,parentId,desiredActive, ?snapshot)` añade la regla aprobada: activo bajo ancestro inactivo → error, inactivo bajo inactivo → permitido si la subrama no arrastra descendientes activos que quedarían incoherentes. No modifica filas. El método **todavía no está conectado al guardado HTTP del formulario**.
- **Transacción:** si el servicio inicia PDO, realiza `BEGIN` y `COMMIT/ROLLBACK`. Si ya existe una transacción externa, **no la confirma ni la revierte**, y **propaga excepciones SQL** para que el orquestador futuro pueda hacer rollback de toda la unidad de trabajo. La auditoría/flash permanece en el controlador actual; este servicio no los emite.
- **Concurrencia:** para evitar snapshots divergentes entre dos transiciones del propio servicio, `snapshot(true)` hace `SELECT ... ORDER BY id FOR UPDATE` sobre **toda** la tabla `categories` dentro de la transacción. Es un bloqueo conservador deliberado para la ventana inicial; **coste O(N)** y posible contención en catálogos grandes. No equivale a garantía frente a todos los escritores externos/phantoms según isolation level; el controlador `save` aún no usa la política. La granularidad y/o serialización de reparentados debe revisarse antes de declarar D2 completada end-to-end.

### 13.3 Pruebas ejecutadas realmente y evidencia

**Entorno:** prueba CLI PHP sobre MySQL local creando `mi_pesca_cat_w1_<8 hex>` con estructura de `categories` + `products` en **InnoDB** y 10 categorías sintéticas (raíz, hija, nieta, hermano, otra rama activa e inactiva y una subrama histórica incoherente), 3 productos de fixture. Se verifica que nombre temporal **no coincida** con `DB_NAME` habitual. Cada ejecución crea una nueva BD, comprueba resultados SQL y ejecuta `DROP DATABASE` únicamente sobre esa BD aleatoria en `finally`; se comprobó mensaje de creación y destrucción correctas en dos ejecuciones. Los procesos workers PHP se cierran usando `proc_close` y `finally` para terminar **solo** los propios. No se utilizó navegador ni se abrió servidor HTTP; no se generaron ventanas o perfiles.

| Código / escenario | Evidencia ejecutada | Resultado |
|---|---|---|
| V01 servicio explícito: padre activo con hija, nieta y hermano | `setActive(1,0)` y lectura SQL; `cascade_ids=[1,2,3,4]`, `children_count=3`, `products_count=3`; productos sin cambios | **PASS** |
| V02/V03 adaptador existente: toggle 1→0 y luego 0→1 | estados `0/0/0/0`, luego `1/0/0/0`; contrato `from,to` vigente | **PASS**, **directo PHP**, NO HTTP |
| V04 idempotencia sobre padre ya inactivo e hija activa heredada | segunda `setActive(1,0)` informa `changed:false`; hija no se toca | **PASS** |
| V05 reactivar hija con padre inactivo | error `inactive_ancestor`; snapshot de estados idéntico | **PASS** |
| V06/07/08 validación previa de jerarquía | padre inexistente, mismo ID, ciclo raíz→nieta, activo bajo padre inactivo rechazados; nodo inactivo bajo padre inactivo permitido; subárbol con hijo activo incoherente bloqueado; ID destino inexistente rechazado | **PASS**, **guardia pura**, no POST/reparentado |
| Fallo SQL controlado en cascada | trigger `BEFORE UPDATE` / `SIGNAL SQLSTATE 45000` sobre nieta, ejecución `setActive`, error `transaction_failed`, comparación de estados sin cambios | **PASS**, rollback SQL |
| Fallo SQL con transacción externa | la excepción `PDOException` se propagó; el caller efectuó rollback; comparación SQL intacta | **PASS** |
| Transacción externa normal | `BEGIN` caller → `setActive` → `ROLLBACK` caller; estados originales restablecidos | **PASS** |
| Concurrencia MySQL **dos conexiones PDO/dos procesos PHP** | worker A desactiva bajo transacción y conserva lock 1,7 s; worker B solicita activación del mismo padre; espera observada **1,616–1,619 s** y resultado final `padre=1,hija/nieta/hermano=0`; sin pérdidas | **PASS** (serialización de dos transiciones) |
| Contrato focal `php scripts/build/categories_admin_contract.php` | **16/16** cláusulas | **PASS** |
| Smoke admin de solo lectura `php scripts/dev/categories_readonly_smoke.php` | 18 categorías habituales (9 activas), filtros y formulario SEO; **sin mutaciones** | **PASS** |
| Build gate `php scripts/build/gate.php` | **828 archivos PHP, 120 JS**, sidebar, Admin V3, SEO, Marketing, CSS global y regresión: `[gate] OK` | **PASS** |
| `php -l` de todos los archivos nuevos/editados PHP | Sin errores de sintaxis | **PASS** |

**Límite de la evidencia:** NO se ejecutaron V01/V02 por HTTP autenticado **con la nueva implementación**, porque `CategoriesController::save()` no puede editarse hasta Ventana 2. La prueba de toggle se hizo vía facade PHP y el Controller existente aún invoca `toggleActiveCascade()` por contrato. No se ejecutó POST de creación, reparentado real ni HTTP 409 de `quick`; corresponden a ventanas siguientes. Tampoco se probaron concurrencias de `save` (sin política) frente a `toggle`, ni phantom inserts/reparentados concurrentes; no presentarlos como resueltos. El rollback probado pertenece **solo a estados**, NO a categoría+SEO+historial+imágenes de D5.

### 13.4 Riesgos pendientes y puerta de autorización

1. **P0 Ventana 2:** `CategoriesController::save()` todavía realiza su propio `UPDATE categories.is_active` y puede dejar descendientes activos cuando se desactiva un padre. Hasta conectar ambos endpoints, la inconsistencia original **persiste** en el formulario. Debe integrar `setActive()` con el nuevo guard y definir transaction ownership para no persistir categoría antes de validar estado.
2. **P0 Ventana 2:** validación `validatePlacement()` aún no se llama en save/quick; por tanto un usuario puede mover/crear activos bajo inactivos mediante endpoints existentes. La implementación del guard por sí sola **no garantiza integridad end-to-end**.
3. **P1 Concurrencia:** lock global de categorías serializa operaciones de este servicio, pero provoca contención y no coordina todavía todos los escritores de jerarquía; planificar control de reparentado, phantoms, deadlocks y tiempos de bloqueo sin modificar schema ni servicios ajenos unilateralmente.
4. **P1 D5:** orquestador para categoría+slug history+redirects+settings+bloques y staging de portada sigue pendiente de ventana específica; **no** tratar rollback del estado como atomicidad SEO.
5. **P1 D4 Ventana 3:** `quick` con mismo slug/diferente padre sigue devolviendo el ID de otra rama; Productos sigue sin enviar `parent_id`. Ambos deben cambiarse juntos, con ventana exclusiva.
6. **D3:** público exclusivamente para auditoría; ningún consumidor, slug, imagen o flag de producto se modificó.
7. **Visibilidad/QA:** no hubo cambios CSS/visual ni navegador en esta etapa. Las capturas autenticadas de Ventana anterior siguen siendo históricas; aprobación visual definitiva del propietario/principal, no automatización.
8. **Siguiente paso de gobierno:** chat principal revisa los servicios y esta evidencia, luego **autoriza expresamente Ventana 2** antes de editar `CategoriesController.php`. La reserva de Ventana 1 no implica permisos automáticos para controller, SEO, Productos ni público.

**Cierre de Ventana 1:** **TÉCNICAMENTE COMPLETADA Y VALIDADA (servicio directo)**. **Cierre funcional global: PENDIENTE. Implementación detenida para esperar aprobación de Ventana 2.** Git sin commits/push ni ramas.

---

## 14. FASE 3 — VENTANA 2 IMPLEMENTADA Y VALIDADA (2026-10-21)

> **Estado: VENTANA 2 COMPLETADA. DETENER EDICIÓN Y ESPERAR AUTORIZACIÓN DEL CHAT PRINCIPAL PARA VENTANA 3.** Autorización expresa verificada antes de editar en `docs/DECISIONES_CATEGORIAS_FASE3.md` (adenda Ventana 2) y `docs/COORDINACION.md` (reserva). Se mantiene el Design System Admin V3 y toda la interfaz previa sin rediseño. **No equivale al cierre funcional total**: D4/quick, D5/SEO/archivos y D3/público siguen en otras ventanas.

### 14.1 Archivos realmente afectados

| Archivo | Tipo de cambio |
|---|---|
| `app/Controllers/Admin/CategoriesController.php` | **Solo `save()` y `toggle()`**: nueva unidad de trabajo transaccional categoría + visibilidad, validación final bajo lock, mensajes de cascada y advertencia SEO parcial. `form()`, `quick()` y uploader privado se mantienen intactos |
| `scripts/build/categories_admin_contract.php` | Contrato focal ampliado de 16 a **20** comprobaciones; verificaciones de estado explícito, transacción, sin actualización directa de `is_active`, rollback y advertencia SEO |
| `docs/modulos/categorias.md` | Registro de implementación, evidencias, limitaciones y puerta para Ventana 3 (esta sección) |

**Servicios de estado:** reutilizados **sin modificaciones nuevas** respecto de Ventana 1: `CategoryAdminService::setActive()`, `toggleActiveCascade()`, `CategoryVisibilityPolicyService` y `CategoryHierarchyGuard`.

**Backup previo privado:** `C:\xampp\mi-pesca-backups\categories_phase3_window2_20261021` (copias anteriores de controlador/servicios/contrato/informe, más scripts de reproducción privados). No se hicieron commits, push, modificaciones a `quick()`, Productos, SEO compartido, público, CSS, layout, rutas, migraciones ni a datos comerciales.

### 14.2 Funcionamiento real, contrato de transacciones

1. `save()` conserva CSRF, `rate_limit_or_fail`, nombres de campos, validaciones anteriores, subida de imagen, redirecciones y procesamiento de SEO existente. Una validación previa evita subidas inútiles; la **validación definitiva se repite bajo transacción y lock**.
2. Abre una transacción PDO y obtiene `CategoryHierarchyGuard::snapshot(true)`, igual que el servicio común: valida categoría existente, padre, ciclo, ancestros, reparentado y slug antes de confirmar. Para el caso **1→0**, construye una proyección **sin escribir** con descendientes inactivos para validar correctamente el **estado final**, incluso si también cambia el padre. Un subárbol inactivo con descendientes activos históricos no puede trasladarse bajo rama inactiva, pero un guardado sin mover ni cambiar estado **no sanea** descendientes sin consentimiento.
3. Modifica exclusivamente columnas propias `name,slug,parent_id,meta_title,meta_description,seo_text,cover_image,sort` **sin escribir `is_active` directamente**. En alta, inserta provisionalmente como inactiva (0) dentro de la misma transacción y solicita después el estado explícito.
4. Llama a `CategoryAdminService::setActive(id,desiredState)` en la **misma transacción**, con `CategoryVisibilityPolicyService` detrás. Desactivar 1→0 desactiva todos los descendientes; activar 0→1 activa solo el nodo; mantener 0→0 o 1→1 no dispara cascada. Si el servicio devuelve rechazo o falla SQL: **ROLLBACK de categoría+jerarquía+visibilidad**, mensaje de error y **sin auditoría ni flash de éxito**.
5. Solo después del commit se ejecuta `Audit::log('category.save')`, guardando `from,to,cascade_ids,children_count,products_count`. Se informa al usuario «También se desactivaron N subcategorías» cuando corresponde. `toggle()` mantiene su método/ruta/CSRF/rate limit y sigue invocando `toggleActiveCascade()` (adaptador a la **misma política**); sus mensajes indican el alcance y audita solo tras el éxito.
6. Conserva `storeCoverImage()` sin cambios, pero si la transacción **categoría/visibilidad** falla después de subir un archivo nuevo generado por esa petición, se intenta retirar **solo ese nuevo archivo** de patrón `/uploads/categories/<hash>.(png|jpg|webp)`; nunca se toca una portada anterior. **Esto es compensación limitada, no staging integral.**
7. Las llamadas `SeoManagerService` / `CategorySeoBlocksService` permanecen **postcommit y no atómicas** hasta Ventana 4. Si falla SEO después de confirmar la categoría, ahora se muestra **advertencia explícita**, no éxito falso: «La categoría y su visibilidad se guardaron, pero ocurrió un error al actualizar SEO». La base puede quedar parcialmente actualizada a nivel editorial. **No confundir este aviso con rollback SEO.**

**Restricción técnica restante:** el controlador contiene la validación/orden de su propio caso de uso de guardado, aunque la **política de estados** está unificada en un único servicio. No se creó un orquestador SEO global en esta ventana; por autorización se mantiene fuera de alcance. La política emplea `SELECT ... ORDER BY id FOR UPDATE` de toda la jerarquía: coste O(N), contención potencial y carreras con `quick()`/otros escritores de jerarquía que aún no usan la política.

### 14.3 Pruebas HTTP autenticadas ejecutadas REALMENTE

**Entorno descartable:**
- Copia privada de código y configuración fuera de XAMPP, `C:\xampp\mi-pesca-backups\categories_phase3_window2_20261021\isolated_site` **durante la ejecución**, eliminada al terminar.
- MySQL `mi_pesca_cat_w2_20261021`: **solo esquema** importado mediante `mysqldump --no-data` de la base habitual, diez categorías ficticias/árboles y usuarios ficticios administradores (sin copiar datos comerciales). PHP integrado loopback **127.0.0.1:18126**, autenticación fixture mediante token privado, sesión de administrador y CSRF reales; validación explícita de `SELECT DATABASE()`.
- Cliente HTTP Python Requests, no navegador; no hubo ventanas, contextos ni perfiles, por lo que no fueron necesarias capturas/teardown de Edge/Firefox. Para respetar el rate limit **sin alterarlo**, la suite usó sesiones administrativas sintéticas diferenciadas; previamente se observó correctamente HTTP **429** al agotar la cuota por una única sesión. No se bajaron límites ni se editaron helpers.
- Comprobaciones leyendo MySQL aislada tras cada POST. Evidencia privada reproducible: `prepare_e2e.py`, `seed_fixture.php`, `query_fixture.php`, `http_e2e.py`, `reset_fixture.py`, `seo_warning_e2e.py`, `uploaded_rollback_e2e.py`, `teardown.py` en el backup citado. Credenciales, token, sitio privado y base fueron eliminados al final; scripts quedan como evidencia histórica fuera de Git.

| Escenario HTTP | Comprobación realizada | Resultado |
|---|---|---|
| Autenticación/CSRF + GET listado, nueva, editar | HTTP 200 y sesión de fixture válida | **PASS** |
| **V01**: desactivar padre por `POST save` | `1/2/3/4=0`; nombre de raíz actualizado en DB; mensaje «También se desactivaron 3 subcategorías» | **PASS** |
| **V02**: desactivar mismo árbol original por `POST toggle` | **Estados idénticos 0/0/0/0**, mensaje equivalente de cascada | **PASS** |
| **V03**: reactivar raíz por ambas vías | `1/0/0/0` (solo raíz activa, hijas/nieta siguen inactivas) | **PASS** |
| **V04**: editar nombre de raíz ya inactiva con hija incoherente activa heredada | Nuevo nombre persistido, `changed:false`; ninguna recascada, hija intacta | **PASS** |
| **V05**: activar/mover hija activa bajo padre inactivo | Redirige con error de ancestro; `parent_id` original intacto | **PASS** |
| **V06–V08**: padre inexistente, mover padre bajo nieta/ciclo y mover subárbol inactivo con hija activa bajo rama inactiva | Errores visibles y ningún `parent_id` cambiado | **PASS** |
| Alta activa debajo de padre inactivo | Rechazada, slug nuevo inexistente en SQL y mensaje claro | **PASS** |
| Alta inactiva debajo de padre inactivo | Creada con `is_active=0` y padre correcto | **PASS** |
| Alta y posterior edición válidas con slug nuevo | Fila actualizada; slug previo ya no figura | **PASS** |
| Regresión SEO de alta | `seo_settings.robots_index=0` y `category_seo_blocks.bullets_json` reales guardados | **PASS** |
| Duplicado slug | Rechazo claro, sin duplicar registro | **PASS** |
| **Rollback SQL forzado** con trigger sobre nieta | Al intentar editar nombre y desactivar padre, falló UPDATE de cascada: nombre **y estados iguales al snapshot previo**, aviso de error, sin éxito | **PASS** |
| CSRF incorrecto | HTTP **403**, sin cambios | **PASS** |
| PNG multipart válido | Archivo realmente subido y ruta de categoría existente en copia privada | **PASS** |
| PNG multipart + rollback forzado | Ni nombre, ni estado, ni portadas cambian: recurso recién subido limpiado tras error | **PASS** |
| Error SEO inducido **después** de commit de categoría | Nombre queda persistido (limitación conocida W4); mensaje de advertencia SEO visible y **sin falso éxito completo** | **PASS** — atomicidad SEO **NO** resuelta |

**Rectificaciones durante las pruebas:** primera ejecución quedó detenida por codificación de arrays SEO en el script Python (se ajustó a `seo_bullets[]`); segunda alcanzó correctamente rate limit HTTP 429, por lo que se distribuyeron los casos entre identidades ficticias; la ejecución posterior completó **`W2_AUTH_HTTP_ALL_TESTS_PASSED`**. Estos obstáculos fueron del cliente de pruebas y se solventaron **solo en scripts privados**, sin reducir controles del proyecto.

### 14.4 Build, regresión y cierre de recursos

- `php -l` en Controller y contrato: **PASS**, sin errores.
- `php scripts/build/categories_admin_contract.php`: **20/20 PASS**.
- `php scripts/dev/categories_readonly_smoke.php`: **PASS**; DB habitual solo lectura, **18 categorías / 9 activas**, render de formulario/listado SEO correcto.
- `php scripts/build/gate.php`: **PASS** (ejecución real) — **828 PHP, 120 JavaScript**, contratos de sidebar/drawer, Admin V3, SEO, Marketing, CSS, checkout/cart y regresiones; `[gate] OK`.
- **Teardown confirmado:** se detuvo **solo** la sesión del servidor PHP de prueba creada por este chat, se comprobó puerto **18126 libre**, se ejecutó `DROP DATABASE IF EXISTS mi_pesca_cat_w2_20261021` **únicamente de fixture** con guarda `DB_NAME` habitual distinto, se eliminó `isolated_site` y se borró `private_config.json` con token/credenciales. No hubo navegadores ni ventanas abiertas, procesos o perfiles de prueba residuales; la base habitual no fue utilizada para escrituras.
- **Sin rediseño CSS, cambios en JS, componentes globales ni modificaciones a otras rutas.** La aprobación visual definitiva sigue en manos del propietario y chat principal.

### 14.5 Pendientes y siguiente autorización

1. **VENTANA 3 NO ABIERTA:** `CategoriesController::quick()` aún reutiliza silenciosamente categoría con mismo slug/otro padre; el formulario Productos no envía `parent_id`. Requiere autorización conjunta de `quick()`, `public_html/assets/js/admin/product_form/index.js`, `app/Views/partials/admin/products/tab_basicos.php`. Mantener JSON 409 aprobado según D4, sin tocar hasta abrir ventana.
2. **VENTANA 4 NO ABIERTA:** SQL de categoría/visibilidad atómico **sí**; SEO/historial/redirects/portadas **NO**. Requiere unidad transaccional propia con mismo PDO, prueba de excepciones y limpieza/staging, slug histórico sin reasignación. El caso real de SEO forzado demostró que, hoy, categoría puede guardarse aunque SEO falle (advertencia adecuada).
3. **D3 Público:** solo caracterización de lectura; no se tocaron consultas públicas ni `products.is_active` y la jerarquía heredada incoherente puede seguir causando páginas visibles por URL directa. Validar alcance posterior con propietario.
4. **Riesgo de concurrencia:** bloqueo conservador completo de `categories` y escritores aún no integrados (`quick`) exigen coordinación futura; prueba concurrente directa de W1 permanece vigente, pero **no se repitió carrera HTTP de `save` vs `quick`** (queda explícitamente pendiente).
5. **Portadas originales:** las tres referencias históricas faltantes §7 permanecieron intactas en la DB habitual. Las pruebas fueron exclusivamente con PNG nuevos aislados.
6. **Próximo paso obligatorio:** chat principal revisa este informe y autoriza **expresamente Ventana 3** antes de cualquier edición de Productos/quick. **No iniciar implementación sin esa autorización.**

**Estado final: VENTANA 2 COMPLETADA CON EVIDENCIA HTTP AUTENTICADA Y BUILD GATE OK; IMPLEMENTACIÓN DETENIDA.**

---

## 15. FASE 3 — VENTANA 3 IMPLEMENTADA Y VALIDADA (2026-10-21)

> **Estado: VENTANA 3 TERMINADA. Edición DETENIDA hasta autorización expresa del chat principal para Ventana 4.** Antes de editar se leyeron la adenda V3 de `docs/DECISIONES_CATEGORIAS_FASE3.md` y la reserva de `docs/COORDINACION.md`. Esos documentos identifican **un único responsable temporal para los dos archivos de Productos: Categorías**; no consta otra reserva concurrente. Se comprobaron las fechas y SHA-256 de ambos archivos antes de respaldar/escribir. Esta confirmación es documental y de archivos; no existe lock técnico ni visibilidad de una sesión ajena no declarada.

### 15.1 Implementación real: alcance y archivos

| Archivo | Cambios estrictamente autorizados |
|---|---|
| `app/Controllers/Admin/CategoriesController.php` | **Únicamente `quick()`:** valida nombre/slug, normaliza raíz, comprueba ancestros activos con `CategoryHierarchyGuard::validatePlacement()` bajo `snapshot(true)`/transacción, compara slug global y padre exacto; responde códigos 200/409/422 y captura carreras MySQL UNIQUE/deadlocks con reintento limitado; conserva rate limit y CSRF |
| `public_html/assets/js/admin/product_form/index.js` | **Únicamente `initCategoryQuickCreate()`:** envía siempre `parent_id` desde el selector (`0` para raíz), conserva datos y modal ante error, actualiza la selección solo tras `ok:true`. Monta el modal de Categorías bajo `document.body` para asegurar que su elemento `position:fixed` sea clicable dentro de formularios anidados en móvil; sincroniza `aria-hidden` |
| `app/Views/partials/admin/products/tab_basicos.php` | **Únicamente modal quick Categorías:** `role=dialog`, `aria-modal`, `aria-labelledby`, título identificable y error con `role=alert`/`aria-live=polite`; **sin cambio de CSS, estructura de productos, modal Marcas ni otro formulario** |
| `scripts/build/categories_admin_contract.php` | 5 verificaciones adicionales de quick + modal + compatibilidad, **25 contratos focales** |
| `docs/modulos/categorias.md` | Registro y pruebas de esta Ventana 3 |

**Verificación del perímetro con respaldo anterior:** script privado `verify_scope.py` comparó contenido UTF-8: todo `CategoriesController.php` **antes de `quick()` es idéntico**, todo JS **antes de `initCategoryQuickCreate()` es idéntico**, y los fragmentos PHP del formulario **anteriores y posteriores al modal de Categorías** son idénticos. Se preservó **la creación rápida de Marcas** y el resto del formulario. No se tocaron `save()`, `toggle()`, SEO, CSS, layout, servicios de visibilidad, catálogo público, migraciones, rutas, gate global ni Git.

### 15.2 Contrato de API definitivo (D4)

- `POST /admin/categories/quick`, `name`, `parent_id`, CSRF y límite de peticiones existentes. `parent_id` omitido, cadena vacía, `0`, valor PHP nulo y texto `null` (también `NULL`) → **raíz / `parent_id:null`**. Otros valores no numéricos, negativos, inexistentes o bajo **cualquier** ancestro inactivo → **HTTP 422** con `ok:false,code:category_invalid_parent`; no inserta.
- Nombre obligatorio, no sospechoso, máximo 120 caracteres; slug producido por `str_slug`, máximo 140. Se conserva el índice DB global `UNIQUE(slug)`.
- Si el slug **no existe** y el padre es admisible: `HTTP 200`, `{ok:true,created:true,id,name,slug,parent_id}`; inserta categoría **activa** en la rama seleccionada.
- Si existe el slug bajo **el mismo padre exacto**, incluida raíz, y está **activo**: `HTTP 200`, `{ok:true,created:false,id,name,slug,parent_id}`; **reutilización expresa**, sin INSERT.
- Si el slug existe bajo **otro padre**: `HTTP 409`, `{ok:false,code:"category_slug_parent_conflict",error,existing:{id,name,slug,parent_id,is_active}}`; **no cambia ni selecciona** otra categoría.
- Si está **inactiva bajo el mismo padre**: `HTTP 409`, `code:"category_inactive_conflict"`; no se reactiva ni reutiliza automáticamente. Un intento bajo un padre inactivo puede rechazarse antes con **422**, por la regla de ancestros aprobada.
- Carrera de `INSERT`: lectura bloqueante de jerarquía + transacción + UNIQUE; al recibir error de clave duplicada (1062), deadlock (1213) o lock timeout (1205), reintento acotado sobre snapshot nuevo; si la duplicación persiste, releer ganador bajo transacción para responder 200/409/422 según padre y estado. Errores no recuperables generan respuesta genérica sin datos SQL sensibles.

**Límite conocido:** el snapshot bloqueante de toda la tabla `categories` protege esta ruta en operaciones concurrentes que respetan la misma política, pero tiene coste de lectura/bloqueo proporcional a N y puede contener escritores grandes; `quick()` no integra todavía SEO ni historial (Ventana 4). El carácter global del slug está reforzado por DB, no por JS.

### 15.3 QA HTTP autenticado REAL: Q01–Q06

**Entorno aislado real:** copia del código **fuera** de XAMPP en `C:\xampp\mi-pesca-backups\categories_phase3_window3_20261021\isolated_site` durante prueba, MySQL `mi_pesca_cat_w3_20261021` (estructura exclusiva `mysqldump --no-data`, sin datos del negocio), diez categorías sintéticas con raíces, hijas, nietas e inactivas, ocho admins fixture. Servidores privados PHP independientes en **127.0.0.1:18128** y **127.0.0.1:18129** apuntando exclusivamente a esa DB; endpoint temporal autenticador token+loopback disponible **solo dentro de la copia privada**, CSRF/sesión de administrador y comprobación `SELECT DATABASE()`. Los scripts están documentados en el respaldo privado, **no** en el árbol web original.

| Prueba | Evidencia/resultados |
|---|---|
| **Q01** slug nuevo bajo raíz y padre activo | **PASS HTTP 200**, `created:true`, `parent_id` persistido tal como se envió; categoría realmente insertada |
| **Q02** slug existente en otra rama, incluyendo raíz vs hija | **PASS HTTP 409** `category_slug_parent_conflict`, ID y `parent_id` originales intactos |
| **Q03** mismo slug y padre activo exacto, incluida raíz | **PASS HTTP 200**, `created:false`, ID anterior intacto; reutilización tras nueva creación |
| **Q04** envío de `parent_id` desde modal real | **PASS**: selector enviado y reflejado en INSERT/reuso; raíz representada como `0` |
| **Q05** error HTTP 409/422 dentro del formulario real | **PASS**: modal abierto, nombre y padre intactos, mensaje accesible visible, `product-category-select`, nombre de producto y código interno sin cambios; ante 200 cierra y elige solo la categoría correcta |
| **Q06** padre inexistente/inactivo/ancestro inactivo, raíz `null`/ausente/`0`/vacío, CSRF | **PASS** 422 o 403 según caso, sin INSERT ilegítimo; pruebas HTTP explícitas de `parent_id='null','NULL','0',''` (compatibilidad de raíz) |
| **Q06 concurrencia real**: dos **servidores PHP distintos y dos sesiones** con barrera de inicio, cuatro carreras | **PASS**: mismos padres [1,1] y [7,7] → **un 200 created:true + un 200 created:false, mismo ID**; diferentes padres [1,7] y [7,1] → **un 200 created:true + un HTTP 409**, único registro global; sin 500 ni IDs cruzados |
| Unicode/nombre con tilde en navegador real | **PASS**: nombre `Viaje Ético W3 [fixture]` → slug `viaje-etico-w3-[fixture]` sobre HTTP FormData UTF-8 |

Los scripts focales privados son `http_q01_q06.py`, `http_null.py`, `concurrent_http.py`, `browser_modal.js` y scripts de preparación/fixtures. Las ejecuciones finales produjeron `Q01_Q06_HTTP_AUTHENTICATED_PASS`, `Q06_NULL_ROOT_COMPAT_HTTP_PASS` y `Q06_CONCURRENT_TWO_HTTP_SERVERS_PASS`. Una primera ejecución de Q tuvo problemas de codificación del cliente Python con tildes, **no** del PHP; por eso el caso Unicode se verificó además con el navegador real que envía FormData UTF-8. Se conservaron rate limits, usando distintos administradores ficticios sin reducir ni desactivar seguridad.

### 15.4 QA visual/funcional de modal y regresión Marcas

- **Edge Chromium headless real** mediante Puppeteer con perfil privado y teardown en `finally`, sobre **`GET /admin/products/new` auténtico**, sesión admin fixture y servidor MySQL aislado; ancho **390 px móvil** y **1280 px escritorio**, screenshots `mobile_390_conflict.png`, `desktop_1280_category_modal.png` conservadas fuera del proyecto.
- Una primera prueba detectó que el botón del modal estaba **fuera del área clicable** a 390 px porque el elemento fixed estaba anidado en contenedores del formulario. Se corrigió **solo dentro de `initCategoryQuickCreate()`**, moviendo ese modal al final de `body` (sin CSS). Tras el cambio, se comprobó geometría/clic reales y el flujo completo pasó.
- **PASS** abrir/cerrar modal; respuesta 409 y 422 no lo cierra ni borra entradas; selección previa de categoría y campos de Producto inalterados; 200 por reutilización/creación selecciona ID y cierra; caracteres acentuados correctamente procesados.
- **Regresión de Marcas real:** en la **misma página** se abrió `brand-quick-modal`, se creó **una marca sintética vía HTTP**, se seleccionó el ID retornado y se mantuvieron los campos Producto intactos. **PASS**; el JS de `initBrandQuickCreate()` y el modal Marcas se verificaron idénticos al backup.
- Browser informó **0 errores de página**, sin overflow horizontal en escritorio; el perfil `profile-w3-owned`, tabs y proceso Edge lanzados se cerraron/eliminaron en `finally` (**PID propio ya no vivo, perfil ausente**). Ninguna ventana del usuario se abrió o cerró.
- **Observación visual preexistente / no abordada por autorización:** en captura de escritorio el modal se alinea al sector izquierdo en vez de centrarse. Es funcional y clicable, pero el aspecto requiere valoración del chat principal/propietario si desea corrección de estilos aparte. **No se rehizo CSS ni se declara aprobación visual definitiva.**

### 15.5 Contratos, gate y teardown definitivo

- `php -l` Controller/modal/contrato, `node --check` JS: **PASS**.
- `php scripts/build/categories_admin_contract.php`: **25/25 PASS**.
- `php scripts/dev/categories_readonly_smoke.php`: **PASS**; DB habitual en **solo lectura**, 18 categorías (9 activas); sin mutaciones.
- `php scripts/build/gate.php`: **PASS en ejecución final** — **829 PHP / 120 JS**, contratos visuales y regresiones globales incluidos; `[gate] OK`.
- **Teardown comprobado:** se detuvieron únicamente los dos servidores PHP propios (sesiones PIDs identificadas), se verificó ausencia de listeners en **18128/18129**, se ejecutó `DROP DATABASE IF EXISTS mi_pesca_cat_w3_20261021` con verificación de nombre distinto de `DB_NAME` real, se borró `isolated_site`, token de fixture y credenciales privadas. **Solo** quedaron respaldo, scripts y capturas fuera de Git. No se tocaron registros, uploads, portadas faltantes ni otras bases del comercio.
- **Integración:** sin commits, push, ramas, migraciones, archivos CSS, routes, `scripts/build/gate.php`, Servicios SEO ni catálogo público.

### 15.6 Riesgos y parada obligatoria

1. **Ventana 3 entregada**: contrato D4, UI del modal y regresión de Marcas validados funcionalmente. La reserva excepcional de los archivos Productos puede ser liberada por el chat principal tras revisión.
2. **Ventana 4 pendiente de autorización**: guardado atómico de Categoría + SEO + historial/redirects + bloques y staging/compensación de portadas. No se implementó ni se probó su rollback integral durante esta ventana.
3. **D3 público** sigue solo lectura; una categoría heredada activa bajo ancestro inactivo puede ser visible por URL pública. No se modificaron productos ni catálogo.
4. **Deuda técnica/performance:** la lectura completa de jerarquía `FOR UPDATE` es conservadora y puede causar contención; posibles escritores externos fuera del contrato aún no serializados. Concurrencia de inserciones quick ya probada en cuatro escenarios HTTP.
5. **Visual pendiente**: el propietario/chat principal decide eventual ajuste de posición del modal escritorio en otra ventana. La prueba headless prueba funcionamiento, **no** aprobación estética.
6. **Regla de coordinación:** **no iniciar Ventana 4** sin autorización expresa del chat principal. No editar más archivos de Productos ni servicios compartidos.

**CIERRE: VENTANA 3 COMPLETADA, EVIDENCIA HTTP + NAVEGADOR + CONCURRENCIA + GATE OK. IMPLEMENTACIÓN DETENIDA.**

---

## 16. FASE 3 — VENTANA 4: TRANSACCIÓN CATEGORY + SEO + HISTORIAL + PORTADAS (2026-10-21)

> **Estado: VENTANA 4 IMPLEMENTADA Y VALIDADA TÉCNICAMENTE. DETENER EDICIÓN; VENTANA 5 PENDIENTE DE AUTORIZACIÓN DEL CHAT PRINCIPAL.** Autorización expresa consultada en última adenda de `docs/DECISIONES_CATEGORIAS_FASE3.md` y reserva de `docs/COORDINACION.md` antes de editar. Servicios/repositorios SEO compartidos se mantuvieron estrictamente de **solo lectura**, sin cambios de contratos. Ventana 5 no está autorizada.

### 16.1 Archivos realmente modificados (solo los permitidos)

1. **Nuevo:** `app/Services/Admin/Categories/CategorySaveService.php`: orquestador con **una transacción PDO** que abarca validación/locks de jerarquía, categoría, transición de visibilidad, historial, redirecciones, SEO settings y bloques editoriales. Publica portada staged antes de commit y compensa ante errores SQL/FS/commit.
2. **Editado:** `app/Controllers/Admin/CategoriesController.php` **solo** en `save()` y `storeCoverImage()`. El Controller conserva CSRF, rate limit, validaciones de nombre/slug/formulario, POST y rutas, y delega en `CategorySaveService`. Convierte fallo en un único error **sin éxito parcial**; hace `Audit::log` y `flash('ok')` **después** del commit integral. El helper prepara portada en `storage/category-cover-staging` (carpeta privada fuera de `public_html`) en vez de publicar el archivo inmediatamente.
3. **Editado:** `scripts/build/categories_admin_contract.php`: actualiza contrato focal de V4 y agrega verificaciones de misma transacción SEO, historial protegido, regreso de slug a canonical, promoción y compensación de portadas. **28 comprobaciones**, sin editar `scripts/build/gate.php`.
4. **Editado:** `docs/modulos/categorias.md`: evidencias y pendientes de este tramo.

**Verificación de ámbito:** `C:\xampp\mi-pesca-backups\categories_phase3_window4_20261021\verify_scope.py` comparó todo el Controller **antes de `save()`** y **desde `toggle()` hasta fin** contra el respaldo previo. Ambos segmentos son idénticos. Ni `quick()` ni `toggle()` cambiaron. **Sin modificaciones a Productos, SEO compartido, catálogo público, CSS, layout, migraciones, rutas, schema, gate global ni Git.**

### 16.2 Operación SQL y atomicidad comprobada

Dentro de `CategorySaveService::save(category,settings,blocks,upload?)`:

1. Rechaza transacción externa no controlada, inicia **una transacción** y lee la jerarquía con `CategoryHierarchyGuard::snapshot(true)` (locks bajo PDO). Conserva estado/idempotencia y validación del destino final de Ventanas 1/2.
2. Verifica slug globalmente único en `categories`, además de índice UNIQUE en MySQL. Consulta con **`FOR UPDATE`** `seo_slug_history` para **slug anterior y nuevo**, imponiendo que toda coincidencia `entity_type='category'` tenga **exactamente el mismo `entity_id`**. Si encuentra propietario diferente, rechaza con `DomainException`, **antes** de llamar a `SeoManagerService::registerSlug()`. Esto protege la ventana frente al `ON DUPLICATE KEY UPDATE entity_id=VALUES(entity_id)` del repositorio SEO global **sin editarlo**.
3. Actualiza/inserta campos de `categories` y llama al `CategoryAdminService::setActive(id,desiredState)` existente en **la misma transacción**; la cascada no cambia su semántica y `products.is_active` permanece intacto.
4. Reutiliza `SeoManagerService` existente para `registerSlug`, `recordCategorySlugChange` y `saveSettings`, y `CategorySeoBlocksService::saveForCategory` para bullets, specs y FAQ. No crean ni confirman transacciones separadas; propagan las excepciones SQL probadas. No hay catch que trague error SEO.
5. **A→B→A:** elimina el redirect de la **URL canónica nueva** `/categoria?slug=<slug>` dentro de la transacción, **antes** de retarget/upsert del historial. El historial del mismo ID se conserva; se establece `B→A`, pero se evita `A→B` simultáneo o `A→A`. No modifica semánticas de marcas/productos.
6. Si se subió una nueva imagen: `storeCoverImage()` verifica tamaño (máx. **4 MB**), dimensiones (máx. **6000 px**), tipo JPG/PNG/WEBP e identidad de `is_uploaded_file`, y mueve el archivo a un staging **privado** con nombre aleatorio de 32 hex. El orquestador verifica destino y promociona `rename()` a `public_html/uploads/categories/` **solo cuando finalizaron correctamente todas las operaciones SQL**, inmediatamente antes del `COMMIT`. Ante cualquier excepción, `ROLLBACK`, borrar **solo archivo nuevo promocionado**, limpiar stage restante. El archivo/ruta anterior **jamás se borra**.
7. Después de `COMMIT` exitoso, devuelve ID, operación y resultado de visibilidad. **Fuera del servicio/ya con SQL confirmado** el controlador registra auditoría y flash de éxito; ante error devuelve flash negativo y ruta de formulario correspondiente.

**Garantías verificadas/limitadas:** todas las **cinco tablas** `categories`, `seo_slug_history`, `seo_redirects`, `seo_settings`, `category_seo_blocks` del **schema aislado** reportaron `ENGINE=InnoDB`. Se validó efectivamente rollback cruzado. No hay transacción filesystem: queda una ventana mínima ante caída completa del proceso entre `rename` y `commit` (posible huérfano); diseño de limpieza de huérfanos por antigüedad y trazabilidad fuera de alcance, requiere aprobación de mantenimiento. La comprobación transaccional del historial protege este camino y las escrituras que respeten los locks, **pero el repositorio SEO compartido sigue ofreciendo su upsert peligroso a otros consumidores**; una garantía global absoluta de titularidad requeriría coordinación para cambiar ese contrato/constraints, no autorizada en V4.

### 16.3 Pruebas T01–T07 HTTP autenticado, SQL y fallas inyectadas

**Aislamiento real:** aplicación clonada temporalmente **fuera de XAMPP**, `C:\xampp\mi-pesca-backups\categories_phase3_window4_20261021\isolated_site` durante pruebas, base exclusiva `mi_pesca_cat_w4_20261021` con **solo estructura** de `mysqldump --no-data` y fixtures sintéticos (categorías, usuarios admins). Servidores PHP solo loopback, sesión admin fixture token privada, CSRF auténtico, verificación `SELECT DATABASE()`. No se copió ni editó contenido de la BD comercial; los controles de rate limit permanecieron activos. Pruebas/scripts privados `http_t01_t07.py`, `sql_fixture.php`, `fault_service.php`, `concurrent_save.py`, `verify_scope.py` y preparación/teardown, fuera del proyecto/Git.

| ID | Prueba real ejecutada | Evidencia y resultado |
|---|---|---|
| **T01** | Alta de Categoría con estado, meta fields, historial, robots `noindex,nofollow`, bullets, specs y FAQ | **PASS**: inserciones y JSON editoriales presentes juntas en DB después de HTTP 302 |
| **T02** | Editar A→B y regresar B→A | **PASS**: historial del mismo ID, `B→A` correcto, `A` no redirige ni existe bucle |
| **T03** | Slug histórico perteneciente a **otra categoría** | **PASS**: petición rechazada, todos los valores y `entity_id` histórico sin cambio, sin mensaje falso de éxito |
| **T04** | Trigger MySQL `SIGNAL SQLSTATE 45000` forzado sobre INSERT de `seo_slug_history` | **PASS**: rollback de categoría+SEO/visibilidad y ausencia de éxito |
| **T04** | Trigger equivalente sobre `seo_redirects` | **PASS**: sin nuevo slug/redirect parcial ni cambios en otras tablas |
| **T05** | Trigger sobre `seo_settings` | **PASS**: alta completa revertida, ni categoría ni historial ni settings parciales |
| **T05** | Trigger sobre `category_seo_blocks` | **PASS**: rollback de todas las escrituras previas |
| **T06** | PNG válido por HTTP multipart con staging | **PASS**: promoción al destino final, `cover_image` persistido, carpeta staging vacía |
| **T07** | SQL falla con PNG staged y portada anterior | **PASS**: nueva portada no queda publicada, stage vacío, portada anterior y fila intactas |
| **T07** | PNG inválido por HTTP multipart | **PASS**: error de uploader, sin inserción ni archivo staged |
| **T07** | Inyección directa en servicio: stage inexistente al publicar | **PASS**: rechazo de publicación y rollback + cleanup |
| **T07** | Inyección directa: subclase PDO hace fallar `commit()` | **PASS**: rollback SQL, eliminación de portada recién promocionada, staging vacío |
| **Regresión** | `quick()` entre padres distintos y `toggle()` de categoría con descendientes | **PASS** HTTP real: 409 y cascada intactos |
| **Concurrencia** | Dos servidores PHP y dos administradores guardan categorías distintas con **el mismo slug** a la vez | **PASS**: solo uno obtiene slug, uno rechaza colisión, historial pertenece al ganador; ningún duplicado ni redistribución silenciosa |

La suite principal produjo **`W4_T01_T07_HTTP_AUTHENTICATED_PASS`**, los scripts de fault injection informaron **`PASS publish_fail`** y **`PASS commit_fail`**, y `concurrent_save.py` pasó. El ensayo inicial de mensajes mezcló un flash de éxito anterior con el nuevo error; se corrigió **en el script de pruebas** drenando feedback entre solicitudes y se repitió la suite de cero sobre la BD aislada, que pasó completa. No se modificó la semántica de flash global.

### 16.4 Regresiones, contratos y límites de aceptación

- `php -l` Controller/orquestador/contrato: **OK**.
- `php scripts/build/categories_admin_contract.php`: **28/28 OK**.
- `php scripts/dev/categories_readonly_smoke.php`: **OK**, BD habitual **solo lectura**, 18 categorías totales / 9 activas, listado y formulario SEO.
- `php scripts/build/gate.php`: **PASS comprobado** — 830 archivos PHP, 120 JavaScript, contratos globales y regresiones (`[gate] OK`, exit code 0).
- El contrato de `quick()` y `toggle()` se preservó íntegro, con comparación exacta del Controller. El CSS, layout y frontend público no se tocaron; no hubo rediseño visual ni navegador en V4 (se usó HTTP real sin Edge/Puppeteer). La aprobación visual sigue siendo humana.
- **Pendiente no probado íntegramente:** recuperación automática de un archivo huérfano si el **proceso muere abruptamente** entre moverlo a público y commit. La inyección de excepción controlada sí prueba compensación. Tampoco se ha cambiado el `SeoSlugHistoryRepository` global ni probado que **otros consumidores no coordinados** mantengan propiedad de historia en una carrera externa. No se ha ejecutado ninguna migración de datos históricos ni saneamiento de enlaces públicos, explícitamente prohibido.
- **Portadas anteriores:** no se borró ni corrigió ninguna de las tres referencias faltantes del comercio documentadas en §7. No hubo cambios sobre DB real salvo smoke de lectura.

### 16.5 Teardown y parada de implementación

**Teardown CONFIRMADO:** servidores PHP privados de sesiones 13804 y 15100 terminados; **puertos 18131 y 18132 libres** antes del borrado; base exclusiva `mi_pesca_cat_w4_20261021` eliminada con guarda contra `DB_NAME` habitual; `isolated_site`, token y configuración con credenciales eliminados. Solo persisten respaldo privado, scripts y evidencia fuera de Git. No se abrió navegador en esta ventana: sin ventanas ni perfiles creados.

**Pendiente siguiente ventana:** Ventana 5 exige nueva autorización específica del chat principal para auditoría pública (según D3 solo lectura); no editar servicios/repo SEO, Productos, público, CSS/layout, rutas, migraciones o Git.

**VENTANA 4: IMPLEMENTADA Y VALIDADA — T01–T07, CONCURRENCIA, 28/28 CONTRATOS, 830 PHP / 120 JS GATE OK, TEARDOWN OK. IMPLEMENTACIÓN DETENIDA. NO COMENZAR VENTANA 5.**

---

## 17. VENTANA 5 — AUDITORÍA DE VISIBILIDAD PÚBLICA, SOLO LECTURA (2026-10-21)

> **ESTADO: AUDITORÍA REALIZADA; PROPUESTA PENDIENTE DE DECISIÓN COMERCIAL Y RESERVAS PARA VENTANA 6. NO IMPLEMENTAR POLÍTICA PÚBLICA SIN AUTORIZACIÓN.** La adenda final de `docs/DECISIONES_CATEGORIAS_FASE3.md` y `docs/COORDINACION.md` se leyeron antes de comenzar. No se reabrieron las Ventanas 1–4. **Único archivo del proyecto modificado: `docs/modulos/categorias.md`.** Todo código público, vistas, servicios, datos comerciales y rutas quedaron en solo lectura. Los scripts y el clon para pruebas se ubicaron **exclusivamente fuera de XAMPP/Git**, bajo `C:\xampp\mi-pesca-backups\categories_phase3_window5_20261021`.

### 17.1 Resumen ejecutivo: constataciones y severidad

| Riesgo | Comportamiento ACTUAL constatado | Severidad / decisión |
|---|---|---|
| Landing de hija activa con padre inactivo | `/categoria?slug=child-parent-off` devuelve **HTTP 200**, `robots=index,follow`, canonical propio; idéntico con flag ON y OFF | **P0 SEO/jerarquía:** decidir visibilidad efectiva por **todos** los ancestros |
| Landing de nieta activa con abuelo inactivo | `/categoria?slug=grandchild-grandparent-off` devuelve **200/index,follow**; el código de resolución no recorre ancestros | **P0:** misma regla para profundidad arbitraria |
| Directorio, home, navegación y sitemap categorías | Leen `categories.is_active=1` del **nodo solamente**. En facetas, hija activa cuyo padre no fue cargado reaparece como **raíz** | **P0 navegación/SEO:** exigir elegibilidad jerárquica uniforme |
| Producto activo en categoría **directamente inactiva** | Con `catalog_hide_inactive_category_products=0`, ficha/listado públicos **200**. Con **1**, ficha **404** y listado general lo excluye | **P0 COMERCIAL:** el propietario debe decidir si el producto continúa público |
| Producto activo bajo ancestro inactivo, pero categoría directa activa | Con flag **0 o 1**, ficha pública **200/index,follow** y aparece en listado/sugerencias/sitemap | **P0:** el flag actual NO comprueba abuelos/padres |
| Productos de categoría inactiva con flag ON | `/productos` puede excluirlos, pero landing de **padre activo**, buscador de sugerencias, productos relacionados y `sitemap_products.xml` todavía pueden exhibirlos | **P0:** divergencia entre descubrimiento, navegación y 404 al abrir ficha |
| Productos “sin categoría” | **Esquema real:** `products.category_id NOT NULL` y FK a `categories.id`: no es un estado válido. Con referencias inválidas simuladas únicamente en fixture, el flag ON las excluye del listado, pero NO de la ficha ni de sugerencias/sitemap | **P1 defensivo**, no afirmar que existen en la BD comercial |
| Categoría activa vacía | Landing directa devuelve **200/index,follow**. `catalog_show_empty_categories=0` quita su **tarjeta** del directorio si el recuento es cero, pero no su URL, enlace de buscador móvil ni necesariamente otras apariciones | **P1 SEO/UX:** decidir indexación y promoción de categorías vacías |
| Redirección a categoría ya oculta | `public_html/index.php` responde **301** por `seo_redirects` ANTES de resolver su destino y sin validar estado. Destino puede resultar **404** o landing aparentemente activa pese a padre inactivo | **P1 SEO:** política de destinos y equivalencia semántica |
| 404 de categoría/producto | En el fixture, respuesta **HTTP 404**, pero el `<head>` contiene `robots=index,follow` y canonical a **home**; la vista de error no corrige esos datos de head | **P1 SEO técnico**: alinear señalización de error, no aplicar 301 a home indiscriminadamente |

**Datos reales de referencia, consultados exclusivamente con SELECT sobre transacción read-only:**
- `app_settings.catalog_hide_inactive_category_products = '0'` **actualmente**, y `catalog_show_empty_categories = '1'`.
- **18 categorías**. **0 categorías activas bajo un ancestro inactivo** en el estado leído. **25 productos**, **19 activos**; **0 productos activos asociados directamente a una categoría inactiva**.
- **Conclusión correcta:** hay **fallas de política reproducibles** en código y fixtures; **NO hay prueba de que estas anomalías jerárquicas existan hoy entre las 18 categorías comerciales**. No se cambió ningún flag ni registro habitual.

### 17.2 Matriz precisa por ruta, servicio, archivo y consumidor (ACTUAL vs PROPUESTA)

| Superficie real | Responsable y consulta/función actual | ACTUAL comprobado (código/GET sintético) | Política candidata / cambio posterior — NO IMPLEMENTADO |
|---|---|---|---|
| `GET /categoria?slug=...` | `app/Controllers/Catalog/CategoryController.php::show()`, `app/Repositories/Catalog/CategoryRepository.php::findActiveBySlug()`: `WHERE slug=? AND is_active=1` | Acepta hija/nieta activa debajo de inactivos. Si el nodo mismo está inactivo, **404**. Su SEO default es indexable y canonical propio si entra | Resolver **elegibilidad de nodo + toda la ruta hasta raíz** antes de construir facet, productos y SEO; devolver respuesta acordada para no elegible |
| Productos en landing `/categoria` | `CategoryController::show()` → `ProductService::paginate()` → `ProductRepository::buildCatalogWhere()` | No añade `enforce_active_category` ni lee el setting; incluye productos de subcategoría directamente inactiva bajo padre visible. Con flag ON, `/categoria?slug=root-ok` y `&sub=direct-off` mostraron un ítem que `/productos` había ocultado | Aplicar **la política de productos aprobada** también a las landings, preferentemente desde un filtro central, sin alterar `products.is_active` |
| `GET /categorias` | `app/Controllers/Catalog/CategoriesController.php::index()`, `FacetService::categoriesWithCounts([])` | Lista nodos activos sin ancestros; nieto activo puede figurar dentro de hija “reconvertida” en raíz. `catalog_show_empty_categories` filtra **solo tarjetas raíz** si `count=0`. Recomendaciones `RecommendedProductsService::forGlobal()` no aplican setting | Construir árbol solo con categorías **efectivamente visibles**; decidir tratamiento de vacías y aplicar contrato de productos a recomendaciones |
| Facetas y filtros (`/productos`, `/categoria`) | `app/Services/Catalog/FacetService.php::categoriesWithCounts()`, `labelMap()`, `buildProductFilters()`; parcial `app/Views/partials/catalogo/facet-cats.php` | Nodos activos únicamente; padre no incluido → hija pasa a raíz. Conteos desde productos activos, en `/categorias` **sin enforcement**, pueden contar ocultos. Vista facet solo presenta raíces y un nivel de hijas; nietas se agregan en total recursivo pero no se renderizan como tercer nivel | Uniformar árbol visible, etiquetas y conteos con los mismos productos efectivamente seleccionables; decidir UX de profundidad sin duplicar consultas |
| `GET /productos` y búsqueda `?q=...` | `app/Controllers/Catalog/ProductsController.php::index()`; `app/Repositories/Catalog/ProductRepository.php::paginate(),priceBounds(),buildCatalogWhere()` | Único listado que construye `enforce_active_category` con setting; **ON = `c.is_active=1` del nodo directo** (INNER de facto vía LEFT JOIN). Nunca comprueba ancestros, pero excluye filas con categoría faltante. Búsqueda en nombre/slug de categoría directa y padre directo, incluyendo inactivos al estar OFF | Política de producto unificada ON/OFF decidida, con jerarquía arbitraria. Filtrar búsqueda y `priceBounds` con el mismo predicado; no cambiar status productos |
| Sugerencias `/productos/suggest.json` y `/productos/suggest-fragment` | `ProductsController::suggest(),suggestFragment()` → `ProductRepository::searchPreview()` → `app/Views/partials/header/_mobile_search_results.php` | **No leen setting**: consulta `p.is_active=1`, sin `c.is_active`. GET ON devolvió sugerencia de producto de rama oculta; una sugerencia puede enlazar luego a ficha 404 | Reutilizar filtro de elegibilidad de productos; HTML y JSON deben devolver **los mismos IDs** |
| `GET /producto?slug=...` | `app/Controllers/Catalog/ProductController.php::show()`, `AppSettingsRepository::get()` | Exige `p.is_active=1`; flag ON y categoría directa inactiva → **404**. Categoría directa activa bajo ancestro inactivo → **200**; flag OFF incluso directo inactivo → **200**. Producto inválido sin categoría fue 200 en fixture, explicado §17.4 | Elegir regla única de detalle producto; si se sigue mostrando, breadcrumbs/links deben evitar categorías ocultas; si se oculta, usar HTTP/SEO coherente |
| Productos relacionados en ficha, carrito y directorio | `app/Services/Catalog/RecommendedProductsService.php::{forProduct,forCart,forProductIds,forGlobal}()` → `ProductRepository::{findRelatedFill,findRelatedForCart,findVisibleByIdsOrdered}()` | Comprueba `p.is_active=1` e inventario según método, **no estado de categorías**. GET de ficha visible con flag ON incluyó enlace a producto de categoría directa inactiva; ese destino retornaría **404**. Directorio `/categorias` puede enlazar productos ocultos | Aplicar elegibilidad de productos a recomendaciones, fallback global y sugerencias; si producto continúa público por decisión comercial, links coherentes |
| Home categorías destacadas | `app/Services/Public/HomePageViewService.php::build()` → `HomeSectionsViewService::categories()` → `CategoryRepository::listHomeFeatured()` | `WHERE is_active=1` del nodo; título, imagen y URL de hija pueden figurar con padre apagado. GET home sintético incluyó enlaces activos de rama escondida | Filtrar elegibilidad de toda la cadena en una única consulta compartida |
| Home productos configurados / YouTube | `HomePageViewService::buildProductRowsById()` → `app/Repositories/Catalog/ProductReadRepository.php::findManyForCart()` y `ProductCardViewModelService` | Filtra `p.is_active` y slug, **no visibilidad de categoría**; depende de IDs configurados y no se probó con secciones pobladas. Riesgo de mostrar producto oculto por la opción ON | Aplicar el mismo predicado de producto a home/manual/YouTube **si** opción comercial indica ocultar |
| Menú y buscador móvil | `app/Views/partials/header/_mobile_search_sheet.php`: `SELECT slug,name FROM categories WHERE is_active=1 LIMIT 8`; `_mobile_menu_drawer.php`, `footer.php` | Chips pueden enlazar hija con antecesor inactivo; enlaces globales estáticos a `/categorias`. El buscador móvil **no respeta** `catalog_show_empty_categories`; GET probó que el link sigue presente al quitar tarjeta de directorio | Chips desde mismas categorías elegibles y decisión de vacías; preservar links estáticos globales |
| Breadcrumbs y navegación contextual | `ProductController::show()`, `CategoryController::show()`, `SeoFrontService`; facetas `facet-cats.php` | Breadcrumb del producto apunta a categoría directa, incluso cuando está inactiva y la ficha producto se mantiene visible por flag OFF; esa landing puede dar **404**. Landing de categoría solo genera Inicio→Categorías→nodo y **omite ancestros reales** | Árbol de breadcrumbs válido por cada profundidad; cuando se permita producto en rama oculta, enlazar a `/productos` o ruta comercial aprobada, no a landing invisible |
| `GET /sitemap_categories.xml` | `app/Controllers/System/MetaController.php::sitemapCategories()` | `categories WHERE is_active=1`, sin ancestros, sin settings SEO `noindex` consultados | Solo categorías elegibles e indexables por contrato comercial; evitar URL 404/noindex |
| `GET /sitemap_products.xml` | `MetaController::sitemapProducts()` | `products WHERE is_active=1`, sin category join, flag ni jerarquía. GET ON conservó producto cuya ficha devuelve 404 | Índice de URLs debe coincidir con ficha accesible/indexable según política y settings SEO |
| `GET /sitemap.xml`, `/sitemap_pages.xml`, `/robots.txt` | `MetaController::{sitemap,sitemapPages,robots}()` | Sitemap index referencia sitemaps fijos; sitemap pages incluye `/productos`, `/categorias`; robots anuncia sitemap e impide admin/login. **No** arregla URLs individuales inválidas | Mantener índice general; corregir solo feeders de URLs elegibles y probar XML/status HTTP |
| Redirects históricos (GET/HEAD) | `public_html/index.php` → `SeoRedirectRepository::findTargetByFrom()`/`registerHit()` | Aplica 301 **antes** del router: no valida destino activo ni ancestros, no aplica setting. Fixture `old-hidden-item` → **301** a hija activa/padre inactivo con 200; `old-direct-off` → **301** a landing inactiva/404 | No redirigir a destino no elegible; preservar solo 301 con equivalente real válido; discutir 404/410 o 301 a alternativa semánticamente equivalente. `registerHit` incrementa hits en GET (solo se ensayó en fixture) |
| SEO on-page / JSON-LD | `app/Services/Seo/SeoFrontService.php::{buildCategory,buildProduct,applySettings,applyAdvancedSettings}`, `seo_settings`, layout head | Las páginas visibles generan canonical propio, robots default index,follow y JSON-LD; no verifican jerarquía. En 404 probados `<head>` conserva `robots=index,follow` y canonical raíz; búsquedas `/productos?q=...` sí tienen noindex,follow | Alinear `HTTP + robots + canonical + sitemap + JSON-LD` con elegibilidad; 404 debería ser inequívoco y no confundirse con canonical de home |

**Rutas registradas realmente en `config/routes.php`**: `GET /`, `/categoria`, `/categorias`, `/productos`, `/productos/suggest.json`, `/productos/suggest-fragment`, `/productos/quick-add`, `/producto`, `/robots.txt`, `/sitemap.xml`, `/sitemap_pages.xml`, `/sitemap_categories.xml`, `/sitemap_products.xml`, `/sitemap_brands.xml`. No inventar `/categoria/{slug}` ni `/productos/{slug}`: los canónicos reales usan query `?slug=`.

### 17.3 Auditoría focal del flag `catalog_hide_inactive_category_products`

**Valor BD comercial hoy: `0` (desactivado).** Definición UI en `app/Views/partials/admin/settings/general/catalog_card.php`, guardado con `app/Controllers/Admin/SettingsController.php` y `AppSettingsRepository`, con default **`0`**.

**Lecturas DIRECTAS del setting en código público:**
1. `ProductsController::index()` → `$filters['enforce_active_category']` → `ProductRepository::buildCatalogWhere()` → agrega `c.is_active=1` solo cuando está ON; afecta listado, totales, búsqueda general y priceBounds.
2. `ProductController::show()` → si ON y hay `category_id` y categoría directa `is_active=0`, renderiza 404. Con `category_is_active=NULL` usa `??1` en guard, no es política válida para registros huérfanos.

**NO LO CONSUMEN como setting o predicado efectivo:** `CategoryController::show()`, `CategoriesController::index()`, `FacetService::categoriesWithCounts([])` para directorio, `ProductRepository::searchPreview()`, funciones related/`findVisibleByIdsOrdered()`, `HomePageViewService`, `HomeSectionsViewService`, `MetaController::sitemapProducts()/sitemapCategories()`, chips del buscador móvil, `public_html/index.php` redirects. Un cambio de flag no sincroniza esos destinos y puede crear enlaces hacia 404.

**Nota de `catalog_show_empty_categories`:** opción distinta (BD actual `1`). Solo interviene en `CategoriesController::index()`: si OFF, elimina categorías **raíz de facetas** con `count=0`. No desactiva categorías, no devuelve 404 por URLs directas y no filtra home/chips/sitemap.

### 17.4 Matriz de casos verificada con fixtures (situación actual)

Fixture aislado: 12 categorías y 9 productos originales, más **una raíz activa vacía** y, exclusivamente para el caso límite de datos corruptos, **dos productos sintéticos con referencia inválida** añadidos con FK deshabilitadas solo en la conexión de fixture (no forman parte de la estructura de datos válida). Se alternaron settings **solamente en BD de fixture**, jamás en la comercial.

| Caso | GET / consultas ACTUALES observados | Impacto |
|---|---|---|
| C activa; padre activo; P activo | Landing **200**, producto **200**, listado y sitemap incluyen | Comportamiento coherente básico |
| C activa; padre **inactivo**; P activo | Landing **200/index,follow**, producto **200** con setting OFF y ON; sitemap categoría y producto los incluyen | Anomalía por ancestro |
| C activa; padre activo; **abuelo inactivo**; P activo | Landing nieta **200/index,follow** y ficha **200** ambos settings; sitemap incluye | Anomalía por profundidad |
| C directamente **inactiva**; P activo; setting **OFF** | Landing C **404**; ficha P **200**, listado P **visible**, sugerencias y sitemap P contienen URL; breadcrumb del P apunta a la landing 404 | Contradicción de navegación |
| C directamente **inactiva**; P activo; setting **ON** | Landing C **404**; ficha P **404**, listado general **omite** P; **sugerencias, productos relacionados y sitemap de P lo siguen incluyendo** | Enlaces a 404 + sitemap inválido |
| Padre activo con hija directamente inactiva y producto activo; setting **ON** | `/categoria?slug=root-ok` **200 e incluye producto de hija inactiva**; `/categoria?slug=root-ok&sub=direct-off` también lo incluye, mientras `/productos` lo excluye | **P0**: misma tienda, dos resultados opuestos |
| Padre reactivado, hija sigue inactiva | `/categoria?slug=parent-reactivated` **200**; `/categoria?slug=child-remains-off` **404**, sin reactivación automática (política Admin ya definida); producto del padre sigue **200** | Estado de descendientes se respeta, pero navegación requiere validarlo |
| Categoría raíz activa **vacía**; `catalog_show_empty_categories=0` | La tarjeta `directory-card` desaparece de `/categorias` (comprobado por clase DOM), **pero** landing URL **200** y enlace sigue en buscador móvil | Ocultación de tarjeta ≠ desindexación |
| Categoría raíz activa vacía; setting vacías **ON** | `/categorias` presenta tarjeta con estado **Sin productos**, landing **200** | Contrato actual correcto para esa opción |
| Producto inactivo `p.is_active=0` | Ficha **404**, listado/sitemap no lo ofrecen | Regla central por estado de producto funciona |
| Producto sintético `category_id=0` o `999` inválido | **No producible con FK vigente**. OFF: ficha/listado/sugerencias/sitemap **200/visible**; ON: ficha **200** (fallback del controller), listado lo omite, sugerencias y sitemap siguen ofreciendo | Caso defensivo para reparación de importaciones antiguas |
| Legacy `/productos?categoria=root-ok` | **301** a `/categoria?slug=root-ok` sin controlar el estado del destino | El redirect legacy sigue la política de categoría destino solo después |
| Histórico `/categoria?slug=old-hidden-item` | **301** a landing hija activa/padre inactivo y luego **200**. Histórico a `direct-off`: **301→404** | SEO no valida elegibilidad antes de redirect |
| Búsqueda `/productos?q=Ancestor` | HTTP **200/noindex,follow** y resultados bajo ancestro inactivo incluso setting ON | Sugerencia/listado indexación puede ser diferente de producto |
| Categoría vacía / producto sin categoría | No son equivalentes. Categoría puede estar vacía; producto sin categoría **no es representable con FK actual** salvo datos inconsistentes | No diseñar reglas sobre un estado inválido como si fuese normal |

**Limitación honesta de matriz:** navegación y header aparecen en muchas respuestas HTML: buscar un slug en todo el body **no prueba que haya tarjeta de directorio**. Se validó `directory-card` específicamente para `catalog_show_empty_categories`; para facetas/links se combina lectura del método que genera los datos con GET. Configuración de productos manuales en home no fue poblada en fixture, por lo tanto se identifica como **riesgo por lectura de código**, no como GET probado con secciones configuradas.

### 17.5 Evidencia reproducible: GET/SQL, alcance y controles

- **BD habitual SOLO lectura**: script privado `audit_real_readonly.php` abre PDO con `SET SESSION TRANSACTION READ ONLY`, únicamente consulta `app_settings`, `categories` y agregados `products`, y termina con rollback sin mutaciones. Resultados agregados en §17.1.
- **BD descartable:** `mi_pesca_cat_w5_20261021` con esquema original importado `mysqldump --no-data`, datos completamente sintéticos, sitio copiado a `C:\xampp\mi-pesca-backups\categories_phase3_window5_20261021\isolated_site`, servidor privado PHP loopback `127.0.0.1:18135`. No usa datos de clientes ni productos comerciales.
- `http_get_matrix.py`: **54 respuestas GET** (27 rutas con setting OFF + las mismas 27 con ON), guardadas en `http_get_evidence.json` privado: home, ambas landings incoherentes, categoría directa apagada, vacía, directorio, productos, búsqueda/suggest JSON/fragment, fichas activas/inactivas, cuatro sitemaps, históricos 301 y robots.
- `summarize_get.py`, `boundary_get.py`, `empty_root_get.py`, `check_404_seo.py`: verificaciones GET específicas de links públicos, productos relacionados, landing padre/filtrado `&sub=`, legacy 301, vacío con setting ON/OFF y 404/robots/canonical. No se disparó ningún POST a rutas del comercio. Las únicas modificaciones SQL fueron inserts y cambios de settings **dentro de la copia MySQL descartable** para construir casos. **Advertencia técnica:** GET a `public_html/index.php` puede registrar `page_view` o `registerHit`; esos efectos, si existieron, quedaron confinados al clon/fixture, no a la base habitual.
- **No se abrió navegador**, porque las respuestas reales se comprobaron mediante GET HTTP y lectura de HTML/XML/JSON; no hubo perfiles/ventanas ni tests visuales con aprobación automática. Conservar scripts en respaldo privado, fuera de Git. Todos los resultados representan ejecución contra **el código clonado existente**, no una propuesta aplicada.

### 17.6 Propuesta de contrato de visibilidad — NO APROBADA, CON ALTERNATIVAS

**Separación obligatoria de dos decisiones independientes:**

**A. Categorías / jerarquía (recomendación técnica):** una categoría es públicamente **elegible** si **ella y todos sus ancestros** están activos y existe cadena válida/finita hasta una raíz. Un padre inactivo bloquea la **landing, navegación, facetas, home destacadas y sitemap** de la rama, aunque una hija tenga `is_active=1` por datos heredados. Esto es una regla de **lectura efectiva**, no equivale a cambiar flags de categorías ni registros existentes. Se propone ante categorías con ciclo/huérfanas históricas no publicarlas y registrar alerta administrativa; nunca “promover” automáticamente una hija a raíz visible. Una raíz reactivada no reactiva descendientes: respetar D1/D2.

**B. Productos activos asociados a rama oculta (DECISIÓN COMERCIAL OBLIGATORIA):**
- **B1 — catálogo estricto, si se aprueba:** cuando la opción `catalog_hide_inactive_category_products=1`, productos de categoría directa o **cualquier ancestro** inactivo se excluyen de listado, ficha, sugerencias, recomendados, home, carrito quick-add y sitemap. Cuando opción OFF, permanecen disponibles en esos destinos de manera uniforme, pero **sin mostrar breadcrumbs/enlaces a categoría pública oculta**. No modificar `products.is_active` ni stock.
- **B2 — catálogo híbrido continuo:** el estado de categorías solo controla landings/navegación; un producto activo puede seguir visible en todo el sitio independientemente de su jerarquía (setting OFF o semántica redefinida). Usar breadcrumbs válidos como Inicio→Productos→Producto. Requiere alinear mensajes del panel de configuración: un checkbox “ocultar productos” que solo afecte /productos es engañoso.
- **B3 — separación por descubrimiento/detalle** (solo si propietario la exige): ocultar tarjetas/búsqueda pero permitir ficha mediante URL directa con política SEO explícita `noindex`; **no recomendado por defecto**, porque exige controles precisos para evitar sitemap→404, facetas sin contenido y enlaces inconsistentes. Elegir solo si hay propósito comercial justificado.

**C. Categorías vacías (decidir aparte):** `catalog_show_empty_categories` debe distinguir “mostrar tarjeta” de “habilitar landing”. Se propone conservar 200 si la categoría es válida y está activa, aun vacía, con contenido editorial útil; si no hay contenido útil, decidir `noindex,follow` y exclusión de sitemap, **no transformar automáticamente una categoría vacía en 404**. Las categorías pueden estar vacías temporalmente.

**D. URLs, robots, redirects e historial:**
- **404** para categoría publicamente no elegible de forma **temporal** es candidato razonable, acompañado por respuesta HTML con `robots=noindex` y **sin canonical a home**; aplicar contrato de producto B1/B2 al detalle. **410** solo para baja verdaderamente definitiva y aprobada, no para desactivación reversible.
- **301** solo si hay reemplazo canónico **realmente equivalente y elegible**, como cambio de slug validado; no redirigir automáticamente toda categoría oculta a Home o /productos (soft 404/UX confusa). Verificar destino antes de responder 301 desde historial; si no es elegible, decidir 404/410 o una alternativa editorial autorizada. Evitar loops y 301→404.
- Sitemap categorías/productos debe enumerar exclusivamente URLs **accesibles e indexables** por decisión A/B/C. `seo_settings.robots_index=0` requiere exclusión del sitemap aun si devuelve 200; revisar redirects de slug histórico independientemente.
- **Canonical** solo al URL propio o equivalente aprobado y accesible, consistencia HTML/JSON-LD; reparar error actual de head en páginas 404 como tarea coordinada de layout/SEO.
- Mantener `robots.txt`/sitemap index general sin incorporar disallow para URLs que necesitan retornar 404/robots indexación: robots no reemplaza HTTP.

### 17.7 Arquitectura candidata, archivos a RESERVAR y performance (no editar todavía)

**Abstracción recomendada:** un único servicio/consulta pública de **visibilidad efectiva de categorías**, que derive `category_id→eligible` usando cadena de ancestros y detecte ciclos/huérfanos. Un segundo predicado de **elegibilidad de productos** combina `p.is_active`, setting aprobado y estado efectivo de su categoría. Evitar repetir `c.is_active` en cada controller, y no hacer consultas recursivas N+1 por tarjeta. Implementación candidata: **CTE recursiva compatible con versión MySQL/MariaDB real** o construir un conjunto de IDs elegibles por consulta y compartirlo por request, con memoización de caminos. No introducir columna `is_effectively_active` o caché denormalizada sin medir y coordinar actualizaciones; no se autoriza migración en esta fase.

**Orden sugerido para una futura Ventana 6, solo tras aprobación/turnos exclusivos:**
1. **Núcleo compartido:** `app/Repositories/Catalog/CategoryRepository.php`, **nuevo servicio public visibility**, `app/Services/Catalog/FacetService.php`. Congelar firma de consultas, probar árbol profundo/ciclos/huérfanos y counts en fixture.
2. **Rutas públicas:** `app/Controllers/Catalog/CategoryController.php`, `CategoriesController.php`, `ProductsController.php`, `ProductController.php`, `QuickAddController.php`; `app/Repositories/Catalog/ProductRepository.php` (paginate, bounds, searchPreview, related y findVisibleByIdsOrdered). **No modificar** estado de producto, precio o stock.
3. **Home y navegación:** `app/Services/Public/HomePageViewService.php`, `HomeSectionsViewService.php`, `app/Repositories/Catalog/ProductReadRepository.php`, `app/Services/Catalog/RecommendedProductsService.php`, parciales `app/Views/partials/header/_mobile_search_sheet.php`, `app/Views/partials/catalogo/facet-cats.php`, `app/Views/pages/catalogo/categorias.php`, breadcrumbs. Reservar solo los que realmente requieran cambio; no rediseñar UI.
4. **SEO/canonical/redirects:** `app/Controllers/System/MetaController.php`, `app/Services/Seo/SeoFrontService.php`, `public_html/index.php` (prefiltro SEO), `app/Repositories/Seo/SeoRedirectRepository.php`, head/error `app/Views/pages/errors/404.php` y layout `head.php` según verificación. **Estos son archivos globales y requerirán autorización/ventana exclusiva del principal**. No tocar `SeoSlugHistoryRepository` sin decisión separada.
5. **Settings:** `app/Controllers/Admin/SettingsController.php` y `app/Views/partials/admin/settings/general/catalog_card.php` **solo** si cambia la semántica/mensaje ON/OFF del flag, con reserva específica de Settings.

**Índices existentes identificados por schema:** `categories.slug UNIQUE`, `categories.parent_id`, `products.category_id,price`, `products.is_active`, `seo_redirects.from_path UNIQUE`, `seo_settings(entity_type,entity_id)`. `EXPLAIN` y medición necesarios para CTE/NOT EXISTS/recorridos de árbol con cientos o miles de categorías y productos; no proponer N consultas por producto/loop. Estudiar filtros de root/sub/slug, orden y paginación, cache por request, 301 y home. No asumir compatibilidad CTE si motor exacto no se verificó.

**REGRESIONES A PROTEGER:** altas/ediciones y toggle admin, quick con slug 409, importaciones que crean/resuelven categoría, variantes/product cards, catálogo normal con flag OFF, stock/filtros precios/marcas/tags, búsqueda JSON y fragment, favoritos, carrito/checkout, home manual, enlaces de campañas, breadcrumbs, SEO JSON-LD, 404 y canonical, sitemap XML y config de vacías. Cada cambio debe probarse en MySQL sintético con ON/OFF y **sin POST comercial real**.

### 17.8 Matriz mínima de aceptación futura y DECISIONES PENDIENTES

| Caso de prueba en futura ventana de implementación | Aceptación candidata (sujeta a A/B/C/D aprobadas) |
|---|---|
| Raíz activa, hija activa, nieta activa | Las tres landings/listados/enlaces permitidos, productos según flag |
| Padre inactivo con hija activa heredada | Ningún destino de categorías (landing, facets, home, chips, sitemap, breadcrumbs) expone hija como visible |
| Abuelo inactivo con padre/hija activos | Misma regla para profundidad ilimitada |
| Padre reactivado, hijos permanecen inactivos | Solo padre reingresa a navegación/sitemap; hijas no |
| P activo directo en categoría apagada, flag OFF | Producto disponible en **todos los destinos acordados**, sin enlaces a landing 404 |
| Mismo P con flag ON | Ficha/listado/sugerencias/recomendaciones/home/carrito/sitemap coherentemente excluidos bajo B1 |
| P activo en categoría directa activa/ancestro inactivo, ambos flags | Resultado según política B1/B2, idéntico en todas las rutas |
| P sin categoría o FK roto (fixture corrupto) | Contrato defensivo inequívoco; testear ON/OFF aun si schema lo prohíbe |
| Categoría vacía / contenido editorial indexable | `catalog_show_empty_categories` controla tarjeta; indexación según regla C |
| Consulta `/productos?categoria` legacy | 301 solo a destino permitido o tratamiento explícito |
| Slug histórico apuntando a nodo oculto | No 301 a 404/soft-404; canonical, robots y HTTP acordados |
| Página 404 categoría/producto | HTTP 404 real, `noindex` sin canonical engañoso ni JSON-LD de entidad ausente |
| Sitemap productos/categorías | Conjunto URLs **idéntico** al conjunto elegible/indexable bajo ambos settings; XML válido |
| 1/2/3+ niveles, ciclos o padres inválidos en fixture | No loops, no promociones artificiales, límites de recursion y tiempos medidos |
| Comparar total, price bounds, facets, cards y API suggest | Mismo conjunto elegible de productos, sin “enlaces a 404” |
| Pruebas de carga/explain | Sin N+1; queries con índices, sin regresión de p95 de catálogo |
| Integridad comercial | Ninguna operación de auditoría/consulta cambia `products.is_active`, `categories.is_active` ni relaciones |

**Decisiones que debe responder el chat principal junto al propietario antes de abrir Ventana 6:**
1. **V5-D1 — Categorías:** aprobar **visibilidad efectiva de toda la cadena**, incluyendo respuesta **404** por URL directa de hija activa bajo padre inactivo y exclusión de menús/facetas/home/sitemap. **Recomendación técnica: SÍ.**
2. **V5-D2 — Producto (CRÍTICA COMERCIAL):** elegir **B1** (setting ON oculta efectivamente producto en *todos* los consumidores, OFF lo mantiene accesible) o **B2** (productos activos independientes de categoría). Precisar efectos en búsqueda, recomendaciones, home, QuickAdd, carrito y sitemap. **No hay aprobación implícita para ocultar productos.**
3. **V5-D3 — Vacías:** mantener landing 200 de categoría activa vacía, y decidir `noindex`/sitemap dependiendo de contenido; `catalog_show_empty_categories` solo controla tarjeta. **Recomendación: diferenciar disponibilidad de promoción/indexación.**
4. **V5-D4 — HTTP/SEO:** aprobar 404 temporal para categoría no elegible, 410 solo baja definitiva, 301 únicamente reemplazo semántico activo, política ante 301 histórico a oculto, canonical/robots/error page y reglas de sitemap/indexación. **Recomendación: evitar 301 automáticos a home y 301→404.**
5. **V5-D5 — Jerarquía/UX:** profundidad arbitraria, breadcrumb real, subcategorías en filtros, conteos compatibles con setting ON/OFF y fallback para productos de categoría oculta si se mantienen visibles.
6. **V5-D6 — Reservas exclusivas:** autorizar solo archivos precisos en §17.7 y ventana secuenciada de núcleo catálogo, consumers, SEO/head. Confirmar responsable de Productos, Home, SEO, Settings y `public_html/index.php`. Nunca tomar permiso de Ventanas 1–4 como reserva nueva.
7. **V5-D7 — Aceptación:** autorizar la matriz anterior en clon MySQL, GET real + pruebas de código que incluyan ON/OFF, escenarios de SEO, recomendaciones, sitemap y carga, más gate/focal y validación visual humana de navegación. No usar BD habitual para reconfigurar settings.

**Deudas separadas (NO CORREGIR EN V5):** `app/Repositories/Seo/SeoSlugHistoryRepository.php` aún permite upsert que reasigna dueño para otros consumidores; archivo huérfano posible tras crash abrupto en la transacción de portadas de V4. No mezclarlas con visibilidad pública ni editarlas sin mandato específico.

**CIERRE DE AUDITORÍA VENTANA 5:** diagnóstico y contrato candidatos documentados, ninguna política comercial aplicada ni estado cambiado. **Detenerse y solicitar aprobación del chat principal para próxima ventana de correcciones.**


### 17.9 Teardown verificado (cierre operativo)

**CONFIRMADO:** servidor PHP privado detenido; puerto **18135 libre**; base descartable `mi_pesca_cat_w5_20261021` eliminada tras comprobar que no coincide con `DB_NAME` comercial; copia temporal `isolated_site` y configuración privada `private_config.json` eliminadas. Solo persisten scripts y evidencia de GET en `C:\xampp\mi-pesca-backups\categories_phase3_window5_20261021`, fuera de la aplicación y de Git. **Ningún navegador ni perfil temporal fue abierto durante V5.** No se modificó el código ni la base comercial. Ventana 6 pendiente de autorización.

---

## 18. FASE 3 — VENTANA 6A: núcleo de visibilidad pública jerárquica (2026-10-21)

> **ESTADO: IMPLEMENTACIÓN NÚCLEO FINALIZADA; SIN EXTENDER A PRODUCTOS/SEO.** Adendas V6A de `docs/DECISIONES_CATEGORIAS_FASE3.md` y `docs/COORDINACION.md` revisadas antes de editar. Reserva exclusiva respetada. La política aprobada V5-D1 se aplica únicamente a lectura de categorías: categoría elegible si ella y **TODOS sus ancestros** están activos, con cadena existente, acíclica y terminada en raíz válida. **No** se cambiaron flags almacenados de categorías/productos ni vínculos comerciales. La política comercial B1/B2 **continúa sin autorización**. La Ventana 6B requiere nuevo permiso del chat principal.

### 18.1 Archivos autorizados realmente modificados

| Archivo | Alcance exacto |
|---|---|
| **Nuevo** `app/Services/Catalog/CategoryPublicVisibilityService.php` | Núcleo de lectura: una consulta `SELECT id,parent_id,is_active FROM categories` por instancia, recorrido **iterativo**, memoización de estados, detección de ciclos, padres huérfanos y estados inactivos; método `visibleIdSet()` y `isVisibleId()`. La segunda lectura reutiliza mapa calculado de IDs elegibles. O(N) por carga inicial y O(1) por comprobación en caché |
| `app/Repositories/Catalog/CategoryRepository.php` | Solo APIs de lectura: agrega `publicVisibility()/visibleCategoryIds()`; `findActiveBySlug()` filtra ancestros; `listHomeFeatured()` filtra **antes del LIMIT** en PHP, conservando orden, portada y compatibilidad/fallback. Los métodos `create()`, `resolveId()`, `resolveIdPlanned()`, `idBySlug()` y `idByName()` no se modificaron |
| `app/Services/Catalog/FacetService.php` | Solo categoría/faceta: construye árbol desde nodos elegibles; jamás convierte hijo de padre ausente/inactivo en raíz; `labelMap()` descarta etiquetas de categorías ocultas, conservando su parte de marcas; la suma de productos por rama cambió a recorrido **iterativo** (evita stack con 2.200 niveles). No cambió SQL ni política comercial de productos, facetas tags, marcas ni filtros/precios |
| `app/Controllers/Catalog/CategoryController.php` | En la rama `if (!$category)` deja `http_response_code(404)` explícito; el repositorio ya devuelve `null` para un nodo activo con ancestro inactivo, ciclo o huérfano. El resto del controller, render SEO, productos y filtros permanecen iguales |
| `app/Controllers/Catalog/CategoriesController.php` | **Sin cambios de archivo**: su `FacetService::categoriesWithCounts([])` queda automáticamente alineado y conserva `catalog_show_empty_categories` y composición de página |
| **Nuevos** `scripts/build/categories_public_visibility_contract.php`, `scripts/dev/categories_public_visibility_smoke.php` | Contrato focal de lectura/alcance y smoke de elegibilidad, featured, facetas y contratos de resolución de Importaciones; chequeo estricto `CATS_EXPECT_DB` cuando se apunta a la fixture |
| `docs/modulos/categorias.md` | Esta sección de cierre, evidencias, límites y parada |

**Verificación de perímetro ejecutada (script privado `verify_scope.py`):** comparó contra respaldo SHA-256 previo las porciones de escritura de `CategoryRepository`, la implementación de marcas/tags/product filters en `FacetService`, el resto de `CategoryController` y la totalidad de `CategoriesController`. No hubo cambios a `ProductRepository`, `ProductController`, `ProductsController`, recomendaciones/sugerencias, sitemap, redirecciones, SEO, Home services, Settings, Importaciones, UI/CSS/HTML/JS, rutas, migraciones ni Git.

### 18.2 Algoritmo y contrato de visibilidad

- Se usa exclusivamente `CategoryPublicVisibilityService`; ningún controller inventa reglas duplicadas. Una sola carga `id,parent_id,is_active` por instancia, con `visibleIdSet` en memoria. Cuando se encuentra un ancestro conocido, utiliza el resultado memoizado. Las rutas cuyo padre **no existe**, parent inválido, ciclo o nodo inactivo se marcan invisibles sin seguir indefinidamente.
- No se promociona una hija activa cuyo padre no esté cargado a una raíz. El filtro ocurre **antes** de ensamblar el árbol de facetas y sus conteos; la acumulación desde hijos a raíces es iterativa, proporcional al número de categorías elegibles. El formato de facetas en interfaz se mantiene con **raíces + hijas inmediatas** (la representación de nietas en UI es una mejora posterior, no autorizada), aunque las sumas incluyen toda la profundidad.
- `CategoryRepository::findActiveBySlug()` conserva salida `?array` / `null`; ahora `null` incluye invisibilidad efectiva, por lo que `GET /categoria?slug=` da 404 real sin tocar SEO global.
- `CategoryRepository::listHomeFeatured()` mantiene orden `sort,name`, `limit` de 1–24 y datos devueltos. El LIMIT se aplica después del filtro jerárquico para **rellenar correctamente** los lugares cuando los primeros nodos ordenados son ocultos.
- Las APIs de importación `idBySlug(),idByName(),create(),resolveId(),resolveIdPlanned()` no recibieron predicado de visibilidad: deben seguir permitiendo administrar/resolver categorías inactivas según contrato existente. El smoke focal probó `idBySlug('child-parent-off')=3` y la planificación dry-run sin inserción.
- **Punto de rendimiento:** el algoritmo actual lee N nodos una vez por instancia; si el proyecto acumula cientos de miles de categorías, perfilar volumen y caché por request antes de introducir CTE/índices nuevos. Los tests locales con 2.219 nodos respaldan coste proporcional, no constituyen SLA de producción.
- **Sin cambios comerciales**: `catalog_hide_inactive_category_products` sigue OFF/ON con semántica previa, y ni `products.is_active` ni el catálogo de productos se filtran por este nuevo servicio. Una landing padre puede seguir mostrando productos de categorías descendientes inactivas hasta que se autorice B1/B2.

### 18.3 Validaciones ejecutadas de verdad — MySQL sintético y GET real

**Aislamiento**: clon privado creado desde carpeta real de proyecto, con esquema SQL `mysqldump --no-data` y **sin datos comerciales**; MySQL exclusiva `mi_pesca_cat_w6a_20261021`, servidor PHP incorporado privado en loopback `127.0.0.1:18141`. Fixture inicial de 12 categorías y 9 productos sintéticos, expandida a **19 categorías**: raíz, hija, nieta, tres niveles ocultos por ancestro, padre reactivado e hija inactiva, ciclo A↔B con FKs existentes, huérfano y descendiente huérfano (FK desactivada SOLO en copia), raíz vacía y nieta legítima. Luego se generó una cadena válida de **2.200 niveles**, total **2.219 categorías**. `SELECT DATABASE()` comprobado explícitamente en scripts; ninguna escritura a DB habitual.

| Prueba | Resultado observado |
|---|---|
| Servicio `visibleIdSet()` + smoke con 19 nodos | **PASS**: 8 IDs elegibles, 11 no elegibles; raíces y nietas válidas, inactivos, ciclo y huérfano rechazados |
| GET `/categoria?slug=` sobre 7 slugs válidos | **7 × HTTP 200**, incluye hija, nieta y categoría vacía |
| GET de 11 slugs no elegibles | **11 × HTTP 404**: directos, padres y abuelos apagados, ciclo A/B, huérfanos y descendientes |
| GET `/categorias` | **HTTP 200**, 5 tarjetas de raíces elegibles en fixture pequeña; ninguna promoción de hija ocultada; con cadena masiva aparecen también raíces válidas adicionales |
| GET facetas `/productos`, `/categoria?slug=root-ok`, `/categoria?slug=good-root` | **HTTP 200** y enlaces de categoría elegibles únicamente en `catalog-acc__link`. Los chips del buscador móvil **siguen separados y fuera de alcance** |
| `CategoryRepository::listHomeFeatured()` | **PASS** sobre fixture, no devuelve IDs ocultos, respeta `limit`, completa 4 lugares. Home `GET /` respondió **200**. El service `HomePageViewService` queda sin modificar |
| Reactivación individual simulada **solo en MySQL descartable** | Padre ID11 `0→1`: GET **404→200**; hija ID12 sigue inactiva y GET **404→404**. Se comprobó que la visibilidad de lectura no reactiva descendientes |
| Productos/regresión observacional | Fichas de cuatro productos activos bajo ramas ocultas conservaron **HTTP 200** con flag habitual OFF: no cambió política comercial. `sitemap_categories.xml` sigue enumerando algunos nodos de ancestro inactivo, identificado como **pendiente SEO de 6B** |
| Importaciones / repositorio | **PASS**: `idBySlug` e `idByName` siguen encontrando nodos invisibles; `resolveIdPlanned` conserva `would_create` y `dry`; **no se ejecutó importación real** |
| Profundidad 2.200 + rendimiento | **PASS** en PHP y GET al último nivel: carga 2.219 nodos (2.208 elegibles), `load_ms=3.7` primera ejecución; caché de 2.200 accesos `52.4 ms` antes de optimización y **`0.25 ms`** después; `facet_ms≈13–14`, `featured_ms≈12`, memoria pico **8 MB**; GET último nivel **200 en ~61 ms** medido localmente |
| Validación de esquema habitual **solo SELECT** | `scripts/dev/categories_public_visibility_smoke.php` **PASS**: 18 categorías, 9 elegibles, 5 raíces, 9 featured, sin anomalía de jerarquía al momento de lectura; no mutó DB |
| Contrato focal de público / contrato existente Admin | **18/18 OK** para Categorías públicas y **28/28 OK** para Categorías Admin |
| Lint PHP | **PASS** servicio, repositorio, FacetService, controlador, contrato y smoke focal |

**Evidencia privada fuera de Git**: `C:\xampp\mi-pesca-backups\categories_phase3_window6a_20261021`, especialmente `http_get_v6a.py`, `benchmark.php`, `reactivation_http.py`, `seed_base.php`, `extend_fixture.php`, `verify_scope.py` y los backups de archivos reservados. No se ejecutó navegador headless/visible: se validó con HTTP GET y SQL, por lo que no se crearon perfiles o ventanas de prueba.

### 18.4 Límites y riesgos pendientes que deben entenderse antes de publicación

1. **D2 PRODUCTOS NO APROBADO:** B1/B2 todavía requiere ratificación del propietario. Las fichas, resultados, búsqueda, sugerencias, relacionados, QuickAdd, carrito y home de productos siguen su contrato previo; **no imponer un filtro nuevo sin decisión comercial**.
2. **SEO/sitemap/redirects fuera de reserva:** `MetaController::sitemapCategories()` todavía filtra solo `categories.is_active=1`; puede incluir una categoría cuya nueva landing responda 404. `MetaController::sitemapProducts()`, redirecciones de `public_html/index.php`, canonical, `robots` y head de errores mantienen las inconsistencias descritas en §17. Se necesita Ventana 6B/SEO exclusiva.
3. **Navegación compartida pendiente:** el buscador móvil `app/Views/partials/header/_mobile_search_sheet.php` sigue consultando nodos activos solo por flag y puede enlazar una landing ahora 404. No modificar HTML/JS sin reserva específica.
4. **UI profunda pendiente:** `FacetService` calcula sumas profundas sin recursión, pero entrega formato legacy de raíces e hijas. UI para nietas exige ventana visual/control de navegación independiente.
5. **Caché por instancia:** si dentro de **una misma petición** se modifica el árbol después de que el repositorio haya calculado visibilidad, esa instancia no se refresca automáticamente; la vía pública es GET y no modifica categorías, por lo que esto no afecta el contrato de lectura normal. La UI pública en dos peticiones ve estados recientes según aislamiento de DB.
6. **Riesgos V4 ajenos**: `SeoSlugHistoryRepository` global puede reasignar historia a través de otros consumidores y queda archivo huérfano ante muerte abrupta entre rename/commit; no abordar en esta ventana.

### 18.5 Gate, cierre de procesos y siguiente autorización

**Gate general: PASS VERIFICADO en la segunda ejecución sobre la versión optimizada — 834 archivos PHP, 120 JavaScript, contratos de Admin/SEO/Marketing/checkout, regresiones, salida `[gate] OK` y exit code 0. Se ejecutaron además `php -l` en seis archivos focales, contrato público **18/18 OK**, contrato Admin **28/28 OK** y smoke habitual solo lectura.

**Teardown FINAL CONFIRMADO:** servidor privado PHP de sesión 19352 detenido, puerto `18141` verificado libre, base descartable `mi_pesca_cat_w6a_20261021` eliminada con guarda contra la DB comercial y comprobación `SELECT DATABASE()`. Copia `isolated_site` y archivo con token `private_config.json` eliminados. Solo se conservaron respaldos, scripts y evidencia privados fuera de Git. No se abrió navegador ni perfil headless/visible; no quedaron recursos propios.

**Solicitud de próxima ventana, NO AUTORIZADA TODAVÍA:** que chat principal defina B1/B2 junto al propietario, reserve los consumidores de Productos/sugerencias/home y, en fase coordinada, SEO (sitemap/redirect/head) y navegación móvil. El chat Categorías debe detener la implementación tras V6A.

**ESTADO V6A: NÚCLEO IMPLEMENTADO, GET/SERVICIO/FACETAS/PROFUNDIDAD/REACTIVACIÓN VALIDADOS, GATE 834 PHP / 120 JS PASS, 18/18 CONTRATO, TEARDOWN OK. IMPLEMENTACIÓN DETENIDA. NO INICIAR 6B.**

---

## 19. VENTANA 6B-1 — Sitemap de categorías y chips móviles (2026-10-21)

> **Estado: implementación técnica completada, pendiente únicamente del cierre de recursos temporales. Ventanas 6B-2 y 6C NO autorizadas.** Las adendas de docs/DECISIONES_CATEGORIAS_FASE3.md y docs/COORDINACION.md fueron leídas antes de editar. El trabajo se limitó a los métodos/bloques compartidos expresamente reservados.

### 19.1 Archivos realmente modificados

| Archivo | Alcance exclusivo |
|---|---|
| app/Repositories/Catalog/CategoryRepository.php | Nuevo método público de solo lectura listPublicVisibleOrdered(?int $limit = null). Reutiliza visibleCategoryIds()/CategoryPublicVisibilityService, carga categorías activas ordenadas por sort ASC, name ASC, descarta jerarquías ocultas y filas sin slug/nombre. Aplica el límite DESPUÉS del filtro, conservando id, slug, name y created_at. |
| app/Controllers/System/MetaController.php | Exclusivamente sitemapCategories(): sustituye la consulta directa is_active=1 por el método compartido, sin cambiar XML, escaping, rawurlencode, prioridad, frecuencia, lastmod ni orden. Sitemap de productos, brands, pages, index y robots intactos. |
| app/Views/partials/header/_mobile_search_sheet.php | Exclusivamente consulta PHP inicial de categorías: utiliza listPublicVisibleOrdered(8). Se conserva el manejo try/catch, el markup completo del modal de búsqueda, HTML/JS/CSS y sugerencias de productos. |
| scripts/build/categories_public_visibility_contract.php | Seis comprobaciones añadidas, total 24; contrato Admin separado permanece 28. |
| docs/modulos/categorias.md | Esta sección 19. |

**Alcance comparado con respaldos antes de modificar:** script privado scope_check.py confirmó equivalencia textual de los métodos MetaController anteriores a sitemapCategories y posteriores a sitemapBrands; todo el HTML móvil desde su cierre PHP; y todos los métodos históricos del repositorio desde idBySlug (incluidos create/resolveId/resolveIdPlanned de Importaciones). No se cambió CategoryPublicVisibilityService ni ninguna API de escritura. No se editó ningún archivo adicional de los módulos Productos, Marketing, Importaciones, SEO, Layout, Settings ni Git.

### 19.2 Semántica y eficiencia

- Sitemap y chips consumen exactamente la misma lista de categorías jerárquicamente visibles. Categorías directas activas con padre/abuelo inactivo, ciclos, huérfanos y descendientes de ramas inválidas no son elegibles.
- El orden público original sort ASC, name ASC queda preservado, y los huecos causados por categorías ocultas se rellenan con las siguientes elegibles. El sitemap no tiene límite; el buscador presenta hasta ocho. Si existen menos de ocho, muestra solo las existentes.
- No hay consultas N+1: una lectura de jerarquía memoizada y una lectura ordenada por instancia de repositorio. La carga y filtrado son proporcionales al número de categorías, sin joins recursivos por fila.
- Mantiene URLs /categoria?slug=..., lastmod por created_at, XML namespace y escaping. Categorías vacías efectivamente activas siguen accesibles; no se alteró catalog_show_empty_categories.

### 19.3 Evidencia REAL: MySQL aislado, GET y XML

**Aislamiento:** clon privado fuera del proyecto, base descartable mi_pesca_cat_w6b1_20261021, estructura exportada sin filas comerciales y fixtures creados únicamente allí. PHP integrado privado en 127.0.0.1:18144. Verificación SELECT DATABASE() en scripts. Prueba inicial: 25 categorías (12 elegibles), incluyendo raíces, hijas, nietas, abuelo/padre inactivos, ciclos, huérfanos, categoría vacía y varias ocultas con sort negativo para preceder a las visibles. Nueve productos sintéticos únicamente para GET regresivos.

| Prueba | Resultado real |
|---|---|
| GET /sitemap_categories.xml y parse XML ElementTree | PASS 200; Content-Type application/xml; 12 URL correctas, sin duplicados; mismos slugs y orden que un cálculo independiente desde los nodos SQL, no desde el servicio PHP. |
| Chips reales del HTML GET / | PASS, exactamente 8 enlaces ph-search-chip ph-search-chip-link en orden idéntico a los primeros ocho elegibles; las categorías ocultas de mayor prioridad no consumen cupos. |
| Enlaces GET de las 12 URL sitemap y los 8 chips | PASS: todos devuelven HTTP 200 en /categoria. |
| Exclusión directa de nodos ocultos | PASS: 12 nodos de control excluidos de XML y chips y sus landings devuelven HTTP 404. |
| Menos de 8 categorías | PASS: seis raíces apagadas solo en BD sintética; quedaron exactamente 3 categorías elegibles, tres URL sitemap y tres chips, todos HTTP 200. |
| 2.200 niveles profundos adicionales | PASS: 2.225 categorías, 2.212 elegibles; XML válido, misma secuencia de slugs que evaluación independiente, sin duplicados. Tres GET sitemap: 53,60 / 35,24 / 50,79 ms locales para ~395 KB. |
| Home 2.225 categorías | PASS: conserva 8 chips ordenados; GET ~56,12 ms en una medición local. |
| Regresiones públicos fuera de alcance | PASS GET /categorias, /productos, fichas de ancestor-branch-item y direct-off-item, /sitemap_products.xml y /sitemap.xml (200). No se alteró la visibilidad comercial de productos. |
| Lint | PASS en MetaController, parcial móvil, CategoryRepository y contrato actualizado. |
| Contratos y smoke habitual | PASS: 24/24 categorías públicas; 28/28 Admin; smoke comercial solo lectura 18 categorías, 9 visibles y 5 raíces. |
| Build gate completo | PASS: 836 archivos PHP, 120 JS, regresiones globales y salida [gate] OK, exit 0. Antes del inicio se verificó que no había otra instancia de gate activa. |

**Interpretación de rendimiento:** no se añadieron consultas por cada categoría. Las mediciones de respuesta son locales contra la base sintética; no representan SLA ni p95 de producción. No se ejecutó EXPLAIN de planes (sin cambios de esquema/índices); pendiente perfilar volúmenes comerciales grandes si crecen.

**Evidencia privada:** C:\xampp\mi-pesca-backups\categories_phase3_window6b1_20261021 contiene backups previos, prepare_isolated.py, seed_base.php, extend_fixture.php, extra_candidates.php, dump_nodes.php, http_v6b1.py, large_http.py, few_categories_fixture.php, test_few_categories.py y scope_check.py, fuera del repositorio. No se abrieron navegadores: solo GET HTTP/XML/HTML; no existe prueba de aprobación visual del buscador.

### 19.4 Límites comerciales y de SEO

1. B1/B2 sigue sin ratificación del propietario. No se filtró ni ocultó un producto adicional; fichas, búsqueda de productos, recomendaciones, QuickAdd, carrito, stock, flags y precios permanecen iguales.
2. El sitemap de categorías ahora coincide con la **visibilidad jerárquica**, pero no aplica aún seo_settings.robots_index/noindex ni reglas de canonical editorial. Eso pertenece a la ventana global SEO 6C con nueva autorización. No declarar optimización SEO integral.
3. SitemapProducts, 301/redirects, canonical/robots de las páginas 404, otros enlaces manuales y sugerencias de productos no fueron modificados y requieren ventanas separadas.
4. Las categorías vacías siguen pudiendo aparecer en chips y sitemap si son jerárquicamente válidas; indexación de contenido editorial, y promoción de vacías, tienen decisión independiente.
5. Los controles HTML/JS visuales son idénticos a los originales; se validó el HTML emitido por GET, no interacción headless ni revisión visual humana.

### 19.5 Teardown y parada

**Teardown FINAL VERIFICADO:** servidor privado PHP de sesión 24520 detenido; puerto 18144 libre; base exclusivamente sintética mi_pesca_cat_w6b1_20261021 eliminada tras verificar que no coincide con la base comercial. Copia isolated_site y configuración privada private_config.json con token/credenciales eliminadas. Permanecen únicamente respaldos, scripts de prueba y resultados externos a Git. No se abrió navegador, perfil ni ventana. No se tocaron procesos de otros chats.

**ESTADO V6B-1: IMPLEMENTACIÓN Y VALIDACIÓN COMPLETADAS — XML, chips 8 y 3, GET de destinos, 2.200 niveles, 24/24 y 28/28 contratos, gate 836 PHP / 120 JS OK, teardown OK. TRABAJO DETENIDO. 6B-2 Productos y 6C SEO NO AUTORIZADAS.**

---

## 20. VENTANA 6B-2 — Política comercial B1 en consumidores públicos de Productos (2026-10-21)

> **Estado: implementación de lectura pública terminada y verificada con datos aislados.** B1 fue aprobada expresamente por el propietario; se leyeron las adendas de docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md, docs/DECISIONES_CATEGORIAS_FASE3.md, docs/COORDINACION.md y este documento ANTES de modificar. Esta ventana NO habilita producción: V6B-3 (POST Cart/Checkout) y V6C (sitemap de productos, redirecciones, canonical, robots y 404/head) siguen sin autorización.

### 20.1 Contrato aplicado

- **OFF/0**: todo producto con products.is_active=1 mantiene ficha, catálogo, búsqueda, sugerencias, relacionados, Home y QuickAdd aunque la categoría directa, padre o abuelo esté inactivo; la ficha y las tarjetas no deben enlazar la landing de categoría que devuelve 404. Sin alterar products.is_active, category_id, stock, precios, variantes ni pedidos.
- **ON/1**: se exige products.is_active=1 y que su categoría y TODOS sus ancestros sean jerárquicamente visibles; ciclos, referencias huérfanas o categorías inactivas no son elegibles. Producto excluido por predicado SQL ANTES de LIMIT/OFFSET, y detalle/QuickAdd responden 404. La base comercial mantiene su valor vigente de setting sin modificación.
- Único núcleo nuevo: **app/Services/Catalog/ProductPublicVisibilityService.php** con cache por instancia del flag consultado desde AppSettingsRepository y del conjunto visible de CategoryPublicVisibilityService. Proporciona predicate(alias), permitsProduct(), isCategoryPubliclyVisible(), eligibleIdsAmong() por lote, sanitizeCategoryLink() y descendantCategoryIds() de profundidad arbitraria. Cuando ON y no hay categorías elegibles, el SQL falla cerrado mediante 1=0. Sin consultas por cada producto.

### 20.2 Archivos autorizados realmente modificados

| Archivo | Implementación precisa |
|---|---|
| Nuevo app/Services/Catalog/ProductPublicVisibilityService.php | Política B1 única; consultas por lote, predicado SQL, caché de jerarquía, eliminación de enlaces rotos y subárboles iterativos de landings. |
| app/Repositories/Catalog/ProductRepository.php | Solo lectores públicos: publicVisibilitySql() ahora centraliza B1; buildCatalogWhere() aplica la política en count/paginate/priceBounds; searchPreview, findRelatedFill, findRelatedForCart y findVisibleByIdsOrdered reutilizan el predicado antes de ordenar/limitar. Selector de categorías en cards sanitizado; category_scope_ids permite filtrar un subárbol completo en SQL. Los métodos de escritura, SKU, precio y selectores administrativos/importador permanecieron sin edición. |
| app/Controllers/Catalog/ProductsController.php | El filtro legacy de visibilidad se mantiene como dato contextual; la autoridad B1 pasa al servicio/repositorio. suggest/suggestFragment heredan política a través de searchPreview; redirect legacy 301 intacto. |
| app/Controllers/Catalog/ProductController.php | Guard B1 para ficha, HTTP 404 ante ON no elegible; breadcrumb incluye categoría solo si su landing es jerárquicamente válida. OFF conserva el producto y utiliza Inicio > Productos > Producto como fallback. Código de precio, variantes, stock y SEO sin cambios. |
| app/Controllers/Catalog/QuickAddController.php | Guard de GET por lote antes de devolver selector de variantes. Conserva 400 para ID inválido, 404 para oculto, 409 para producto simple, y pricing sin alteraciones. |
| app/Controllers/Catalog/CategoryController.php | Solo filtros de productos de una landing ya visible: agrega category_scope_ids del subárbol completo antes de paginate/priceBounds/facetas. Respeta sub seleccionado y B1, sin modificar guard de categoría ni SEO. |
| app/Services/Catalog/FacetService.php | Conteos de categorías, marcas y tags usan la misma política B1; precio de faceta de variantes usa el precio efectivo mínimo de variante activa, igual al repositorio. Bajo OFF atribuye productos de categorías ocultas al ancestro público visible más próximo, sin publicar esas categorías. Caché del servicio por instancia; conserva el parámetro legacy como diagnóstico, sin reintroducir c.is_active. |
| app/Services/Public/HomePageViewService.php | IDs de secciones manuales y YouTube filtrados con un único SELECT público en batch ANTES de precios y tarjetas. Sigue leyendo ProductReadRepository sin modificarlo, preservando uso en Cart. |
| app/Services/Catalog/RecommendedProductsService.php | **Sin modificación necesaria:** forProduct, forCart, forProductIds y forGlobal reutilizan los selectores filtrados del ProductRepository, incluyendo fallback y analytics. |
| Nuevo scripts/build/categories_product_visibility_contract.php | Contrato focal B1 de 28 comprobaciones. Scripts de QA privados/aislados conservados fuera del repositorio. |
| docs/modulos/categorias.md | Sección 20, resultados y límites. |

**Perímetro verificado contra backup privado:** script scope_b1.py comprobó segmentos de ProductRepository correspondientes a lectura/escritura de Admin e Importaciones sin cambios, bloque de redirección legacy intacto, cálculo de precio/variantes de ProductController idéntico, pricing de QuickAdd inalterado y armado de secciones del Home sin cambios. No se editaron ProductReadRepository, CategoryRepository, CategoryPublicVisibilityService, sitemap ni MetaController, SEO, Cart/Checkout/Payment, Settings, Importaciones, HTML/CSS/JS, rutas globales, migraciones, base habitual o Git.

### 20.3 Pruebas REALES MySQL/HTTP: OFF vs ON

**Aislamiento:** clon privado fuera del proyecto en C:\xampp\mi-pesca-backups\categories_phase3_window6b2_20261021, esquema MySQL exportado con mysqldump --no-data, base descartable mi_pesca_cat_w6b2_20261021, servidor privado localhost 127.0.0.1:18147. Fixtures sintéticos: 19 categorías iniciales (raíz/hija/nieta válida, ancestro inactivo, categorías directas inactivas, ciclo, huérfano, vacía y padre reactivado), 16 productos (15 activos, uno inactivo), dos productos con variantes y stock, Home manual/YouTube configurado. Más adelante se añadieron 2.200 categorías en cadena y un producto activo al último nivel. Las únicas escrituras de categorías/settings/variantes se hicieron exclusivamente en MySQL descartable; scripts verificaron SELECT DATABASE().

| Caso comprobado | OFF/0 (observado) | ON/1 (observado) |
|---|---|---|
| 15 productos activos de fixture base, 9 en ramas ocultas | **15 fichas 200; 15 en total de catálogo**, incluidos ciclos/huérfanos sintéticos | **6 fichas 200; 9 fichas 404**; solo categorías de cadena válida |
| Producto p.is_active=0 | **404** | **404** |
| Paginación per_page=4 | 15 únicos, **4 páginas**, sin duplicados | 6 únicos, **2 páginas**, sin duplicados |
| priceBounds efectivo, incluye variantes | **12000–99999** | **12000–21000** |
| Conteo de faceta raíz visible con subcategoría inactiva | **3** (incluye producto comercialmente visible de hija oculta sin convertirla en categoría pública) | **2** (excluye producto oculto) |
| Búsqueda general y suggested JSON/HTML | Productos activos de ramas ocultas disponibles | Ningún slug oculto en resultados ni sugerencias |
| Productos relacionados y recomendados | Activos de ramas ocultas pueden aparecer | Selectores solo devuelven productos elegibles, antes del LIMIT |
| Home manual y YouTube | IDs de manual [101,103,105,111,114,115,116]; YouTube [101,103,105,111,114,115] | IDs manual [105,114,116]; YouTube [105,114] |
| QuickAdd GET variantes visible/oculta | Ambas devuelven **200** cuando activas | Variante visible **200**, oculta **404** |
| Landings de categorías públicas | Productos activos en ramas descendientes permanecen listables | Productos de descendientes ocultos excluidos de la landing |
| Breadcrumb de producto con categoría oculta | Se omite link a landing 404, producto permanece 200 | No existe ficha pública para producto no elegible |
| Raíz e hijo profundo de 2.200 niveles válidos | Producto del nivel 2.200 aparece en ambas landings y ficha | Mismo resultado: toda la cadena está activa |

**Ejecución HTTP**: http_b1.py completó W6B2_E2E_OFF_ON_PASS. **Smoke DB por servicio**: smoke_product.php verificó total, paginación y precio efectivo, facetas de root y good-root, recomendaciones, IDs manuales y YouTube en los dos modos (ambos PASS). **Profundidad/rendimiento**: stress_get.py y deep_root_check.py confirmaron GET de /productos, /producto, /categoria (raíz y última hija) y suggest JSON para el último nivel, con OFF y ON, HTTP 200, respuestas locales de aprox. **21–104 ms** (muestras puntuales, no percentil ni SLA).

### 20.4 Riesgos y alcance expresamente pendiente

1. **No habilitar producción B1 ON aún.** Cart/Checkout/Payment, agregado al carrito por POST y preflight de checkout **NO** tienen la protección transaccional de B1. Un consumidor puede intentar POST directo aunque ficha/QuickAdd estén ocultos; no afirmar seguridad de compra hasta V6B-3. Carritos existentes tampoco se limpian ni invalidan automáticamente.
2. **V6C SEO sigue sin autorización:** sitemap_products.xml aún puede listar productos que en ON responden 404; redirects históricos, canonical, robots, JSON-LD y head 404 permanecen como antes. No se tocó el sitemap ni se intentó “arreglar” esa discrepancia por fuera de reserva.
3. **Legacy GET /productos?categoria=...:** redirección 301 preexistente a una landing oculta puede terminar en 404 aun bajo OFF; las redirecciones están explícitamente fuera de alcance (6C). La ficha de producto y catálogo normal sí cumplen B1.
4. **Facetas de subárboles:** se mejoró la suma de productos OFF atribuyendo cantidades de hijos ocultos a ancestros visibles; no se incorporó UI de nietas porque HTML/JS/CSS están prohibidos. El alcance profundo de landing se calcula por consulta de categorías y filtro de IDs SQL antes de paginar, no N+1 por producto.
5. **Relaciones corruptas históricas:** cuando ON la categoría no elegible o FK huérfana no supera el conjunto de IDs; solo se ensayó con datos sintéticos (la BD habitual exige FK). No se saneó ningún registro comercial.
6. **Rendimiento:** el predicado ON incrusta IDs de categorías elegibles en el SQL, calculados una vez por instancia. Se probó la cadena 2.200 sin N+1 y sin errores de tamaño de consulta, pero para catálogos muy grandes se deberá medir EXPLAIN/índices/p95 e introducir una estrategia acotada; no hubo benchmark de carga multicliente. La caché es por instancia, no global.
7. **Aprobación visual:** no se modificó UI ni se hizo aprobación visual humana; las pruebas fueron GET HTTP, HTML/JSON y smoke PHP.

### 20.5 Contratos, gate y cierre de recursos

- Nuevo contrato B1: **28/28 PASS**, contrato público Categorías existente **24/24 PASS**, contrato Admin Categorías **28/28 PASS** tras preservar compatibilidad del flag legacy sin duplicar filtros.
- Lint PHP: nuevos/afectados y contrato **PASS**.
- Smoke sobre DB habitual: scripts/dev/categories_public_visibility_smoke.php **PASS**, **18 categorías / 9 elegibles / 5 raíces**, lectura sin mutaciones.
- Gate general: **PASS ejecutado sobre versión final — 838 PHP, 120 JavaScript y regresiones globales, salida [gate] OK, exit code 0**. Se verificó que no había otro proceso de gate activo al iniciar.
- **Teardown FINAL VERIFICADO:** servidor privado PHP de sesión 24052 detenido, puerto **18147 libre**, base MySQL descartable **mi_pesca_cat_w6b2_20261021** eliminada con comprobación de nombre distinto de DB_NAME habitual; copia isolated_site y configuración private_config.json con credenciales retiradas. Permanecen únicamente respaldos, scripts y evidencia sintética fuera del repositorio. No se abrió navegador ni se crearon perfiles.
- **Detención obligatoria:** chat principal revisa el cierre, y autoriza separadamente 6B-3 (Cart/Checkout/Payment) y 6C (SEO/sitemap Productos). No hacer commits ni push.

**ESTADO FINAL V6B-2: IMPLEMENTACIÓN Y VALIDACIÓN TÉCNICAS COMPLETADAS — GET OFF/ON, paginación, facetas, precios, variantes, Home/YouTube, QuickAdd, profundidad 2.200, contratos 28/24/28 PASS, gate 838 PHP / 120 JS PASS, teardown OK. IMPLEMENTACIÓN DETENIDA. V6B-3 Y V6C NO AUTORIZADAS.**


---

## 21. Ventana 6B-3 — Protección B1 de Carrito, Checkout e intents (21–22/10/2026)

**Estado técnico: implementación terminada y pruebas focales superadas.** La adenda autorizó exclusivamente cambios acotados de compra B1, sin modificar Productos públicos, SEO, Settings, imports, repositorios financieros, bases comerciales, JavaScript, rutas ni migraciones. Se leyeron `docs/DECISIONES_CATEGORIAS_FASE3.md`, `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md`, `docs/COORDINACION.md` y este informe antes de editar. **No equivale a autorización de producción integral**: V6C SEO sigue pendiente y existen riesgos operativos descritos en 21.4.

### 21.1 Contrato implementado

- **B1 OFF (0):** permitir la compra de productos activos, incluso en categorías directa o jerárquicamente ocultas. **B1 ON (1):** bloquear nuevas compras de productos bajo una categoría o ancestro inactivo, huérfano o cíclico, sin modificar `products.is_active`, precios, stock ni historial.
- **Nuevo `app/Services/Checkout/CheckoutProductEligibilityGuard.php`:** núcleo de validación de compras. En lectura simple utiliza la política pública B1 previa; en validación definitiva exige transacción PDO, bloquea el flag de settings, lee categorías `ORDER BY id FOR UPDATE` y bloquea productos y variantes en orden estable. Comprueba jerarquía completa e integridad de product/variant; rechaza condiciones inválidas con un error `B1_UNAVAILABLE`. No crea ni modifica entidades comerciales; permite validar lotes sin consultas por cada producto.
- **Carrito:** al agregar por POST o aumentar cantidad se impide usar productos ocultos B1 ON; un artículo que se vuelve oculto **permanece en el carrito**, con indicador `is_publicly_available=false` y mensaje de indisponibilidad. No puede seleccionarse ni pasar al checkout; se permite removerlo por decisión expresa del cliente. Se preservan carritos anónimos y de usuario, lógica de variantes y precios preexistentes.
- **Checkout/intents:** validación de carrito bruto antes de permitir continuación, creación de CheckoutIntent o su reutilización; preflight vuelve a validar. `CheckoutOrderService::confirm` y `OrderFromIntentService::createOrderFromIntent` ejecutan el guard definitivo **en la misma transacción de pedido y stock**, antes de sus escrituras. Pedido ya creado conserva la rama idempotente.
- **Mercado Pago:** antes de solicitar cobro efectivo se revalida el intent. Si se confirma un pago aprobado mientras B1 ON torna el producto no elegible, se conserva en los repositorios existentes el registro del proveedor y el intent, **no** se crea nuevo pedido y se registra evento/auditoría para conciliación manual. Se responde HTTP 202 con `manual_reconciliation_required=true` en escenario ensayado. **Sin cobros ni devoluciones reales; sin refund automático.**

### 21.2 Archivos modificados dentro de la reserva

| Archivo | Cambio |
|---|---|
| **Nuevo** `app/Services/Checkout/CheckoutProductEligibilityGuard.php` | Política única de compra, lectura con locks transaccionales, validación de productos, variantes y jerarquía. |
| `app/Services/Cart/Legacy/LegacyCartEngine.php` | Guard B1 en add/bulkUpdate, conservación y marcado de líneas ocultas. |
| `app/Services/Cart/CartViewModelService.php` y `CartSelectionService.php` | Indisponibilidad y exclusión de artículos ocultos del checkout; compatibilidad con items legacy. |
| `app/Controllers/Cart/CartController.php` | Mensajes sobre indisponibilidad, sin cambios de rutas. |
| `app/Views/partials/carrito/item.php` y `summary.php` | Alertas funcionales existentes; no CSS ni JavaScript nuevos. |
| `app/Controllers/Checkout/CheckoutController.php` | Bloqueo de entrada al checkout con ítems ocultos conservados. |
| `app/Services/Checkout/CheckoutIntentService.php` | B1 antes de crear/reutilizar intent. |
| `app/Services/Checkout/CheckoutStockPreflightService.php` | B1 de carrito y comprobación con locks durante preflight. |
| `app/Services/Checkout/CheckoutOrderService.php` | Guard bajo transacción para confirmación por transferencia. |
| `app/Services/Orders/OrderFromIntentService.php` | Guard en creación real desde intent/webhook después de idempotencia. |
| `app/Controllers/Checkout/PaymentController.php` | Validación justo antes del cobro y registro del caso aprobado sin pedido. **El método no reservado de selección de medio de pago se restauró íntegramente.** |
| `app/Controllers/Webhooks/MercadoPagoWebhookController.php` | Conservación de pago aprobado sin pedido y respuesta de conciliación. |
| **Nuevos** `scripts/build/categories_checkout_b1_contract.php` y `scripts/dev/categories_checkout_b1_isolated.php` | Contrato focal y smoke que exige explícitamente una base aislada. |
| `docs/modulos/categorias.md` | Informe §21. |

**Control de alcance:** `scope_check.py` comparó los métodos no reservados de Carrito, Checkout, PaymentController y webhook contra el respaldo previo. **PASS**; tampoco se tocaron ProductRepository, ProductReadRepository, PaymentRepository, CartRepository, CheckoutIntentRepository, OrderRepository, Importaciones, SEO global, Settings, CSS global, JS, rutas ni migraciones.

### 21.3 Validaciones reales ejecutadas (solo datos sintéticos)

El clon temporal privado se creó fuera de `htdocs` bajo `C:\xampp\mi-pesca-backups\categories_phase3_window6b3_20261021`, con MySQL descartable `mi_pesca_cat_w6b3_20261021` y servidor de pruebas `127.0.0.1:18152`. Se usaron esquema exportado sin datos comerciales y fixtures sintéticos (usuarios, carrito, categorías directas, padres/abuelos inactivos, ciclos y huérfanos, productos simples/variantes, inventario e intents). Los scripts comprobaron el nombre de DB. El cliente MP fue sustituido por un mock **solo dentro del clon**; no se enviaron solicitudes de pago reales.

| Caso | Resultado comprobado |
|---|---|
| B1 OFF/ON, guard normal y bajo transacción | **PASS** en `test_cart.php` y smoke aislado: productos visibles y ocultos, variantes e integridad de ID; 4 escenarios × 2 modos y dos tipos de lectura. |
| Agregado POST directo con CSRF | **PASS**: sin CSRF HTTP 419; producto oculto B1 OFF HTTP 200, ON HTTP 422; producto elegible HTTP 200. |
| Carrito antiguo después del cambio OFF→ON | **PASS**: artículo guardado y señalado como no disponible, sin selección; intent, preflight y confirmación transferencia bloqueados; eliminación explícita permitida. |
| POST de actualizar cantidad oculta | **PASS**: mensaje de indisponibilidad, línea/cantidad sin cambio y sin eliminación automática. |
| Checkout HTTP autenticado | **PASS**: GET redirige a carrito; preferencia MP, preflight e inicio efectivo de pago bloqueados (422); transferencia rechaza creación y redirige a revisión. El selector de medio de pago continúa intacto. |
| Cobro MP aprobado simulado antes de bloqueo | **PASS**: se conserva registro approved/raw JSON/intento; dos intentos de crear pedido rechazados; número de órdenes y stock sin cambio. |
| Webhook aprobado HTTP (sin proveedor real) | **PASS**: dos notificaciones sucesivas con mock retornan HTTP 202, `code=b1_unavailable`, `order_id=0`, `manual_reconciliation_required=true`, sin pedido. |
| Idempotencia de pedido ya existente | **PASS**: intent ya utilizado retorna ID del pedido histórico, sin duplicación aun bajo B1 ON. |
| Concurrencia de dos conexiones PDO | **PASS**: transacción desactiva categoría; guard de confirmación espera alrededor de 1,7 segundos, lee el estado posterior al commit y rechaza compra. |
| Perímetro de archivos y contratos | **PASS** scope; Checkout B1 **31/31**, Productos B1 **28/28**, Categorías públicas **24/24**, Admin **28/28**. |
| Lint PHP | **PASS** de 16 archivos focales. |
| Build gate final tras reconexión (22/10/2026) | **PASS CONFIRMADO**, 844 PHP y 120 JS, regresiones globales, `[gate] OK`, **exit code 0**. |

### 21.4 Riesgos y pendientes, sin autorización implícita

1. **Deuda preexistente de reutilización de intents:** la prueba de `CheckoutIntentRepository::findReusable` demostró que ciertos intentos con campos de campaña `NULL` generan tokens distintos al intentar reutilizarlos. No se editó este repositorio compartido por falta de reserva. **La idempotencia de pedido ya creado sí se probó**; la de reutilización de intent **no está resuelta** y requiere decisión y reserva separada.
2. **Conciliación financiera:** registrar un pago aprobado sin pedido evita su pérdida y un pedido incorrecto. **No resuelve por sí solo la operación de atención, conciliación o eventual reintegro**; deben aprobarse el procedimiento operativo y pruebas correspondientes. No se implementó reembolso ni modificación general de estados de pagos.
3. **SEO 6C no autorizado:** sitemap de productos, canonical, robots, redirecciones y 404/head siguen pendientes y no se modificaron.
4. **Pruebas limitadas a un entorno sintético:** la carrera real de dos PDO y el mock del webhook demuestran las rutas ensayadas, no una garantía universal de integración con pagos reales o todas las intercalaciones posibles. No se ejecutó un pago real ni carga masiva.
5. **Sin aprobación de producción integral:** solicitar revisión del chat principal de estas limitaciones, reservar los archivos necesarios para mejorar reutilización/conciliación y autorizar V6C por separado. Ningún commit/push realizado.

### 21.5 Cierre de recursos temporales — CONFIRMADO (22/10/2026)

**Último gate confirmado después de la reconexión:** `scripts/build/gate.php` completó **844 archivos PHP, 120 JavaScript, regresiones globales y [gate] OK, exit code 0**. El lint focal y los cuatro contratos (Checkout B1 31/31, B1 público 28/28, Categorías públicas 24/24 y Admin 28/28) pasaron sobre la misma revisión definitiva.

**Teardown real confirmado:** al retomar, el puerto PHP privado 18152 ya estaba libre, pero MariaDB había quedado detenido tras el reinicio de la PC; por ello el primer intento de teardown falló **sin borrar nada**. Se inició temporalmente **la instancia habitual de MariaDB 10.4.32 con su configuración existente**, se eliminó **por SQL exclusivamente** `mi_pesca_cat_w6b3_20261021` previa guarda contra `DB_NAME` habitual, y se retiraron `isolated_site` y `private_config.json` (clon, credenciales y mock MP). Se verificó puerto 18152 libre, ausencia de esos archivos y base temporal eliminada. La instancia habitual de MariaDB se cerró **ordenadamente con mysqladmin**, regresando al estado previo de detenerse: puerto 3306 libre. No se borró ninguna carpeta del directorio de datos del servidor, ni se cambió la BD comercial. Solo se conservaron respaldos, scripts y evidencia de pruebas en directorio privado fuera de Git.

**Alcance final:** la comprobación focal `scope_check.py` pasó sobre métodos no reservados. Se restauró el método no reservado de selección de medio de pago; no hubo cambios en ProductReadRepository, PaymentRepository, importaciones, SEO, rutas, migraciones ni Git. No se abrieron perfiles de navegador, pagos externos, ni se realizaron commits o push.

**VENTANA 6B-3: IMPLEMENTACIÓN, PRUEBAS, GATE Y LIMPIEZA COMPLETADOS. B1 NO AUTORIZADA PARA PRODUCCIÓN INTEGRAL POR SEO 6C Y RIESGOS PENDIENTES DE INTENTS/CONCILIACIÓN. NO INICIAR V6C SIN NUEVA AUTORIZACIÓN.**


---

## 22. Ventana 6C-A — Sitemap, canonical, robots y 404 (2026-10-22)

> **ESTADO: IMPLEMENTADA Y VALIDADA TÉCNICAMENTE. NO PRODUCCIÓN INTEGRAL. V6C-B REDIRECCIONES NO AUTORIZADA.** Antes de intervenir se leyeron las últimas adendas de docs/DECISIONES_CATEGORIAS_FASE3.md, docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md, docs/COORDINACION.md y este informe. Exclusivamente ajustes SEO reservados; no se tocaron datos comerciales, estados, Checkout, Marketing, redirecciones, configuración global ni repositorios compartidos.

### 22.1 Archivos modificados y alcance

| Archivo | Cambio focal implementado |
|---|---|
| app/Controllers/System/MetaController.php | **Solamente sitemapProducts() y sitemapCategories()**. Sitemap Productos aplica en SQL ProductPublicVisibilityService::predicate(): B1 OFF conserva activos de rama oculta, ON los excluye por todos los ancestros. LEFT JOIN batch de seo_settings para filtrar robots_index=0 antes de emitir URLs y excluir canonical editorial distinto del propio. Sitemap Categorías conserva CategoryRepository::listPublicVisibleOrdered(), y usa un SELECT batch de seo_settings para excluir noindex y canonical incompatible. Conserva XML, escaping, orden y lastmod. sitemapBrands, sitemapPages, índice y robots.txt intactos. |
| app/Services/Seo/SeoFrontService.php | Solo buildProduct() y buildCategory(): construyen JSON-LD de Product/Offer/CollectionPage DESPUÉS de aplicar canonical editorial, conservando schema_override, FAQ y contenido; no se modificaron buildBrand(), SEO Pages ni cálculo de precios. |
| app/Views/partials/head.php | Exclusivamente metadatos y JSON-LD de entidad: detecta errors/404 ANTES del body de error, fuerza un único robots noindex,nofollow, no emite canonical ni OG URL engañosos ni JSON-LD Product/CollectionPage; suprime extraHead tardío para 404. Mantiene el diseño, CSS, JS, iconos y JSON-LD institucional global. |
| app/Views/pages/errors/404.php | Elimina meta robots duplicado de extraHead; conserva status 404 y HTML de la página sin ningún rediseño. |
| scripts/build/categories_seo_b1_contract.php | Contrato focal nuevo: 28 comprobaciones. |
| docs/modulos/categorias.md | Sección 22 y estado de la ventana. |

ProductController.php y CategoryController.php **no necesitaron edición**; ya usaban render_page(errors/404) y los guards B1/jerárquicos. Comprobación de perímetro con scope_contract.py: **PASS**, comparando respaldos previos a la ventana. No se modificaron public_html/index.php, ProductsController, SeoRedirectRepository, SeoSlugHistoryRepository, ProductRepository, CategoryRepository, Products/Cart/Checkout/Payment, Importaciones, Settings, rutas, migraciones, CSS/JS ni otros métodos SEO globales. Sin commit/push.

### 22.2 Validación realmente ejecutada — clon y MySQL aislados

Entorno privado externo al proyecto: C:\xampp\mi-pesca-backups\categories_phase3_window6ca_20261022; MySQL sintético mi_pesca_cat_w6ca_20261022 con esquema solamente, 19 categorías (raíces, hijas, nietas, padre/abuelo inactivo, ciclos/huérfanos), 16 productos de prueba (15 activos y uno inactivo, simples/variantes), 7 ajustes editoriales seo_settings y B1 alternado solo en fixture; servidor HTTP 127.0.0.1:18155. Los scripts de fixture confirman SELECT DATABASE() antes de escribir. No hubo navegación comercial, POST comercial ni pagos reales.

| Escenario | Resultado verificado por HTTP real |
|---|---|
| Sitemap Productos, B1 OFF | **12 URLs** en XML correcto, incluyendo productos activos en categoría oculta que son indexables; excluidos noindex y canonical editorial incompatible. |
| Sitemap Productos, B1 ON | **4 URLs** en XML correcto: productos activos elegibles por categoría + ancestros, excluidos noindex/canonical incompatible. |
| Sitemap Categorías (ambos modos) | **6 URLs** jerárquicamente elegibles e indexables; categorías con noindex editorial o canonical ajeno excluidas. |
| Destinos de ambos sitemaps | **Todos GET 200**, canonical propio, una sola etiqueta robots con index; sin URL 404/noindex ni duplicados. |
| Robots y canonical editoriales | Ficha y categoría noindex,follow respetadas; index,nofollow preservado en entidades válidas. Canonical editorial a /productos y /categorias conservado en HTML, pero esas fichas no se anuncian como URL canónica propia del sitemap. |
| 404 reales, B1 OFF y ON | **14 GET** combinados (producto/categoría ausente, inactivo, ancestro oculto o sin slug): HTTP 404, exactamente un robots=noindex,nofollow, **sin canonical** y sin Product/CollectionPage JSON-LD. Organización/WebSite institucional global sigue intacto. |
| JSON-LD luego de overrides | **PASS** en ambos modos: Product y su Offer usan canonical final del producto; CollectionPage usa canonical editorial final. |
| Cambio OFF/ON de B1 | Producto bajo ancestro oculto mantiene ficha 200 OFF y 404 ON; el 404 tiene head correcto. |
| Profundidad 2.200 | **PASS** con 2.219 categorías y 2.206 URLs sitemap indexables. XML ~394 KB; 3 GET por modo entre ~44 y ~64 ms para categorías. Sitemap Productos entre ~18 y ~39 ms. Tiempos locales puntuales, no SLA ni percentiles. |
| Lint y nuevos/anteriores contratos | **PASS** php -l en todos los archivos afectados; SEO 6C-A **28/28**; SEO Admin **OK**; Checkout B1 **31/31**; Productos B1 **28/28**; Categorías públicas **24/24**; Categorías Admin **28/28**. |
| Build gate general definitivo | **PASS confirmado**, **848 PHP y 120 JavaScript**, regresiones y contratos globales, salida [gate] OK, exit code **0**. Se inició sin otro gate activo. |

La implementación de sitemap utiliza un SELECT con JOIN SEO para productos y una consulta batch SEO para categorías, sin N+1. Los tests verificaron XML con parser, HTML head con parser, JSON-LD parseable y destinos GET reales. No hubo test headless visual ni ensayo de rastreo externo. En 404 se conserva el JSON-LD global Organization/WebSite, **no** el de entidad inexistente.

### 22.3 Limitaciones y reservas futuras

1. **6C-B redirects sigue NO AUTORIZADA:** redirects históricos, legacy 301, cadenas/ciclos y destinos 301→404 no fueron tocados; el sitemap no anuncia URL inelegible, pero una URL antigua todavía puede redirigir a 404. No corregir sin nueva ventana.
2. **CheckoutIntentRepository::findReusable con campañas NULL**, deuda preexistente confirmada: 0 vs NULL impide reutilización de algunos intents. Reserva separada 6B-4, no autorizada.
3. **Mercado Pago aprobado sin pedido:** se conserva trazabilidad, pero falta protocolo operativo/conciliación contable antes de autorizar producción. Sin reembolsos automáticos.
4. Canonical editorial que apunta a otra URL se mantiene en la página indexable y hace que su URL propia quede fuera del sitemap; no se verificó equivalencia o indexación de destinos de canonical ni destinos de redirección, materia de auditoría posterior.
5. Rendimiento medido en clon sintético, sin EXPLAIN en base comercial ni prueba de carga masiva. No modificar comercial ni inferir SLA a partir del fixture.
6. **No aprobar producción integral**, no hacer commit/push y detenerse a la espera de la autorización explícita de 6C-B.

### 22.4 Teardown

**Teardown confirmado (22/10/2026):** se detuvo exclusivamente el servidor PHP privado de sesión 1352, puerto **18155 libre**. El script privado con guarda frente a DB_NAME habitual eliminó **por SQL solo mi_pesca_cat_w6ca_20261022**; se comprobaron eliminados isolated_site y private_config.json (clon, token, credenciales). Los respaldos y los scripts de evidencia quedaron fuera del proyecto. La instancia habitual de MariaDB 10.4.32, levantada exclusivamente para el ensayo, se cerró **ordenadamente con mysqladmin** y se verificó puerto **3306 libre**. No se eliminaron archivos de datos comerciales, no se hicieron commits ni push y no se abrieron navegadores o perfiles de prueba.

**ESTADO FINAL 6C-A: IMPLEMENTACIÓN, HTTP/SQL/JSON-LD, LINT, CONTRATOS, GATE 848 PHP/120 JS Y TEARDOWN TODOS VALIDADOS. IMPLEMENTACIÓN DETENIDA; 6C-B Y 6B-4 NO AUTORIZADAS, SIN APROBACIÓN DE PRODUCCIÓN INTEGRAL.**


---

## 23. Ventana 6C-B — Redirecciones históricas seguras y B1 (2026-10-22)

> **Estado: implementación del núcleo GET y validación técnica COMPLETADAS, pero 6C-B NO CERRADA INTEGRALMENTE**: dos inconsistencias HEAD/tracking preexistentes requieren reserva expresa del chat principal. No producción. Se leyeron las adendas vigentes de DECISIONES_CATEGORIAS_FASE3, COORDINACION y POLITICA_VISIBILIDAD_PRODUCTOS_B1 antes de editar. No commits/push ni cambios en base comercial.

### 23.1 Archivos cambiados y perímetro

- **Nuevo app/Services/Seo/PublicRedirectTargetResolver.php**: resuelve cadenas de hasta 12 saltos, detecta ciclos A→B→A, auto-redirects A→A, caminos incompletos, códigos fuera de 301/302 y destinos inválidos. Solo permite ruta local relativa con slash inicial, sin host/esquema/puerto, //, barras invertidas, traversal, caracteres de control, CRLF, escapes codificados peligrosos, claves query duplicadas/arrays ni rutas privadas. Permite solo destinos públicos conocidos y comprueba existencia real de página, producto, categoría y marca. Reutiliza ProductPublicVisibilityService para B1 OFF/ON, CategoryRepository::findActiveBySlug para jerarquías y configuración editorial noindex/canonical para evitar redirigir a destino no indexable/no canónico. Valida subcategoría contra subárbol en batch sin consultar cada ancestro. Colapsa A→B→C en un único salto; respeta 301/302, degradando a 302 si una cadena contiene un tramo temporal. No crea equivalencias ni actualiza historiales.
- **public_html/index.php**: ÚNICAMENTE bloque SEO de redirects históricos; emite Location solo después de validación y registra un hit solo al emitir redirect válido. Error al registrar hit no convierte el redirect emitido en 404. Para HEAD falla cerrado sin Location porque el router general no sirve HEAD de fichas públicas (ver §23.4). El bloque previo UTM/tracking y todo el boot, login, ruteo, guards, analytics y dispatch posterior permanecen intactos.
- **app/Controllers/Catalog/ProductsController.php**: ÚNICAMENTE bloque 0 legacy inicial de index(). Valida categorías públicas jerárquicamente y marcas reales antes de 301. Mantiene q/min/max/orden/page/perPage/per_page y sub legítima; datos manipulados (arrays, sub ajena, slug faltante) producen 404 verdadero, no Location. El resto del catálogo/facetas/cards/búsqueda/SEO no cambia.
- **SeoRedirectRepository.php SIN EDITAR**: se utilizan sus métodos de SELECT y registerHit originales. SeoSlugHistoryRepository, SeoManagerService, productos, categorías comerciales, sitemap, canonical, head, 404, Cart/Checkout/Payment, Importaciones, CSS/JS, rutas, schema y Settings NO EDITADOS.
- **Nuevos** scripts/build/categories_seo_redirects_contract.php (35 comprobaciones) y scripts/dev/categories_seo_redirects_isolated.php (smoke con guarda estricta de nombre de BD). Este informe §23.
- scope_check.py privado cotejó respaldo anterior y comprobó **PASS**: fuera del bloque SEO de index.php y del bloque legacy de ProductsController no hubo cambios; repositorio de redirects compartido idéntico.

### 23.2 HTTP/SQL reales, solo MySQL descartable

Clon privado: C:\xampp\mi-pesca-backups\categories_phase3_window6cb_20261022\isolated_site, BD sintética mi_pesca_cat_w6cb_20261022, servidor HTTP 127.0.0.1:18158. Schema solo DDL, categorías/productos/variantes/brands/seo_settings sintéticos; 47 redirects de prueba iniciales, más slugs históricos explícitos y chain profunda. Todos los fixtures comprobaron SELECT DATABASE() antes de escribir. B1 modificado solo en BD temporal. Sin pagos reales.

| Caso | Resultado observado |
|---|---|
| GET 301/302, A→B y A→B→C | PASS: redirección simple y cadena resuelta directamente al destino 200, estado original 301/302 cuando corresponde; cadena 301→302 degradada a 302. |
| Ciclos/self/exceso saltos | PASS: A→A, A→B→A, cadena de 14 tramos (límite 12) y estado 307 fixture rechazados con HTTP 404, sin Location. |
| Destinos invalidos | PASS: URLs https externas, //host, /admin, /checkout, productos/categorías inexistentes, productos inactivos, controles CRLF, traversal, doble encoding, %2F, marca desconocida y parámetros manipulados: HTTP 404 sin Location. |
| B1 OFF/ON | PASS: producto activo en categoría de ancestro inactivo redirige 301 en OFF, no redirige y devuelve 404 en ON. Categorías directas/ancestros ocultos siempre bloqueadas. |
| SEO editorial | PASS: destinos con robots_index=0 o canonical editorial distinto del suyo no reciben 301. |
| Compatibilidad legacy | PASS: categorías/marcas válidas 301; ocultas/no existentes/array/sub ajena 404. Conserva q/min/max/orden/page/perPage y escaping de Unicode. |
| seo_slug_history | PASS: filas históricas sintéticas con redirect EXPLÍCITO de producto/categoría redirigen a ficha 200. Un histórico sin seo_redirects responde 404; ningún redirect es inventado automáticamente. |
| Hit counters | PASS: aumenta 1 por redirección validada; un salto intermedio de cadena, ciclo, auto-redirect, ruta inválida o HEAD denegado no genera hit. |
| Cambios de visibilidad entre GET | PASS: categoría activa→inactiva→activa en DB descartable cambió 301→404→301 en ambas rutas, sin caché entre requests. |
| Performance profundidad 2200 | PASS: cadena de 2.200 niveles, redirect histórico y legacy con subprofunda; tiempos puntuales locales ~17–37 ms, destino GET 200; lectura de subárbol batch, sin N+1 por ancestro. |
| Regresión SEO 6C-A | PASS: sitemap Productos 12 OFF/4 ON y Categorías 6; URLs 200 indexables con canonical propio, 404 noindex,nofollow único sin canonical ni JSON-LD de entidad. |
| Contratos y gate | **Redirect 35/35, SEO B1 28/28, Checkout B1 31/31, Productos B1 28/28, Categorías públicas 24/24, Admin Categorías 28/28, SEO Admin OK**. Lint focal PASS. Gate global **853 PHP / 120 JavaScript, [gate] OK, exit code 0**. Se comprobó antes que no hubiera otro gate ejecutándose. |

Las comprobaciones fueron HTTP GET, HEAD de seguridad, SQL real de hits y XML/HTML/JSON-LD. No hubo navegador ni QA visual. No se promete atomicidad de redirect entre peticiones distintas.

### 23.3 Política aplicada

No se tocaron las tablas seo_redirects ni seo_slug_history para colapsar cadenas. Solo se incrementan hits en redirects aprobados. El resolutor falla cerrado si el destino es externo, desconocido, noindex o B1 oculto; no emite redirección a Inicio ni 410. Mantiene la precedencia preexistente de normalización UTM y filtros sin cambiar su captura de first-touch.

### 23.4 Riesgos reproductibles que requieren NUEVA RESERVA (NO OCULTAR)

**P1 funcional — HEAD de extremo a extremo.** GET /producto?slug=good-item, /categoria?slug=root-ok, /marca?slug=marca-qa y /productos da HTTP 200 en el clon. La misma URL por HEAD devuelve 404 porque config/routes.php solo registra GET y el dispatcher usa el método literal. Emitir 301 HEAD hacia esos destinos llevaría a otro HEAD 404, contrario al contrato. **Mitigación dentro de la reserva:** el bloque SEO histórico no emite Location en HEAD; la petición falla cerrada con 404. **Esto NO satisface el soporte HEAD completo.** Pedir reserva puntual al chat principal sobre dispatch de HEAD en public_html/index.php y/o config/routes.php para permitir HEAD real de páginas públicas sin afectar POST, CSRF, autenticación ni servir cuerpo indebidamente.

**P1 SEO — limpieza UTM previa puede emitir 301 hacia 404.** HTTP real: GET /old-cat-hidden?utm_source=fixture → 301 /old-cat-hidden → 404. También GET /productos?categoria=child-parent-off&utm_medium=email → 301 /productos?categoria=child-parent-off → 404. Ocurre en el bloque ANTERIOR de limpieza de tracking del entrypoint, expresamente solo lectura en 6C-B. GET /old-direct?utm_source=fixture legítimo genera la cadena de limpieza 301 y luego 301 SEO. **Se requiere reserva precisa para coordinar validación/orden del bloque UTM con el resolutor**, preservando captura de campaña/first touch, sin open redirect ni cambios de ruteo no autorizados.

**Deudas separadas para producción:** 6B-4 de CheckoutIntent con campañas NULL y procedimiento verificable de conciliación Mercado Pago aprobado sin pedido; Etapa 7 QA integral. No se han modificado y siguen pendientes de autorización independiente.

**Decisión de entrega:** núcleo histórico y legacy GET VALIDADO. **NO DECLARAR 6C-B CERRADA O APROBADA PARA PRODUCCIÓN** mientras ambas inconsistencias reproducidas exijan una reserva adicional. Esta ventana no incluye el arreglo del router/normalización tracking; solicitarlos al chat principal antes de Etapa 7.

### 23.5 Teardown operativo

**Teardown CONFIRMADO:** servidor PHP privado de sesión 14688 detenido; puerto 18158 libre; **únicamente** la base descartable mi_pesca_cat_w6cb_20261022 eliminada mediante SQL con guarda DB_NAME comercial; clon isolated_site y credenciales private_config.json eliminados. Se conservaron scripts de prueba y backups privados fuera de Git. **MariaDB estaba activa antes de esta ventana y permaneció en funcionamiento**, sin detener ni reiniciar el proceso preexistente. No hubo navegadores/perfiles, pagos reales, commits ni push.

**ESTADO FINAL 6C-B: NÚCLEO GET + LEGACY, HTTP/SQL, CONTRATOS, GATE 853 PHP/120 JS Y TEARDOWN VALIDADOS. NO CERRADA INTEGRALMENTE: el chat principal debe reservar dos ajustes adicionales (ruteo HEAD y canonicalización tracking) antes de producción/Etapa 7. IMPLEMENTACIÓN DETENIDA.**


### 23.6 Subventana 6C-B2 — HEAD público seguro y canonicalización UTM (22/10/2026)

**Estado técnico: implementación de las rutas seguras y validación completadas; HEAD de ficha de Producto y búsqueda autenticada quedó BLOQUEADO por escrituras en controladores fuera de la reserva.** El resultado sustituye las limitaciones originales HEAD/tracking de §23.4 donde corresponda, pero NO permite declarar cerrado HEAD integral ni producción. Antes de editar se leyeron las adendas vigentes de DECISIONES_CATEGORIAS_FASE3.md, COORDINACION.md y POLITICA_VISIBILIDAD_PRODUCTOS_B1.md.

#### Alcance real y archivos cambiados

| Archivo | Modificación |
|---|---|
| public_html/index.php | Buffer de salida HEAD sin cuerpo; despacho HEAD limitado a whitelist literal de rutas públicas de solo lectura (no fallback GET global); bloque SEO histórico GET/HEAD con validación final y cero hits HEAD; bloque UTM/normalización reforzado para detectar duplicaciones, arrays manipulados y codificación peligrosa; first-touch únicamente GET; resolución de destino único antes de emitir Location, sin 301 hacia 404; page_view global únicamente GET. POST/CSRF/login/boot/guards/dispatch restante inalterados. |
| app/Services/Seo/PublicRedirectTargetResolver.php | Nuevo método resolveCleanedRequest(): reutiliza las cadenas explícitas existentes, la validación de destinos legacy y la política B1/SEO para generar destino limpio en una sola redirección segura. No altera la normalización de URLs o validación B1 de 6C-B, ni escribe historiales. |
| app/Controllers/Catalog/ProductsController.php | Exclusivamente bloque legacy de index(): marca[]=slug se distingue como filtro múltiple legítimo y NO como slug de 301; listas escalares seguras se conservan y arrays manipulados se rechazan. No se modificaron sugerencias, facetas, B1, búsqueda ni Audit. |
| config/routes.php | SIN cambios. La whitelist HEAD del entrypoint bastó para las rutas seguras. |
| scripts/build/categories_seo_redirects_contract.php | Adaptado a nueva semántica HEAD segura, mantiene 35 comprobaciones. |
| scripts/build/categories_seo_redirects_head_tracking_contract.php y scripts/dev/categories_seo_redirects_head_tracking_isolated.php | NUEVOS: 29 contratos focales y smoke SQL que rechaza expresamente DB no descartable. |
| docs/modulos/categorias.md | Esta sección y cierre. |

**Comparación de perímetro:** scope_check.py verificó contra respaldos previos que el entrypoint solamente cambió en bloques HEAD, UTM, redirects SEO, selección de key y page_view; el resto de guards/boot/dispatch es idéntico. config/routes.php byte-idéntico; ProductsController fuera de bloque legacy intacto; en el resolutor solamente se agregó el método nuevo. No se alteraron otros controladores, Analytics, Marketing, SEO Admin, Checkout, Pago, Importaciones, CSS/JS, esquemas ni base habitual. No commits/push.

#### Pruebas REALES: HTTP, sesión y SQL aislados

Clon completo en C:\xampp\mi-pesca-backups\categories_phase3_window6cb2_20261022, servidor loopback 127.0.0.1:18163, base descartable mi_pesca_cat_w6cb2_20261022 (schema sin datos comerciales; fixtures de categorías, productos, variantes, B1 OFF/ON, SEO editorial y redirects/slug history). Fixtures PHP verifican SELECT DATABASE() antes de toda escritura. Sin cobros reales ni endpoints externos.

| Ensayo | Resultado observado |
|---|---|
| GET vs HEAD para rutas seguras | PASS: /, /productos sin q, /categoria visible, /marca válida, /categorias, /marcas, /terminos y /privacidad: mismos estados 200 y Content-Type; HEAD entrega exactamente 0 bytes. Categoría o marca inexistente 404 GET/HEAD; HEAD 0 bytes. Verificación adicional con curl.exe -I. |
| HEAD históricos y legacy seguros | PASS: redirect a categoría/marca elegibles conserva HTTP 301/302 y Location al destino final; HEAD de destino 200 sin cuerpo. Ciclos, endpoints privados, ocultos, noindex e inexistentes sin Location, HTTP 404. |
| HEAD deliberadamente bloqueado | HEAD de /producto?slug=..., redirect histórico hacia producto y /productos?q=... → HTTP 404 sin Location/cuerpo, para impedir efectos de Analytics/Audit. NO afirmar que sean equivalentes a GET; requieren nueva reserva descrita abajo. |
| Tracking legítimo | PASS: /old-direct?utm_source=..., cadena A→B→C con UTM, categorías y marcas legacy con UTM/fbclid → UN ÚNICO 301 hacia destino final válido. q, min, max, orden, page, perPage, sub y caracteres Unicode válidos conservados. |
| Tracking inválido | PASS: histórico inexistente, categoría oculta, marca inexistente, ciclo, destino externo y canonical/noindex incompatible con UTM: 404 sin Location, sin 301→404. |
| First-touch GET, HEAD sin atribución | PASS con sesión PHP real: HEAD con UTM no creó analytics_utm. Primer GET almacenó utm_source, utm_medium, utm_campaign y captured_at; segunda campaña GET no reemplazó los datos. |
| Manipulación de URL | PASS: utm_source[]; trackers duplicados; categoria[]; CRLF, encoding ambiguo y arrays maliciosos sin Location. El normalizador no pasa hosts externos. |
| Filtros con arrays válidos | PASS: marca[]=..., dos marcas y tag[color][]=... con UTM continúan GET 200 sin Location; no se destruyen arrays. Por seguridad se omite canonicalización UTM si existen filtros estructurados, sin perder first-touch GET. |
| HEAD sin escrituras | PASS 26 requests HEAD, antes/después SQL iguales: orders, payments, checkout_intents, analytics_events, import_jobs, products, categories, inventory, cart_items; stock total y suma de seo_redirects.hits idénticos. Fixture previo: 203 analytics_events, 63 hits, stock total 80; todos sin incremento. |
| CSRF POST | PASS: POST /carrito/agregar sin token devolvió HTTP 419; se conservaron los métodos POST, sus guards y handlers. |
| Regresión de redirects 6C-B | PASS E2E GET (matriz actualizada para reconocer marca[]= como filtro): cadenas, ciclos, destinos inseguros, visibilidad y B1 ON/OFF, noopenredirect. |
| Regresión SEO 6C-A | PASS: sitemap Productos 12 URLs B1 OFF y 4 ON; Categorías 6; destinos 200 indexables; 404 con un solo noindex,nofollow, sin canonical ni schema de entidad. |
| Scope/lint/contratos | PASS: lint PHP de los seis archivos focales; nuevo contrato HEAD/UTM 29/29; redirects 35/35; SEO B1 28/28; Checkout B1 31/31; Productos B1 28/28; público Categorías 24/24; Admin Categorías 28/28; SEO Admin OK. |
| Build gate definitivo | PASS: 858 PHP y 120 JavaScript, regresiones globales, [gate] OK, exit code 0; no había gate ajeno activo al iniciarlo. |

#### BLOQUEO FUNCIONAL HEAD: requiere reserva focal adicional del principal

1. ProductController::show() siempre ejecuta AnalyticsService::trackProductView(id) al servir GET. Despacharlo como HEAD provocaría una falsa visita de producto en analytics_events. Por mandato expreso de 6C-B2 este subcaso quedó detenido, no se modificó el controlador. Solicitar reserva para condicionar esa escritura al método GET real; luego activar HEAD /producto y destinos históricos de producto y probar SQL sin product_view.
2. ProductsController::index() ejecuta Audit::log('catalog_search',...) cuando hay término q y usuario autenticado. HEAD /productos?q=... permanece deliberadamente bloqueado para evitar auditoría falsa; solicitar reserva solo para hacer ese tracking GET-only preservando el GET normal. HEAD /productos sin q sí funciona.
3. Filtros marca[] o tag[grupo][] con UTM se atienden sin canonización adicional para no perder arrays, conservando first-touch. No hay open redirect ni pérdida de filtros; normalización ulterior de arrays requeriría un contrato explícito.

**Conclusión:** las cadenas 301 UTM→404 de §23.4 quedaron corregidas en los casos ensayados. HEAD funciona sin cuerpo/escrituras para el conjunto seguro, pero NO para Producto ni búsqueda autenticada hasta ampliar reserva. 6C-B sigue pendiente de aceptación integral del principal. Las deudas de CheckoutIntent con campañas NULL, conciliación de Mercado Pago y Etapa 7 QA continúan fuera del alcance y bloquean producción. No iniciar etapas nuevas sin autorización.

### 23.7 Limpieza y estado operativo 6C-B2

**Teardown FINAL VERIFICADO (22/10/2026):** servidor PHP privado de sesión 1508 detenido; puerto 18163 libre; base MySQL descartable mi_pesca_cat_w6cb2_20261022 eliminada POR SQL con guarda de nombre respecto a DB comercial; isolated_site, mock y private_config.json temporal eliminados; scripts y backups privados conservados fuera de Git. **MariaDB preexistente permaneció en ejecución** sin reinicio ni cierre. Ningún navegador/perfil de prueba, pago real, commit ni push.

**ESTADO FINAL 6C-B2: HEAD/UTM DEL ALCANCE SEGURO, HTTP/SQL, LINT, CONTRATOS, GATE 858 PHP/120 JS Y LIMPIEZA VERIFICADOS. IMPLEMENTACIÓN DETENIDA. Solicitar dos reservas focales sobre tracking de ProductController y búsqueda autenticada de ProductsController antes de cerrar HEAD integral. No iniciar Etapa 7 ni Checkout.**


---

## 24. Cierre integral V2 de Categorías — 22/10/2026

**Resultado:** implementación y QA funcional/técnico/visual de Categorías completados dentro del dominio asignado. **NO es autorización de producción ni aprobación estética del propietario.** Se siguió `docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md`, sin microventanas, sin operaciones reales y sin modificar módulos de otros especialistas.

### Correcciones realizadas

- **HEAD integral seguro:** `ProductController::show()` registra `trackProductView()` exclusivamente con método HTTP real **GET**; `ProductsController::index()` registra `Audit::log('catalog_search')` y actualiza su throttle solo con **GET**. `public_html/index.php` ahora admite HEAD de `/producto`, `/productos?q` y redirecciones históricas hacia esos destinos, siempre por whitelist pública y con cuerpo vacío. HEAD no ejecuta Analytics/Audit de esas operaciones ni PageView; POST, CSRF, autenticación y reglas B1 continúan intactos.
- **Visibilidad Admin comprensible:** `CategoriesIndexService` integra `CategoryPublicVisibilityService::visibleIdSet()` en batch, sin N+1 por fila. Se separó el KPI **Activas** de **Visibles en tienda** y se añadió aviso **Oculta en tienda** cuando una categoría activa tenga ancestros inactivos, un ciclo o padre huérfano. No se cambió ningún flag comercial. El formulario explica la visibilidad jerárquica.
- **Resiliencia del formulario:** `CategoriesController::save()` rechaza con **422** y sin escritura campos escalares manipulados como arrays (nombre, ID, slug, metadatos y portada). Ajustes compactos mobile y estado semántico en CSS propio `admin-v3/pages/categories.css`, reutilizando los componentes y colores V3; no se creó otra hoja paralela.
- **Contrato nuevo:** `scripts/build/categories_integral_contract.php` con **18/18** verificaciones; contrato HEAD/UTM previo actualizado y ampliado a **31/31**.

### QA HTTP/SQL y navegador que efectivamente se ejecutó

El ensayo usó una **única copia privada reutilizable** del sistema y base MySQL **`mi_pesca_cat_integral_20261022`**, con únicamente DDL y categorías/productos/variantes/usuarios/SEO/redirects sintéticos. Puerto loopback 18166, sesiones/CSRF reales, guardas `SELECT DATABASE()`, ninguna escritura a DB comercial, ningún pago real.

- **Admin autenticado:** GET listado, alta, edición, filtros/estados vacíos; sin permiso **403**, petición anónima **401** en modo JSON, CSRF inválido **403**, arrays manipulados **422**. Altas HTTP/SQL padre-hija; colocación cíclica rechazada sin cambiar relaciones; desactivación en cascada y reactivación del padre sin reactivar hijos; quick create idempotente **200 creado/reutilizado**, conflicto de slug/padre **409**, padre inactivo **422**.
- **HEAD vs GET + B1 OFF/ON:** HTTP reales de Producto, búsquedas `/productos?q`, Categorías, Marcas, Home, índices, legales, 404 y redirects históricos/legacy; mismos estados/Location y Content-Type relevante, **HEAD con cero bytes de cuerpo**. SQL/sesión antes/después de HEAD: `product_view`, `page_view`, `catalog_search`, órdenes, pagos, stock y hits **sin alteración**. Control positivo: GET real de producto suma visita y GET autenticado de búsqueda registra auditoría y throttle; HEAD nunca lo hace.
- **SEO/regresiones:** sitemaps Productos B1 OFF **12 URLs** y ON **4 URLs**, Categorías **9 URL indexables** tras nuevas categorías fixture; todos los destinos devuelven GET 200, index/canonical propios; noindex/canonical editorial y 404 con robots único noindex,nofollow y sin JSON-LD de entidad intactos. Redirecciones, cadenas, ciclos, UTM, open redirect y B1 conservan la política validada en 6C.
- **Seguridad/compra:** contratos Checkout B1 y Productos B1 reejecutados; no se modificaron CheckoutIntent, MP ni transacciones de compra. Cambios concurrentes/rollback de Categorías de etapas previas conservan sus contratos y evidencias; no se repitieron de modo destructivo todos los fallos inyectados históricos.
- **Edge real:** **25 vistas base + 25 interactivas** en **363, 390, 768, 1024 y 1600 px**, con Dashboard/Pedidos como referencias. Listado Admin (incluyendo inactivas y vacío), alta, edición, SEO expandido (31 controles), páginas públicas; **sin overflow horizontal ni errores JS**. HTML required verificado. **28 capturas PNG** permanecen fuera del repositorio en `C:\xampp\mi-pesca-backups\categories_integral_v2_20261022\screenshots\`: `categories-390.png`, `categories-1600.png`, `category-new-390.png`, `category-edit-1600.png`, `edit-seo-expanded-390.png`, `empty-list-390.png`, y referencias de Dashboard/Pedidos. Las capturas no equivalen a aprobación estética del propietario.
- **Escala sintética:** jerarquía de **2.200 niveles**, con medianas locales de ~**45–53 ms** para listado Admin, **34 ms** búsqueda, **62–66 ms** formularios, **102 ms** categoría profunda y **54 ms** sitemap. Lectura batch sin N+1 por ítem. Limitación no bloqueante: el selector completo de padres genera ~**855 KB HTML** con 2.200 categorías; eventual buscador/autocomplete si el negocio alcanza ese tamaño.
- **Contratos y gate definitivos:** integral **18/18**, HEAD/UTM **31/31**, redirects **35/35**, SEO B1 **28/28**, Checkout B1 **31/31**, Productos B1 **28/28**, Categorías públicas **24/24**, Admin **28/28**, SEO Admin **OK** y lint PHP focal OK. `scripts/build/gate.php` terminó **861 PHP / 120 JS, [gate] OK, exit code 0**, sin gate paralelo al iniciarlo.

### Archivos modificados por este cierre integral

`app/Controllers/Catalog/ProductController.php`, `app/Controllers/Catalog/ProductsController.php`, `public_html/index.php`, `app/Controllers/Admin/CategoriesController.php`, `app/Services/Admin/Categories/CategoriesIndexService.php`, `app/Views/pages/admin/categories/index.php`, `app/Views/pages/admin/categories/form.php`, `public_html/assets/css/admin-v3/pages/categories.css`, `scripts/build/categories_seo_redirects_head_tracking_contract.php`, **nuevo** `scripts/build/categories_integral_contract.php` y este informe. Sin modificar rutas globales, AnalyticsService, Audit global, Cart/Checkout, Finanzas/MP, Marketing, Importaciones, Atributos ni esquema. **No commits ni push.**

### Riesgos ajenos y cierre operativo

- **Principal/Checkout:** la reutilización de CheckoutIntent con campañas SQL `NULL` y el procedimiento de conciliación de pagos Mercado Pago aprobados sin pedido siguen **pendientes** y bloquean producción; no fueron modificados.
- **Categorías, escala opcional:** UX del selector nativo de padres podría mejorar si existen miles de nodos. **Principal/propietario:** la aceptación estética final sigue siendo humana; el navegador real demuestra funcionamiento/responsive, no su aprobación.
- **Limpieza verificada:** servidor PHP propio del puerto **18166** detenido; BD sintética **eliminada por SQL** y verificada distinta de DB habitual; sitio clonado, credenciales y perfiles Edge privados retirados; **28 screenshots** y scripts de evidencia conservados fuera de Git. **MariaDB preexistente sigue ejecutándose**. Sin pagos reales ni datos comerciales mutados.

**Estado final integral: ENTREGADO PARA REVISIÓN E INTEGRACIÓN DEL CHAT PRINCIPAL. Categorías validada técnicamente en su dominio; PRODUCCIÓN NO AUTORIZADA por deudas financieras y QA/aceptación transversal independientes.**
