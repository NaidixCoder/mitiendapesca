# Nota Tecnica: Seguridad Carrito / Checkout

Fecha: 2026-03-18

## Alcance

Se completo el endurecimiento del flujo `carrito -> checkout -> shipping -> payment` con foco en manipulacion desde navegador y consistencia server-side.

## Cambios principales

- Seleccion de items del carrito persistida en sesion mediante `app/Services/Cart/CartSelectionService.php`
- Resumen del carrito actualizado por AJAX en `public_html/assets/js/cart/cart.js` y `app/Controllers/Cart/CartController.php`
- Checkout, shipping, intent y creacion de pedido operan solo sobre items seleccionados en:
  - `app/Controllers/Checkout/CheckoutController.php`
  - `app/Controllers/Checkout/ShippingController.php`
  - `app/Services/Shipping/CheckoutShippingService.php`
  - `app/Services/Checkout/CheckoutIntentService.php`
  - `app/Services/Checkout/CheckoutOrderService.php`
- Post-compra: solo se remueven del carrito los items efectivamente comprados en `app/Services/Checkout/CheckoutOrderService.php`

## Endurecimientos aplicados

### POST /carrito/seleccion

- Ignora keys inventadas
- Mantiene solo keys validas del carrito actual

### POST /carrito/quitar

- Ya no responde exito silencioso para `product_id` inexistente
- Devuelve `404` si el item no esta en el carrito

### POST /carrito/actualizar

- Ya no responde `ok: true` para keys inexistentes
- Devuelve `404` si la key no pertenece al carrito
- Sigue clampando cantidades al stock real

### CSRF

- `app/Support/helpers.php` ahora devuelve JSON consistente para requests AJAX con CSRF invalido

## Pruebas ejecutadas

- Carrito guest y autenticado
- Manipulacion de `selected_keys[]`
- Manipulacion de `qty[...]`
- `remove` con IDs falsos
- `shipping calculate/select` con `address_id`, `calculation_id` y `option_id` invalidos
- Replay de seleccion de envio luego de cambiar cantidad del carrito
- `payment select` con metodo invalido
- `mp/preference` y `mp/payment` con direccion invalida

## Resultados

- No se encontro bypass util para modificar subtotal, envio, seleccion, items comprados o direccion desde el navegador
- `shipping` invalida snapshots viejos cuando cambia la firma del carrito
- `payment` no toma montos del navegador; reconstruye el intent server-side
- Mercado Pago quedo operativo en local tras ajustar `app/Services/Payments/MercadoPagoService.php` para no enviar `auto_return` en hosts no publicos
- `POST /checkout/pagar` fue validado y redirige correctamente al `init_point` de MP

## Validaciones finales

- `php -l` paso en los archivos PHP modificados
- `POST /checkout/mp/preference` validado con `200`
- `POST /checkout/pagar` validado con `302` a Mercado Pago

## Riesgo residual

- La integracion de Correo en este entorno sigue corriendo en modo QA/simulado para cotizacion
- No se modifico configuracion ni credenciales de Correo Argentino
- Conviene repetir un smoke test en el entorno mas parecido a produccion antes de cerrar despliegue
