# Checkout/Cart Contracts

## Entrypoints (scope)

| METHOD + PATH | Handler | ResponseType | Notas (session keys tocadas) |
| --- | --- | --- | --- |
| `GET /carrito` | `App\\Controllers\\Cart\\CartController@index` | `view` (`render_page`) | Lee `repeat_order_items`, `cart_merge_pending`. |
| `POST /carrito/agregar` | `App\\Controllers\\Cart\\CartController@add` | `json` o `redirect` | No escribe sesion directa; usa carrito persistente. |
| `POST /carrito/actualizar` | `App\\Controllers\\Cart\\CartController@update` | `json` o `redirect` | No escribe sesion directa; usa carrito persistente. |
| `POST /carrito/quitar` | `App\\Controllers\\Cart\\CartController@remove` | `json` o `redirect` | No escribe sesion directa; usa carrito persistente. |
| `POST /carrito/vaciar` | `App\\Controllers\\Cart\\CartController@clear` | `json` o `redirect` | No escribe sesion directa; usa carrito persistente. |
| `POST /carrito/repeat/merge` | `App\\Controllers\\Cart\\CartController@repeatMerge` | `redirect` | Lee+borra `repeat_order_items`. |
| `POST /carrito/repeat/replace` | `App\\Controllers\\Cart\\CartController@repeatReplace` | `redirect` | Lee+borra `repeat_order_items`. |
| `POST /carrito/repeat/cancel` | `App\\Controllers\\Cart\\CartController@repeatCancel` | `redirect` | Borra `repeat_order_items`. |
| `POST /carrito/merge/unify` | `App\\Controllers\\Cart\\CartController@mergeUnify` | `redirect` | Consume `cart_merge_pending` (via servicio). |
| `POST /carrito/merge/replace` | `App\\Controllers\\Cart\\CartController@mergeReplace` | `redirect` | Consume `cart_merge_pending` (via servicio). |
| `POST /carrito/merge/discard` | `App\\Controllers\\Cart\\CartController@mergeDiscard` | `redirect` | Consume `cart_merge_pending` (via servicio). |
| `GET /checkout` | `App\\Controllers\\Checkout\\CheckoutController@index` | `view` o `redirect` | Lee/escribe `checkout_shipping`, `checkout_shipping_calc`, `checkout_payment`, `checkout_cart_sig_warned`, `checkout_last_order_id`, `checkout_last_order_ts`. |
| `POST /checkout/confirmar` | `App\\Controllers\\Checkout\\CheckoutController@confirm` | `redirect` | Sin escritura de sesion relevante. |
| `POST /checkout/transferencia` | `App\\Controllers\\Checkout\\CheckoutController@transferencia` | `redirect` | Puede escribir `checkout_shipping` (free), `checkout_last_order_id`, `checkout_last_order_ts`. |
| `POST /checkout/pago/seleccionar` | `App\\Controllers\\Checkout\\PaymentController@select` | `json` | Escribe `checkout_payment`. |
| `POST /checkout/mp/preference` | `App\\Controllers\\Checkout\\PaymentController@preference` | `json` | Usa snapshot de checkout intent (lee `checkout_shipping` via service). |
| `POST /checkout/mp/payment` | `App\\Controllers\\Checkout\\PaymentController@payment` | `json` | Usa checkout intent + crea/actualiza pago. |
| `POST /checkout/pagar` | `App\\Controllers\\Checkout\\PaymentController@pay` | `redirect` | Redirige a `init_point` de MP (sin cambio de ruta local). |
| `GET /pago/resultado` | `App\\Controllers\\Checkout\\PaymentController@result` | `redirect` | Redirige a `/cuenta/pedidos` o `/checkout?step=4`. |
| `POST /checkout/envio/calcular` | `App\\Controllers\\Checkout\\ShippingController@calculate` | `json` | Escribe `checkout_shipping_calc`; puede limpiar `checkout_shipping`. |
| `POST /checkout/envio/seleccionar` | `App\\Controllers\\Checkout\\ShippingController@select` | `json` | Escribe `checkout_shipping`; puede limpiar `checkout_shipping_calc`. |

## Session keys (checkout/cart)

- `uid`
- `email`
- `urole` / `role`
- `cart_count`
- `cart_merge_pending`
- `repeat_order_items`
- `checkout_shipping`
- `checkout_shipping_calc`
- `checkout_payment`
- `checkout_cart_sig_warned`
- `checkout_last_order_id`
- `checkout_last_order_ts`
- `marketing_campaign_id` (lectura en checkout intent/confirm)
- `email_campaign_id` (lectura en checkout intent/confirm)

