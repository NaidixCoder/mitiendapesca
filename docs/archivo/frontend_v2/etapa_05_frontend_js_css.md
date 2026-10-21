# ETAPA 05 - Frontend JS y CSS

## Estado
En curso. JS 5.1 cerrado; CSS 5.2 en curso.

## Objetivo
Ordenar frontend por ownership y disenar una base CSS mantenible.

## Alcance
- public_html/assets/js
- public_html/assets/css

## Tareas
0. Composicion de vistas (precondicion de frontend):
   - Estandarizar `section(...)` como mecanismo unico en vistas.
   - Eliminar `include base_path(...)` para reducir acoples y facilitar refactors JS/CSS.
1. JS:
   - Consolidar ownership por pagina/modulo.
   - Evitar side-effects globales entre modulos (wizard vs variants, etc).
   - Definir contrato de eventos globales permitidos.
2. CSS:
   - Crear base:
     - css/tokens.css
     - css/base.css
     - css/components/*
     - css/pages/*
     - css/utilities.css
   - Migrar gradualmente desde brand.css/app.css sin romper UI.
3. Unificar colores/fuentes/spacing via tokens.
4. Eliminar estilos duplicados u overrides contradictorios.

## Avance aplicado
- `include base_path(...)` en `app/Views` reducido a `0` (migrado a `section(...)`).
- Se migraron:
  - `app/Views/pages/admin/proveedores/index.php`
  - `app/Views/pages/admin/proveedores/form.php`
  - `app/Views/pages/admin/marketing_email/index.php`
  - `app/Views/pages/admin/marketing_email/automations.php`
  - `app/Views/pages/admin/marketing_email/campaigns.php`
  - `app/Views/pages/admin/marketing_email/campaign_show.php`
  - `app/Views/pages/admin/marketing_email/campaign_form.php`
  - `app/Views/pages/admin/marketing_email/queue.php`
  - `app/Views/partials/admin/products/variants/sale_fragment.php`
  - `app/Views/partials/admin/products/variants/editor_fragment.php`
- Verificacion:
  - `scripts\\build_gate.bat` OK.

## Auditoria actual (2026-02-12)

### JS: estado real de ownership
- Entrypoint unico: `public_html/assets/js/app.js` (loader por `data-page` + fallbacks).
- Modulo variantes: owner real `public_html/assets/js/admin/product_variants/index.js` + submodulos.
- Wrappers legacy removidos en esta etapa:
  - `public_html/assets/js/admin/product_variants.js`
  - `public_html/assets/js/admin/product_images.js`
  - `public_html/assets/js/admin/dashboard.js`
  - `public_html/assets/js/admin/orders.js`
- Compatibilidad pendiente controlada:
  - `public_html/assets/js/admin/orders/show/index.js` como owner activo de detalle de pedidos.
  - `public_html/assets/js/admin/orders_show.js` solo wrapper legacy temporal.

### JS: archivos mas grandes (top)
- `public_html/assets/js/admin/marketing/campaign_editor/index.js` (~1174 lineas)
- `public_html/assets/js/admin/orders/show/index.js` (~389 lineas)
- `public_html/assets/js/admin/product_form/index.js` (~658 lineas)
- `public_html/assets/js/checkout/mp_payment_brick.js` (~562 lineas)
- `public_html/assets/js/admin/product_images/index.js` (~546 lineas)
- `public_html/assets/js/admin/product_variants/lifecycle_actions.js` (~359 lineas)
- `public_html/assets/js/admin/product_variants/pricing_bulk_actions.js` (~326 lineas)
- `public_html/assets/js/admin/product_variants/values_actions.js` (~300 lineas)

### CSS: estado real
- Hoja principal con mayor peso:
  - `public_html/assets/css/components/admin.css` (~1579 lineas)
- Otras hojas grandes:
  - `public_html/assets/css/system/design-system.css` (~367 lineas)
  - `public_html/assets/css/components/catalog-card.css` (~336 lineas)
  - `public_html/assets/css/aside.css` (~313 lineas)
  - `public_html/assets/css/brand.css` (~281 lineas)
- Carga actual desde `app/Views/partials/head.php`:
  - `app.css`, `brand.css`, `aside.css`, `product.css` (+ Tailwind CDN).

## ETAPA 5.1 (JS) - CERRADA
1. Loader `app.js` normalizado con paths canonicos por modulo.
2. Contratos por pagina consolidados (`dashboard`, `orders`, `orders-show`, `product-form`).
3. Campaign editor dividido en modulos reales (`template/html/card/modal/preview/picker/validation`).
4. Wrappers legacy minimizados y acotados a compatibilidad temporal.

### Avance ETAPA 5.1 aplicado
- `public_html/assets/js/app.js`:
  - `admin-orders-index` ahora carga `./admin/orders/index.js` (`initOrdersIndex`).
  - `admin-orders-show` ahora carga `./admin/orders/show/index.js` (`init`).
  - `admin-dashboard` ahora carga `./admin/dashboard/index.js` + `./admin/orders/panel.js`.
  - fallback admin de ordenes usa `callModule(...)` canonico (evita doble bind por rutas distintas).
  - fallback de `orders/show` usa `./admin/orders/show/index.js`.
  - `ensureVariantsModule` ahora carga `./admin/product_variants/index.js` (owner real).
  - se elimino el boot admin duplicado por URL y se consolidaron fallbacks/observers en un unico flujo principal (loader por `data-page` + fallbacks minimos).
- Archivos eliminados (wrappers legacy sin referencias):
  - `public_html/assets/js/admin/product_variants.js`
  - `public_html/assets/js/admin/product_images.js`
  - `public_html/assets/js/admin/dashboard.js`
  - `public_html/assets/js/admin/orders.js`
- Resultado: menos acoplamiento por alias de rutas y menor riesgo de inicializaciones duplicadas.
- Refactor adicional en Orders Show:
  - `public_html/assets/js/admin/orders/show/index.js` reducido de ~953 a ~389 lineas (owner real).
  - Extraccion a modulos:
    - `public_html/assets/js/admin/orders/show/copy.js`
    - `public_html/assets/js/admin/orders/show/fiscal.js`
    - `public_html/assets/js/admin/orders/show/shipping.js`
    - `public_html/assets/js/admin/orders/show/arca.js`
    - `public_html/assets/js/admin/orders/show/index.js` (owner real del detalle)
  - Se corrigio un bug de scope en debug inicial (`sel` fuera de `init`) y se normalizo `bindMarkPaid` con `carrierSel` local.
  - `public_html/assets/js/admin/orders/show.js` queda como entrypoint fino hacia el owner real.
  - `public_html/assets/js/admin/orders_show.js` queda como compat legacy temporal (wrapper de 5 lineas).
- Reorganizacion estructural adicional:
  - `public_html/assets/js/admin/product_form.js` -> `public_html/assets/js/admin/product_form/index.js`
  - `public_html/assets/js/admin/marketing/email_campaign_editor.js` -> `public_html/assets/js/admin/marketing/campaign_editor/index.js`
  - `public_html/assets/js/admin/marketing/email_campaign_editor.js.tmp` -> `public_html/assets/js/admin/marketing/_deprecated/email_campaign_editor.js.tmp`
  - `public_html/assets/js/admin/orders/show/{index,copy,fiscal,shipping,arca}.js` como estructura final del modulo.
  - Reordenamiento de archivos sueltos en `js/admin`:
    - `attributes.js` -> `attributes/index.js`
    - `audit_filters.js` -> `audit/filters.js`
    - `entity_seo_score.js` -> `seo/entity_score.js`
    - `product_seo_score.js` -> `seo/product_score.js`
    - `product_seo_blocks.js` -> `seo/product_blocks.js`
    - `products_wizard.js` -> `products/wizard.js`
    - `imports_preview.js` -> `imports/preview.js`
    - `settings_envios.js` -> `settings/envios.js`
    - `facturacion_reportes.js` -> `billing/reportes.js`
    - `facturacion_cierres_mensuales.js` -> `billing/cierres_mensuales.js`
  - `public_html/assets/js/admin/orders_show.js` eliminado (0 referencias externas).
  - Resultado: raiz `public_html/assets/js/admin` sin archivos sueltos.
- Extraccion en Campaign Editor:
  - `public_html/assets/js/admin/marketing/campaign_editor/template_actions.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/html_utils.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/card_builder.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/card_modal.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/preview_modal.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/product_picker.js`
  - `public_html/assets/js/admin/marketing/campaign_editor/form_validation.js`
  - `index.js` ahora importa esas piezas en vez de mantenerlas embebidas.
- Verificacion de cierre:
  - `scripts\\build_gate.bat` OK.
  - `campaign_editor/index.js` reducido a ~1174 lineas.

## Proximo corte recomendado (ETAPA 5.2)
1. CSS: crear base sin romper:
   - `css/tokens.css`
   - `css/base.css`
   - `css/components/*`
   - `css/pages/*`
   - `css/utilities.css`
2. Migrar gradualmente reglas desde `components/admin.css` hacia componentes/paginas con ownership explicito.
3. Evitar nuevas reglas globales en `brand.css`/`app.css` mientras dure la migracion.

### Avance ETAPA 5.2 aplicado
- Base CSS inicial creada:
  - `public_html/assets/css/system/tokens.css`
  - `public_html/assets/css/system/base.css`
  - `public_html/assets/css/system/utilities.css`
- `public_html/assets/css/app.css` ahora importa la base de sistema al inicio:
  - `system/tokens.css`
  - `system/base.css`
  - `system/utilities.css`
- Split de `public_html/assets/css/components/admin.css` en modulos:
  - `public_html/assets/css/components/admin/base.css` (~284 lineas)
  - `public_html/assets/css/components/admin/variants.css` (~616 lineas)
  - `public_html/assets/css/components/admin/ops.css` (~679 lineas)
  - `public_html/assets/css/components/admin.css` queda como agregador via `@import`.
- Alcance de este corte:
  - Solo foundation no disruptivo (sin cambios visuales intencionales en componentes/paginas).

## Criterio de cierre
- Pantallas criticas sin regresion visual.
- Menos acoplamiento cruzado JS/CSS.
- Build gate OK.

