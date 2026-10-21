# Home secciones administrables - roadmap 2 etapas

## Objetivo
Eliminar hardcodes en Home para bloques de productos y mover la configuracion al panel Admin con persistencia en `app_settings`.

## Etapa 1 - Destacados + Nuevos ingresos administrables

### Alcance
- Nuevo ajuste en Admin: `Secciones Home`.
- Configuracion de dos secciones fijas:
  - `featured` (Destacados)
  - `new_arrivals` (Nuevos ingresos)
- Campos por seccion:
  - `enabled` (on/off)
  - `title`
  - `badge`
  - `product_ids` (seleccion manual desde catalogo)
- Persistencia en `app_settings` como JSON (`home_sections_config`).
- Home publico consume ese JSON y renderiza con partials existentes:
  - `/partials/home/featured-grid`
  - `/partials/home/new-arrivals-grid`

### Criterios de salida
- Guardar cambios desde Admin sin errores.
- Home refleja dinamicamente productos/titulos/badges guardados.
- Sin arrays hardcodeados de productos en la vista Home.

## Etapa 2 - Secciones dinamicas N (misma plantilla)

### Alcance
- Evolucionar `home_sections_config` para soportar lista ordenada de bloques:
  - `[{ key, template, enabled, title, badge, product_ids, sort_order }]`
- Reusar una misma plantilla base de grid (evitando duplicar vistas por seccion).
- Permitir crear/duplicar/reordenar secciones desde Admin.
- Mantener compatibilidad con payload de Etapa 1 mediante migracion blanda en lectura.

### Criterios de salida
- N secciones configurables sin tocar codigo.
- Render ordenado por `sort_order`.
- Estructura extensible para futuros templates (`products_grid`, `banner`, `brands`, etc.).
