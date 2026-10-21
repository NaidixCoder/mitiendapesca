# Propiedades Filtrables vs Opciones de Variante

## Objetivo

Cerrar una definicion de negocio y de UX para evitar que el sistema siga mezclando:

- datos del producto que sirven para filtrar o describir
- datos que generan variantes vendibles

Este documento reemplaza cualquier interpretacion ambigua basada en la palabra `atributo` como concepto visible para el usuario del admin.

## Definiciones

### 1. Opcion de variante

Una `opcion de variante` es una dimension del producto que genera combinaciones vendibles.

Impacta en al menos uno de estos puntos:

- SKU
- stock
- precio
- seleccion concreta de una variante

Ejemplos:

- `Color`
- `Talle`
- `Medida`, si cambia la variante real vendida

### 2. Propiedad filtrable

Una `propiedad filtrable` es un dato del producto que no genera variantes.

Sirve para:

- filtrar en catalogo
- describir el producto
- mostrar informacion en ficha
- enriquecer estructura y contexto del producto

Ejemplos:

- `Modalidad`
- `Material`
- `Accion`
- `Formato`
- `Largo`, si solo describe o filtra y no crea una variante vendible

## Regla de negocio

La clasificacion no depende del nombre del campo.

Depende del uso comercial.

### Ejemplo importante: `Largo`

`Largo` puede ser:

- `opcion de variante`, si cambia la variante vendida
- `propiedad filtrable`, si solo describe el producto y se usa para filtrar

## Decision de producto

No usar `atributo` como concepto principal en la UI del admin.

El lenguaje visible debe separar solo estos dos mundos:

- `Opciones de variante`
- `Propiedades`

En contexto de catalogo publico o facetas, las `Propiedades` pueden ser filtrables.

## Decision sobre `/admin/attributes`

La pantalla tecnica puede seguir viviendo en la ruta:

- `/admin/attributes`

Pero visualmente no debe presentarse al usuario como `Atributos`.

Debe representar el configurador maestro del catalogo.

Nombre sugerido visible:

- `Propiedades del catalogo`

Dentro de ese configurador deben existir dos tipos de definicion maestra:

- `variant_option`
- `filter_property`

## Decision tecnica

Cada definicion maestra del catalogo debe tener un `purpose` obligatorio.

Valores permitidos:

- `variant_option`
- `filter_property`

Esto evita depender de combinaciones ambiguas de flags para decidir el comportamiento central.

Los flags complementarios pueden seguir existiendo, pero no deben reemplazar el `purpose`.

### Flags complementarios esperables

- `is_public`
- `is_filterable`

Interpretacion:

- una `variant_option` puede o no ser visible
- una `filter_property` puede o no ser visible
- una `filter_property` puede o no ser filtrable

Pero solo una `variant_option` puede entrar al flujo de variantes

## Flujo de producto

### Producto simple

Pestañas relevantes:

- `Basicos`
- `Venta`
- `Propiedades`
- `Imagenes`
- `SEO`
- `Publicacion`

En `Propiedades` se cargan solamente definiciones de tipo:

- `filter_property`

Ejemplo:

- `Modalidad = Baitcast`
- `Material = Carbono`
- `Formato = Telescopica`

No existe flujo de combinaciones.

### Producto con variantes

Pestañas relevantes:

- `Basicos`
- `Venta`
- `Opciones de variante`
- `Propiedades`
- `Variantes`
- `Imagenes`
- `SEO`
- `Publicacion`

#### A. `Opciones de variante`

Aqui se pueden usar solo definiciones maestras con:

- `purpose = variant_option`

Ejemplo:

- `Color`
- valores: `Rojo`, `Verde`, `Azul`

Desde esta pestaña se definen los ejes que generan combinaciones.

#### B. `Propiedades`

Aqui se pueden usar solo definiciones maestras con:

- `purpose = filter_property`

Ejemplo:

- `Modalidad = Baitcast`
- `Material = Carbono`

Estas propiedades aplican al producto, no a cada variante.

#### C. `Variantes`

Esta pestaña trabaja solo sobre las combinaciones generadas por:

- `Opciones de variante`

Aqui se completa:

- SKU
- stock
- precio
- estado de la variante

## Regla clave para warnings y completitud

Los warnings de:

- `Variantes incompletas`

deben calcularse usando solo:

- `variant_option`

Nunca deben considerar:

- `filter_property`

Consecuencia directa:

`Modalidad` nunca debe volver a disparar variantes incompletas.

## Regla de persistencia

### Propiedades

Las `filter_property` se guardan a nivel producto.

No deben exigir valor por variante.

### Opciones de variante

Las `variant_option` participan en:

- generacion de combinaciones
- valores por variante
- validacion de variantes incompletas

## Reglas de UX

### Lo que el usuario debe entender

- `Propiedades`: describen el producto
- `Opciones de variante`: crean variantes vendibles
- `Variantes`: completan SKU, stock y precio por combinacion

### Lo que no debe volver a pasar

No debe ser posible que una propiedad pensada solo para filtro:

- aparezca como requerida para variantes
- ensucie el editor de combinaciones
- rompa el warning de completitud

## Ejemplos cerrados

### Caso 1

Producto: caña simple

- `Modalidad = Baitcast`
- `Formato = Telescopica`

Todo eso va en:

- `Propiedades`

### Caso 2

Producto: señuelo con variantes de color

- `Color = Rojo / Verde / Azul`

Esto va en:

- `Opciones de variante`

Luego:

- `Variantes`

### Caso 3

Producto: caña con `Color` y `Modalidad`

- `Color` -> `Opciones de variante`
- `Modalidad` -> `Propiedades`

## Decision de implementacion

Antes de seguir modificando logica de producto o de variantes:

1. mantener esta definicion como fuente de verdad
2. adaptar admin y backend a este modelo
3. no introducir nuevas features sobre el modelo viejo mezclado

## Proximo paso recomendado

Implementar la separacion tecnica real en producto:

1. `Opciones de variante`
2. `Propiedades`
3. ajustar warning de variantes incompletas para mirar solo `variant_option`
