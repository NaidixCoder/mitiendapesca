# Catalog Sort Redesign

Objetivo:
- Reemplazar el `select` actual del orden del catálogo.
- Mobile: `bottom sheet`
- Desktop: `popover/dropdown custom`

## Alcance

Trabajar sobre:
- `app/Views/partials/catalogo/topbar.php`
- `public_html/assets/css/v2/pages/public/catalog.css`
- `public_html/assets/js/catalog/sort.js`
- `public_html/assets/js/app.js`

## Implementación

### 1. Reemplazar el `select` por un trigger custom

En `app/Views/partials/catalogo/topbar.php`:
- Dejar un botón visible con el label actual:
  - `Relevancia`
  - `Menor precio`
  - `Mayor precio`
  - `Novedades`
- Mantener un `input hidden name="orden"` con el valor actual.
- Renderizar las opciones desde `sortOptions`.

### 2. Desktop: popover / dropdown custom

- El mismo trigger abre un panel pequeño debajo del botón.
- Las opciones se renderizan como botones o items clickeables.
- La opción activa debe quedar marcada.
- Al elegir una opción:
  - actualizar el `hidden input`
  - submit del form

### 3. Mobile: bottom sheet

- El mismo trigger, en `mobile`, abre:
  - backdrop
  - sheet desde abajo
- El contenido es el mismo listado de opciones.
- Al elegir una opción:
  - actualizar el `hidden input`
  - submit del form

### 4. JS dedicado

Crear:
- `public_html/assets/js/catalog/sort.js`

Responsabilidades:
- abrir y cerrar el control
- detectar breakpoint `mobile/desktop`
- cerrar al click afuera
- cerrar con `Escape`
- cerrar con backdrop
- actualizar el valor seleccionado
- submit del form

### 5. CSS dedicado

Agregar en `catalog.css` clases nuevas tipo:
- `catalog-sort`
- `catalog-sort__trigger`
- `catalog-sort__label`
- `catalog-sort__chevron`
- `catalog-sort__popover`
- `catalog-sort__sheet`
- `catalog-sort__backdrop`
- `catalog-sort__option`
- `catalog-sort__option--active`

## Limpieza

Cuando el nuevo componente esté funcionando:
- eliminar estilos viejos de:
  - `catalog-toolbar__select`
  - `catalog-toolbar__select-wrap`
  - `catalog-toolbar__select-icon`

## Criterio de trabajo

- No seguir parchando el `select`.
- Reemplazarlo completamente.
- Mantener la lógica fuera de la vista.
- Reutilizar `sortOptions` y el form actual.

## Orden de ejecución

1. Reemplazar markup en `topbar.php`
2. Crear `catalog/sort.js`
3. Conectarlo en `app.js`
4. Agregar CSS para:
   - `bottom sheet` mobile
   - `popover` desktop
5. Limpiar CSS obsoleto del `select`
