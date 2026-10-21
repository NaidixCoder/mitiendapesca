# Catalog Stage 01: Metadatos de Atributos

## Objetivo

Preparar la base profesional para atributos filtrables y visibles, sin activar aun todo el sistema de facetas publicas.

Esta etapa fija las reglas del modelo y deja el proyecto listo para crecer sin rehacer la base cuando la web este en produccion.


## Relacion con el plan integral

Documento madre:

- [catalog_full_integrated_implementation_plan.md](C:/xampp/htdocs/mi-pesca/docs/referencia/catalogo/catalog_full_integrated_implementation_plan.md)

Decision log:

- [catalog_decision_log.md](C:/xampp/htdocs/mi-pesca/docs/referencia/catalogo/catalog_decision_log.md)


## Alcance

Esta etapa incluye:

- extender metadatos de `attributes`
- definir semantica de cada atributo
- preparar soporte backend para leer esos metadatos
- dejar documentado el comportamiento esperado

Esta etapa no incluye:

- filtros publicos por atributos
- chips navegables
- rediseño del sidebar del catalogo
- tab final de atributos en producto
- backfill completo de datos


## Problema a resolver

Hoy existe infraestructura de atributos, pero no existe una capa formal que diga:

- si un atributo es filtrable
- si un atributo es visible al cliente
- si un atributo genera variantes
- en que orden debe aparecer
- como debe agruparse o etiquetarse publicamente

Sin eso, cualquier integracion futura con catalogo publico queda fragil.


## Estado actual auditado

Tablas relevantes ya existentes:

- `attributes`
- `attribute_values`
- `product_attributes`
- `product_attribute_values`
- `product_variants`
- `product_variant_values`
- `variant_value_sets`
- `variant_value_set_items`

Campos actuales de `attributes`:

- `id`
- `name`
- `slug`
- `type`
- `created_at`

Conclusion:

- la tabla ya existe y es el lugar correcto para agregar metadatos


## Resultado esperado

Al cerrar esta etapa, cada atributo debe poder responder:

- es filtrable o no
- es visible o no
- es eje de variante o no
- que orden tiene
- que unidad usa
- que grupo funcional integra


## Propuesta tecnica

## 1. Extender tabla `attributes`

Campos sugeridos:

- `code` `varchar(120)` nullable o unique segun decision final
- `is_filterable` `tinyint(1)` default `0`
- `is_public` `tinyint(1)` default `1`
- `is_variant_axis` `tinyint(1)` default `0`
- `facet_sort` `int unsigned` default `0`
- `group_name` `varchar(80)` nullable
- `unit` `varchar(32)` nullable

Opcional futuro:

- `public_label`
- `facet_widget`
- `help_text`

### Criterio

- `slug` puede seguir siendo la referencia principal externa
- `code` sirve si se quiere una clave tecnica mas estable
- no bloquear esta etapa por decidir si `code` sera obligatorio desde el dia uno


## 2. Semantica definida

### `is_filterable`

- controla si el atributo puede aparecer como faceta en catalogo

### `is_public`

- controla si el atributo puede renderizarse en ficha o bloques publicos

### `is_variant_axis`

- controla si el atributo es candidato a generar combinaciones de variantes

### `facet_sort`

- define el orden de facetas en frontend

### `group_name`

- permite agrupar atributos por bloques, por ejemplo:
  - Medidas
  - Construccion
  - Uso

### `unit`

- define sufijo o semantica de valor:
  - `m`
  - `cm`
  - `g`
  - `lb`


## 3. Reglas funcionales de negocio

Reglas que esta etapa deja fijadas:

- un atributo puede ser filtrable y no visible
- un atributo puede ser visible y no filtrable
- un atributo puede ser eje de variante y tambien filtrable, pero no siempre
- un producto simple puede usar atributos filtrables sin tener variantes


## 4. Backend

Tareas esperadas:

- migration para `attributes`
- ajustes en repositorios y servicios admin que leen atributos
- asegurar compatibilidad hacia atras cuando falten columnas en entornos viejos

Archivos candidatos:

- [AdminVariantReadRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Admin/AdminVariantReadRepository.php)
- controladores/servicios de atributos en admin

Objetivo tecnico:

- que el sistema siga funcionando aunque aun no se usen visualmente todos los nuevos campos


## 5. Admin

No hace falta cerrar toda la UI final de atributos en esta etapa, pero si:

- mostrar metadatos nuevos en el admin de atributos
- permitir editarlos
- validarlos

Minimo deseable:

- filtrable
- visible
- eje de variante
- orden
- unidad


## Fuera de alcance

No hacer aun:

- sidebar publico por atributos
- filtros reales de catalogo
- chips editoriales
- chips navegables
- QA masivo de productos


## Archivos y areas a tocar

### DB

- nueva migration sobre `attributes`

### Admin

- [AttributesController.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Admin/AttributesController.php)
- traits de escritura/catalogo en admin de atributos
- vistas de admin de atributos

### Lectura tecnica

- [AdminVariantReadRepository.php](C:/xampp/htdocs/mi-pesca/app/Repositories/Admin/AdminVariantReadRepository.php)
- servicios relacionados con variantes/atributos


## Checklist de implementacion

- crear migration de metadatos en `attributes`
- actualizar lectura de atributos para contemplar columnas nuevas
- actualizar guardado admin de atributos
- exponer campos nuevos en UI admin de atributos
- validar create/edit
- verificar compatibilidad con editor de variantes
- documentar atributos base iniciales por rubro


## Criterio de aceptacion

La etapa se considera cerrada cuando:

- `attributes` ya soporta metadatos funcionales
- admin permite editarlos
- el editor de productos/variantes no se rompe
- queda claro para el equipo que:
  - filtrable
  - visible
  - eje de variante
  son conceptos distintos


## Riesgos

### Riesgo 1

Agregar demasiados campos de una sola vez.

Mitigacion:

- agregar solo los metadatos utiles para las proximas etapas

### Riesgo 2

Acoplar de nuevo atributo filtrable con variante.

Mitigacion:

- respetar el decision log


## Cierre esperado de la sesion de implementacion

Al finalizar esta etapa, el reporte de cierre debe incluir:

- migration creada
- archivos tocados
- validacion realizada
- comportamiento confirmado
- pendientes reales para la etapa 2

