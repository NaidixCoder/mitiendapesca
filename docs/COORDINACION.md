# MiTiendaPesca — Coordinación entre chats

**Entrada obligatoria a la documentación:** [docs/README.md](README.md). Antes de leer planes antiguos, seguir su índice de reglas vigentes y reservas.

> Protocolo operativo v1.0 | Inicio: 2026-10-20 | Proyecto local: `C:\xampp\htdocs\mi-pesca`.
> Este documento coordina trabajo humano/asistido; **no implementa bloqueos reales ni sincroniza conversaciones automáticamente**.

## 1. Roles
- **Chat principal — Arquitectura y coordinación:** inventario, decisiones transversales, resolución de conflictos, asignaciones, auditoría e integración. Evita tocar el código de un módulo asignado.
- **Chat especializado:** implementa y prueba solo su módulo autorizado; registra cambios, contratos afectados, pruebas realizadas y pendientes.
- **Propietario del proyecto:** valida prioridades, decisiones de negocio, cambios de alcance y operaciones riesgosas.

## 2. Fuentes y jerarquía práctica
1. Decisiones expresas vigentes del propietario; ante duda o contradicción, detener el cambio conflictivo.
2. **Para UI Admin V3:** estándar operativo `docs/GUIA_VISUAL_ADMIN_V3.md` (versión consolidada de las decisiones visuales) + contrato formal `CONTRATO_VISUAL_ADMIN_2026.md` + arquitectura `docs/ARQUITECTURA_ADMIN_V3.md` y contratos `scripts/build/*` aplicables. No confundir un bloque histórico con la decisión más reciente.
3. `PLAN_CSS_UNIFICADO.md` y `PLAN_DE_TRABAJO.md` (este último conserva las prioridades aunque su detalle de avance es anterior).
4. `MANIFEST.md` como historial y evidencia de cambios; `docs/ESTADO_DESARROLLO.md` como síntesis operativa, **no** reemplaza el historial.
5. Ante documentos desactualizados o incompatibles: dejar constancia de la discrepancia y acordar la resolución antes de implementar.

## 3. Asignaciones actuales
| Área | Responsable operativo | Situación | Regla |
|---|---|---|---|
| Arquitectura, documentación y coordinación | Chat principal (este chat) | Activo | Escribir únicamente documentación de coordinación hasta acordar un cambio técnico |
| `/admin/marketing/*` (Campañas y Email) | Otro chat de Marketing, informado por el usuario | **Trabajo en curso, reserva preventiva** | Este chat y cualquier tercero: SOLO LECTURA; no pisar cambios |
| Dashboard, Pedidos, SEO | Sin chat nuevo asignado | Implementaciones recientes documentadas; revisiones visuales pendientes | No iniciar escrituras sin nueva asignación |
| Marcas `/admin/brands/*` | Chat especializado de Marcas | En desarrollo | Responsable exclusivo del módulo y `docs/modulos/marcas.md` |
| Categorías `/admin/categories/*` | Chat especializado de Categorías | Segunda revisión / desarrollo | Responsable exclusivo del módulo y `docs/modulos/categorias.md` |
| Atributos `/admin/attributes/*` | Chat especializado de Atributos | En desarrollo | Responsable exclusivo del módulo y `docs/modulos/atributos.md` |
| Otros módulos | Sin asignar | Por planificar | Definir alcance y archivos antes de abrir trabajo paralelo |

**Reserva preventiva:** la asignación Marketing procede de la indicación del usuario. No existe un lock técnico ni confirmación automática de que el otro chat haya leído este archivo.

## 4. Ámbitos y archivos sensibles
- Marketing: `app/Views/pages/admin/marketing_campaigns/`, `app/Views/pages/admin/marketing_email/`, `app/Views/partials/admin/marketing_*`, `app/Controllers/Admin/*Marketing*`, controladores de campañas/email, servicios y repositorios correspondientes, `public_html/assets/css/admin-v3/pages/marketing-*.css`, `public_html/assets/js/admin/marketing/` y contratos/smokes `scripts/*/marketing_*`.
- Componentes transversales: `app/Views/layouts/admin.php`, `app/Views/partials/admin/page_header.php`, `app/Views/partials/admin/sidebar_v2.php`, `app/Support/helpers.php`, `public_html/assets/css/admin-v3/{tokens,base,layout}.css`, `public_html/assets/css/admin-v3/components/`, `public_html/assets/js/admin/core/`.
- Contratos e infraestructura transversal: `config/routes.php`, `scripts/build/gate.php`, migraciones DB, modelos/servicios compartidos, `MANIFEST.md` y documentos rectores.
- **Regla:** tener una ruta asignada no autoriza cambios indirectos en archivos transversales; solicitar coordinación y reservar el archivo con precisión.

## 5. Protocolo de inicio de trabajo
1. Leer este archivo, `docs/ESTADO_DESARROLLO.md` y los cuatro documentos centrales de la sección 2.
2. Confirmar alcance funcional, rutas, archivos previstos, contratos JS/DOM, pruebas y si el módulo ya tiene responsable.
3. Antes de editar, actualizar **una sola fila de asignación** o pedir al chat principal que lo haga. No escribir si otra sesión figura activa sobre los mismos archivos.
4. No asumir que el contexto de otro chat está sincronizado: la documentación en disco es el mecanismo de traspaso.
5. Antes de cambios grandes, realizar respaldo privado **fuera** del proyecto; no crear ZIP incremental salvo orden expresa.

## 6. Durante y al cerrar cada tarea
- Trabajar en archivos acotados, conservar backend, rutas, nombres de campos, selectores JS y DB salvo autorización explícita; respetar `av3-`, CSS por ámbito y componentes comunes. **Para tareas de UI, leer y aplicar `docs/GUIA_VISUAL_ADMIN_V3.md`** (mobile-first, tokens, componentes y referencias).
- Para un archivo compartido, detener la edición propia, registrar el conflicto y asignar un único responsable temporal. No hay ediciones simultáneas sobre el mismo archivo.
- No realizar pruebas con envíos reales de emails, trabajadores scheduler/queue, pagos, cambios de datos, migraciones ni acciones irreversibles sin entorno/alcance seguro y autorización específica.
- **Cierre obligatorio de navegadores de prueba:** aplicar `docs/pruebas/PROTOCOLO_NAVEGADORES.md` en toda automatización con Edge/Chrome/Firefox/Playwright/WebDriver. Headless por defecto; cerrar pestañas, contextos y procesos *propios* mediante `finally`/teardown incluso si la prueba falla. Verificar que no queden ventanas/procesos huérfanos. Nunca matar navegadores ajenos, reiniciar Explorer ni detener XAMPP como limpieza.
- Al cerrar: detallar archivos tocados, funcionalidad, respaldo, resultado de `scripts/build/gate.php`, pruebas focales, validación visual real o pendiente y posibles conflictos.
- **Regla de entrega multichat:** cada chat actualiza exclusivamente `docs/modulos/<su-modulo>.md` tras cada etapa significativa y al cerrar, siguiendo `docs/modulos/README.md`. Debe registrar fallas, cambios efectivos, CSS, pruebas reales/sintéticas, archivos y pendientes; el chat principal lee ese informe sin necesidad de recibir copias de la conversación.
- El chat principal mantiene `docs/ESTADO_DESARROLLO.md` y coordina las actualizaciones del `MANIFEST.md` en una ventana exclusiva. No declarar aprobado algo verificado solo de forma sintética.
- Si falla una prueba, falta conexión o hay colisión de cambios, registrar bloqueo; nunca informar como concluido.

## 7. Integración y control de concurrencia
- **Vigente desde 2026-10-20:** Git está conectado localmente a `https://github.com/NaidixCoder/mitiendapesca.git`, rama `main`. El propietario aprobó **una sola carpeta de XAMPP** y sin worktrees por ahora; el código local es fuente de verdad. Ver `docs/PROTOCOLO_GIT_MULTICHAT.md`.
- **Solo el chat principal** administra commits, push e integración; los especializados no ejecutan cambios de rama, Git destructivo o publicación.
- **Un único editor por archivo** y seguimiento manual de las reservas activas. Git no bloquea archivos ni coordina automáticamente los chats. Los archivos globales requieren autorización/turno de edición.
- La carpeta fue vinculada después de la auditoría inicial; la trazabilidad histórica está en `docs/ESTADO_DESARROLLO.md`, no es una instrucción de volver a configurar Git.
- El chat principal **no puede** notificar automáticamente a otros chats: el propietario transmite nuevas reglas, y luego cada chat consulta la documentación compartida.

## 8. Protocolo Git y mensaje de arranque para chats especializados
- **Lectura obligatoria general:** `docs/PROTOCOLO_GIT_MULTICHAT.md` y `docs/modulos/README.md` antes de cualquier edición. Git está conectado; solamente el chat principal gestiona commits y push.

«Trabajás en MiTiendaPesca, ruta `C:\xampp\htdocs\mi-pesca`. Antes de editar leé `docs/COORDINACION.md`, `docs/ESTADO_DESARROLLO.md`, `docs/PROTOCOLO_GIT_MULTICHAT.md`, `docs/GUIA_VISUAL_ADMIN_V3.md`, `CONTRATO_VISUAL_ADMIN_2026.md`, `PLAN_CSS_UNIFICADO.md`, `MANIFEST.md` y `docs/ARQUITECTURA_ADMIN_V3.md`. Respetá la asignación de archivos, no toques áreas ajenas ni compartidas sin coordinar. Git ya está conectado: no hagas commits, push ni cambies ramas. Mantené actualizado el informe exclusivo de tu módulo en docs/modulos/ y al finalizar registrá cambios, pruebas y pendientes; no crees ZIP incrementales».

## 9. Reserva temporal — Categorías Fase 3 (2026-10-21)

- **Decisión formal vigente:** [`docs/DECISIONES_CATEGORIAS_FASE3.md`](DECISIONES_CATEGORIAS_FASE3.md). Leer antes de editar el servicio de estados, quick o SEO.
- **Ventana 1 autorizada únicamente al chat de Categorías:** `app/Services/Catalog/CategoryAdminService.php`, `app/Services/Admin/Categories/CategoryHierarchyGuard.php`, nuevo servicio de política `app/Services/Admin/Categories/CategoryVisibilityPolicyService.php`, su contrato/pruebas focales e informe `docs/modulos/categorias.md`. No permitir edición concurrente de esos archivos.
- **No autorizadas aún:** adaptación de Controller/formulario, dos archivos Productos de creación rápida, SEO compartido, catálogo público, CSS global, migraciones o Git desde ese chat. Cada fase necesita evidencias y próxima autorización expresa.
- Marcas continúa **sin aprobación visual** y en trabajo exclusivo de CSS. Atributos mantiene correcciones P0 compartidas bloqueadas; Marketing y otros chats deben respetar esta reserva.

### Actualización — Ventana 2 de Categorías autorizada (2026-10-21)

- Ventana 1 finalizada según `docs/modulos/categorias.md` §13; ver decisión detallada `docs/DECISIONES_CATEGORIAS_FASE3.md` (adenda final).
- **Reserva exclusiva para chat Categorías, Ventana 2:** `app/Controllers/Admin/CategoriesController.php` **solo save/toggle**, los tres servicios de estado/jerarquía previamente reservados, `app/Views/pages/admin/categories/form.php` y `app/Views/partials/admin/categories/toggle_action.php` **solo si se necesitan mensajes funcionales**, contratos/pruebas focales y `docs/modulos/categorias.md`.
- No autorizados: `quick()`, archivos de Productos, servicios SEO compartidos, catálogo público, CSS, layouts, rutas, migraciones, otros módulos o Git. `scripts/build/gate.php` puede ejecutarse, **no editarse**.
- El formulario debe respetar la política única, validar antes de confirmar y evitar escrituras parciales de categoría/visibilidad; la atomicidad completa con SEO/archivos sigue como deuda de Ventana 4. Terminado el E2E HTTP sobre MySQL aislado, liberar reserva y esperar siguiente autorización.

### Actualización — Ventana 3 Categorías autorizada (2026-10-21)

- Ventana 2 cerrada con pruebas HTTP aisladas y gate documentados en `docs/modulos/categorias.md` §14.
- **Reserva EXCLUSIVA temporal para Categorías (Ventana 3):** `app/Controllers/Admin/CategoriesController.php` **solo quick()**; `public_html/assets/js/admin/product_form/index.js` **solo initCategoryQuickCreate()**; `app/Views/partials/admin/products/tab_basicos.php` **solo modal quick Categorías**; contratos/pruebas focales y `docs/modulos/categorias.md`.
- La reserva de los **dos archivos Productos** es una excepción focal autorizada por principal. No abrir edición simultánea desde otro chat, y confirmar disponibilidad antes de escribir. Sin permiso para el resto del formulario de Productos ni el modal de Marcas.
- Contrato y pruebas exactas en `docs/DECISIONES_CATEGORIAS_FASE3.md` (adenda V3). **No cambiar SEO, público, CSS, layout, otros servicios compartidos ni Git**. V4 y V5 siguen sin autorización.

### Actualización 2026-10-21 — Categorías Ventana 4

- **Ventana 3 documentada y terminada**: `docs/modulos/categorias.md` §15. Liberadas las reservas temporales de Productos `public_html/assets/js/admin/product_form/index.js` y `app/Views/partials/admin/products/tab_basicos.php` de Categorías.
- **Ventana 4 reservada solo al chat de Categorías**: `app/Controllers/Admin/CategoriesController.php` **solo save()/storeCoverImage()**, nuevo orquestador local `CategorySaveService.php`, ajustes compatibles indispensables de servicios Categorías, contrato y pruebas focales, `docs/modulos/categorias.md`. Ver `docs/DECISIONES_CATEGORIAS_FASE3.md` (última adenda).
- **SEO compartido en SOLO LECTURA**, incluido `SeoSlugHistoryRepository.php` (riesgo conocido de cambio silencioso de propietario), y cualquier archivo de otros módulos. Si hace falta modificarlo, detenerse y pedir permiso preciso. No tocar CSS, Productos, catálogo público, rutas, schema, migraciones, gate global, Git ni base comercial.
- Marcas sigue sin aprobación visual; sus archivos continúan exclusivos de su chat hasta cierre declarado por propietario. Atributos sin autorización para sus P0 compartidos.

## 10. Nuevo módulo en coordinación — Importaciones Admin V3 (2026-10-21)

- **Chat especializado nuevo:** Importaciones; documento rector [`docs/PLAN_IMPORTACIONES_ADMIN_V3.md`](PLAN_IMPORTACIONES_ADMIN_V3.md), informe único [`docs/modulos/importaciones.md`](modulos/importaciones.md).
- **Ventana 1 AUTORIZADA** y reservada solo a ese chat: `app/Views/pages/admin/imports/{index,view}.php`, `app/Views/partials/admin/imports/**`, `public_html/assets/js/admin/imports/preview.js`, **nuevo** `public_html/assets/css/admin-v3/pages/imports.css`, contratos/smokes focales de Importaciones y `docs/modulos/importaciones.md`. `ImportsIndexViewModel`, `ImportsViewViewModel`, `ImportsViewPresenterService` solo para estado y datos visuales estrictamente necesarios, documentando el alcance.
- **No autorizados en Ventana 1:** `ImportsController.php`, `app/Services/Import/**`, `app/Repositories/Imports/**`, SQL/DB, importadores Catalog/Variants/SEO/Marcas/Categorías/Atributos, `head.php`, `app.js`, `gate.php`, rutas y shell compartidos. Un refactor de negocio requiere ventanas futuras. No usar `git add .`, ni commits/push desde el chat especializado.
- **Concurrencia:** Categorías conserva su Ventana 4 sobre `CategoriesController` y servicios Categorías; Marcas sigue pendiente de aceptación visual, Atributos pendiente P0/visual. Importaciones no modifica sus archivos ni ejecuta pruebas de escritura sobre base comercial. Su suite aislada no debe coincidir con ejecuciones pesadas de otros chats sin coordinación.
- Criterio visual obligatorio: Dashboard/Pedidos + `docs/GUIA_VISUAL_ADMIN_V3.md`; no aprobación automática por passing tests. La V1 finaliza con entrega para **revisión visual del propietario**, no habilita Ventana 2 por sí misma.

### Actualización 2026-10-21 — Categorías Ventana 5 (auditoría pública, SOLO LECTURA)

- Ventana 4 **cerrada técnicamente** según `docs/modulos/categorias.md` §16: 28/28 contrato reejecutado por principal. Deuda separada: filesystem ante caída abrupta y `SeoSlugHistoryRepository` global.
- **Ventana 5 autorizada exclusivamente como investigación pública de solo lectura** al chat de Categorías. Puede leer rutas/controladores/repositorios/vistas públicas, SEO, sitemap y settings; puede ejecutar GET/SELECT o ensayos sobre una copia MySQL descartable. **Solo puede EDITAR `docs/modulos/categorias.md`**. Ningún otro archivo del proyecto está reservado para escritura en V5.
- Quedan liberadas al terminar V4 las reservas de edición anteriores de `CategoriesController::save()` y `CategorySaveService.php`; ello **no** autoriza a otros chats a editarlos sin nueva reserva. Importaciones continúa Ventana 1, Marcas trabajo visual exclusivo, Atributos pendiente QA/P0.
- El propietario deberá decidir política pública y tratamiento de productos/enlaces tras el informe, antes de permitir cambios en catálogo, SEO, sitemap, rutas o migraciones. Ver `docs/DECISIONES_CATEGORIAS_FASE3.md` (última adenda).

### Importaciones — Ventana 1B de QA y refinamiento autorizada (2026-10-21)

- V1 completada técnicamente por chat de Importaciones; principal reejecutó **68/68 contrato** y `node --check`; examinó capturas sintéticas. **No hay aprobación estética final** ni refactor de backend autorizado.
- **V1B al mismo responsable**: conservar reserva de vistas/parciales solo Importaciones, `public_html/assets/css/admin-v3/pages/imports.css`, `public_html/assets/js/admin/imports/preview.js`, sus ViewModels/presenter solo para presentación, contrato/tests focales y `docs/modulos/importaciones.md`. Pruebas HTTP autenticadas únicamente con fixtures/BD descartable; NO tocar datos reales.
- Ver `docs/PLAN_IMPORTACIONES_ADMIN_V3.md` (adenda V1B) para suite visual/funcional; comparar con shell real Dashboard/Pedidos, 363/390/768/1024/1600. Sin edición de ImportsController, motor, repositories, productos, SEO, otras apps, CSS global, head.php, app.js, gate.php o Git.
- V2 y V3 siguen **NO autorizadas**. Categorías está en Ventana 5 de auditoría pública (solo lectura); su informe no se solapa con esta reserva.

### Actualización 2026-10-21 — Categorías Ventana 6A, alcance técnico acotado

- Ventana 5 terminó en auditoría §17 de `docs/modulos/categorias.md`: 54 GET aislados, sin alteraciones comerciales. Resolución técnica V5-D1: categorías visibles solo si nodo y **todos** los ancestros están activos. **Decisión de productos B1/B2 reservada al propietario.**
- **Reserva EXCLUSIVA para Categorías V6A:** nuevo `app/Services/Catalog/CategoryPublicVisibilityService.php`; `app/Repositories/Catalog/CategoryRepository.php` (solo read de categorías/featured); `app/Services/Catalog/FacetService.php` (solo árbol de categorías); `app/Controllers/Catalog/{CategoryController,CategoriesController}.php` (solo landing/directorio); contratos/scripts focales nuevos `categories_public_*` y `docs/modulos/categorias.md`. Ver adenda V6A en `docs/DECISIONES_CATEGORIAS_FASE3.md`.
- Sin permiso para modificar ProductRepository/ProductsController/ProductController, precios, stock, settings, Home services, archivos de Importaciones, servicios SEO, sitemap/MetaController, public_html/index.php, HTML/CSS/JS, head/layout, rutas, DB/migraciones, gate global o Git. Sin correcciones de SEO/robots globales hasta reserva futura; las pruebas V6A deben aislarse en MySQL sintético.
- El código público V6A será **parcial y no se publica como corrección terminada** antes de consumidores de Productos/SEO y QA. Importaciones conserva Ventana 1B; Marcas y Atributos sus propias reservas.

### Actualización 2026-10-21 — Importaciones Ventana 1C: hotfix P0 rollback

- V1B terminada técnicamente con E2E autenticado aislado. Principal reejecutó `71/71` de contrato y examinó capturas de la app con shell real; **aprobación visual definitiva pendiente**.
- **P0 confirmado:** importar 2000→2480, editar manualmente 2499, revertir deja 2000 porque `products.updated_at` no existe y `detectConflicts` omite null. Formato snapshot v2 solo trae `before`.
- **Reserva exclusiva V1C al chat Importaciones:** `app/Services/Import/{ImportRevertService,ImportService}.php`, `app/Repositories/Imports/ImportRevertRepository.php`, `app/Services/Admin/Imports/ImportsViewPresenterService.php` solo elegibilidad de rollback, `app/Views/partials/admin/imports/view/notices.php` solo aviso si procede, contratos/pruebas de rollback exclusivamente Importaciones y `docs/modulos/importaciones.md`. Ver contrato completo en `docs/PLAN_IMPORTACIONES_ADMIN_V3.md` (adenda V1C).
- **Fail-closed:** no overwrite/delete automático de snapshots sin postestado validable (incluidos legacy); comparar tras locks y en misma transacción; compatibilidad de versión y UI simultánea, LIFO seguro. Prohibido tocar `ImportsController`, importadores de catálogo, Servicios/Repositories compartidos, HTML/CSS/JS general, schema/DB comercial o Git. Si se requiere ampliar, solicitar reserva; no improvisar.
- V2 refactor general y V3 integridad general **permanecen cerradas**. Categorías está en su V6A en otros archivos; evitar solapar gateways pesados.

### Actualización 2026-10-21 — Categorías V6B-1: sitemap de categorías y buscador móvil

- V6A completada técnicamente y contrato reejecutado por principal: **18/18 público, 28/28 Admin**; `docs/modulos/categorias.md` §18. Falta coherencia de sitemap y chips con 404 jerárquicos.
- **V6B-1 autorizada SOLO chat Categorías:** `app/Controllers/System/MetaController.php` **únicamente `sitemapCategories()`**, `app/Views/partials/header/_mobile_search_sheet.php` **únicamente bloque SQL/PHP de selección de categorías**, `CategoryRepository.php` **solo nuevo helper público read si necesario**, ajustes mínimos justificados en `CategoryPublicVisibilityService.php`, `scripts/build/categories_public_visibility_contract.php`, scripts focales `categories_public_*` y `docs/modulos/categorias.md`.
- Ninguna edición de sitemapProducts, Productos, SEO global, redirects/entrypoint, head/404, Home services, settings, CSS/JS, otros módulos, DB habitual ni Git. Los archivos MetaController y header parcial son **compartidos**: reservar sólo esos métodos/bloques y no permitir a otros chats cambios simultáneos.
- La decisión comercial productos B1/B2 **no fue ratificada**; V6B-2 de Productos y V6C SEO todavía NO autorizadas. Ver adenda `docs/DECISIONES_CATEGORIAS_FASE3.md`.

### Actualización 2026-10-21 — B1 ratificada y Ventana 6B-2 PREPARADA

- **Decisión comercial DEFINITIVA aprobada por propietario:** **B1**, documentada en `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md`. OFF mantiene disponibles productos activos de ramas ocultas; ON los excluye de TODA superficie pública y compra. Sin cambios de `products.is_active` ni flags/categorías comerciales. Los textos antiguos «B1/B2 pendiente» son históricos, **ya no vigentes**.
- **Ventana 6B-1 continúa como única ventana de implementación Categorías autorizada ahora.** No tocar productos hasta el cierre técnico y reserva liberada de V6B-1.
- **Ventana 6B-2 PREPARADA, NO AUTORIZADA A EJECUTAR:** futura reserva temporal de ProductPublicVisibilityService (nuevo), `ProductRepository` (solo SELECT público), `ProductsController`, `ProductController`, `QuickAddController`, `CategoryController` (solo selectores de productos), `RecommendedProductsService`, `HomePageViewService`, `ProductReadRepository` (solo lectores públicos) y contratos especializados, según `docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md`. **Aprobar expresamente el inicio tras verificar el cierre V6B-1 y el estado de archivos.**
- **Ventanas posteriores SIN RESERVA/NO AUTORIZADAS:** 6B-3 Cart/Checkout (POST y carritos existentes), 6C SEO/head, sitemap Productos y redirects históricos. 6B-2 aislada no equivale a tienda segura/lista para producción.
- Marcas, Atributos e Importaciones conservan sus permisos por módulo. No hay permiso para reescribir CSS global, JS, routes, schema, BD habitual, datos comerciales ni Git desde especialistas.

### Actualización 2026-10-21 — Categorías V6B-2 AUTORIZADA

- V6B-1 CERRADA técnicamente: §19 de `docs/modulos/categorias.md`; principal reejecutó **24/24 público y 28/28 Admin**. Reservas de edición de sitemap categorías y chips móviles LIBERADAS; no retocarlos en V6B-2.
- **B1 comercial definitiva** (`docs/POLITICA_VISIBILIDAD_PRODUCTOS_B1.md`): OFF productos activos continúan visibles/vendibles pese a rama oculta; ON se excluyen de todas las superficies públicas y compra. Sin cambios de flags, stock, precios ni pedidos.
- **Ventana 6B-2 exclusivamente chat Categorías:** nuevo `ProductPublicVisibilityService.php`; `ProductRepository.php` solo consultas públicas; `ProductsController.php` index/suggest; `ProductController.php` guard/breadcrumb; `QuickAddController.php` GET; `CategoryController.php` solo listado productos; `FacetService.php` conteos públicos; `RecommendedProductsService.php` visibilidad; `HomePageViewService.php` tarjetas públicas; nuevos contratos/pruebas focales y `docs/modulos/categorias.md`. Reservas detalladas en última adenda `docs/DECISIONES_CATEGORIAS_FASE3.md`.
- **Prohibido editar** `ProductReadRepository` usado por carrito, `CategoryRepository`, SEO/sitemaps, mobile sheet, Cart/Checkout/Payment, Settings, código de Importaciones, CSS/HTML/JS, rutas, migraciones, DB habitual o Git. Si falta permiso pedirlo. **V6B-3** (compra) y **V6C** (SEO) NO abiertas. No considerar listo para producción al cerrar V6B-2.
