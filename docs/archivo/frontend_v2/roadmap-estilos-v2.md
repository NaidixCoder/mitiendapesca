# Roadmap Estilos V2 (Objetivo Final)

## Principios obligatorios
- V2 es la unica arquitectura de estilos del proyecto.
- `tokens.css` es la unica fuente de verdad para colores, spacing, radios, sombras y tipografia.
- Regla estricta: **nada hardcodeado** en componentes/paginas (`#hex`, `rgb/rgba`, medidas sueltas fuera de tokens, etc.).
- No mezclar legacy con v2 en nuevas implementaciones.

## Estructura final CSS (V2)
`public_html/assets/css/v2/`

- `public.css` (entrypoint publico)
- `admin.css` (entrypoint admin)
- `tokens.css` (variables globales unicas)
- `base.css` (reset, tipografia, elementos base)
- `utilities.css` (helpers utilitarios)
- `components/`
  - `ui.css` (botones, inputs, badges, cards reutilizables)
  - `ui-admin.css` (componentes reutilizables admin)
  - `table.css` (tabla reutilizable)
  - `forms.css`
- `layouts/`
  - `public-header.css`
  - `public-footer.css`
  - `admin-shell.css`
- `pages/`
  - `public/` (`home.css`, `catalog.css`, `product.css`, `checkout.css`, etc.)
  - `admin/` (`dashboard.css`, `orders.css`, `facturacion/*.css`, etc.)

## Regla de carga
- Eliminar dependencia de legacy:
  - `app.css`
  - `brand.css`
  - `aside.css`
  - `product.css`
- Todo estilo debe vivir en `v2/*`.

## Carga desde `head.php`
Carga por contexto:

```php
<?php if ($isAdminPage): ?>
<link rel="stylesheet" href="<?= e(asset('css/v2/admin.css')) ?>">
<?php else: ?>
<link rel="stylesheet" href="<?= e(asset('css/v2/public.css')) ?>">
<?php endif; ?>
```

## Orden de imports base (comun)
1. `tokens.css`
2. `base.css`
3. `utilities.css`
4. `components/*`

## Orden de imports en `v2/public.css`
1. Base comun
2. `layouts/public-header.css`
3. `layouts/public-footer.css`
4. `pages/public/*` (ordenado por dominio/ruta)

## Orden de imports en `v2/admin.css`
1. Base comun
2. `layouts/admin-shell.css`
3. `pages/admin/*` (dashboard, orders, facturacion, etc.)

## Definicion de cierre
- No quedan estilos activos fuera de `v2/*`.
- No quedan hardcodes visuales fuera de `tokens.css`.
- Todas las vistas publicas renderizan con `v2/public.css`.
- Todas las vistas admin renderizan con `v2/admin.css`.
