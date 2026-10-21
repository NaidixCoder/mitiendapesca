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
