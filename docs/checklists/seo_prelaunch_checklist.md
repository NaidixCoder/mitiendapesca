# SEO Pre-Lanzamiento

Objetivo: cerrar lo minimo necesario de SEO antes de publicar, sin desviar demasiado tiempo de trabajo a cambios de bajo impacto.

## Prioridades

1. Ficha de producto (`/producto?slug=...`)
2. Landing de categoria (`/categoria?slug=...`)
3. Landing de marca (`/marca?slug=...`)
4. Listado general (`/productos`)

## Lo que ya esta resuelto

- `head.php`
  - `title`
  - `meta description`
  - `canonical`
  - `robots`
  - Open Graph
  - Twitter Card
  - JSON-LD global
- `SeoFrontService`
  - SEO de producto
  - SEO de categoria
  - SEO de marca
- `breadcrumbs.php`
  - `BreadcrumbList` JSON-LD
- canonicalizacion de listados/filtros legacy

## Antes de produccion: tareas obligatorias

### 1. Ficha de producto

- Migrar `app/Views/pages/catalogo/producto.php` a clases V2
- Migrar partials de producto que siguen en utilitarias/legacy:
  - `app/Views/partials/producto/title-price.php`
  - `app/Views/partials/producto/buy-box.php`
  - `app/Views/partials/producto/long-desc.php`
  - `app/Views/partials/components/related-products.php`
- Revisar copy visible en PDP:
  - titulo claro
  - descripcion corta util
  - bloque de compra sin texto demo
- Quitar o corregir textos placeholder:
  - `3 y 6 cuotas sin interes (demo)`
- Revisar que la descripcion larga tenga contenido real en productos principales
- Confirmar schema de producto correcto:
  - precio
  - disponibilidad
  - imagen
  - url canonica

### 2. Categorias

- Completar `seo_text` real en categorias con potencial de busqueda
- Completar `bullets/specs/faq` solo en categorias importantes
- Revisar que el copy no sea generico ni repetido entre categorias
- Priorizar categorias con mas valor comercial o volumen:
  - señuelos
  - cañas
  - reels
  - accesorios

### 3. Marcas

- Completar `seo_text` real en marcas relevantes
- No invertir demasiado en marcas con poca busqueda
- Mantener foco en marcas que la gente realmente busca

### 4. Listado general `/productos`

- Mantener `seo-text.php` como soporte
- No sobreinvertir tiempo ahi
- Asegurar solo:
  - texto limpio
  - clases migradas
  - coherencia visual

## Auditoria PDP: legacy/Tailwind pendiente

### `app/Views/pages/catalogo/producto.php`

Sigue usando utilitarias:

- `container`
- `mx-auto`
- `px-3`
- `sm:px-4`
- `lg:px-6`
- `py-6`
- `grid`
- `grid-cols-1`
- `lg:grid-cols-2`
- `gap-6`
- `space-y-6`

### `app/Views/partials/producto/title-price.php`

Sigue mezclando clases legacy/utilitarias:

- `space-y-2`
- `text-2xl`
- `sm:text-3xl`
- `leading-tight`
- `flex`
- `items-end`
- `gap-3`
- `flex-wrap`
- `text-sm`
- `text-gray-400`
- `line-through`
- `pb-1`
- `text-xs`
- `text-gray-500`
- `text-amber-500`
- `text-gray-700`

Ademas:

- tiene simbolos rotos/mojibake
- renderiza estrellas placeholder `(0)`

### `app/Views/partials/producto/buy-box.php`

Sigue muy mezclado:

- `p-4`
- `sm:p-5`
- `space-y-4`
- `space-y-3`
- `flex`
- `items-center`
- `justify-between`
- `text-xs`
- `text-slate-500`
- `sr-only`
- `hidden`
- `text-sm`
- `text-gray-600`
- `space-y-1`

Ademas:

- copy demo de cuotas
- logica de UI importante convive con markup

### `app/Views/partials/producto/long-desc.php`

Sigue en utilitarias:

- `mt-10`
- `rounded-2xl`
- `border`
- `border-gray-200`
- `bg-white`
- `p-5`
- `sm:p-6`
- `text-lg`
- `sm:text-xl`
- `font-semibold`
- `mb-3`
- `prose`
- `prose-sm`
- `sm:prose-base`
- `max-w-none`
- `text-gray-700`

### `app/Views/partials/components/related-products.php`

Sigue en utilitarias:

- `mt-10`
- `text-xl`
- `font-semibold`
- `mb-4`
- `grid`
- `grid-cols-2`
- `sm:grid-cols-4`
- `lg:grid-cols-6`
- `gap-3`

## Tareas SEO recomendadas pero no obligatorias para salir

- Agregar modulo real de reseñas y valoraciones
- Activar `mejor_valorado` cuando haya datos reales
- Simulador de financiacion en PDP
- Mejorar FAQ schema en producto/categoria

## Tareas a no hacer antes de salir

- Cuotas reales por card consultando Mercado Pago
- Reescrituras grandes de SEO sobre `/productos`
- Sobreoptimizar marcas menores

## Criterio de salida

Antes de publicar, deberia quedar cerrado:

- PDP visualmente limpia y sin textos demo
- Categoria y marca con copy SEO real en las landings importantes
- Canonical/meta/schema funcionando
- Sin mojibake en contenido visible
- Sin bloques legacy evidentes en las pantallas principales de catalogo
