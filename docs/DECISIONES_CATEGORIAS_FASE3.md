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
