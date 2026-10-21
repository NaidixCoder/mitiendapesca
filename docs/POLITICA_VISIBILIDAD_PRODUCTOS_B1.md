# MiTiendaPesca — Política comercial B1 de visibilidad de Productos y Categorías

> **DECISIÓN COMERCIAL DEFINITIVA:** aprobada expresamente por el propietario el 2026-10-21.
> **Estado de implementación:** B1 aprobada como contrato; implementación integral pendiente. La Ventana 6B-1 de Categorías (sitemap categorías/chips) continúa con su autorización independiente. Ventana 6B-2 **PREPARADA, NO ABIERTA** hasta cierre técnico y liberación de reservas V6B-1; futuras Cart/Checkout/SEO exigirán reservas separadas. Fuente de investigación `docs/modulos/categorias.md` §§17–18.

## 1. Regla comercial vinculante

**A. Categorías:** una categoría es públicamente elegible solo cuando `categories.is_active = 1` para ella y **todos** sus ancestros, con camino existente y acíclico hasta raíz. Esta es una regla de **lectura**, no altera flags ni relaciones. Las categorías inelegibles no aparecen en páginas, facetas, directorio, home de categorías, buscador de categorías o sitemap; URL de landing responde HTTP 404.

**B. Productos:** siempre se exige `products.is_active = 1`. La opción persistente `catalog_hide_inactive_category_products` controla si el estado de las categorías afecta además a los productos:

| Opción existente | Producto activo ligado a categoría invisible por estado propio o de cualquier ancestro | Comportamiento |
|---|---|---|
| **OFF / 0** (valor actual en BD al momento de auditoría) | **Sigue disponible para la venta.** | Ficha HTTP 200, listado y búsqueda, sugerencias, relacionados, destacados/recomendaciones, QuickAdd, carrito y checkout, sitemap de producto si indexable, sin cambio de flag ni precio/stock. Nunca generar breadcrumb/enlace a landing categoría que devuelve 404: fallback a `/productos` o jerarquía visible válida sin inventar ruta. |
| **ON / 1** | **No está disponible públicamente para nueva compra.** | Excluir de fichas (HTTP 404), listados/filtros, precios agregados, búsqueda y sugerencias JSON/HTML, recomendados, home manual/YouTube, QuickAdd, agregar al carrito y confirmar checkout, y sitemap de productos. En carritos existentes, marcar no disponible y exigir corregir antes de confirmar (no eliminar silenciosamente). |

- **Independencia de estados:** cambiar la opción **nunca** debe cambiar `products.is_active`, `categories.is_active`, `category_id`, stock, precio, atributos ni pedidos ya creados. Un producto no se da de baja en Admin por ocultarse en web.
- **Sin nuevas opciones globales:** preservar flag y valor existente; no sustituir por B2 ni por ocultación parcial según canal. Cuando ON, todas las vías públicas de descubrimiento/compra deben coincidir. Cuando OFF, no anunciar como oculta una ficha visible.
- **Relaciones inválidas:** el esquema habitual obliga `products.category_id` con FK; si el dato está huérfano por corrupción histórica, bajo ON debe fallar cerrado para venta pública y registrarse diagnóstico; OFF no legitima reparaciones automáticas ni equivale a consentimiento para exponer registros corruptos. Caracterizar en pruebas aisladas y no tocar BD comercial.
- **Categorías vacías:** siguen siendo páginas elegibles HTTP 200 cuando la jerarquía está activa; la opción separada `catalog_show_empty_categories` controla su tarjeta en directorio, no la venta de productos ni el estado de la landing.
- **HTTP/SEO:** 404 real para categoría o producto no elegible, head noindex sin canonical engañoso a home; no 301 genérico a inicio ni 410 para desactivación reversible. Redirecciones históricas 301 solo hacia destino válido equivalente e indexable; sitemaps contienen solo URLs públicas e indexables. Reparación de 404/head y redirects permanece en ventana SEO exclusiva.
- **Historial y procesos administrativos:** pedidos pasados, comprobantes, búsqueda interna Admin, reportes de ventas, importaciones, devoluciones y auditoría no se ocultan ni alteran por esta regla pública.

## 2. Núcleo técnico y responsabilidades

**Reutilizar** `CategoryPublicVisibilityService` de V6A para todas las comprobaciones de categoría: no crear predicados distintos por controlador ni consultas N+1. Introducir, en fase autorizada, **una política pública de elegibilidad de producto** que combine `p.is_active`, flag y IDs de categoría visibles; cache del flag y conjunto por request; no usar esta política en escrituras/admin/importadores ni forzar exclusión de productos cuando OFF.

**Superficies a converger por etapas:**
- **V6B-1, ya autorizada:** sitemap de categorías y chips de categorías en móvil (sin política de productos).
- **V6B-2, PREPARADA:** fichas de Productos, `/productos` y `/categoria` (solo elegibilidad de productos de la landing), búsqueda JSON/fragment, facetas/conteos/priceBounds, QuickAdd GET, recomendados y home de productos. Alcance exclusivo de lectura/exhibición. Se necesita un método compartido que pueda alimentar todos esos consumidores con la misma política ON/OFF.
- **V6B-3, NO ABIERTA:** endpoints de **carrito** POST y **checkout/pagos/stock preflight** con política ON de no compra y revisión de carritos existentes, mediante reserva de Cart/Checkout/Payment y regresiones transaccionales. Importante: V6B-2 por sí sola **NO habilita producción**; un producto oculto no debe seguir comprándose por POST directo.
- **V6C, NO ABIERTA:** sitemap de Productos, canonical/robots/head 404, redirects históricos, JSON-LD y SEO compartido, con reserva explícita. La reparación del `SeoSlugHistoryRepository` global sigue separada.

## 3. Ventana 6B-2 — PLAN LISTO, NO AUTORIZACIÓN DE INICIO

**Disparador:** entrega documentada de V6B-1, revisión del chat principal y liberación de `MetaController::sitemapCategories()`, `_mobile_search_sheet.php` y posibles helpers `CategoryRepository`. No iniciar modificaciones antes de una **autorización expresa adicional del chat principal**, incluso con B1 aprobada.

**Reservas de escritura propuestas, sujetas a confirmación antes de abrir ventana:**
1. **Nuevo** `app/Services/Catalog/ProductPublicVisibilityService.php` o equivalente, únicamente elegibilidad pública de lectura con ON/OFF de Settings y reutilización del servicio de Categorías.
2. `app/Repositories/Catalog/ProductRepository.php`: **solo selectores de lectura pública** `buildCatalogWhere`/`paginate`/`priceBounds`/`searchPreview`/`findRelatedFill`/`findRelatedForCart`/`findVisibleByIdsOrdered` y `findQuickAdd` si necesario; **no** `insert/update/slug/stock` ni métodos compartidos de escritura usados por importadores/admin.
3. `app/Controllers/Catalog/ProductsController.php`, `ProductController.php`, `QuickAddController.php`: solo lectura pública/listado/suggest/ficha/QuickAdd, preservando retorno/status/contratos. No alterar legacy redirects SEO sin otra ventana.
4. `app/Controllers/Catalog/CategoryController.php` **solo filtro de elegibilidad de productos en landing visible**; no tocar guard de categorías ya implementado.
5. `app/Services/Catalog/RecommendedProductsService.php` **solo filtrado de resultados visibles al público**; preservar cálculos stock/precio y lógica recomendaciones.
6. `app/Services/Public/HomePageViewService.php` y `app/Repositories/Catalog/ProductReadRepository.php` **solo carga/selección de tarjetas públicas de Home/YouTube/manual**, sin cambiar contrato para carrito, Settings o Admin; si la lectura se comparte con Cart y no puede separarse de manera segura, crear API dedicada o detener y pedir reserva.
7. Pruebas focales nuevas `scripts/build/categories_product_visibility_contract.php`, `scripts/dev/categories_product_visibility_*.php`, informe `docs/modulos/categorias.md` únicamente. No añadir hooks UI/globales.

**Fuera de V6B-2:** `MetaController`/sitemap, `public_html/index.php`/redirects, SEO/head/404, Cart/Checkout/Payment POST, Settings, vistas/JS/CSS, archivos de Importaciones/Marcas/Atributos, schema/migraciones, datos comerciales y Git. En consumidores compartidos aplicar solo cambios de lectura pública, no mutar globalmente `findManyForCart()`/visibilidad de la administración.

**Criterios previos para autorizar:** comprobar estado y `git diff` de cada archivo objetivo, evitar escritura simultánea de otros chats, decidir si V6B-2 se divide en dos reservas cuando el alcance lo justifique. Nunca ejecutar gate simultáneo con Importaciones.

## 4. Pruebas mínimas y salida de 6B-2 (cuando se abra)

En **clon MySQL descartable**, con `SELECT DATABASE()` y dos variantes ON/OFF:
- Producto activo en categoría activa; directa inactiva; hija activa/padre apagado; nieta activa/abuelo apagado; categoría huérfana/cíclica **solo fixture**; producto inactivo; variantes del producto y categoría vacía.
- **OFF:** fichas/listas/sugerencias/relacionados/home/QuickAdd accesibles para producto activo en rama oculta, sin enlaces de breadcrumb a 404.
- **ON:** todos esos destinos excluyen/404 al mismo producto. JSON y fragment contienen iguales IDs y total/paginación/precios/rangos/facetas son coherentes; nunca se enlaza ficha 404 desde cards.
- Confirmar que las rutas de Categorías propias siguen 404 jerárquico con ON y OFF; no afectar marcas, tags, stock, precio ni count; preservar admin/importaciones.
- Prueba de rendimiento `EXPLAIN`/benchmark para volumen razonable, sin N+1; GET autenticado cuando corresponda con perfil aislado, y contrato/gate.
- Mantener **estado explícito de integración incompleta** hasta 6B-3 Cart/Checkout y 6C SEO: no anunciar listo para producción después de pasar los GET.

## 5. Fuente de verdad y cambios a la decisión

La aprobación expresa B1 del propietario prevalece sobre los textos históricos `B1/B2 pendiente` redactados durante V5/V6A/V6B-1; estos quedan como antecedentes del momento en que se escribieron. No reabrir decisión B1/B2 a menos que el propietario la modifique de forma explícita. Documento rector complementario: `docs/DECISIONES_CATEGORIAS_FASE3.md`.

## Estado actualizado del plan — 2026-10-21

**V6B-1 completada y validada técnicamente. V6B-2 ABIERTA con autorización expresa del chat principal**, exclusivamente en los archivos y métodos descritos en la última adenda de `docs/DECISIONES_CATEGORIAS_FASE3.md` y su reserva en `docs/COORDINACION.md`. Las menciones anteriores de «PREPARADA/NO ABIERTA» describían el estado antes del cierre de V6B-1 y han quedado superadas. V6B-3 Cart/Checkout y V6C SEO siguen **NO autorizadas**. B1 sigue siendo la decisión comercial definitiva.
