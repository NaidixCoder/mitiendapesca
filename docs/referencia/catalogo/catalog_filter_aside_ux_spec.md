# Catalog Filter Aside UX Spec

## Objetivo

Definir una estructura de UX clara y escalable para el aside de filtros del catalogo, evitando que se vuelva demasiado extenso cuando se activen mas atributos filtrables.


## Problema

El aside actual funciona bien con:

- categorias
- marcas
- precio

Pero al sumar atributos filtrables reales como:

- modalidad
- largo
- accion
- material
- formato

el panel puede crecer demasiado y perder claridad, sobre todo en mobile.


## Principio rector

No mostrar todo al mismo nivel.

El usuario debe entender una jerarquia clara:

1. donde esta navegando
2. como acota rapido
3. como refina tecnicamente


## Jerarquia propuesta

### Nivel 1. Navegacion principal

- Categorias

### Nivel 2. Recorte comercial fuerte

- Marca
- Precio

### Nivel 3. Refinamiento tecnico

- atributos filtrables agrupados


## Estructura objetivo

## Mobile

Orden dentro del bottom sheet:

1. resumen de filtros activos
2. Categorias
3. Marca
4. Precio
5. Mas filtros
6. acciones finales

### Resumen de filtros activos

Ubicacion:

- debajo del titulo `Filtros`

Contenido:

- cantidad de filtros activos
- chips resumidos
- accion `Limpiar`

Ejemplo:

- `3 filtros activos`
- `Marca: Shimano`
- `Modalidad: Spinning`
- `Accion: Rapida`

### Categorias

- visible y prioritaria
- abierta por defecto
- mantiene estructura actual de arbol

### Marca

- visible por defecto
- abierta por defecto
- lista corta con scroll interno si hace falta

### Precio

- visible por defecto
- abierta por defecto

### Mas filtros

- bloque contenedor de todos los atributos filtrables
- cerrado por defecto
- si existe algun atributo activo adentro, arranca abierto

Dentro de `Mas filtros`, usar grupos por `group_name`.

Ejemplos:

- Uso
- Medidas
- Construccion
- Rendimiento

Cada grupo:

- se renderiza como acordeon
- arranca cerrado por defecto
- si tiene algun filtro activo, arranca abierto

Cada faceta dentro del grupo:

- muestra titulo claro
- muestra opciones con count
- muestra solo 5 a 7 opciones inicialmente
- si tiene mas, agrega `Ver mas`


## Desktop

Orden recomendado en sidebar:

1. Filtros activos
2. Categorias
3. Marca
4. Precio
5. separador
6. Mas filtros

### Filtros activos

Solo aparece si hay seleccion.

Debe incluir:

- chips activos
- `Limpiar todo`

### Categorias

- visible y alta prioridad

### Marca

- visible y alta prioridad

### Precio

- visible y alta prioridad

### Mas filtros

- bloque secundario de refinamiento
- puede estar abierto por defecto
- los grupos internos se comportan como acordeones


## Agrupacion por atributos

Se debe aprovechar `group_name` del atributo para organizar el aside.

Ejemplo sugerido:

### Uso

- modalidad
- tecnica

### Medidas

- largo
- tamaño
- peso

### Construccion

- material
- formato

### Rendimiento

- accion
- potencia
- profundidad


## Reglas UX

- no mostrar atributos filtrables como una lista plana larga
- no abrir todos los grupos por defecto
- abrir automaticamente el grupo que tenga una seleccion activa
- no mostrar facetas irrelevantes para el contexto actual
- si una faceta tiene 0 resultados, no renderizarla
- si una faceta tiene una sola opcion util, evaluar ocultarla
- mostrar counts siempre que ayuden a la decision
- mantener `Limpiar todo` visible


## Reglas de visibilidad

### Siempre visibles

- Categorias
- Marca
- Precio

### Secundarios

- atributos filtrables

### Contextuales

El orden y presencia de atributos debe poder variar por landing.

Ejemplos:

#### `categoria?slug=canas`

Priorizar:

- modalidad
- largo
- accion
- formato
- material

#### `categoria?slug=reeles`

Priorizar:

- tamaño
- relacion
- capacidad
- material

#### `categoria?slug=senuelos`

Priorizar:

- tipo
- profundidad
- peso
- accion


## Comportamiento recomendado

### Auto-open contextual

Un grupo debe abrirse automaticamente si:

- tiene algun filtro activo
- o contiene una faceta marcada como prioritaria para la landing

### Ver mas / Ver menos

Aplicar por faceta, no por grupo completo.

### Scroll

- el panel general puede seguir teniendo scroll
- pero las listas largas deben tener scroll interno controlado
- evitar que precio y aplicar queden demasiado abajo


## Propuesta minima de implementacion

Para una primera version razonable:

- mantener `Categorias`, `Marca` y `Precio`
- agregar bloque `Mas filtros`
- dentro de `Mas filtros`, agrupar por `group_name`
- grupos colapsables
- auto-open si hay filtros activos
- chips activos visibles arriba
- `Limpiar todo`

Esta version ya mejora mucho la escalabilidad sin rehacer toda la UX.


## Propuesta posterior

En una fase mas avanzada:

- permitir prioridad de facetas por categoria
- permitir widgets por tipo de atributo
- agregar buscador interno solo en facetas largas
- diferenciar visualmente filtros basicos y filtros tecnicos


## Criterio de aceptacion

Se considera resuelto cuando:

- el aside escala con multiples atributos sin sentirse caotico
- el usuario entiende que esta haciendo en cada nivel
- los filtros activos son visibles y removibles
- mobile y desktop comparten la misma logica jerarquica
- el sidebar no se convierte en una lista plana interminable
