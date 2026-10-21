# Pre-produccion - checklist de seguridad checkout/pagos

Fecha: 2026-03-02  
Estado: solo analisis (sin cambios de codigo aplicados en este documento)

## Objetivo
Validar antes de produccion que no exista forma de cerrar pedidos con montos inconsistentes por manipulacion de flujo, sesion o condiciones de carrera.

## Hallazgos a revisar

### 1) Transferencia no suma envio en total de pedido
- Riesgo: alto.
- Sintoma: el flujo exige seleccionar envio, pero el servicio de confirmacion por transferencia crea pedido con `shipping=0`.
- Impacto: subfacturacion/subcobro en pedidos por transferencia.
- Archivos:
  - `app/Controllers/Checkout/CheckoutController.php`
  - `app/Services/Checkout/CheckoutOrderService.php`

### 2) Intent de pago reutiliza `checkout_shipping` de sesion sin revalidacion fuerte
- Riesgo: medio.
- Sintoma: al crear intent, toma `$_SESSION['checkout_shipping']['cost']` si existe.
- Falta: verificar `cart_sig`, expiracion/TTL y vigencia de `calculation_id` en ese mismo punto.
- Impacto: total de pago potencialmente desactualizado si el carrito cambio entre tabs/sesiones.
- Archivos:
  - `app/Services/Checkout/CheckoutIntentService.php`
  - `app/Controllers/Checkout/PaymentController.php`

### 3) Webhook de Mercado Pago no compara monto/moneda contra intent
- Riesgo: medio.
- Sintoma: con `status=approved` se procesa orden sin comparar de forma explicita `transaction_amount` y `currency_id` contra el intent.
- Impacto: riesgo de aceptar pagos aprobados con desvio de monto/moneda en escenarios edge.
- Archivo:
  - `app/Controllers/Webhooks/MercadoPagoWebhookController.php`

## Checklist tecnico antes de deploy

1. Transferencia:
- [ ] Tomar costo de envio desde snapshot validado en servidor.
- [ ] Persistir `shipping`, `discount` y `total` consistentes en `orders`.
- [ ] Cubrir caso de envio gratis (`cost=0`) sin romper flujo.

2. Intent/pay:
- [ ] Revalidar snapshot de envio en `createIntent()` (firma de carrito + vigencia).
- [ ] Si el snapshot expiro o no coincide, devolver error controlado y forzar recalculo de envio.
- [ ] Registrar en audit/log cuando se invalida snapshot por mismatch.

3. Webhook MP:
- [ ] Comparar `transaction_amount` vs `intent.amount` con tolerancia definida.
- [ ] Comparar `currency_id` vs moneda del intent.
- [ ] Si no coincide, no crear/adjuntar pedido y dejar traza de auditoria.

4. Pruebas funcionales minimas:
- [ ] Compra MP end-to-end con envio pago.
- [ ] Compra MP end-to-end con envio gratis.
- [ ] Transferencia end-to-end con envio pago.
- [ ] Cambio de carrito entre calculo de envio y pago (debe invalidar y pedir recalculo).
- [ ] Reintento/idempotencia webhook (no duplicar pedido).

5. Observabilidad:
- [ ] Log estructurado para rechazos por mismatch (shipping/cart/payment).
- [ ] Dashboard o consulta rapida para detectar ordenes con `shipping=0` fuera de regla.

## Criterio de salida
Solo avanzar a produccion cuando:
- No existan ordenes con total inconsistente respecto a carrito + envio.
- Intent y webhook validen coherencia de monto/moneda.
- Las pruebas de regresion de checkout/pago pasen en ambiente staging.

