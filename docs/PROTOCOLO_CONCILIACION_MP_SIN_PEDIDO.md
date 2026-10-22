# MiTiendaPesca — Conciliación de Mercado Pago aprobado sin pedido
**Fecha:** 2026-10-22 · **Responsable:** Chat principal / responsable administrativo-financiero.

**Estado:** procedimiento preventivo y de supervisión, no habilitación de producción. No ordena ni autoriza reembolsos, creación artificial de pedidos, acreditaciones, alteración de stock o cambio de saldos. Requiere autorización explícita del responsable comercial-financiero para cada resolución.

## Qué significa el incidente

La aplicación puede recibir `status=approved` de Mercado Pago cuando el pedido ya no puede confirmarse: producto oculto por B1, stock insuficiente, intento inexistente o error de consistencia. **El cobro puede existir aunque el pedido no exista.** No se debe mostrar éxito de compra ni intentar repetir el cobro. Los registros `payments` guardan `provider_payment_id`, estado, importe, moneda, `intent_id` y `raw_json`; `admin_audit_log` y eventos del webhook pueden aportar evidencia. Las respuestas HTTP del caso B1 ya incluyen `manual_reconciliation_required=true` con 202; Checkout devuelve también 202 `approved_without_order` en el caso genérico. El webhook con error distinto de B1 puede continuar respondiendo 422 para permitir reintentos; no asumir que todos los casos generan una alerta idéntica.

## Detección diaria y ante cada alerta (solo lectura)

Ejecutar con un usuario de base **exclusivamente de lectura**, dentro del entorno administrativo autorizado, después de identificar la BD correcta. No exportar `raw_json`, documentos fiscales, tarjeta, email ni datos personales a tickets abiertos. Esta consulta es una **lista de candidatos**; puede haber pagos aprobados cuyo pedido existe pero falta enlazar en `payments`.

```sql
SELECT p.id AS payment_row_id,
       p.provider_payment_id, p.status, p.amount, p.currency,
       p.intent_id, p.order_id AS payment_order_id,
       i.status AS intent_status, i.order_id AS intent_order_id,
       o.id AS order_by_token, p.updated_at
FROM payments AS p
LEFT JOIN checkout_intents AS i ON i.id = p.intent_id
LEFT JOIN orders AS o ON o.checkout_token = i.token
WHERE p.provider = 'mercadopago'
  AND p.status = 'approved'
  AND (p.order_id IS NULL OR p.order_id = 0)
ORDER BY p.updated_at ASC, p.id ASC;
```

Revisar también eventos `payment_approved_but_product_unavailable`, `payment_approved_without_order`, `approved_missing_external_reference` y respuestas 422 de webhook. Una alerta auditada no sustituye la consulta de candidatos; un pago puede no haber llegado a registrar el evento esperado.

## Procedimiento humano obligatorio por caso

1. **Abrir incidencia interna** con responsable, fecha/hora, `provider_payment_id`, `payment_row_id`, `intent_id` y estado. Marcar «conciliación pendiente»; bloquear cualquier confirmación de pedido o comunicación que dé por cerrado el incidente. No exponer `raw_json` al cliente.
2. **Confirmar con Mercado Pago por canal oficial** el estado vigente del pago, monto, moneda, cuenta receptora, historial de devolución/contracargo, referencia externa e idempotencia. Un `status=approved` local puede estar desactualizado.
3. **Cotejar contra registros internos**: `payments` por (provider, provider_payment_id), `checkout_intents` por token, `orders` por `checkout_token` y `order_id`, auditoría, stock, B1 y pagos duplicados. Verificar que no exista ya un pedido creado por otro webhook.
4. **Resolver con aprobación humana documentada**:
   - Si el pedido existe y pago/importe/cliente coinciden, determinar y ejecutar por procedimiento aprobado el enlace contable faltante, sin crear otra orden ni volver a descontar stock.
   - Si no existe pedido, decidir con el responsable si corresponde gestionar una nueva compra válida **bajo consentimiento del cliente y disponibilidad actual**, o tramitar devolución por el canal oficial. Ni la aplicación ni este protocolo eligen automáticamente.
   - Si el importe, moneda, identidad, fraude o estado difieren, escalar a revisión contable y soporte de Mercado Pago; no crear pedido ni acreditar el dinero manualmente.
5. **Registrar el cierre** con aprobación, acción, ID del movimiento oficial, fecha, conciliación en libro/caja, comunicación al cliente y comprobación de ausencia de cobro/pedido duplicado. Reconciliar también el siguiente extracto del proveedor.

## Barreras técnicas de aceptación antes de producción

- Simular pago aprobado **sin pedido** por producto B1 oculto, stock insuficiente, intento ausente e interrupción controlada de creación. El usuario nunca recibe `ok:true` por un `approved` huérfano; respuesta conserva el ID de proveedor y advierte no pagar de nuevo.
- Webhook y checkout concurrentes/repetidos: una sola orden (cuando es legítima), pago registrado de manera idempotente y sin doble stock ni doble factura; verificar reintentos del proveedor.
- Acceso a consulta/lista operativa solo a usuarios autorizados; no exponer información sensible; auditar acciones humanas; sin acciones automáticas de refund, cancelación ni pedido forzado.
- Verificar manejo de respuesta 202 en cliente web y de eventos 422 en monitoreo; asignar responsable de revisión real. **Hasta completar estas pruebas y el proceso humano, la producción sigue bloqueada.**

**No aplicar SQL UPDATE/DELETE manual ni refund automático sobre registros comerciales sin una autorización específica, respaldo/auditoría y procedimiento aprobado.**
