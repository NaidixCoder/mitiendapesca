# Checkout Mercado Pago: flujo actual

## Objetivo

Documentar, de forma corta, como funciona hoy el checkout con Mercado Pago y como interviene el webhook.

## Entrypoints principales

| PATH | Handler | Uso |
| --- | --- | --- |
| `POST /checkout/mp/preference` | `App\Controllers\Checkout\PaymentController@preference` | Crea `checkout_intent` y preferencia de MP. |
| `POST /checkout/mp/payment` | `App\Controllers\Checkout\PaymentController@payment` | Procesa pago con card brick / API de pagos. |
| `GET /pago/resultado` | `App\Controllers\Checkout\PaymentController@result` | Redirige al usuario luego del checkout. |
| `POST /webhooks/mercadopago` | `App\Controllers\Webhooks\MercadoPagoWebhookController@handle` | Reconciliacion final del pago. |

## Piezas involucradas

- `checkout_intents`: snapshot del checkout antes de pagar.
- `payments`: union e idempotencia entre Mercado Pago y `orders`.
- `orders`: pedido final.
- `OrderFromIntentService`: crea pedido desde intent cuando el pago ya esta confirmado.
- `CheckoutOrderService`: crea pedido `pending_payment` para estados pendientes.

## Flujo resumido

### 1. Se crea el intent

- El checkout genera un `checkout_intent`.
- El token del intent viaja a MP como `external_reference`.

### 2. Se intenta el pago

- `PaymentController@payment` llama a Mercado Pago.
- Siempre persiste o actualiza una fila en `payments` usando:
  - `provider = mercadopago`
  - `provider_payment_id = payment_id`
  - `intent_id`

### 3. Si Mercado Pago responde `approved`

- Se crea el pedido desde `checkout_intent` con `OrderFromIntentService`.
- Se enlaza `payments.order_id`.
- Si llega el webhook despues, no crea otro pedido.

### 4. Si Mercado Pago responde `pending`, `in_process` o `authorized`

- Se crea el pedido con `CheckoutOrderService`.
- El pedido queda en `pending_payment`.
- Se descuenta stock en ese momento.
- Se enlaza `payments.order_id`.

### 5. Cuando llega el webhook

- El webhook consulta el pago real en Mercado Pago.
- Actualiza la fila en `payments`.
- Si el pago no esta `approved`, responde `200` y no cambia el pedido.
- Si el pago esta `approved`:
  - busca `payments.order_id`
  - si ya existe un pedido `pending_payment`, lo pasa a `paid`
  - si no existe pedido enlazado, usa el fallback de crear desde intent

## Idempotencia

- `payments` tiene `UNIQUE(provider, provider_payment_id)`.
- La escritura de pagos usa `createOrUpdateByProviderPaymentId()`.
- `attachOrderIfEmpty()` evita relinkear un pago ya asociado.
- Si el webhook llega varias veces para el mismo `payment_id`, no duplica pedidos.
- Si el pedido ya estaba pagado/procesado, la reconciliacion hace `noop`.

## Seguridad del webhook

- `MP_WEBHOOK_SIGNATURE_SECRET`: clave oficial de Mercado Pago para validar `x-signature`.
- `MP_WEBHOOK_SECRET`: secret adicional por query string, hoy mantenido por compatibilidad.
- Si `MP_WEBHOOK_SIGNATURE_SECRET` existe, el webhook valida:
  - `x-signature`
  - `x-request-id`
  - `data.id`

## Auditoria y logs

- Auditoria principal: `order_mark_paid_mp`.
- Modos:
  - `reconciliation_mode = existing_order`
  - `reconciliation_mode = created_from_intent`
- `storage/logs/app.log` registra:
  - firma invalida
  - `approved` reconciliado
  - fallback de creacion
  - anomalias reales

## Estado final esperado

- `approved` directo: pedido creado una sola vez.
- `pending` / `in_process` / `authorized`: pedido creado como `pending_payment`, luego webhook lo lleva a `paid`.
- Reintentos del webhook: sin duplicados, sin doble descuento de stock.
