# Pendientes: Seguridad Carrito / Checkout

Fecha: 2026-03-18

## Estado actual

El flujo `carrito -> checkout -> shipping -> payment` quedo endurecido contra manipulacion client-side y probado en entorno local autenticado.

Nivel estimado actual: `8/10`

## Pendientes prioritarios

### 1. Webhook de Mercado Pago

Auditar en detalle:

- `POST /webhooks/mercadopago`
- validacion de secret / origen
- idempotencia ante reintentos
- tolerancia a payloads parciales o campos faltantes
- rechazo de eventos no asociados a intents validos
- proteccion contra reprocesamiento de pagos ya usados

Objetivo:

- confirmar que no se pueda crear o mutar un pedido con un webhook adulterado o repetido

### 2. Flujo de pago aprobado por tarjeta

Probar end-to-end:

- `POST /checkout/mp/payment`
- aprobacion real o sandbox controlado
- creacion de pedido desde intent
- no duplicacion de orden ante retry / refresh / replay

Objetivo:

- validar cierre completo del flujo de tarjeta, no solo preference y redirect

### 3. Observabilidad y errores de seguridad

Revisar:

- respuestas `401`, `419`, `422`, `409`
- consistencia de body JSON en todos los endpoints AJAX
- registro en logs de rechazos relevantes
- calidad de mensajes para frontend sin filtrar informacion sensible

Objetivo:

- mejorar trazabilidad y diagnostico sin degradar seguridad

### 4. Race conditions / concurrencia

Probar:

- doble submit rapido en checkout
- multiples requests simultaneas a `mp/preference`, `mp/payment`, `shipping/select`
- carrito que cambia entre pasos y entre tabs

Objetivo:

- confirmar que locks, firmas e idempotencia resisten concurrencia real

### 5. Smoke test preproduccion

Ejecutar en entorno lo mas parecido a produccion:

- shipping real
- Mercado Pago real/sandbox controlado
- cuenta con multiples direcciones
- carrito con productos simples y variantes

Objetivo:

- detectar diferencias de infraestructura que no aparecen en localhost

## Pendientes secundarios

### 6. Revisar respuestas de endpoints guest protegidos

En algunas pruebas por shell local, ciertos `401/419` devolvieron body vacio aunque el codigo server-side esta correcto.

Objetivo:

- confirmar comportamiento real desde navegador / servidor web
- distinguir si fue problema del entorno de prueba o del runtime HTTP

### 7. Revisar webhook + pagos contra fraude basico

Explorar:

- mismatch entre `external_reference`, `intent_id` y `payment_id`
- intent usado con otro usuario
- payloads con moneda o amount inconsistentes

Objetivo:

- subir cobertura de 8/10 a 9/10

## Criterio para considerar el flujo “muy cubierto”

Se puede considerar el flujo en `9/10` si se valida:

- webhook robusto
- pago tarjeta end-to-end sin duplicaciones
- buena observabilidad de rechazos
- pruebas de concurrencia sin hallazgos relevantes
