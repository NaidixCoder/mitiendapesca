# Estructura CSS V2 (Guia Oficial)

## Objetivo
Tener un sistema de estilos escalable, vendible y facil de mantener:
- una sola entrada (`index.css`),
- tokens como fuente de verdad,
- componentes reutilizables,
- overrides por pagina bien acotados.

## Principios
1. Single source of truth: colores, radios, sombras y spacing viven en `tokens.css`.
2. Reuso primero: si una regla sirve en 2+ pantallas, va a `components/`.
3. Paginas minimas: `pages/` solo contiene ajustes puntuales por modulo/pantalla.
4. Nada de nombres temporales (`lote_a`, `tmp`, etc.) en estado final.
5. Evitar `!important` (solo excepcion justificada y documentada).

## Estructura objetivo

```txt
public_html/assets/css/v2/
  index.css
  tokens.css
  base.css
  utilities.css

  components/
    ui.css
    ui-admin.css
    buttons.css
    forms.css
    cards.css
    tables.css
    badges.css

  layouts/
    admin-shell.css
    public-shell.css

  pages/
    admin/
      dashboard.css
      orders.css
      products.css
      attributes.css
      settings.css
      audit.css
      marketing.css
      billing.css
    public/
      home.css
      catalog.css
      product.css
      checkout.css
      account.css

  themes/
    default.css
```

## Estado actual y mapping
- `components/ui.css` es el entry de componentes reutilizables.
- `layouts/admin-shell.css` es el layout shell admin.
- `pages/admin_lote_a.css`, `components/generic.css` y `pages/admin_shell.css` fueron eliminados.

## Que va en cada archivo

### `tokens.css`
- Solo variables (`--v2-*`).
- Se permiten valores literales (hex/rgb/hsl) solo aqui.
- Ejemplos: `--v2-color-primary`, `--v2-color-accent`, `--v2-color-border`, `--v2-shadow-sm`.

### `base.css`
- Reset suave y tipografia base.
- Estilos neutrales globales.

### `utilities.css`
- Utilidades chicas y atomicas (`.ui-grid`, `.ui-stack`, etc.).

### `components/ui.css`
- Componentes transversales (public + admin): botones, cards, badges, campos base, tabla base.

### `components/ui-admin.css`
- Reutilizable solo para admin (headers de modulo, densidad, patrones admin comunes).

### `layouts/admin-shell.css`
- Sidebar, offsets, centrado general del contenido admin.
- Reglas globales de layout (`admin-surface`, `admin-wrap`).

### `pages/admin/*.css`
- Solo ajustes de cada pantalla admin.
- No duplicar componentes aqui.

## Orden de imports (obligatorio)

`index.css` debe respetar este orden:
1. `tokens.css`
2. `base.css`
3. `components/ui.css`
4. `components/ui-admin.css`
5. `utilities.css`
6. `layouts/*.css`
7. `pages/*`
8. `themes/*` (si aplica)

## Reglas de colores y temas
1. No usar `#xxxxxx` directo fuera de `tokens.css`.
2. En componentes/paginas usar solo `var(--v2-...)`.
3. Si hace falta derivar tono: `color-mix(... var(--v2-...))`.
4. Para rebrand: cambiar palette en `tokens.css` (y opcionalmente en `themes/default.css`).

## Convencion de nombres
- Prefijo reusable: `ui-*`.
- Prefijo admin reusable: `admin-*`.
- Evitar nombres por etapa: `lote`, `vtemp`, `nuevo2`, etc.

## Politica de migracion (paso a paso)
1. Crear/mover componente reusable a `components/`.
2. Reemplazar clases en vistas del modulo.
3. Dejar en `pages/` solo lo puntual.
4. Eliminar imports/archivos deprecados cuando quede sin uso.

## Checklist por cambio CSS
- [ ] No se agregaron literales de color fuera de `tokens.css`.
- [ ] No se agregaron `!important` sin justificacion.
- [ ] No se duplico una regla reusable en `pages/`.
- [ ] Se mantiene `scripts\\build_gate.bat` en verde.
- [ ] Desktop + mobile sin regresiones visuales evidentes.

## Proximo ajuste recomendado
1. Continuar migracion por lotes hacia `pages/admin/*` y `pages/public/*`.
2. Agregar enforce en gate para bloquear nuevos archivos `*_lote_*` o `*_tmp_*`.

## Deuda tecnica activa
- `layouts/admin-shell.css` mantiene `!important` en reglas de compatibilidad/cascada del sidebar (`.nav-admin` y color de links).
- Motivo: evitar regresiones durante la migracion de vistas legacy.
- Salida planificada: quitar `!important` al cerrar la migracion de layout admin y subir especificidad con selectores v2 consistentes.
