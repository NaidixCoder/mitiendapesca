# Plan Integral: Catalogo, Editorial, Atributos y Filtros Reales

## Objetivo

Definir un plan de implementacion completo para llevar el catalogo a un estado profesional, sin reconstruir lo que ya existe en admin y base de datos, y evitando cambios estructurales peligrosos una vez que la tienda este en produccion con clientes, ventas y datos reales.

Este documento parte de una auditoria real del proyecto:

- admin de productos ya tiene editor de variantes y atributos
- la base de datos ya tiene tablas de atributos, valores, variantes y sets
- categorias y marcas ya tienen `seo_text`, `cover_image` y bloques SEO estructurados
- el catalogo publico todavia no consume atributos como filtros reales

La conclusion central es esta:

- no hay que inventar un sistema nuevo de atributos
- hay que integrar el sistema existente con catalogo publico, landing editorial y admin


## Auditoria Resumida

### 1. Admin: creacion y edicion de productos

El admin de productos ya dispone de una estructura madura.

Archivos relevantes auditados:

- [ProductsController.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/ProductsController.php)
- [ProductFormService.php](C:/xampp/htdocs/mi-pesca/app/Services/Admin/Products/ProductFormService.php)
- [ProductSaveService.php](C:/xampp/htdocs/mi-pesca/app/Services/Admin/Products/ProductSaveService.php)
- [form_tabs.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/form/form_tabs.php)
- [tab_basicos.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_basicos.php)
- [tab_venta.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_venta.php)
- [tab_variantes.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_variantes.php)
- [AdminVariantReadRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Admin/AdminVariantReadRepository.php)

Tabs actuales del editor de producto:

- Basicos
- Inventario / Venta
- SEO
- Publicacion
- Variantes
- Imagenes

Capacidades actuales detectadas:

- asignacion de marca
- asignacion de categoria
- proveedor
- descripcion corta y larga
- modo de venta simple o variantes
- precios, costo, margen, stock
- imagenes
- SEO de producto
- atributos globales
- valores por atributo
- seleccion de valores por producto
- generacion de variantes a partir de atributos y valores
- sets de valores reutilizables
- alta rapida de atributos desde el editor de variantes

Conclusion:

- el admin de productos ya tiene casi toda la infraestructura necesaria para soportar atributos filtrables
- lo que falta no es "crear variantes", sino conectar atributos al catalogo publico y ordenar su gobierno en admin


### 2. Admin: atributos

Controlador y acciones detectadas:

- [AttributesController.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/AttributesController.php)
- [AttributesCatalogActions.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/Concerns/AttributesCatalogActions.php)
- [AttributesWriteActions.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/Concerns/AttributesWriteActions.php)
- [AttributesValueSetActions.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/Concerns/AttributesValueSetActions.php)

El sistema actual ya soporta:

- catalogo global de atributos
- valores por atributo
- creacion rapida
- sets reutilizables por categoria o marca

Conclusion:

- ya existe un nucleo de PIM liviano para atributos
- conviene aprovecharlo y extenderlo


### 3. Base de datos auditada

Tablas relevantes confirmadas:

- `products`
- `categories`
- `brands`
- `attributes`
- `attribute_values`
- `product_attributes`
- `product_attribute_values`
- `product_variants`
- `product_variant_values`
- `variant_value_sets`
- `variant_value_set_items`
- `product_seo_blocks`
- `category_seo_blocks`
- `brand_seo_blocks`

Campos clave detectados:

#### `products`

- `brand_id`
- `category_id`
- `sale_mode`
- `short_desc`
- `long_desc`
- `price`
- `list_price`
- `cost`
- `margin_pct`
- `meta_title`
- `meta_description`

#### `categories`

- `meta_title`
- `meta_description`
- `seo_text`
- `cover_image`

#### `brands`

- `meta_title`
- `meta_description`
- `seo_text`
- `cover_image`

#### `attributes`

- `name`
- `slug`
- `type` (`select`, `text`, `number`)

#### `attribute_values`

- `attribute_id`
- `value`
- `sort`

#### `product_attributes`

- `product_id`
- `attribute_id`
- `sort`
- `is_required`

#### `product_attribute_values`

- `product_id`
- `attribute_id`
- `value_id`

#### `product_variants`

- `product_id`
- `sku`
- `title`
- `price`
- `list_price`
- `cost`
- `margin_pct`
- `is_active`

#### `product_variant_values`

- `variant_id`
- `attribute_id`
- `value_id`

Conclusion:

- la base ya tiene un modelo relacional suficiente para filtros por atributos
- no hace falta rehacer la estructura de variantes
- si hiciera falta, los cambios nuevos deben ser minimos y orientados a catalogo/editorial, no a reemplazar el modelo actual


### 4. Catalogo publico: estado actual

Archivos auditados:

- [FilterService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FilterService.php)
- [ProductRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Catalog/ProductRepository.php)
- [FacetService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FacetService.php)

Filtros reales actuales:

- texto libre `q`
- categoria / subcategoria
- marca
- precio
- orden
- paginacion

Limite actual:

- no hay filtros publicos por atributos
- no hay facetas por modalidad, largo, tamaño, accion, material, etc.
- no hay chips navegables enlazados a atributos reales

Conclusion:

- el cuello de botella no esta en admin ni en DB
- esta en la capa de integracion del catalogo publico


## Principio Rector

No mezclar en una sola pieza conceptual:

- contenido editorial
- chips visuales
- chips navegables
- filtros reales del catalogo

Deben convivir, pero cada uno con su propia capa de datos.


## Arquitectura Objetivo

### Capa 1. Catalog support

Bloque generico compartido del catalogo.

Archivo actual:

- [catalog-support.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/catalog-support.php)

Funcion:

- apoyo comercial liviano
- CTA a WhatsApp
- mensaje generico del catalogo

No debe mezclarse con SEO editorial real ni con filtros.


### Capa 2. Editorial por slug

Aplica a:

- `/categoria?slug=...`
- `/marca?slug=...`

Contenido:

- imagen de portada
- eyebrow
- titulo
- introduccion
- FAQ
- eventualmente tips o chips editoriales
- CTA editorial opcional

Base actual disponible:

- `seo_text`
- `cover_image`
- `category_seo_blocks`
- `brand_seo_blocks`

Render actual:

- [editorial-blocks.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/editorial-blocks.php)


### Capa 3. Atributos de producto

Informacion estructurada que describe productos.

Ejemplos:

- modalidad
- largo
- tamaño
- accion
- material
- peso
- capacidad

Estas facetas deben salir del sistema existente:

- `attributes`
- `attribute_values`
- `product_attributes`
- `product_attribute_values`
- `product_variants`
- `product_variant_values`


### Capa 4. Facetas del catalogo

Filtros reales de listado, construidos sobre datos estructurados.

Ejemplos:

- categoria
- marca
- precio
- modalidad
- largo
- accion
- material

Esta es la capa hoy faltante.


### Capa 5. Chips

Hay que separar dos tipos:

#### Chips editoriales

Funcion:

- apoyo visual
- consejos
- navegacion sugerida opcional

No necesariamente dependen de una faceta real.

Ejemplos:

- Elegi el largo segun el lugar
- Revisa la accion de la caña

#### Chips navegables

Funcion:

- enlazar a filtros reales del catalogo

Ejemplos:

- Cañas 3.6m
- Accion rapida
- Carbono

Estos chips deben conocer:

- label
- destino
- tipo de destino
- si el destino es editorial, URL libre
- si el destino es filtro real, `filter_key` y `filter_value`


## Propuesta de Implementacion Full

## Etapa 0. Congelamiento conceptual previo a produccion

Objetivo:

- tomar decisiones de modelo antes de salir a produccion

Definir:

- que atributos seran globales y filtrables
- cuales son solo para variantes
- cuales son solo descriptivos
- que landings tendran editorial enriquecido
- que se considera chip editorial vs chip navegable

Entregable:

- listado inicial de atributos canónicos por rubro
- convenciones de slug y naming
- criterios de carga en admin


## Etapa 1. Consolidacion editorial por categoria y marca

Objetivo:

- dejar landings editoriales administrables y consistentes

### 1.1. Mantener la separacion actual

- `catalog-support.php` sigue como bloque generico
- `editorial-blocks.php` sigue como bloque editorial por slug

### 1.2. Extender el modelo editorial

Agregar para categorias y marcas una estructura editorial propia.

Opciones:

- tabla nueva dedicada
- o extender `category_seo_blocks` y `brand_seo_blocks`

Recomendacion:

- crear tablas nuevas de editorial para no contaminar SEO blocks viejos con semanticas nuevas

Tablas sugeridas:

- `category_editorial_blocks`
- `brand_editorial_blocks`

Campos sugeridos:

- `entity_id`
- `eyebrow`
- `title_override`
- `intro_text`
- `cta_text`
- `cta_url`
- `is_active`
- `updated_at`

Tablas hijas sugeridas:

- `category_editorial_quick_tips`
- `brand_editorial_quick_tips`
- `category_editorial_links`
- `brand_editorial_links`

### 1.3. Admin

Agregar a categorias y marcas una seccion nueva:

- portada
- intro editorial
- quick tips
- links/chips
- FAQ

Sin logica en vistas:

- controller arma el payload
- service normaliza y persiste
- view solo renderiza campos

### 1.4. Frontend

Desktop:

- imagen
- intro a la izquierda
- FAQ a la derecha
- chips debajo o entre intro y FAQ segun diseño final

Mobile:

- layout apilado
- FAQ con `details`


## Etapa 2. Gobierno profesional de atributos

Objetivo:

- ordenar el sistema actual de atributos para que sirva de base a filtros reales

### 2.1. Clasificacion funcional de atributos

Agregar metadatos al catalogo de atributos.

Campos nuevos sugeridos en `attributes`:

- `code`
- `is_filterable`
- `is_variant_axis`
- `is_product_feature`
- `is_public`
- `facet_sort`
- `unit`
- `group_name`

Notas:

- `slug` ya existe, pero conviene sumar `code` estable si se quiere desacoplar presentacion de uso interno
- no todos los atributos deben ser filtrables
- no todos los atributos filtrables deben generar variantes

### 2.2. Reglas iniciales

Ejemplo para cañas:

- `largo`: filtrable, visible, no necesariamente eje de variante
- `accion`: filtrable, visible
- `modalidad`: filtrable, visible
- `material`: filtrable, visible
- `color`: puede ser eje de variante, no siempre filtro principal

### 2.3. Admin de atributos

Mejoras necesarias:

- poder marcar un atributo como filtrable
- definir orden de faceta
- definir label publico
- definir unidad
- definir tipo de widget sugerido

Widgets sugeridos:

- checkbox list
- radio
- pills
- range


## Etapa 3. Producto: integracion de atributos en el editor

Objetivo:

- asegurar que los atributos filtrables queden correctamente cargados por producto

### 3.1. Tab dedicado o mejora de Variantes

Opciones:

- seguir todo dentro de `Variantes`
- o agregar tab nuevo `Atributos`

Recomendacion:

- agregar tab `Atributos` para separar mejor:
  - informacion filtrable y descriptiva del producto
  - combinatoria de variantes

### 3.2. Flujos requeridos

En producto simple:

- poder asignar atributos filtrables aunque no existan variantes

En producto con variantes:

- mantener ejes de variante
- permitir atributos de producto transversales no ligados a cada variante

### 3.3. Regla tecnica importante

No forzar que todo atributo filtrable sea un atributo de variante.

Ejemplo:

- una caña puede filtrarse por material sin que el material genere variantes

### 3.4. Calidad de carga

Agregar validaciones:

- atributos requeridos por categoria
- evitar duplicados
- evitar valores huerfanos
- avisos si una categoria tiene facetas definidas y el producto no las completa


## Etapa 4. Facetas reales en catalogo publico

Objetivo:

- extender el catalogo para consumir atributos reales como filtros

### 4.1. FilterService

Extender [FilterService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FilterService.php) para aceptar filtros dinamicos por atributo.

Propuesta:

- mantener filtros core actuales
- agregar `attributes` como mapa estable

Ejemplo de salida:

```php
[
  'q' => '',
  'categoria' => 'canas',
  'marca' => ['shimano'],
  'min' => '10000',
  'max' => '80000',
  'orden' => 'relevancia',
  'attributes' => [
    'modalidad' => ['spinning'],
    'accion' => ['rapida'],
    'largo' => ['3-60m'],
  ],
]
```

Formato GET sugerido:

- `attr[modalidad][]=spinning`
- `attr[accion][]=rapida`
- `attr[largo][]=3-60m`

### 4.2. Repository

Extender [ProductRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Catalog/ProductRepository.php) para construir joins y where por atributos.

Puntos clave:

- filtrar productos simples y con variantes sin duplicar filas
- usar `EXISTS` o joins acotados para evitar explosiones de cardinalidad
- soportar seleccion multiple por atributo
- combinar con categoria, marca y precio

### 4.3. FacetService

Extender [FacetService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FacetService.php) para devolver facetas de atributos.

Salida objetivo:

- categorias
- marcas
- precio
- atributos visibles para el contexto actual

Cada faceta de atributo debe traer:

- `attribute_code`
- `attribute_label`
- `type`
- `options[]`
- `count`
- `selected`

### 4.4. Sidebar

Agregar bloques dinamicos en el aside de filtros.

Ejemplos:

- Modalidad
- Largo
- Accion
- Material

Con scroll interno si hiciera falta.

### 4.5. Chips activos

Extender active filter chips para soportar atributos.

Ejemplo:

- Marca: Shimano
- Accion: Rapida
- Largo: 3.60m

Con remocion individual.


## Etapa 5. Chips editoriales administrables

Objetivo:

- permitir que categoria y marca tengan chips gestionables desde admin

### 5.1. Tipos

Cada chip debe tener un tipo:

- `text`
- `url`
- `filter`

### 5.2. Estructura sugerida

Tabla sugerida:

- `editorial_chips`

Campos:

- `id`
- `scope_type` (`category`, `brand`)
- `scope_id`
- `label`
- `chip_type`
- `url`
- `filter_key`
- `filter_value`
- `sort`
- `is_active`

Ejemplos:

Chip editorial puro:

- `label`: `Elegi el largo segun el lugar`
- `chip_type`: `text`

Chip de navegacion libre:

- `label`: `Ver todas las cañas de spinning`
- `chip_type`: `url`
- `url`: `/categoria?slug=canas&attr[modalidad][]=spinning`

Chip de filtro real:

- `label`: `Accion rapida`
- `chip_type`: `filter`
- `filter_key`: `accion`
- `filter_value`: `rapida`

### 5.3. Render

El view model editorial debe resolver estos chips en backend:

- label
- href final
- variant visual
- si es clickeable o no

La vista no debe armar URLs ni interpretar reglas.


## Etapa 6. SEO editorial real

Objetivo:

- mejorar el valor de categoria y marca sin confundir soporte con SEO

### 6.1. Mantener nomenclatura clara

- `catalog-support`: bloque generico
- `editorial-blocks`: bloque editorial de landing

### 6.2. Contenido por slug

Cada slug de categoria o marca debe poder tener:

- intro unica
- FAQ propia
- chips propios
- portada

### 6.3. Reglas editoriales

Ya existe base documental y debe seguirse:

- contenido util para humanos
- no repetir el mismo texto entre slugs
- no usar relleno
- FAQ reales


## Etapa 7. Produccion segura

Objetivo:

- minimizar riesgo de meter una migracion grande con ventas reales activas

### Estrategia recomendada

#### 7.1. Antes de produccion

Conviene dejar hechas al menos estas bases:

- metadatos de atributos filtrables
- convenciones de uso
- tab o flujo para carga de atributos de producto
- migraciones nuevas ya aplicadas

No es obligatorio activar aun todos los filtros publicos.

#### 7.2. Salida inicial

Produccion puede salir con:

- categoria
- marca
- precio
- bloque editorial por slug
- chips editoriales simples

Y con esto ya preparado internamente:

- atributos estructurados
- admin listo para cargarlos

#### 7.3. Activacion progresiva

Luego activar por fases:

1. atributo interno cargable
2. atributo visible en producto
3. atributo filtrable en catalogo
4. chips navegables conectados a filtro

Esto baja el riesgo de romper la tienda viva.


## Etapa 8. Migraciones y backfill

Objetivo:

- llevar datos actuales a la nueva capa sin dañar lo existente

### 8.1. Migraciones DB

Crear solo lo faltante:

- metadatos de `attributes`
- tablas de editorial chips o editorial extendido

### 8.2. Backfill inicial

Por cada rubro prioritario:

- crear atributos canónicos
- crear valores
- mapear productos existentes

Prioridad sugerida:

1. Cañas
2. Reeles
3. Señuelos

### 8.3. QA de datos

Checks:

- productos sin categoria
- productos sin marca
- productos de categorias prioritarias sin atributos filtrables
- atributos sin valores
- valores duplicados o inconsistentes


## Etapa 9. QA funcional

### Admin

- crear producto simple con atributos
- crear producto con variantes
- editar valores sin perder variantes
- crear atributo rapido
- crear set de valores
- guardar y reabrir producto

### Catalogo publico

- filtrar por una faceta de atributo
- filtrar por multiples facetas
- combinar con marca, precio y categoria
- mantener sort y paginacion
- chips activos correctos
- URL compartible

### Editorial

- categoria con portada + intro + FAQ + chips
- marca con contenido equivalente
- sin contenido, no renderizar bloque vacio


## Riesgos

### Riesgo 1. Sobreacoplar variantes con facetas

Error:

- asumir que todo filtro debe salir de variantes

Impacto:

- complejidad innecesaria
- mal modelado de productos simples

Mitigacion:

- separar atributo filtrable de eje de variante

### Riesgo 2. Mezclar editorial con filtros reales

Error:

- usar chips visuales como sustituto de facetas

Impacto:

- UX inconsistente
- mantenimiento manual excesivo

Mitigacion:

- separar chips editoriales y chips navegables

### Riesgo 3. Querer hacer todo antes de salir

Error:

- bloquear produccion por una capa avanzada que aun no es imprescindible

Mitigacion:

- activar por etapas
- dejar lista la base, no todo el frontend


## Recomendacion Ejecutiva

Si el objetivo es salir a produccion pronto sin hipotecar el futuro, la secuencia recomendada es esta:

### Fase A. Antes de salir

- consolidar editorial por categoria y marca
- agregar estructura minima formal para chips editoriales
- definir y migrar metadatos de atributos filtrables
- habilitar carga ordenada de atributos en admin de producto

### Fase B. Produccion inicial

- salir con filtros actuales
- mantener editorial enriquecido
- cargar atributos en productos desde ya, aunque no todos sean visibles como filtro

### Fase C. Post salida controlada

- activar filtros reales por atributos por rubro
- empezar por una categoria piloto
- luego extender a las demas

Esta es la mejor relacion entre:

- velocidad
- calidad de base
- riesgo operativo


## Criterio de cierre

Este plan se considera plenamente implementado cuando:

- categorias y marcas tienen bloque editorial gestionable desde admin
- los chips editoriales son administrables
- los atributos filtrables estan gobernados en admin
- los productos pueden cargar atributos sin depender de variantes
- el catalogo publico puede filtrar por atributos reales
- los chips navegables pueden enlazar a esos filtros
- todo convive sin logica de negocio en vistas

