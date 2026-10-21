# Migracion V2 Carrito

## Objetivo

Migrar `/public_html/carrito` desde markup/clases legacy a un CSS nuevo `v2`, manteniendo intacta la logica actual del carrito y sus hooks JS.

Referencia visual:
- mockup mobile aportado por el usuario

Observaciones cerradas:
- ignorar precio tachado
- mostrar `precio de lista`
- debajo mostrar badge `-%x descuento transferencia`
- debajo mostrar `precio transferencia`
- usar variables de [tokens.css](C:/xampp/htdocs/mi-pesca/public_html/assets/css/v2/tokens.css)

## Auditoria actual

### Vista principal

Archivo:
- [index.php](C:/xampp/htdocs/mi-pesca/app/Views/pages/carrito/index.php)

Estado:
- usa muchas clases legacy / Tailwind
- mezcla layout, estados de merge/repeat y render de items
- no tiene namespace visual `v2`
- el carrito no vacio hoy renderiza:
  - titulo + vaciar
  - avisos de cambio / merge / repeat
  - lista de items
  - resumen lateral

### Hooks JS que no se deben romper

Archivo:
- [cart.js](C:/xampp/htdocs/mi-pesca/public_html/assets/js/cart/cart.js)

Hooks obligatorios:
- `[data-cart-form]`
- `[data-cart-item]`
- `data-key`
- `data-stock`
- `[data-cart-qty]`
- `[data-cart-stock-msg]`
- `[data-cart-line-total]`
- `[data-cart-remove]`
- `[data-cart-subtotal]`

Conclusión:
- podemos rehacer el HTML y clases
- no debemos cambiar esos atributos

### Datos disponibles por item

Origen:
- [CartController.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Cart/CartController.php)
- servicio de lectura del carrito ya entrega `items` y `subtotal`

Campos usados hoy en la vista:
- `id`
- `variant_id`
- `key`
- `sku`
- `name`
- `variant_label`
- `thumb`
- `price`
- `list_price`
- `qty`
- `stock`
- `line_total`

Conclusión:
- ya existe la informacion necesaria para mostrar:
  - precio de lista
  - descuento transferencia
  - precio transferencia

### Estado vacio

Archivo:
- [empty.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/carrito/empty.php)

Estado:
- tambien usa Tailwind / legacy
- no responde al mockup de carrito cargado

### Recomendados

Archivos:
- [related-products.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/components/related-products.php)
- [empty.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/carrito/empty.php)

Estado:
- el carrito debe reutilizar el mismo bloque `Elegidos para vos` que usa producto
- reutiliza `product-card`
- en el mockup el bloque `Elegidos para vos` aparece debajo del resumen del carrito cargado

Conclusión:
- hay que decidir si el carrito no vacio tambien mostrara ese bloque
- idealmente si, reutilizando el mismo partial o una variante minima

### Carga de CSS v2

Archivo:
- [head.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/head.php)

Estado:
- no existe aun `cart.css` en `v2`
- hoy `head.php` carga:
  - `tokens.css`
  - `base.css`
  - `ui.css`
  - `utilities.css`
  - layouts publicos
  - `catalog.css`
  - `catalog-facets.css`
  - `catalog-card.css`
  - `product.css`

Conclusión:
- hay que crear y registrar un nuevo archivo:
  - `public_html/assets/css/v2/pages/public/cart.css`

## Decision de implementacion

### CSS

Crear:
- [cart.css](C:/xampp/htdocs/mi-pesca/public_html/assets/css/v2/pages/public/cart.css)

Reglas:
- namespace propio
  - `cart-page__...`
  - `cart-list__...`
  - `cart-item__...`
  - `cart-summary__...`
- usar tokens de [tokens.css](C:/xampp/htdocs/mi-pesca/public_html/assets/css/v2/tokens.css)
- no meter colores duros salvo excepciones justificadas

### Vista

Rehacer:
- [index.php](C:/xampp/htdocs/mi-pesca/app/Views/pages/carrito/index.php)

Con esta estructura objetivo:
- header de carrito
- selector superior estilo mockup
  - `Todos los productos`
- lista de items
- resumen:
  - productos
  - envio
  - total
- CTA `Continuar compra`
- bloque `Elegidos para vos`

### Item de carrito

Cada item debe mostrar:
- checkbox visual
- miniatura
- nombre del producto
- selector de cantidad
- icono/boton eliminar
- `precio de lista`
- badge `-%x descuento transferencia`
- `precio transferencia`

Jerarquia visual:
- `precio de lista` arriba
- badge de transferencia debajo
- `precio transferencia` como segundo precio fuerte

Nota:
- ignorar precio tachado segun pedido del usuario

### Logica comercial

No inventar datos nuevos en la vista.

Necesitamos un view-model por item que exponga:
- `list_price_label`
- `transfer_price_label`
- `transfer_discount_pct`
- `has_transfer_discount`

La vista no deberia calcular porcentaje.

### Recomendados

Mantener reutilizacion de `product-card` siempre que no rompa el mockup.

Si hace falta:
- envolver recomendaciones con una shell visual nueva del carrito
- pero no rehacer `product-card` dentro de esta etapa

## Archivos a tocar

### Nuevos
- [cart.css](C:/xampp/htdocs/mi-pesca/public_html/assets/css/v2/pages/public/cart.css)

### Modificados
- [head.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/head.php)
- [CartController.php](C:/xampp/htdocs/mi-pesca/app/Controllers/Cart/CartController.php)
- [index.php](C:/xampp/htdocs/mi-pesca/app/Views/pages/carrito/index.php)
- [empty.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/carrito/empty.php)
- [related-products.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/components/related-products.php)

Si hace falta para el view-model:
- servicio o helper de precios comerciales compartido

## Fuera de alcance

- rehacer la logica JS del carrito
- rehacer `product-card`
- tocar checkout
- cerrar ahora mismo detalles pendientes de catalogo/cards

## Criterio de aceptacion

- `/carrito` usa clases nuevas `v2`
- `cart.css` esta cargado desde `head.php`
- los hooks `data-cart-*` siguen funcionando
- el carrito no vacio replica el mockup mobile de forma cercana
- se muestra:
  - precio de lista
  - badge `-%x descuento transferencia`
  - precio transferencia
- no se reintroduce precio tachado
- el resumen y CTA quedan claramente visibles
- `Elegidos para vos` aparece debajo del resumen

## Riesgos

- si se rehace demasiado el markup sin respetar `data-cart-*`, se rompe `cart.js`
- si el descuento se calcula en la vista, se mezcla logica comercial con presentacion
- si se toca `product-card` para resolver carrito, se vuelve a mezclar este trabajo con catalogo

## Orden recomendado

1. crear `cart.css`
2. registrar `cart.css` en `head.php`
3. preparar view-model de precios comerciales por item
4. rehacer carrito no vacio
5. rehacer carrito vacio
6. integrar bloque `Elegidos para vos`
7. validar que `cart.js` sigue funcionando
