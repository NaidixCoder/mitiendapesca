# Catalog Editorial Chips and Filterable Attributes

## Objetivo

Definir una propuesta para evolucionar el bloque editorial de `/categoria` y `/marca` hacia un formato mas rico, con:

- imagen de portada
- intro editorial
- chips administrables desde admin
- FAQ
- CTA

Y, en paralelo, definir como habilitar filtros reales por atributos como:

- modalidad
- largo
- tamano
- accion
- material

## Problema actual

Hoy el catalogo permite filtrar principalmente por:

- categoria
- marca
- precio

Pero no existe una capa de atributos estructurados de producto que permita:

- filtrar por modalidad
- filtrar por largo
- filtrar por tamano
- filtrar por accion
- filtrar por material
- construir chips navegables que apliquen filtros reales

Ademas, el bloque editorial actual soporta:

- `seo_text`
- `bullets`
- `specs`
- `faq`

Eso alcanza para una primera version editorial, pero no representa exactamente el formato visual de referencia.

## Decisiones de arquitectura

Separar claramente 2 conceptos:

### 1. Contenido editorial

Es contenido administrado por slug para enriquecer la landing.

Ejemplos:

- imagen de portada
- titulo / subtitulo
- texto editorial
- chips de consejos
- preguntas frecuentes
- CTA

Este contenido vive en el bloque editorial y no depende necesariamente de facetas reales del catalogo.

### 2. Filtros reales del catalogo

Son atributos estructurados de producto que permiten:

- filtrar el listado
- generar conteos
- construir URLs compartibles
- mostrar chips navegables

Ejemplos:

- modalidad = spinning
- largo = 3.60m
- accion = rapida
- material = carbono

## Formato visual objetivo

En desktop, el bloque editorial deberia poder soportar un formato como este:

- columna izquierda:
  - imagen
  - eyebrow
  - titulo
  - texto introductorio

- columna derecha:
  - FAQ

- franja inferior opcional:
  - chips editoriales
  - chips navegables
  - CTA

## Tipos de chips

No conviene mezclar todos los chips bajo una sola idea.

### A. Chips editoriales

Sirven para contenido de apoyo.

Ejemplos:

- `Elegi el largo segun el lugar`
- `Revisa la accion de la cana`
- `Considera el peso del señuelo`

Caracteristicas:

- no necesariamente filtran
- pueden ser texto solo visual
- pueden abrir una FAQ o anclar a una seccion
- pueden enlazar opcionalmente a una URL

### B. Chips navegables

Sirven para aplicar filtros reales del catalogo.

Ejemplos:

- `Cañas livianas`
- `Cañas 3.6m`
- `Accion rapida`
- `Carbono`

Caracteristicas:

- deben mapear a atributos reales del producto
- deben construir una URL valida
- deben ser consistentes con el sistema de facetas

## Recomendacion de implementacion para chips

### Etapa 1. Chips editoriales administrables

Agregar soporte admin por slug para una lista de chips simples.

Cada chip podria tener:

- `label`
- `kind`
- `url` opcional
- `sort`
- `is_active`

`kind` posible:

- `text`
- `link`
- `faq_anchor`

Esto permite construir el formato visual sin depender aun del sistema de atributos.

### Etapa 2. Chips navegables conectados a filtros reales

Agregar soporte admin para chips que apunten a filtros estructurados.

Cada chip deberia tener:

- `label`
- `filter_key`
- `filter_value`
- `sort`
- `is_active`

Ejemplo:

- `label`: `Accion rapida`
- `filter_key`: `accion`
- `filter_value`: `rapida`

Resultado esperado:

- el chip construye una URL tipo `/categoria?slug=canas&accion=rapida`

## Modelo de datos recomendado para filtros reales

Para soportar filtros como modalidad, largo o tamano, hace falta una capa de atributos estructurados.

## Tablas sugeridas

### `attributes`

Campos sugeridos:

- `id`
- `code`
- `name`
- `type`
- `is_filterable`
- `sort`
- `is_active`

Ejemplos:

- `modalidad`
- `largo`
- `tamano`
- `accion`
- `material`

### `attribute_values`

Campos sugeridos:

- `id`
- `attribute_id`
- `label`
- `slug`
- `sort`
- `is_active`

Ejemplos:

- `spinning`
- `casting`
- `3.60m`
- `rapida`
- `carbono`

### `product_attribute_values`

Campos sugeridos:

- `product_id`
- `attribute_id`
- `attribute_value_id`

Opcional si se quieren soportar valores libres:

- `raw_value`
- `normalized_value`

## Consideraciones especiales

### Atributos cerrados

Usar listas controladas.

Ejemplos:

- modalidad
- accion
- material

### Atributos semi-controlados

Pueden ser controlados por valores predefinidos.

Ejemplos:

- tamano
- potencia

### Atributos numericos

Conviene guardar valor normalizado.

Ejemplos:

- largo en centimetros
- peso en gramos

Esto permite:

- filtrar por rangos
- ordenar
- evitar depender de strings como `3.60m`

## Backend necesario

Para que funcione el filtrado real, habria que extender:

- normalizacion de request
- repositorio de productos
- facetas del sidebar
- chips activos
- builders de query string

El backend deberia soportar:

- filtros simples por slug de atributo
- multiples valores por atributo cuando corresponda
- conteos por faceta dentro del contexto actual

## Frontend necesario

### Sidebar / filtros

Agregar nuevas secciones de filtro segun los atributos activos para esa landing.

Ejemplos:

- Modalidad
- Largo
- Accion
- Material

### Chips activos

Mostrar chips removibles tambien para atributos.

Ejemplos:

- `Modalidad: Spinning`
- `Largo: 3.60m`
- `Material: Carbono`

### Bloque editorial

Permitir:

- chips editoriales
- chips navegables
- CTA

Sin mezclar la logica de filtros dentro de la vista.

## Admin necesario

## Para categorias y marcas

Agregar un panel editorial mas rico, con:

- `cover_image`
- `editorial_eyebrow`
- `editorial_title_override` opcional
- `editorial_intro`
- `editorial_chips`
- `editorial_cta_label`
- `editorial_cta_url`

## Para atributos de producto

Se necesita:

- ABM de atributos
- ABM de valores de atributo
- asociacion de atributos filtrables a productos

Idealmente:

- select o multiselect en admin de producto
- validacion de consistencia

## Implementacion propuesta por etapas

Si, conviene partirlo.

### Etapa 1. Evolucion del bloque editorial

Objetivo:

- soportar el formato visual enriquecido
- permitir chips editoriales gestionables desde admin

Tareas:

1. ampliar el modelo editorial de categoria y marca
2. agregar panel admin para chips editoriales
3. renderizar chips en el bloque editorial
4. permitir CTA opcional

Resultado:

- bloque editorial mas cercano al diseño deseado
- sin depender aun de atributos filtrables reales

### Etapa 2. Atributos filtrables del catalogo

Objetivo:

- habilitar filtros reales por modalidad, largo, tamano, accion, material, etc.

Tareas:

1. crear tablas de atributos y valores
2. vincular atributos a productos
3. extender request/filter/repository/facets
4. mostrar filtros en sidebar
5. permitir chips navegables que apunten a esos filtros

Resultado:

- filtros reales y escalables
- chips que navegan a facetas reales

### Etapa 3. Integracion editorial + facetas

Objetivo:

- conectar el bloque editorial con el sistema de filtros

Tareas:

1. permitir chips editoriales de tipo link a filtros
2. mostrar exploracion rapida por atributos relevantes para cada slug
3. mantener URLs limpias y compartibles

Resultado:

- bloque editorial util
- navegacion rapida hacia filtros reales

## Lo que no conviene hacer

- hardcodear chips por categoria en la vista
- simular filtros con texto no estructurado
- usar `seo_text` para resolver atributos de producto
- mezclar contenido editorial con facetas reales sin modelo de datos

## Recomendacion final

Primero implementar chips editoriales administrables para destrabar el formato visual.

Despues construir el sistema de atributos filtrables de producto.

Ese orden permite:

- avanzar rapido en UX/editorial
- evitar deuda tecnica
- no forzar filtros falsos en el frontend

## Criterio de aceptacion

La propuesta se considera lista para pasar a implementacion cuando quede definido:

1. que campos editoriales nuevos tendran categoria y marca
2. como se cargan chips editoriales desde admin
3. que atributos de producto seran filtrables
4. que modelo de datos soportara esos atributos
5. como se construiran chips navegables hacia filtros reales
