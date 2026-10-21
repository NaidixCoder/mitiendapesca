# Propuesta de remediacion: auditoria Checkout / Webhook Mercado Pago

## Objetivo

Documentar una propuesta concreta para resolver los hallazgos altos y medios detectados en el flujo actual de checkout + webhook de Mercado Pago.

## Estado

- Implementado.
- El webhook ahora cierra pedidos pendientes enlazados cuando Mercado Pago termina en estado terminal no aprobado.
- La creacion de pedido pendiente por tarjeta ahora usa `intentToken` como `checkout_token`.
- El pedido pendiente puede reutilizar el snapshot de totales del intent.
- Los pagos acreditados quedan alineados operativamente en `processing`.

## Prioridad alta

### 1. Cerrar pedidos pendientes cuando el pago termina mal

#### Problema

- Hoy un pago que primero queda `pending` / `in_process` / `authorized` crea un pedido `pending_payment`.
- Si luego Mercado Pago lo deja en `rejected`, `cancelled`, `refunded` o similar, el webhook no cambia el pedido.
- Eso puede dejar stock reservado indefinidamente.

#### Propuesta

- Extender `MercadoPagoWebhookController` para que, si encuentra `payments.order_id` y el estado remoto es terminal no aprobado:
  - `rejected` -> mover pedido a `payment_expired`
  - `cancelled` -> mover pedido a `payment_expired`
  - `refunded` / `charged_back` / `chargeback` -> no tocar pedidos ya cobrados sin una regla explicita adicional; solo auditar
- Reutilizar `AdminOrderService::changeStatus()` para:
  - aplicar una transicion valida
  - reponer stock cuando corresponda
  - evitar duplicados si el webhook llega varias veces

#### Cambio minimo sugerido

- En `app/Controllers/Webhooks/MercadoPagoWebhookController.php`:
  - antes del `return 200` de estados no `approved`, buscar si el pago ya esta enlazado a un pedido
  - si el estado es terminal y el pedido sigue `pending_payment`, llamar a `changeStatus()`
- Agregar auditoria especifica:
  - `order_payment_failed_mp`
  - o seguir usando `order_status_changed` con metadata `source=mp_webhook`

#### Beneficio

- Evita stock inmovilizado por pagos que nunca se acreditan.
- Cierra el ciclo de pedidos pendientes de tarjeta.

## Prioridad alta

### 2. Evitar pedidos duplicados en reintentos de tarjeta

#### Problema

- Para tarjeta, `CheckoutOrderService` no usa `checkout_token` reutilizable.
- Si el usuario reintenta mientras el pago previo sigue `pending`, se puede crear otro pedido `pending_payment`.

#### Propuesta

- Darle al flujo de tarjeta pendiente una clave de idempotencia a nivel pedido, igual que hoy existe para transferencia.
- Opcion preferida: usar el `intentToken` como `checkout_token` del pedido pendiente de tarjeta.

#### Cambio minimo sugerido

- Cambiar la firma de `CheckoutOrderService::confirm()` para aceptar un parametro opcional `?string $checkoutToken = null`.
- Desde `PaymentController@payment`, cuando el estado sea `pending` / `in_process` / `authorized`, llamar:
  - `confirm($uid, $addressId, $selectedMethod, $intentToken)`
- En `CheckoutOrderService`:
  - si llega `checkoutToken`, buscar `orders.checkout_token`
  - si ya existe, devolver ese pedido y no crear otro

#### Beneficio

- Un mismo intento de pago pendiente no genera varios pedidos.
- El webhook sigue pudiendo reconciliar sobre un pedido unico.

## Prioridad media

### 3. Unificar snapshot de envio y totales entre flujo aprobado y flujo pendiente

#### Problema

- El pedido creado desde intent usa `shipping` del snapshot del checkout.
- El pedido pendiente creado por `CheckoutOrderService` fuerza `shipping = 0`.
- Eso puede generar diferencias de total entre:
  - pago aprobado directo
  - pago que primero quedo pendiente

#### Propuesta

- Hacer que `CheckoutOrderService` use el mismo snapshot de envio que `CheckoutIntentService`.
- Idealmente, reutilizar la misma fuente de verdad para subtotal, shipping, discount y total.

#### Cambio minimo sugerido

- En `CheckoutOrderService`, reemplazar el hardcode `shipping = 0` por lectura de `$_SESSION['checkout_shipping']`, igual que hace `CheckoutIntentService`.
- Mejor opcion aun:
  - pasarle al servicio el `checkout_intent` o el `intentToken`
  - y construir el pedido pendiente desde ese snapshot, no desde la sesion viva

#### Beneficio

- El pedido queda igual sin importar si MP aprueba al instante o demora.
- Reduce diferencias contables y de UX.

## Prioridad media

### 4. Unificar el estado final despues de pago acreditado

#### Problema

- Pago aprobado directo crea pedido en `processing`.
- Webhook sobre pedido pendiente lo deja en `paid`.
- Eso deja dos estados distintos para el mismo hito de negocio: "pago acreditado".

#### Propuesta

- Definir un solo criterio operativo:
  - Opcion A: siempre terminar en `paid`, y dejar `processing` para una accion posterior de preparacion
  - Opcion B: siempre terminar en `processing` cuando el pago ya esta confirmado

#### Recomendacion

- Elegir una unica semantica y usarla en ambos caminos.
- Si hoy admin/logistica ya trabaja mejor con `processing`, conviene:
  - que `markPaidMercadoPago()` deje el pedido en `processing`
  - o que luego de `paid` exista una transicion automatica inmediata y consistente

#### Beneficio

- Automatizaciones, dashboard y operacion hablan el mismo idioma.
- Menos ramas especiales en pedidos pagos.

## Orden sugerido

1. Resolver terminales no aprobados en webhook.
2. Agregar idempotencia real para tarjeta pendiente usando `intentToken`.
3. Unificar snapshot de envio/totales.
4. Definir y unificar estado final `paid` vs `processing`.

## Criterios de aceptacion

- Un pago pendiente que termina rechazado libera stock y cierra el pedido.
- Reintentar un mismo pago pendiente no crea pedidos duplicados.
- El total del pedido no cambia segun el pago haya sido aprobado directo o via webhook posterior.
- Todos los pagos acreditados terminan en un mismo estado operativo.
