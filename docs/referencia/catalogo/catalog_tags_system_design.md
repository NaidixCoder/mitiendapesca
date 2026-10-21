# Sistema de Tags de Catalogo

## Objetivo

Implementar un sistema liviano de tags para clasificacion y filtrado de catalogo, separado del sistema de variantes reales.

Este sistema debe resolver casos como:

- `Modalidad > Baitcast`
- `Tecnica > Flote`
- `Formato > Telescopica`

Sin:

- agregar un step nuevo al wizard de producto
- contaminar el flujo de `Variantes`
- volver a usar `product_attributes` y `product_attribute_values` para algo que no genera SKU


## Diagnostico

El sistema actual ya tiene un modulo valido para variantes reales:

- `attributes`
- `attribute_values`
- `product_attributes`
- `product_attribute_values`
- `product_variants`
- `product_variant_values`

Ese modelo esta bien para:

- `Color`
- `Talle`
- `Medida` cuando cambia SKU/stock/precio

No es un buen modelo para:

- `Modalidad`
- `Tecnica`
- `Formato`
- `Material`

Porque esos datos no necesitan:

- generacion de combinaciones
- warnings de variantes incompletas
- un tab dedicado
- matrices de valores

La confusion anterior vino exactamente de mezclar esos dos mundos.


## Decision

Se define una separacion fuerte:

- `Variantes`
  - siguen usando el sistema existente
  - solo para opciones que generan combinaciones vendibles

- `Tags de catalogo`
  - sistema nuevo
  - simple
  - pensado para filtros y clasificacion
  - no afecta variantes


## Alcance del sistema de tags

Los tags deben servir para:

- clasificar productos de forma liviana
- clasificar variantes cuando una propiedad no aplica igual a todas
- filtrar catalogo publico mas adelante
- mostrar chips o etiquetas si se necesita
- permitir agrupacion semantica

Los tags no deben:

- generar variantes
- afectar SKU
- afectar stock
- afectar precio por variante
- crear pasos extra en el editor de producto


## Modelo propuesto

### 1. Grupos de tags

Sirven para ordenar semanticamente los tags.

Ejemplos:

- `Modalidad`
- `Tecnica`
- `Formato`
- `Material`

Tabla propuesta:

`catalog_tag_groups`

Campos:

- `id`
- `name`
- `slug`
- `sort`
- `is_active`
- `created_at`


### 2. Tags

Son valores concretos dentro de un grupo.

Ejemplos:

- grupo `Modalidad`
  - `Baitcast`
  - `Pejerrey`
  - `Spinning`

- grupo `Formato`
  - `Telescopica`
  - `2 tramos`
  - `3 tramos`

Tabla propuesta:

`catalog_tags`

Campos:

- `id`
- `group_id`
- `name`
- `slug`
- `sort`
- `is_active`
- `created_at`

Reglas:

- unico por `group_id + slug`
- un tag pertenece a un solo grupo


### 3. Asignacion a producto

Relacion muchos a muchos simple.

Tabla propuesta:

`product_catalog_tags`

Campos:

- `product_id`
- `tag_id`
- `created_at`

Reglas:

- PK compuesta `product_id + tag_id`
- no hay metadata extra


### 4. Asignacion a variante

Relacion muchos a muchos simple para cuando una clasificacion no aplica igual a todas las variantes.

Tabla propuesta:

`variant_catalog_tags`

Campos:

- `variant_id`
- `tag_id`
- `created_at`

Reglas:

- PK compuesta `variant_id + tag_id`
- una variante puede tener tags propios
- estos tags no reemplazan necesariamente los del producto; los complementan


## Por que tablas nuevas

No conviene reutilizar:

- `attributes`
- `attribute_values`
- `product_attributes`
- `product_attribute_values`

Porque esas tablas ya estan semanticamente ligadas al flujo de variantes y a su UI.

Aunque tecnicamente se podria forzar, volveria la confusion:

- un valor filtrable pareceria una opcion de variante
- el admin seguiria pensando que todo es "atributo"
- se mantiene complejidad innecesaria

El sistema de tags debe ser independiente y mucho mas liviano.


## Flujo de admin propuesto

### A. Catalogo maestro

Nuevo modulo o submodulo:

- `Admin > Catalogo > Tags`

Pantallas:

1. `Grupos de tags`
2. `Tags`

Ejemplo de uso:

1. crear grupo `Modalidad`
2. dentro de ese grupo crear:
   - `Baitcast`
   - `Pejerrey`
   - `Spinning`

No hace falta una UI compleja.

Debe ser algo tipo:

- listado
- alta
- edicion
- orden
- activar/desactivar


### B. Producto

Los tags se cargan en dos niveles.

#### Tags generales del producto

Se cargan dentro de `Basicos`.

No deben vivir en un tab propio.

Ubicacion sugerida:

- debajo de `Marca`, `Categoria` y `Proveedor`
- antes de `Descripcion corta`

Bloque sugerido:

`Clasificacion del catalogo`

Copy sugerido:

- `Asigna tags para filtrar y clasificar el producto en el catalogo. No generan variantes.`

UI sugerida:

- selector por grupo
- multiseleccion simple
- chips seleccionados

Ejemplo:

- `Modalidad`
  - [x] Baitcast
- `Formato`
  - [x] Telescopica

No hace falta crear un mini framework de matrices.


#### Tags especificos por variante

Se cargan dentro de `Variantes`, asociados a cada variante concreta.

Esto cubre casos como:

- un producto con varias longitudes
- donde ciertas tecnicas o usos aplican solo a algunas medidas

Ejemplo:

- producto:
  - `Modalidad = Pejerrey`
- variante `3.60 m`
  - `Tecnica = Flote`
- variante `4.20 m`
  - `Tecnica = Flote`

Regla:

- si el tag aplica a todo el producto, va en `Basicos`
- si el tag cambia segun la variante, va en `Variantes`


## UX del editor de producto

### Producto simple

Flujo:

1. `Basicos`
   - nombre
   - marca
   - categoria
   - proveedor
   - tags generales del catalogo
   - descripciones
2. `Venta`
3. `Imagenes`
4. `SEO`
5. `Publicacion`


### Producto con variantes

Flujo:

1. `Basicos`
   - nombre
   - marca
   - categoria
   - proveedor
   - tags generales del catalogo
   - descripciones
2. `Venta`
3. `Variantes`
   - opciones reales de variante
   - tags especificos por variante cuando haga falta
4. `Imagenes`
5. `SEO`
6. `Publicacion`

Los tags generales siguen cargandose en `Basicos`.

`Variantes` queda exclusivamente para:

- `Color`
- `Talle`
- etc.

Pero puede exponer un sub-bloque chico de `Tags de variante` por fila o por editor de variante.


## Guardado

### Save de producto

Al guardar `Basicos`:

1. guardar producto como hoy
2. leer `catalog_tag_ids[]`
3. sincronizar pivot `product_catalog_tags`

Regla:

- borrar relaciones viejas que ya no estan seleccionadas
- insertar nuevas

No hace falta versionado especial.


### Save de variante

Al guardar `Variantes`:

1. guardar combinaciones y datos de variante como hoy
2. leer tags especificos por variante
3. sincronizar pivot `variant_catalog_tags`

Regla:

- el save de tags por variante no debe interferir con `product_variant_values`
- los tags de variante no generan combinaciones
- solo enriquecen clasificacion y filtros


## Lectura en admin

Al abrir `Basicos`:

1. traer grupos activos
2. traer tags activos por grupo
3. traer tags ya asignados al producto
4. pintar seleccion


## Integracion futura con catalogo publico

No entra en esta etapa, pero el diseño debe soportarlo.

Futuro esperado:

- `FacetService` leeria tags por grupo
- `FilterService` normalizaria `tag[group_slug][]=tag_slug|tag_id`
- `ProductRepository` filtraria via `product_catalog_tags`

Importante:

el aside actual no se toca en esta etapa


## Archivos probables a tocar en implementacion

### DB

- `database/schema.sql`
- nueva migracion SQL para:
  - `catalog_tag_groups`
  - `catalog_tags`
  - `product_catalog_tags`

### Admin producto

- `app/Views/partials/admin/products/tab_basicos.php`
- `app/Services/Admin/Products/ProductSaveService.php`
- `app/Repositories/Admin/ProductWriteRepository.php`
- `app/Services/Admin/Products/ProductFormService.php`
- `app/Views/partials/admin/products/variants/sale_fragment.php`
- `app/Services/Admin/Variants/VariantWriteService.php`
- `app/Repositories/Admin/AdminVariantReadRepository.php`

### Nuevo modulo admin tags

- controlador admin nuevo
- vistas index/form para grupos y tags
- repositorio o servicios de escritura/lectura
- entradas de sidebar si se decide exponerlo ya


## Criterio de aceptacion

Se considera bien implementado cuando:

1. se puede crear un grupo de tags
2. se pueden crear tags dentro del grupo
3. en `Basicos` se pueden asignar tags al producto
4. el producto simple puede guardar tags sin tocar `Variantes`
5. el producto con variantes puede guardar tags generales en `Basicos`
6. el producto con variantes puede guardar tags especificos por variante en `Variantes`
7. `Modalidad > Baitcast` no genera ninguna confusion con SKU/stock/combinaciones
8. el wizard no gana steps nuevos


## Recomendacion de implementacion

Hacerlo en 2 etapas.

### Etapa 1

- tablas nuevas
- modulo admin de grupos/tags
- bloque en `Basicos`
- guardado y lectura de tags generales en producto

### Etapa 2

- tags por variante
- integracion con catalogo publico
- facetas por grupo
- chips activos
- limpieza/acomodo del aside


## Decision cerrada

Para filtros livianos del catalogo:

- usar tags

Para variantes reales:

- mantener el sistema actual de variantes

No volver a mezclar ambas cosas.
