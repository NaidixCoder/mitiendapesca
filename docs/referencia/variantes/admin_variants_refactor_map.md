# ETAPA 2 - Admin Productos/Variantes (Mapa de Refactor)

## Medicion inicial (lineas)

- `app/Controllers/Admin/ProductsController.php`: 1569
- `app/Controllers/Admin/ProductVariantsController.php`: 741
- `public_html/assets/js/admin/product_variants.js`: 2425

## Endpoints de variantes/productos admin (scope)

| Endpoint | Handler | Owner service actual | Repos/SQL tocado | JS |
| --- | --- | --- | --- | --- |
| `GET /admin/products/variants` | `ProductVariantsController@listPage` | `VariantReadService@getData` | `AdminVariantReadRepository` (read model de variants + inventory + attrs) | `product_variants.js` |
| `GET /admin/products/variants.json` | `ProductVariantsController@index` | `VariantReadService@getData` | `AdminVariantReadRepository` | `product_variants.js` |
| `GET /admin/products/variants/fragment` | `ProductVariantsController@fragment` | `VariantReadService@getData` | `AdminVariantReadRepository` | `product_variants.js` |
| `GET /admin/products/variants/editor-fragment` | `ProductVariantsController@fragmentEditor` | `VariantReadService@getData` | `AdminVariantReadRepository` | `product_variants.js` |
| `POST /admin/products/variants/save` | `ProductVariantsController@saveVariant` | `VariantWriteService@saveVariant` (+ `VariantPricingService` / `VariantStockService`) | `product_variants`, `inventory_variants` | `product_variants.js` |
| `POST /admin/products/variants/toggle` | `ProductVariantsController@toggle` | `VariantLifecycleService@toggle` | `product_variants` | `product_variants.js` |
| `POST /admin/products/variants/delete` | `ProductVariantsController@deleteVariant` | `VariantLifecycleService@deleteVariant` | `product_variants`, `cart_items`, relaciones | `product_variants.js` |
| `POST /admin/products/variants/generate` | `ProductVariantsController@generate` | `VariantWriteService@generateVariants` + `VariantLifecycleService@cleanupObsoleteVariants` | `product_variants`, `product_variant_values`, `inventory_variants` | `product_variants.js` |
| `POST /admin/products/variants/cleanup-obsolete` | `ProductVariantsController@cleanupObsolete` | `VariantLifecycleService@cleanupObsoleteVariants` | variants + inventory + cart | `product_variants.js` |
| `POST /admin/products/variants/delete-incomplete` | `ProductVariantsController@deleteIncomplete` | `VariantLifecycleService@deleteIncompleteVariants` | variants + inventory + cart | `product_variants.js` |
| `POST /admin/products/variants/attributes/*` | `ProductVariantsController@attribute*` | `VariantAttributesService` | `product_attributes`, `product_variant_values` | `product_variants.js` |
| `POST /admin/products/variants/values/*` | `ProductVariantsController@values*` | `VariantAttributesService` | `product_attribute_values`, `attribute_values` | `product_variants.js` |
| `POST /admin/products/variants/sets/*` | `ProductVariantsController@*ValueSet*` | `VariantValueSetsService` | `variant_value_sets`, `variant_value_set_items` | `product_variants.js` |
| `POST /admin/products/update-iva.json` | `ProductsController@updateIvaJson` | `ProductPricingService@updateIvaAndReprice` | SQL de pricing/IVA centralizado en service de producto | `product_variants.js` |
| `POST /admin/products/image-upload` | `ProductsController@imageUpload` | `ProductImagesService@upload` | `product_images` + `ProductImageService` | `product_variants.js` |
| `POST /admin/products/image-delete` | `ProductsController@imageDelete` | `ProductImagesService@delete` | `product_images` + `ProductImageService` | `product_variants.js` |
| `POST /admin/products/image-cover` | `ProductsController@imageCover` | `ProductImagesService@cover` | `product_images` + `ProductImageService` | `product_variants.js` |
| `POST /admin/products/image-assign` | `ProductsController@imageAssign` | `ProductImagesService@assign` | `product_images` | `product_variants.js` |
| `POST /admin/products/image-bulk-assign` | `ProductsController@imageBulkAssign` | `ProductImagesService@bulkAssign` | `product_images` + `ProductImageService` | `product_variants.js` |
| `POST /admin/products/image-bulk-delete` | `ProductsController@imageBulkDelete` | `ProductImagesService@bulkDelete` | `product_images` + `ProductImageService` | `product_variants.js` |
| `POST /admin/products/image-sort` | `ProductsController@imageSort` | `ProductImagesService@sort` | `product_images` | `product_variants.js` |

## Responsabilidades detectadas

- Pricing: owner de persistencia en `VariantPricingService`; UI/calculo visual sigue en JS sin cambios.
- Stock: owner de persistencia/snapshot en `VariantStockService` (sin SQL en controller para este slice).
- Images: owner en `ProductImagesService` (controller orquesta endpoints image*).
- Attributes/Values: owner en `VariantAttributesService`.
- Lifecycle (toggle/delete/cleanup): owner en `VariantLifecycleService`.
- Value Sets (admin/import/create): owner en `VariantValueSetsService`.
- Bulk actions: en `product_variants.js` + endpoints de variantes.

## Duplicacion y riesgos actuales

- `VariantAttributesService` sigue sobredimensionado (todavia conserva metodos movidos por compat interna).
- `ProductsController` sigue grande en `form/save` pese a extraer slices de pricing/images.

## Slice migrado en este ciclo: STOCK + PRICING + ATTRIBUTES/VALUES/SETS

### Cambios aplicados

- Nuevo owner de stock:
  - `app/Services/Admin/Variants/VariantStockService.php`
- Nuevo owner de pricing:
  - `app/Services/Admin/Variants/VariantPricingService.php`
- Repositorio de stock extendido:
  - `app/Repositories/VariantInventoryRepository.php` (nuevo metodo `upsert`)
- Repositorio de pricing:
  - `app/Repositories/VariantPricingRepository.php` (metodo `updatePricing`)
- `VariantWriteService@saveVariant` delega persistencia de stock en `VariantStockService`.
- `VariantWriteService@saveVariant` delega persistencia de pricing en `VariantPricingService`.
- `ProductVariantsController::variantSnapshot` deja de consultar `inventory_variants` directo y usa `VariantStockService`.
- `ProductVariantsController::variantSnapshot` toma snapshot de pricing desde `VariantPricingService`.

### Cobertura del slice

- Escritura de stock/umbral en guardado de variante: migrada.
- Lectura de stock para auditoria de guardado (`before/after`): migrada.
- Escritura de pricing en guardado de variante: migrada.
- Lectura de pricing para auditoria (`before/after`): migrada.

### Campos exactos incluidos en Pricing slice (segun schema real)

- `product_variants.price`
- `product_variants.list_price`
- `product_variants.cost`
- `product_variants.margin_pct`
- `product_variants.price_updated_at` (actualizacion automatica en cambios de precio/lista)

### ETAPA 2.3 (Read Model GET) implementada

- Nuevo repositorio de lectura:
  - `app/Repositories/Admin/AdminVariantReadRepository.php`
- Nuevo owner de lectura:
  - `app/Services/Admin/Variants/VariantReadService.php`
- GET endpoints de variantes (`listPage`, `index`, `fragment`, `fragmentEditor`) migrados a `VariantReadService`.
- `VariantAdminService@getData` fue reemplazado por uso directo de `VariantReadService` en controllers y luego eliminado en ETAPA 2.7.

### Attributes/Values/Sets slice implementado (ETAPA 2.2)

- Owner de endpoints de variantes y sets:
  - `app/Services/Admin/Variants/VariantAttributesService.php`
- Controllers migrados a delegar en el owner:
  - `app/Controllers/Admin/ProductVariantsController.php`
    - `attributeAdd`
    - `attributeRemove`
    - `attributeRemovePreview`
    - `attributeUpdate`
    - `valuesSet`
    - `valuesCreate`
    - `valuesSuggestFragment`
    - `createValueSetFromProduct`
    - `importValueSet`
  - `app/Controllers/Admin/AttributesController.php`
    - `index` (listado de sets)
    - `valueSetItemsFragment`
    - `valueSetDeleteJson`
    - `valueSetEditFragment`
    - `valueSetSaveJson`

Tablas del slice attributes/values/sets (schema real detectado):
- `product_attributes`
- `product_attribute_values`
- `product_variant_values`
- `attribute_values`
- `attributes`
- `variant_value_sets`
- `variant_value_set_items`

Endpoints de rutas afectados (sin cambios de path/UX):
- `POST /admin/products/variants/attributes/add`
- `POST /admin/products/variants/attributes/remove-preview`
- `POST /admin/products/variants/attributes/remove`
- `POST /admin/products/variants/attributes/update`
- `POST /admin/products/variants/values/set`
- `POST /admin/products/variants/values/create`
- `GET /admin/products/variants/values/suggest-fragment`
- `POST /admin/products/variants/sets/import`
- `POST /admin/products/variants/sets/create`

### ETAPA 2.2b (owner real, sin facade)

- `VariantAttributesService` ahora implementa lógica real del slice y no referencia `VariantAdminService`.
- `ProductVariantsController@generate` usa `VariantAttributesService::setAttributeValues` para la persistencia de selección previa a generar.
- En `VariantAdminService` los métodos públicos del slice attributes/values/sets fueron degradados a helpers privados para evitar uso externo nuevo.

## Arquitectura objetivo ETAPA 2 (plan)

- Carpeta target: `app/Services/Admin/Variants/`
- Owners planeados:
  - `VariantPricingService` (implementado en este ciclo)
  - `VariantStockService` (implementado en este ciclo)
  - `VariantAttributesService` (implementado en ETAPA 2.2b, owner real)
  - `VariantReadService` (implementado en ETAPA 2.3, owner de lecturas GET)
  - `ProductImagesService` (implementado en ETAPA 2.4B, owner de imagenes)
  - `VariantBulkService` (pendiente, si acciones masivas siguen creciendo)

### ETAPA 2.4 (inicio Products slice) implementada

- Nuevo owner en producto:
  - `app/Services/Admin/Products/ProductPricingService.php`
- `ProductsController@updateIvaJson` ahora solo orquesta y delega en `ProductPricingService`.
- Se mantuvieron ruta y contrato JSON del endpoint `POST /admin/products/update-iva.json`.

### ETAPA 2.4B (Products Images) implementada

- Nuevo owner en producto:
  - `app/Services/Admin/Products/ProductImagesService.php`
- `ProductsController` delega los endpoints de imagenes:
  - `imageUpload`
  - `imageDelete`
  - `imageCover`
  - `imageAssign`
  - `imageBulkAssign`
  - `imageBulkDelete`
  - `imageSort`
- Se mantuvieron rutas HTTP y contratos JSON/redirect del flujo de imagenes.

### ETAPA 2.5 (VariantAdminService write/lifecycle) implementada

- Nuevo owner de escritura de variantes:
  - `app/Services/Admin/Variants/VariantWriteService.php`
- `ProductVariantsController` ya no usa `VariantAdminService` para endpoints de escritura/lifecycle:
  - `generate` y `saveVariant` -> `VariantWriteService`
  - `toggle`, `deleteVariant`, `cleanupObsolete`, `deleteIncomplete` -> `VariantLifecycleService`
- `ProductsController@form` usa `VariantReadService` para `variantsCtx` (sin depender de `VariantAdminService@getData`).

### ETAPA 2.6 (split de VariantAttributesService) implementada

- Nuevo owner de lifecycle:
  - `app/Services/Admin/Variants/VariantLifecycleService.php`
- Nuevo owner de sets:
  - `app/Services/Admin/Variants/VariantValueSetsService.php`
- `ProductVariantsController` migro lifecycle a `VariantLifecycleService`.
- `AttributesController` migro gestion de sets a `VariantValueSetsService`.
- `ProductVariantsController` migro `create/import set` a `VariantValueSetsService`.

### ETAPA 2.7 (eliminacion VariantAdminService) implementada

- `app/Services/Admin/VariantAdminService.php` eliminado del codigo productivo.
- Referencias a `VariantAdminService` en codigo productivo (`app/`, `config/`, `public_html/`): 0.
- Build gate reforzado para fallar si reaparece `VariantAdminService`.

### ETAPA 2.8 (JS modular de variantes) implementada

- Se modularizo el entrypoint de variantes sin cambiar UX/rutas:
  - `public_html/assets/js/admin/product_variants.js` -> wrapper de compat (`export { init } ...`)
  - `public_html/assets/js/admin/product_variants/index.js` -> owner del flujo actual
- Se crearon modulos base para separar responsabilidades:
  - `public_html/assets/js/admin/product_variants/api.js`
  - `public_html/assets/js/admin/product_variants/state.js`
  - `public_html/assets/js/admin/product_variants/events.js`
  - `public_html/assets/js/admin/product_variants/validators.js`
  - `public_html/assets/js/admin/product_variants/utils.js` (existente, mantenido)
- `app.js` no requirio cambios (se mantiene `./admin/product_variants.js` como punto de entrada estable).

### ETAPA 2.9 (ProductsController reduction) - slice FORM implementado

- Nuevo owner de armado de contexto del form:
  - `app/Services/Admin/Products/ProductFormService.php`
- `ProductsController@form` ahora solo orquesta y delega en:
  - `ProductFormService::buildContext($id, $_GET)`
- Build gate/smoke actualizado para incluir `ProductFormService`.

### ETAPA A+B (pasada consolidada) implementada

- `ProductsController@save` fue extraido a owner dedicado:
  - `app/Services/Admin/Products/ProductSaveService.php`
- `ProductsController` ahora solo orquesta en `save()` y delega en `ProductSaveService::handle()`.
- SQL de guardado y snapshots de producto/inventario movido a:
  - `app/Repositories/Admin/ProductWriteRepository.php`
- `ProductSaveService` mantiene orquestacion/validaciones y ya no ejecuta SQL directo.
- `VariantAttributesService` fue reducido eliminando bloques duplicados ya cubiertos por owners dedicados:
  - removidos metodos de sets (migrados definitivamente a `VariantValueSetsService`)
  - removidos metodos de lifecycle no dueños (cubiertos por `VariantLifecycleService`)
- JS de variantes:
  - se separaron utilidades de dinero a `public_html/assets/js/admin/product_variants/money.js`
  - se separo feedback/toast a `public_html/assets/js/admin/product_variants/feedback.js`
  - se separo quick-create de atributos a `public_html/assets/js/admin/product_variants/attr_quick.js`
- `public_html/assets/js/admin/product_variants/index.js` mantiene el flujo, pero con menos responsabilidad directa.

### Cierre ETAPA A (ajuste final del ciclo)

- `VariantAttributesService` fue reducido por debajo del umbral operativo:
  - `app/Services/Admin/Variants/VariantAttributesService.php` -> 565 lineas
  - extraccion interna a:
    - `app/Services/Admin/Variants/VariantAttributesLifecycleTrait.php`
    - `app/Services/Admin/Variants/VariantAttributesSelectionTrait.php`
- `product_variants/index.js` dejo de ser gigante y quedo como coordinador:
  - `public_html/assets/js/admin/product_variants/index.js` -> 132 lineas
  - `public_html/assets/js/admin/product_variants/bindings.js` centraliza binds/eventos existentes (sin cambios de UX/rutas/JSON).
- Gate y smoke actualizados para incluir archivos nuevos del slice.

### ETAPA C (controllers admin delgados) - avance parcial

- `AttributesController` redujo duplicacion del flujo de borrado de valores:
  - nuevo helper interno `deleteAttributeValueResult(...)` reutilizado por:
    - `valueDelete`
    - `valueDeleteJson`
- `ProductVariantsController` extrajo la auditoria transversal a owner dedicado:
  - `app/Services/Admin/Variants/VariantAuditService.php`
  - `saveVariant`, `toggle`, `deleteVariant`, `cleanupObsolete`, `deleteIncomplete` ahora delegan logging/snapshot en ese service.
- `ProductVariantsController` se dividio por responsabilidades en traits:
  - `app/Controllers/Admin/Concerns/VariantReadActions.php`
  - `app/Controllers/Admin/Concerns/VariantAttributeActions.php`
  - `app/Controllers/Admin/Concerns/VariantLifecycleActions.php`
  - `app/Controllers/Admin/ProductVariantsController.php` queda como shell + `auditService`.
- Resultado de tamano:
  - `ProductVariantsController`: de ~757 a ~17 lineas (metodos movidos a concerns).
- Se mantuvieron rutas, flashes y contratos JSON sin cambios.
- `AttributesController` tambien se dividio en concerns:
  - `app/Controllers/Admin/Concerns/AttributesCatalogActions.php`
  - `app/Controllers/Admin/Concerns/AttributesWriteActions.php`
  - `app/Controllers/Admin/Concerns/AttributesValueSetActions.php`
  - `app/Controllers/Admin/AttributesController.php` queda como shell.
- Resultado de tamano:
  - `AttributesController`: de ~608 a ~11 lineas (metodos movidos a concerns).
- ETAPA C cerrada: controllers admin del slice quedan delgados y orientados a orquestacion.

### ETAPA D (scripts y herramientas) implementada

- Reorganizacion por funcion:
  - Build:
    - `scripts/build/gate.sh`
    - `scripts/build/gate.bat`
    - `scripts/build/smoke.php`
  - Audit:
    - `scripts/audit/usage_checkout_cart.php`
  - Cron:
    - `scripts/cron/correo_tracking.php`
    - `scripts/cron/email_marketing.php`
  - Dev:
    - `scripts/dev/cleanup-auth-login-events.php`
    - `scripts/dev/email-marketing-runner.php`
    - `scripts/dev/loyalty-backfill.php`
    - `scripts/dev/scan-corruption.php`
- Compatibilidad mantenida con wrappers legacy:
  - `scripts/build_gate.sh`, `scripts/build_gate.bat`, `scripts/build_gate_smoke.php`
  - `scripts/usage_audit_checkout_cart.php`
  - `scripts/correo_tracking_cron.php`, `scripts/email_marketing_cron.php`
  - `tools/*.php` ahora wrappers hacia `scripts/dev/*`
- Gate y smoke actualizados para ejecutar rutas nuevas (`scripts/build/*`, `scripts/audit/*`) sin romper comandos viejos.

### Hotfixes post 2.7 (estabilidad UX)

- Menus de fila `...` en variantes:
  - corregido manejo de eventos en menu portalizado (`body`) para `toggle/delete`.
  - corregido cierre por click fuera sin romper acciones internas del menu.
- Modal de confirmacion en variantes:
  - cierre por backdrop y tecla `Escape`.
- Atributos > eliminar valor:
  - se mantiene bloqueo seguro por `VALUE_IN_USE` / `VALUE_IN_SET`.
  - se muestra uso por valor en grilla (productos/variantes/sets) para evitar fallo silencioso.
  - compat de schema: se removio dependencia de columna inexistente `attribute_values.is_active`.

### Subetapa A.1 (continuacion JS variants) - avance

- `public_html/assets/js/admin/product_variants/bindings.js`:
  - reducido de ~2013 a ~1181 lineas.
- Extracciones realizadas:
  - `public_html/assets/js/admin/product_variants/selection.js`
  - `public_html/assets/js/admin/product_variants/errors.js`
  - `public_html/assets/js/admin/product_variants/lifecycle_actions.js`
  - `public_html/assets/js/admin/product_variants/save_actions.js`
  - `createFeedback(...)` movido a `public_html/assets/js/admin/product_variants/feedback.js`
  - helpers `withId(...)` y `flashEl(...)` en `public_html/assets/js/admin/product_variants/utils.js`
  - helper `isMoneyField(...)` en `public_html/assets/js/admin/product_variants/money.js`
- Contratos/UX: sin cambios en rutas ni payloads; solo modularizacion interna.

## Plan proxima iteracion (sin implementar en este ciclo)

1. ETAPA C - Controllers admin delgados:
   - continuar bajando `ProductVariantsController` por handlers tematicos (read/write/lifecycle) sin cambiar contratos.
   - reducir `AttributesController` para acercarlo al objetivo (<300 lineas) manteniendo mismas rutas/respuestas.

