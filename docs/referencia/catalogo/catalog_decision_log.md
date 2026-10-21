# Catalog Decision Log

## Proposito

Registrar decisiones tecnicas y funcionales del rediseño e integracion del catalogo.

Regla:

- toda decision de arquitectura, alcance o comportamiento estable debe quedar asentada aca
- este archivo complementa el plan integral, pero no lo reemplaza
- no usarlo para tareas menores ni para inventario de archivos


## 2026-03-14

### 1. Los atributos filtrables no dependen de variantes

Decision:

- un producto simple puede tener atributos filtrables
- un atributo filtrable no necesita ser eje de variante

Impacto:

- evita modelar mal el catalogo
- permite filtrar productos simples por modalidad, largo, material, accion, etc.


### 2. Un atributo puede ser filtrable y no visible

Decision:

- la visibilidad en ficha y la capacidad de filtrar son decisiones distintas

Metadatos objetivo:

- `is_filterable`
- `is_public`
- `is_variant_axis`

Impacto:

- permite usar atributos con valor comercial interno sin exponerlos necesariamente en frontend


### 3. No se rehace el sistema de atributos existente

Decision:

- se aprovecha la infraestructura actual de admin y DB:
  - `attributes`
  - `attribute_values`
  - `product_attributes`
  - `product_attribute_values`
  - `product_variants`
  - `product_variant_values`
  - `variant_value_sets`
  - `variant_value_set_items`

Impacto:

- se reduce riesgo
- se evita una migracion estructural innecesaria


### 4. El cuello de botella actual esta en catalogo publico

Decision:

- el problema principal no es admin ni base de datos
- el faltante principal es la integracion de atributos como facetas reales del catalogo publico

Impacto:

- las siguientes etapas deben priorizar:
  - metadatos de atributos
  - carga ordenada en admin
  - filtros publicos


### 5. `catalog-support` y editorial son capas distintas

Decision:

- `catalog-support.php` queda como bloque generico compartido del catalogo
- el bloque editorial por slug queda separado

Impacto:

- no mezclar CTA generico con contenido editorial o SEO de categoria/marca


### 6. Chips editoriales y chips navegables se separan

Decision:

- un chip visual/editorial no equivale automaticamente a una faceta real

Tipos definidos:

- chip editorial
- chip navegable

Impacto:

- evita acoplar contenido y filtro de forma improvisada


### 7. No hay logica de negocio en vistas

Decision:

- normalizacion, armado de payload, URLs, facetas, chips y reglas deben resolverse en controllers/services/view models

Impacto:

- vistas mas limpias
- menor riesgo de regresiones al escalar


### 8. La salida a produccion no debe bloquearse por la fase avanzada de facetas

Decision:

- conviene dejar la base correcta antes de produccion
- la activacion total de filtros avanzados puede ser progresiva

Impacto:

- equilibrio entre velocidad y solidez

