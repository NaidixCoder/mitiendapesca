# Sistema de Tags de Catalogo - Etapa 2

## Objetivo

Agregar soporte para tags especificos por variante dentro del flujo actual de `Variantes`.

Esta etapa debe permitir casos como:

- producto: `Modalidad = Pejerrey`
- variante `3.60 m`: `Tecnica = Flote`
- variante `4.20 m`: `Tecnica = Flote`

Sin:

- alterar la generacion de combinaciones
- afectar SKU, stock o precio
- agregar steps nuevos al wizard


## Alcance

Entra en esta etapa:

- lectura de tags por variante
- guardado de tags por variante
- UI dentro de `Variantes`
- persistencia en `variant_catalog_tags`

No entra en esta etapa:

- cambios en el aside publico
- facetas publicas por tags
- chips activos en catalogo
- tags condicionales por categoria


## Base ya disponible

La etapa 1 ya dejo lista:

- `catalog_tag_groups`
- `catalog_tags`
- `product_catalog_tags`
- `variant_catalog_tags`

Tambien ya existe:

- flujo de `Variantes` restaurado
- `sale_fragment.php` como superficie real de trabajo
- `VariantReadService` y `VariantWriteService`


## Regla funcional

### Tags generales

Se cargan en `Basicos`.

Aplican al producto completo.

Ejemplo:

- `Modalidad = Pejerrey`


### Tags por variante

Se cargan en `Variantes`.

Aplican solo a una variante concreta.

Ejemplo:

- variante `3.60`
  - `Tecnica = Flote`
- variante `4.20`
  - `Tecnica = Flote`


### Regla de convivencia

Los tags por variante:

- no reemplazan necesariamente a los del producto
- complementan la clasificacion
- no generan combinaciones
- no participan en `product_variant_values`


## UX objetivo

La UX debe mantenerse simple.

No se debe abrir un submodulo enorme dentro de `Variantes`.

### Propuesta

Dentro de la tabla/editor de variantes:

- cada variante puede abrir un bloque chico `Tags`
- ese bloque muestra grupos y checkboxes
- el guardado ocurre junto con `Guardar variantes`

Opciones posibles:

1. bloque expandible por fila
2. drawer lateral por variante
3. modal liviano por variante

Recomendacion:

- usar bloque expandible por fila o drawer lateral

No usar:

- tab nuevo
- wizard interno
- matriz compleja


## Lectura

Se debe extender el read-model de variantes para incluir:

- todos los grupos/tags activos
- tags seleccionados por cada variante

Salida esperada:

- `variant_tag_catalog`
- `variant_tags_by_variant`

Esto debe prepararse en backend, no en la vista.


## Guardado

Al guardar variantes:

1. guardar datos actuales de variantes como hoy
2. leer estructura `variant_catalog_tags`
3. sincronizar tags por variante en `variant_catalog_tags`

Reglas:

- si una variante pierde un tag, se elimina la relacion
- si gana uno nuevo, se inserta
- no tocar `product_catalog_tags`
- no tocar `product_variant_values`


## Archivos probables a tocar

### Backend

- `app/Repositories/Admin/AdminVariantReadRepository.php`
- `app/Services/Admin/Variants/VariantReadService.php`
- `app/Services/Admin/Variants/VariantWriteService.php`
- nuevo repositorio utilitario si hace falta para tags

### Vistas

- `app/Views/partials/admin/products/variants/sale_fragment.php`

### JS

- `public_html/assets/js/admin/product_variants/index.js`
- `public_html/assets/js/admin/product_variants/bindings.js`
- modulo nuevo especifico si conviene


## Criterio de aceptacion

Se considera bien implementado cuando:

1. un producto con variantes puede tener tags generales en `Basicos`
2. una variante puede tener tags propios en `Variantes`
3. guardar tags por variante no rompe combinaciones existentes
4. guardar tags por variante no dispara warnings de variantes incompletas
5. al reabrir el producto, los tags por variante persisten
6. la UI sigue siendo comprensible y no suma friccion excesiva


## Casos de prueba

### Caso 1

- producto con variante por `Color`
- tags generales:
  - `Modalidad = Baitcast`
- tags por variante:
  - `REH -> Tecnica = Flote`


### Caso 2

- producto con variante por `Largo`
- tags generales:
  - `Modalidad = Pejerrey`
- tags por variante:
  - `3.60 -> Tecnica = Flote`
  - `4.20 -> Tecnica = Flote`


### Caso 3

- producto con variantes pero sin tags por variante
- no debe cambiar nada del flujo actual


## Riesgos

### Riesgo 1

Que la UI de `Variantes` se vuelva demasiado pesada.

Mitigacion:

- no renderizar todos los grupos abiertos por defecto
- usar bloque colapsable o editor puntual


### Riesgo 2

Mezclar tags con valores de combinacion.

Mitigacion:

- mantener persistencia separada
- no reutilizar `product_variant_values`


### Riesgo 3

Sobrecargar el save de variantes.

Mitigacion:

- sincronizacion simple por variante
- payload claro


## Decision cerrada

La etapa 2 agrega tags por variante dentro del flujo de `Variantes`.

No modifica:

- `Basicos`
- el wizard
- el aside publico

Y no vuelve a mezclar:

- tags de catalogo
- opciones que generan variantes
