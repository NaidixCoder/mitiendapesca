# Catalog Stage 04: UX del Aside de Filtros

## Objetivo

Reorganizar el aside de filtros del catalogo para que escale correctamente cuando se activen mas atributos filtrables, sin convertir el panel en una lista plana interminable.


## Relacion con documentos previos

Documento madre:

- [catalog_full_integrated_implementation_plan.md](C:/xampp/htdocs/mi-pesca/docs/referencia/catalogo/catalog_full_integrated_implementation_plan.md)

Spec UX:

- [catalog_filter_aside_ux_spec.md](C:/xampp/htdocs/mi-pesca/docs/referencia/catalogo/catalog_filter_aside_ux_spec.md)

Etapa previa:

- [catalog_stage_03_public_attribute_facets.md](C:/xampp/htdocs/mi-pesca/docs/referencia/catalogo/catalog_stage_03_public_attribute_facets.md)


## Problema a resolver

La etapa 03 deja disponible la capa tecnica de facetas por atributos, pero el aside actual no esta preparado para escalar con muchas facetas.

Riesgos actuales:

- crecimiento vertical excesivo
- demasiadas secciones al mismo nivel
- baja legibilidad en mobile
- menor comprension del usuario sobre que esta filtrando


## Decision aplicada

La UX del aside se organiza en tres niveles:

1. navegacion principal
2. recorte comercial fuerte
3. refinamiento tecnico

Traduccion concreta:

- Categorias
- Marca
- Precio
- Mas filtros

Y dentro de `Mas filtros`:

- grupos por `group_name`


## Alcance

Esta etapa incluye:

- sumar bloque `Filtros activos`
- sumar accion `Limpiar todo`
- crear bloque `Mas filtros`
- agrupar atributos por `group_name`
- hacer grupos colapsables
- abrir automaticamente grupos con seleccion activa
- limitar listas largas con `Ver mas / Ver menos`
- mantener buen comportamiento en mobile y desktop

Esta etapa no incluye:

- widgets avanzados por tipo de atributo
- facetas numericas o ranges por atributo
- prioridad por categoria basada en reglas complejas de negocio
- buscador interno por faceta


## Resultado esperado

En mobile:

- el bottom sheet sigue siendo claro
- `Categorias`, `Marca` y `Precio` siguen siendo el nucleo
- los atributos tecnicos viven en `Mas filtros`
- los grupos activos se abren solos

En desktop:

- el sidebar mantiene foco en los filtros principales
- los filtros tecnicos no compiten visualmente con categoria, marca y precio
- el usuario ve siempre sus filtros activos y puede limpiarlos rapido


## Estructura objetivo

## Mobile

Orden:

1. header `Filtros`
2. resumen de filtros activos
3. Categorias
4. Marca
5. Precio
6. Mas filtros
7. Aplicar

### Reglas

- `Categorias` abierta
- `Marca` abierta
- `Precio` abierto
- `Mas filtros` cerrado por defecto
- si algun atributo esta activo dentro de `Mas filtros`, abrir ese bloque
- si un grupo tiene selecciones activas, abrir ese grupo


## Desktop

Orden:

1. Filtros activos
2. Categorias
3. Marca
4. Precio
5. separador visual
6. Mas filtros

### Reglas

- `Mas filtros` visible
- grupos internos colapsables
- facetas largas con `Ver mas`


## Agrupacion

Los atributos filtrables deben agruparse por `group_name`.

Ejemplos:

- Uso
- Medidas
- Construccion
- Rendimiento

Si un atributo no tiene `group_name`, debe entrar en un fallback estable:

- `Otros filtros`


## Filtros activos

Debe existir un bloque de resumen cuando haya alguna seleccion.

Contenido:

- cantidad de filtros activos
- chips activos
- accion `Limpiar todo`

Reglas:

- visible solo si hay filtros activos
- removible individualmente desde chips
- `Limpiar todo` resetea categoria secundaria, marca, precio y atributos


## Ver mas / Ver menos

Aplicar en:

- marcas
- listas largas de valores dentro de una faceta

No aplicar:

- a grupos completos

Regla sugerida:

- mostrar 5 a 7 items inicialmente
- expandir bajo demanda


## Implicancias tecnicas

## 1. View model de facetas

Conviene dejar la estructura agrupada desde backend, no armarla en la vista.

Nueva estructura objetivo:

```php
[
  'primary' => [
    'categories' => [...],
    'brands' => [...],
    'price' => [...],
  ],
  'attribute_groups' => [
    [
      'key' => 'uso',
      'label' => 'Uso',
      'is_open' => true,
      'facets' => [
        [
          'code' => 'modalidad',
          'label' => 'Modalidad',
          'selected_count' => 1,
          'is_open' => true,
          'options' => [...],
        ],
      ],
    ],
  ],
]
```

La vista no deberia:

- agrupar atributos
- decidir grupos fallback
- decidir si un grupo arranca abierto


## 2. Servicios

Areas candidatas:

- [FacetService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FacetService.php)
- [FilterChipService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FilterChipService.php)
- posible view model especifico para sidebar

Tareas:

- agrupar atributos por `group_name`
- calcular `selected_count`
- decidir `is_open`
- exponer data para `Ver mas`


## 3. Vistas

Partials candidatos:

- [facet-refine.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-refine.php)
- [facet-attributes.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-attributes.php)

Posible refactor:

- separar `facet-active-summary.php`
- separar `facet-attribute-groups.php`
- separar `facet-group.php`

Objetivo:

- reducir complejidad del partial principal
- mantener cero logica de negocio en vista


## 4. JS opcional liviano

Solo si hace falta:

- abrir/cerrar acordeones
- `Ver mas / Ver menos`

No debe introducir:

- logica de filtrado
- armado de URLs
- dependencia compleja para operar el form


## 5. CSS

Se debe contemplar:

- desktop sidebar
- mobile sheet
- estados abiertos/cerrados
- listas con scroll interno
- estados activos


## Archivos probables a tocar

Backend:

- [FacetService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FacetService.php)
- posible nuevo view model de sidebar

Frontend PHP:

- [facet-refine.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-refine.php)
- [facet-attributes.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-attributes.php)
- nuevos partials si se divide el render

Frontend CSS:

- [catalog-facets.css](C:/xampp/htdocs/mi-pesca/public_html/assets/css/v2/pages/public/catalog-facets.css)

Frontend JS:

- [filters.js](C:/xampp/htdocs/mi-pesca/public_html/assets/js/catalog/filters.js)


## Checklist

- definir estructura agrupada en backend
- agregar resumen de filtros activos
- agregar `Limpiar todo`
- agregar bloque `Mas filtros`
- agrupar atributos por `group_name`
- definir fallback `Otros filtros`
- auto-open de grupos con seleccion activa
- limitar listas largas con `Ver mas`
- validar mobile
- validar desktop


## Criterio de aceptacion

La etapa se considera cerrada cuando:

- el aside no se siente plano ni interminable
- el usuario identifica claramente filtros principales y tecnicos
- los filtros activos son visibles y faciles de limpiar
- los atributos se presentan agrupados
- mobile y desktop comparten la misma logica de jerarquia
- no hay logica de negocio en vistas


## Nota de implementacion

Esta etapa es ideal antes de cargar muchos atributos reales en produccion.

Si se carga data masiva primero y se deja el aside tal como esta, la UX puede degradarse rapido.
