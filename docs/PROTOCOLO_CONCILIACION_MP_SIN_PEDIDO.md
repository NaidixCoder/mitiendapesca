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

## Protección adicional en implementación (no aceptada aún): `payment_in_flight`

El flujo de Checkout introduce un indicador persistente `checkout_intents.fail_reason='payment_in_flight'` inmediatamente **antes** de solicitar el cargo externo. Su propósito es impedir una segunda cobranza si el proceso se interrumpe entre el cargo y su registro en `payments`. Cuando la respuesta del proveedor queda registrada de forma verificable, el marcador se libera. Un marcador no resuelto implica **resultado desconocido**, no pago rechazado ni pagado.

Candidatos para investigación **solo lectura** en entorno administrativo autorizado:

```sql
SELECT id, user_id, address_id, status, fail_reason, created_at
FROM checkout_intents
WHERE status = 'created'
  AND fail_reason = 'payment_in_flight'
ORDER BY created_at, id;
```

**Operación:** el responsable debe cotejar la referencia del intent con el historial oficial de Mercado Pago, `payments`, `orders` y auditoría antes de permitir nuevos cobros. Si falta evidencia de resultado no se debe limpiar manualmente el marcador ni solicitar otro pago. Desbloquear únicamente mediante un procedimiento administrativo con aprobación y log de auditoría, todavía **no desarrollado ni autorizado para producción**. No hay devolución, creación de pedido ni movimiento contable automáticos.

**Estado técnico al 22/10/2026:** el código del marcador está implementado localmente y sintácticamente válido, pero su E2E aislado específico está pendiente porque la herramienta bloqueó su ejecución. El marcador no constituye por sí solo conciliación financiera aprobada.

## Evidencia integral del cierre tÃ©cnico V2 (22/10/2026)

- MariaDB temporal y descartable 127.0.0.1:18959, base mi_checkout_integral_v2_test, con guardias DATABASE() y @@port y datos sintÃ©ticos.
- 22/22 casos del repositorio de intents y pagos: campaÃ±as NULL/0, aislamiento, cobro repetido, enlace Ãºnico, importe, moneda, ID y referencia.
- 15/15 casos del webhook real con cliente MP simulado: cobro invÃ¡lido, firma ausente, token inexistente, segundo pago distinto, reintento y excepciÃ³n de proveedor. El creador de pedido se simulÃ³ solo en esta prueba.
- 18/18 casos SQL de locks entre conexiones, cobros huÃ©rfanos y campaÃ±as diferentes para el mismo carrito.
- 10/10 casos de marcador duradero anterior a llamar al proveedor, bloqueo de reintento y liberaciÃ³n posterior.
- 6/6 comprobaciones de OrderFromIntentService real tras dos procesos PHP iniciados concurrentemente: mismo order_id, una orden, una fila, stock de 5 a 3 una sola vez e intent consumido; con pausa deliberada dentro de transacciÃ³n para garantizar solapamiento. Email, lealtad y evaluaciÃ³n global B1 fueron sustituidos por mocks sintÃ©ticos.
- Regresiones unitarias y estÃ¡ticas de Checkout, frontend y CategorÃ­as B1.
- No hubo pagos reales, conexiones MP productivas, devoluciones ni cambios comerciales. No se probÃ³ homologaciÃ³n externa end-to-end ni se sustituyÃ³ la conciliaciÃ³n humana.

### Condiciones para operar comercialmente

Antes de habilitar cobros reales, un responsable administrativo-financiero debe aprobar formalmente el protocolo, quedar asignado a la revisiÃ³n de cobros sin pedido y marcadores payment_in_flight, contar con un canal de atenciÃ³n al comprador y autorizar individualmente la resoluciÃ³n de cada caso. Sin procedimiento adoptado, prueba externa de homologaciÃ³n y aceptaciÃ³n transversal, producciÃ³n permanece bloqueada. Nunca liberar marcadores por SQL manual sin evidencia documentada ni ejecutar reembolsos automÃ¡ticos.