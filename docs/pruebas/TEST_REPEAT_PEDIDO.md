# Test Plan: Repetir Pedido (`/cuenta/pedidos/ver?id=...`)

## Objetivo
Validar que la accion **Repetir pedido**:
- respete `variant_id` del pedido,
- no agregue lineas invalidas con precio `0` por perdida de variante,
- no agregue productos sin stock,
- informe correctamente los omitidos.

## Precondiciones
- Usuario autenticado con pedidos historicos.
- Al menos estos escenarios de datos:
1. Producto simple con stock.
2. Producto simple sin stock.
3. Producto con variantes con `variant_id` valido y stock.
4. Producto con variantes con `variant_id` valido y sin stock.
5. Pedido viejo con `variant_id=0` pero `variant_sku` presente.
6. Pedido viejo con `variant_id=0` y sin `variant_sku` utilizable.

## Casos de prueba

### Caso 1: Carrito vacio, pedido con todo disponible
1. Vaciar carrito.
2. Ir a `/cuenta/pedidos/ver?id={pedido}`.
3. Click en `Repetir pedido`.
4. Verificar redirect a `/carrito?repeat=1`.
5. Verificar que se agregan todos los items esperados.
6. Verificar que precios y subtotales no son `0` salvo que el producto realmente valga `0`.

Resultado esperado:
- Todos los items se agregan correctamente.
- No aparece aviso de omitidos.

### Caso 2: Carrito vacio, pedido con items parcialmente sin stock
1. Vaciar carrito.
2. Repetir pedido con mezcla de stock y sin stock.
3. Verificar `/carrito?repeat=1`.

Resultado esperado:
- Se agregan solo los disponibles.
- Flash de aviso indicando cantidad de omitidos.
- Ningun item sin stock queda seleccionable para checkout.

### Caso 3: Carrito con productos, repetir pedido
1. Agregar cualquier producto al carrito.
2. Repetir pedido.
3. Verificar `/carrito?repeat=1`.

Resultado esperado:
- Se suman (merge) solo los disponibles.
- Se mantiene contenido previo del carrito.
- Si hubo omitidos, se informa.

### Caso 4: Pedido con variantes (snapshot correcto)
1. Repetir pedido que tenga variantes con `variant_id` valido.

Resultado esperado:
- Se agrega exactamente la variante original (mismo SKU/titulo).
- No se reemplaza automaticamente por otra variante.

### Caso 5: Pedido viejo con `variant_id=0` y `variant_sku` recuperable
1. Repetir pedido viejo donde el snapshot no tiene `variant_id`.

Resultado esperado:
- Se resuelve la variante por `variant_sku`.
- Se agrega con precio y stock correctos.

### Caso 6: Pedido viejo con `variant_id=0` y sin variante recuperable
1. Repetir pedido viejo sin `variant_id` ni `variant_sku` valido.

Resultado esperado:
- Si el producto requiere variante, ese item se omite.
- Se muestra aviso de omitido.
- No aparece linea con precio `0` por fallback al producto base.

### Caso 7: Link "Ver producto" en detalle de pedido
1. En `/cuenta/pedidos/ver?id={pedido}`, click en `Ver producto`.

Resultado esperado:
- URL debe ser `/producto?id={id}` (sin `/public_html`).
- Abre ficha publica correcta del producto.

## Checklist rapido de regresion
- `/carrito/repeat/merge` sigue funcionando.
- `/carrito/repeat/replace` sigue funcionando.
- Checkout no permite avanzar con items no seleccionables por stock.
- No hay errores PHP ni warnings en logs del flujo.
