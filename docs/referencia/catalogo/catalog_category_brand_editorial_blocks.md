# Catalog Editorial Blocks

## Objetivo

Implementar un bloque editorial administrable para las landings:

- `/categoria?slug=...`
- `/marca?slug=...`

Debe gestionarse desde el panel de admin y quedar separado del bloque genérico de soporte del catálogo.

## Contexto actual

Hoy existen dos conceptos distintos:

### 1. Bloque genérico de soporte

Archivo actual:

- `app/Views/partials/catalogo/catalog-support.php`

Este bloque:

- es compartido entre `/productos`, `/categoria` y `/marca`
- muestra un texto genérico
- incluye CTA a WhatsApp
- no es un bloque SEO/editorial real

Conclusión:

- debe mantenerse como bloque de soporte/comercial
- no debe absorber contenido editorial por slug

### 2. Bloque editorial estructurado

Hoy existen piezas previas para categorías y marcas:

- `App\Services\Seo\CategorySeoBlocksService`
- `App\Services\Seo\BrandSeoBlocksService`
- repositorios asociados
- estructura con:
  - `seo_text`
  - `bullets`
  - `specs`
  - `faq`

Conclusión:

- la base de datos y parte de la lógica ya existen
- falta consolidar el render, el naming y el uso consistente en frontend/admin

## Decisión de arquitectura

Separar claramente dos bloques:

### A. Bloque genérico

Responsabilidad:

- soporte al usuario
- CTA
- mensaje transversal del catálogo

Archivo:

- `catalog-support.php`

Uso:

- `/productos`
- opcionalmente también `/categoria` y `/marca`, pero siempre como bloque secundario

### B. Bloque editorial

Responsabilidad:

- contenido específico por categoría o marca
- texto útil para el usuario
- información editable desde admin

Nuevo archivo propuesto:

- `app/Views/partials/catalogo/editorial-blocks.php`

Uso:

- `/categoria?slug=...`
- `/marca?slug=...`

No usar en:

- `/productos`

## Alcance funcional

El bloque editorial debe soportar:

### 1. Texto principal

Fuente:

- `seo_text`

Uso:

- introducción editorial
- descripción contextual de la categoría o marca

### 2. Puntos clave

Fuente:

- `bullets`

Uso:

- highlights rápidos
- ventajas o criterios de elección

### 3. Especificaciones o guía rápida

Fuente:

- `specs`

Uso:

- tabla corta tipo `label / value`
- por ejemplo:
  - “Uso recomendado”
  - “Modalidades”
  - “Nivel”

### 4. FAQ

Fuente:

- `faq`

Uso:

- preguntas frecuentes específicas de la landing

## Reglas de render

### Render en `/categoria`

Mostrar `editorial-blocks.php` si existe al menos uno de estos contenidos:

- `seo_text`
- `bullets`
- `specs`
- `faq`

Orden recomendado:

1. listado / grilla
2. paginación
3. `editorial-blocks.php`
4. `catalog-support.php`

### Render en `/marca`

Misma regla que categoría.

Orden recomendado:

1. listado / grilla
2. paginación
3. `editorial-blocks.php`
4. `catalog-support.php`

### Render en `/productos`

No usar el bloque editorial.

Mantener únicamente:

- `catalog-support.php`

## Admin

### Categorías

Pantalla:

- `app/Views/pages/admin/categories/form.php`

Debe permitir editar:

- `seo_text`
- `bullets`
- `specs`
- `faq`

Estado esperado:

- `seo_text` ya existe
- verificar si el panel de edición estructurada ya está montado y funcional
- si no está visible o quedó incompleto, integrarlo en el formulario

### Marcas

Pantalla:

- `app/Views/pages/admin/brands/form.php`

Debe permitir editar:

- `seo_text`
- `bullets`
- `specs`
- `faq`

Estado esperado:

- `seo_text` ya existe
- verificar si el panel estructurado ya está montado y funcional

## Refactor de naming

### Problema

El nombre `seo-blocks.php` induce a confusión y quedó mezclado con el bloque genérico.

### Acción

Renombrar:

- `app/Views/partials/catalogo/seo-blocks.php`

a:

- `app/Views/partials/catalogo/editorial-blocks.php`

Actualizar referencias en:

- `app/Views/pages/catalogo/categoria.php`
- `app/Views/pages/catalogo/marca.php`

## Archivos a tocar

### Frontend / vistas

- `app/Views/pages/catalogo/categoria.php`
- `app/Views/pages/catalogo/marca.php`
- `app/Views/partials/catalogo/editorial-blocks.php` (nuevo o renombrado)
- `app/Views/partials/catalogo/catalog-support.php`
- `public_html/assets/css/v2/pages/public/catalog.css`

### Admin

- `app/Views/pages/admin/categories/form.php`
- `app/Views/pages/admin/brands/form.php`
- cualquier partial admin que ya renderice el panel estructurado

### Servicios / repositorios

- `app/Services/Seo/CategorySeoBlocksService.php`
- `app/Services/Seo/BrandSeoBlocksService.php`
- repositorios asociados, solo si falta ajuste

## Estilo visual esperado

El bloque editorial no debe verse igual al bloque de soporte.

### Bloque editorial

Debería sentirse:

- más informativo
- más editorial
- menos CTA-driven

Visualmente:

- título fuerte
- texto cómodo de leer
- bullets claros
- tabla simple
- FAQ en acordeón o cards

### Bloque de soporte

Debería seguir viéndose como:

- mensaje complementario
- ayuda/acompañamiento
- CTA a WhatsApp

## Criterio de contenido

No usar texto genérico repetido entre landings.

El contenido editorial debe ser:

- específico por slug
- útil para un humano
- corto pero sustancioso

No hacer:

- relleno de keywords
- copy repetido en todas las categorías
- FAQ inventadas sin valor

## Propuesta de implementación por etapas

Sí, conviene partirlo en 2 etapas.

### Etapa 1. Consolidación técnica

Objetivo:

- recuperar y ordenar la estructura existente

Tareas:

1. Renombrar `seo-blocks.php` a `editorial-blocks.php`
2. Volver a integrarlo en `/categoria` y `/marca`
3. Mantener `catalog-support.php` como bloque aparte
4. Confirmar que admin permite guardar:
   - `seo_text`
   - `bullets`
   - `specs`
   - `faq`
5. Asegurar render correcto cuando:
   - hay contenido
   - no hay contenido

Resultado esperado:

- categorías y marcas ya pueden tener bloque editorial real por slug
- `/productos` sigue con soporte genérico

### Etapa 2. Re-estilizado y curaduría de contenido

Objetivo:

- mejorar UX visual y calidad editorial

Tareas:

1. Diseñar el look definitivo del bloque editorial
2. Ajustar spacing, jerarquía y responsive
3. Revisar si FAQ usa `details` o cards
4. Cargar contenido real desde admin para slugs prioritarios
5. Definir lineamientos editoriales para nuevos slugs

Resultado esperado:

- bloque editorial útil, consistente y visualmente integrado

## Criterio de aceptación

La tarea se considera cerrada cuando:

1. `/productos` usa solo `catalog-support.php`
2. `/categoria?slug=...` puede mostrar bloque editorial específico
3. `/marca?slug=...` puede mostrar bloque editorial específico
4. el contenido se administra desde admin
5. el naming deja de mezclar “support” con “editorial”
6. no queda lógica duplicada o ambigua entre partials

## Nota importante

No avanzar todavía con el re-estilizado final del bloque editorial en esta etapa si el rediseño de `/categoria` y `/marca` sigue pendiente.

Primero ordenar:

- naming
- render
- integración admin
- flujo de datos

Después diseñar.
