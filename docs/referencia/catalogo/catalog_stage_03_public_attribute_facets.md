# Catalog Stage 03: Facetas Publicas por Atributo

## Objetivo

Integrar atributos `select` marcados como filtrables al catalogo publico.

## Alcance de esta etapa

- normalizacion de request `attr[slug][]=value_id`
- filtrado real en catalogo publico usando `product_attribute_values`
- facetas publicas por atributo
- chips activos para atributos
- preservacion de atributos en sort y navegacion

## Decision aplicada

En esta etapa los valores de atributo viajan por `id` de `attribute_values`.

Ejemplo:

- `attr[modalidad][]=12`
- `attr[formato][]=48`

Esto permite integrar ya con la DB actual sin rediseñar `attribute_values`.

## Alcance que queda fuera

- slugs propios para `attribute_values`
- facetas de atributos numericos
- rangos por atributo
- UI avanzada tipo pills/accordeon por widget

## Archivos involucrados

- [FilterService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FilterService.php)
- [ProductRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Catalog/ProductRepository.php)
- [FacetService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FacetService.php)
- [FilterChipService.php](C:/xampp/htdocs/mi-pesca/app/Services/Catalog/FilterChipService.php)
- [facet-attributes.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-attributes.php)
- [facet-refine.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/facet-refine.php)
- [topbar.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/catalogo/topbar.php)
- controladores de catalogo

## Estado

Implementado a nivel backend + render base.

## Pendiente posterior

- validacion funcional en navegador
- evaluar slugs propios por valor
- mejorar UX visual de facetas por tipo de atributo

