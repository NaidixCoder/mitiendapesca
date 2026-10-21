# Lineamientos Editoriales para Slugs de Catalogo

## Objetivo

Definir como cargar contenido editorial para las landings:

- `/categoria?slug=...`
- `/marca?slug=...`

Aplica al contenido administrado desde el panel de admin en:

- `seo_text`
- `bullets`
- `specs`
- `faq`

Este documento no define estilos visuales. Solo fija criterios de contenido y consistencia.

## Alcance

Estos lineamientos aplican a:

- categorias
- subcategorias
- marcas

No aplican al bloque generico `catalog-support`.

## Regla principal

Cada slug debe tener contenido propio, util y especifico.

No cargar texto generico repetido entre multiples categorias o marcas solo para "tener algo".

## Estructura de contenido

### 1. `seo_text`

Es el texto principal del bloque editorial.

Debe explicar:

- que incluye esa categoria o que caracteriza a esa marca
- para que uso sirve
- que deberia mirar el usuario antes de elegir

Recomendacion:

- 1 a 3 parrafos
- 120 a 300 palabras
- frases cortas
- tono claro y practico

### 2. `bullets`

Son puntos clave de lectura rapida.

Usarlos para:

- ventajas concretas
- criterios de eleccion
- diferenciales reales
- datos utiles que se entienden de un vistazo

Recomendacion:

- 3 a 5 bullets
- una idea por linea
- frases cortas

### 3. `specs`

Es una guia rapida tipo etiqueta + valor.

Usarlos para ordenar informacion como:

- uso recomendado
- modalidad
- nivel
- gama
- material
- entorno

Recomendacion:

- 3 a 6 filas
- label breve
- value concreto

### 4. `faq`

Son preguntas frecuentes reales del usuario.

Usarlas para responder dudas como:

- diferencias entre opciones
- que conviene para empezar
- compatibilidades
- usos recomendados
- cuidados o contexto de compra

Recomendacion:

- 2 a 5 preguntas
- respuestas breves y claras
- evitar preguntas artificiales

## Lineamientos para categorias

En una categoria, el contenido debe ayudar a entender:

- que productos entran ahi
- para que tipo de pesca o uso sirven
- como orientarse para elegir

### `seo_text` de categoria

Deberia cubrir:

- descripcion general de la categoria
- para quien sirve
- variables de eleccion mas importantes

Ejemplos de enfoque:

- `reeles`: tipos, tamanos, usos y equilibrio con la cana
- `canas`: largo, accion, potencia, material
- `senuelos`: tipos, profundidad, contexto de uso

### `bullets` de categoria

Buenos ejemplos:

- `Opciones para pesca liviana, media y pesada`
- `Modelos para agua dulce y salada`
- `Distintos tamanos segun tecnica y equipo`

Malos ejemplos:

- `La mejor calidad`
- `Gran variedad`
- `Productos increibles`

### `specs` de categoria

Ejemplos:

- `Uso recomendado` -> `Spinning liviano y variado`
- `Modalidades` -> `Costa, embarcado, agua dulce`
- `Nivel` -> `Inicial a avanzado`

### `faq` de categoria

Ejemplos utiles:

- `Que reel conviene para empezar`
- `Que diferencia hay entre un frontal y un rotativo`
- `Como elegir el tamano correcto`

## Lineamientos para marcas

En una marca, el contenido debe explicar:

- que caracteriza a la marca
- en que productos suele destacar
- para que perfil de usuario sirve

### `seo_text` de marca

Deberia cubrir:

- identidad o enfoque general de la marca
- categorias donde se destaca
- nivel o perfil de uso

No prometer cosas que no esten justificadas.

### `bullets` de marca

Buenos ejemplos:

- `Marca reconocida por su confiabilidad en pesca deportiva`
- `Catalogo con opciones de entrada y gama media`
- `Presencia fuerte en reels y accesorios`

Malos ejemplos:

- `La numero uno del mundo`
- `Calidad insuperable`
- `La mejor de todas`

### `specs` de marca

Ejemplos:

- `Enfoque` -> `Pesca deportiva`
- `Categorias fuertes` -> `Reeles, canas y accesorios`
- `Perfil de usuario` -> `Inicial, intermedio y avanzado`

### `faq` de marca

Ejemplos utiles:

- `Para que tipo de usuario conviene esta marca`
- `En que productos se destaca`
- `Tiene opciones de entrada o solo gamas altas`

## Que evitar

- texto duplicado entre slugs
- keyword stuffing
- frases vacias
- claims exagerados
- atributos tecnicos inventados
- escribir solo para buscadores

## Tono recomendado

El tono debe ser:

- claro
- directo
- concreto
- util para una persona que esta evaluando compra

No usar:

- lenguaje inflado
- slogans vacios
- exceso de venta dura

## Checklist rapido antes de guardar

Antes de guardar contenido nuevo para un slug, revisar:

1. El texto sirve para un humano o solo rellena.
2. El contenido es especifico de ese slug.
3. Los bullets agregan informacion real.
4. Las specs ordenan la lectura y no repiten lo mismo.
5. Las FAQ responden dudas reales.
6. No hay promesas tecnicas o comerciales sin sustento.

## Criterio de minima

Si no hay suficiente informacion util para completar todos los bloques:

- priorizar `seo_text`
- luego `bullets`
- luego `faq`
- usar `specs` solo si realmente aportan orden o contexto

Es mejor cargar menos contenido pero bueno, que llenar todos los campos con texto debil.

## Nota operativa

Por ahora, estos lineamientos quedan documentados y no se imponen desde el admin.

Mas adelante, si hace falta, se pueden sumar:

- ayudas contextuales en formularios
- placeholders mas orientados
- validaciones editoriales livianas
