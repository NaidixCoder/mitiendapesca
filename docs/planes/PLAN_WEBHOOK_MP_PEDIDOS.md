# Plan de implementación: webhook de Mercado Pago para pedidos con `pending_payment`

## Objetivo

Ajustar el flujo de Mercado Pago para que, cuando el checkout devuelva un estado `pending`, `in_process` o `authorized`, la tienda:

1. cree el pedido,
2. lo deje en `pending_payment`,
3. no lo marque como pagado todavía,
4. y luego un webhook de Mercado Pago lo actualice a `paid` cuando el pago cambie a `approved` / `accredited`.

## Estado actual

- Existe la ruta `POST /webhooks/mercadopago`.
- El controller del webhook ya consulta el `payment_id` en Mercado Pago.
- El webhook hoy crea pedido sólo cuando el pago ya viene `approved`.
- Si el checkout crea un pedido `pending_payment`, el webhook no lo toma para cambiar su estado a `paid`.
- No hay validación de firma `x-signature` / `x-request-id` implementada todavía.
- La respuesta del webhook no está optimizada para contestar `200` rápido en todos los casos.

## Auditoría técnica reciente

Lo que ya vimos en el código actual:

- La ruta existe en `config/routes.php`.
- El controller actual del webhook está en `app/Controllers/Webhooks/MercadoPagoWebhookController.php`.
- El servicio de MP consulta el pago remoto con `getPayment()`.
- El repositorio de pagos ya permite buscar por `provider_payment_id` y evitar enlaces duplicados con `attachOrderIfEmpty()`.
- Ya existe auditoría para el caso `order_mark_paid_mp`.
- La lógica de admin ya sabe mover un pedido de `pending_payment` a `paid`.

Lo que todavía falta para cubrir el flujo completo:

- El webhook no actualiza un pedido `pending_payment` existente cuando MP luego confirma el pago.
- No hay validación de firma real de webhook.
- La confirmación del pago todavía depende de consultar MP antes de responder.
- El flujo todavía mezcla “crear pedido” y “marcar pedido existente” según el estado, y eso hay que dejarlo explícito para que no haya ambigüedades.

## Problema a resolver

Hoy el flujo de tarjeta pendiente queda incompleto:

- checkout recibe `pending`,
- se crea el pedido,
- pero falta el segundo paso: cuando Mercado Pago luego confirma el pago, el webhook debe actualizar el pedido existente y no crear otro.

Además, necesitamos evitar una transición ambigua:

- si el pedido ya fue creado en `pending_payment`,
- el webhook no debe recrearlo,
- sólo debe reconciliar el estado final del pago con ese pedido.

## Plan técnico

### 1) Unificar el vínculo pago ↔ pedido

- Guardar `provider_payment_id` y `external_reference` de forma consistente.
- Asegurar que el pedido creado en `pending_payment` quede asociado al `payment_id` de Mercado Pago.
- Reutilizar la tabla `payments` como punto de unión e idempotencia.

### 2) Cambiar el webhook para actualizar pedidos pendientes

- Cuando llegue un evento de Mercado Pago:
  - consultar el pago en MP,
  - leer `status`, `external_reference` y `payment_id`,
  - buscar el pedido asociado.
- Si el pedido ya existe y está en `pending_payment`:
  - cambiarlo a `paid` cuando el pago quede `approved` / `accredited`,
  - registrar auditoría,
  - evitar duplicados.
- Si no existe pedido asociado:
  - mantener el comportamiento actual de fallback.

### 3) Mantener idempotencia real

- Si el webhook llega varias veces para el mismo `payment_id`, no debe duplicar pedidos ni reabrir stock.
- Si el pedido ya está en `paid`, el webhook debe responder OK sin volver a procesar.
- Si el `payment_id` ya fue enlazado, sólo actualizar si hace falta y sin reinsertar.

### 4) Seguridad del webhook

- Agregar validación de firma si el flujo actual de Mercado Pago lo permite:
  - `x-signature`
  - `x-request-id`
- Mantener el `secret` propio como capa adicional si sigue siendo útil.
- Responder `200` rápido una vez validado lo mínimo indispensable.

### 5) Respuesta y logging

- Loguear el evento recibido, el `payment_id`, el `external_reference` y el estado final.
- Registrar auditoría de admin cuando el pedido pase a pagado por webhook.
- Mantener la respuesta del endpoint simple y no bloqueante.

## Criterios de aceptación

- Un pago de tarjeta con estado `pending` crea un pedido `pending_payment`.
- Cuando Mercado Pago luego lo confirme, el webhook cambia ese pedido a `paid`.
- No se crean pedidos duplicados.
- No se descuenta stock dos veces.
- La auditoría de admin registra el cambio de estado.
- El webhook responde `200` rápido y es seguro para reintentos.

## Orden sugerido de implementación

1. Ajustar el modelo de asociación pedido/pago.
2. Cambiar el webhook para buscar y actualizar pedidos existentes.
3. Agregar validación de firma si aplica.
4. Revisar idempotencia y auditoría.
5. Probar el flujo completo en sandbox.

## Etapas para continuar en un chat nuevo

### Etapa 1: reconciliación de pedido pendiente

Objetivo:

- Si el checkout ya creó un pedido `pending_payment`, el webhook debe encontrar ese pedido y marcarlo como `paid` cuando MP confirme el pago.
- No debe recrear pedidos si ya existe uno asociado.

Puntos a revisar:

- `app/Controllers/Webhooks/MercadoPagoWebhookController.php`
- `app/Repositories/Payments/PaymentRepository.php`
- `app/Services/Admin/AdminOrderService.php`
- `app/Services/Checkout/CheckoutOrderService.php`

### Etapa 2: idempotencia y seguridad

Objetivo:

- Evitar dobles transiciones ante reintentos de Mercado Pago.
- Dejar una validación más sólida del webhook.

Puntos a revisar:

- unique key por `provider + provider_payment_id`
- `attachOrderIfEmpty()`
- firma `x-signature` / `x-request-id` si el flujo actual la soporta
- respuesta HTTP rápida

### Etapa 3: auditoría y trazabilidad

Objetivo:

- Registrar claramente cuándo un pedido pasa de `pending_payment` a `paid` por webhook.
- Dejar logs útiles para admin sin ruido.

Puntos a revisar:

- `Audit::log('order_mark_paid_mp', ...)`
- `storage/logs/app.log`
- auditoría en admin

### Etapa 4: validación funcional

Objetivo:

- Confirmar que el flujo queda cerrado para:
  - `approved`
  - `pending`
  - `in_process`
  - reintentos del webhook

Casos a probar:

- tarjeta aprobada
- tarjeta pendiente
- transferencia pendiente
- webhook repetido para el mismo `payment_id`

## Contexto exacto para retomar en otro chat

- Existe la ruta `POST /webhooks/mercadopago` en `config/routes.php`.
- El controller actual está en `app/Controllers/Webhooks/MercadoPagoWebhookController.php`.
- Hoy el webhook consulta el pago en MP con `MercadoPagoService::getPayment()`.
- Hoy el webhook crea pedido sólo cuando el pago viene `approved`.
- El checkout ya puede crear pedidos `pending_payment`.
- Para transferencia, ese pedido ya queda creado y con stock descontado.
- Para tarjeta pendiente queremos el mismo principio: pedido creado, stock descontado, estado `pending_payment`, y luego webhook que lo lleve a `paid`.
- No hay validación real de `x-signature` / `x-request-id` todavía.
- Ya existe auditoría `order_mark_paid_mp`.
- Ya existe lógica de admin para pasar `pending_payment -> paid`.
- El cambio que falta es que el webhook use esa lógica sobre el pedido ya existente, en vez de crear otro pedido nuevo.

## Nota

Este documento describe el plan y su estado actual de implementacion.
## Estado de implementacion

- Etapa 1: concluida.
- Etapa 2: concluida.
- Etapa 3: concluida.
- Etapa 4: concluida.
- El webhook ya reconcilia pedidos `pending_payment` existentes sin recrearlos cuando el pago de Mercado Pago pasa a `approved`.
- `payments` ya usa escritura idempotente basada en `UNIQUE(provider, provider_payment_id)` mediante `createOrUpdateByProviderPaymentId()`.
- `PaymentController` y `MercadoPagoWebhookController` ya no hacen `find + create` manual para el registro de pagos.
- La validacion oficial de firma queda disponible mediante `MP_WEBHOOK_SIGNATURE_SECRET`, usando `x-signature`, `x-request-id` y `data.id`.
- La auditoria `order_mark_paid_mp` ahora distingue `existing_order` vs `created_from_intent` y agrega contexto de webhook/pago.
- `storage/logs/app.log` ahora registra solo hitos `approved` y anomalias del webhook, evitando ruido de estados transitorios.
- La validacion funcional del codigo cubrio `approved`, `pending`, `in_process`, `authorized` y reintentos; ademas se corrigio la lectura de `data.id`/`topic` desde query string del webhook.
- Pendiente solo la prueba manual end-to-end en sandbox de Mercado Pago.

## Checklist de prueba manual en sandbox

- Configurar `MP_WEBHOOK_SECRET` y, si se quiere validar firma, `MP_WEBHOOK_SIGNATURE_SECRET`.
- Verificar que la URL del webhook apunte a `POST /webhooks/mercadopago` y sea accesible desde Mercado Pago.
- Confirmar que exista el indice `UNIQUE(provider, provider_payment_id)` en `payments`.

### Caso approved directo

- Iniciar checkout con tarjeta que apruebe en sandbox.
- Verificar que se cree un unico pedido y quede enlazado en `payments.order_id`.
- Confirmar que el pedido no se duplica si llega el webhook otra vez.
- Revisar que el pedido quede pagado y exista auditoria `order_mark_paid_mp`.

### Caso pending / in_process / authorized

- Iniciar checkout con un medio sandbox que devuelva estado pendiente o en revision.
- Verificar que se cree el pedido en estado `pending_payment`.
- Confirmar que `payments` guarde `provider_payment_id` y `order_id`.
- Cuando el webhook pase a `approved`, verificar que el mismo pedido cambie a `paid`, sin crear otro.

### Reintentos del webhook

- Reenviar el mismo webhook para un `payment_id` ya procesado.
- Verificar que no se creen nuevas filas de pedido.
- Confirmar que no se descuente stock otra vez.
- Revisar que el log muestre `approved_already_reconciled` o equivalente.

### Trazabilidad

- Revisar `storage/logs/app.log` para hitos `approved` y anomalias reales.
- Revisar auditoria admin para distinguir `existing_order` vs `created_from_intent`.
- Verificar que el `payment_id` quede visible en el detalle del pedido.

### Firma

- Con `MP_WEBHOOK_SIGNATURE_SECRET` activo, confirmar que un webhook valido responda `200`.
- Probar un request sin firma o con firma invalida y verificar `403 invalid_signature`.
