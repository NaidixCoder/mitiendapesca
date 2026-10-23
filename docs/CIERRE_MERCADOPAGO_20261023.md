# MiTiendaPesca — Cierre de integración Mercado Pago (23/10/2026)

## Dictamen actual

**Cierre de auditoría local y contratos: APROBADO (alcance acotado). Habilitación de cobros MP en producción: NO GO.**

El flujo de checkout por transferencia tiene HTTP autenticado 19/19 PASS y carrera de dos sesiones/servidores HTTP auténticos 3/3 PASS, según `docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md`. Esto no homologa los pagos externos.

### Verificación focal ejecutada el 23/10/2026

- `scripts/build/checkout_payment_integrity_contract.php`: 26/26 PASS
- `scripts/build/checkout_payment_orphan_contract.php`: 12/12 PASS
- `scripts/build/checkout_intent_reuse_contract.php`: 12/12 PASS
- `scripts/build/transversal_mp_signature_contract.php`: 15/15 PASS; HMAC sintético sin conexión al proveedor
- `scripts/build/checkout_payment_frontend_contract.php`: 12/12 PASS
- `scripts/dev/checkout_postcommit_integrity_isolated.php`: 3/3 PASS

**Total focal: 80/80 PASS.** Los contratos estáticos no sustituyen recorridos HTTP/E2E externos ni garantizan recuperación operativa.

Se revisaron los controladores reales `app/Controllers/Checkout/PaymentController.php` y `app/Controllers/Webhooks/MercadoPagoWebhookController.php`. Existen validación de autenticación/CSRF, intentos, persistencia del pago, comprobación de datos del proveedor antes de crear la orden, autenticación HMAC, manejo de rechazos y señalización de pagos aprobados sin pedido para conciliación. La presencia y el PASS de contratos no equivale a haber probado todos esos circuitos en red.

## Condiciones de aceptación externas pendientes (bloquean GO producción)

1. **Credenciales de prueba y compra por entorno de pruebas del propio comercio**, sin exhibir ni insertar secretos en reportes/chats. Validar respuestas approved, rejected y pending en la versión de Checkout/API realmente usada por el frontend.
2. **Webhook real desde Mercado Pago hacia endpoint HTTPS accesible** (no localhost). Configurar tópico payment, secreto de firma y el método de autenticación que espera nuestra URL; verificar HMAC, ID del recurso y consulta posterior del pago a Mercado Pago. Probar notificación repetida, fuera de orden y notificación de recurso desconocido.
3. **Correlación financiera:** para pago aprobado concordante, exactamente un `payments` y un `orders` enlazados, stock descontado una vez y estado de pedido consistente. Para pago rechazado/pending, no confirmar indebidamente el pedido. Para error de persistencia posterior al cargo, que el pago aprobado quede visible como `manual_reconciliation_required` y no se informe éxito comercial falso.
4. **Operación de conciliación aprobada por el propietario:** definir responsable, frecuencia, reporte, procedimiento de consulta por provider_payment_id, prohibición de recobrar a ciegas un estado incierto, criterio para crear/vincular pedido o devolver dinero y registro auditable. Verificar en ambiente de prueba al menos un pago aprobado sin pedido y su resolución manual.
5. **Configuración productiva final:** HTTPS de webhook, credenciales correctas separadas por entorno, secreto de firma configurado, observabilidad de fallas, seguimiento de registros huérfanos y deshabilitación del cobro si falla verificación crítica.

## Procedimiento de homologación externa en una sola sesión

- Usar el panel de Mercado Pago *Tus integraciones* para obtener credenciales de prueba y cargar URL HTTPS de pruebas del webhook (sin compartir tokens por chat). Realizar compra ficticia aprobada, rechazada y pendiente según tarjetas documentadas; verificar las filas/estados correlacionados en una base de pruebas.
- En el panel, simular el evento firmado y luego verificar entrega auténtica; repetir y reordenar eventos para comprobar idempotencia.
- Ejecutar un caso sintético de excepción posterior al pago (sin generar cargos reales) y conciliarlo con un registro de auditoría.
- Completar planilla de aceptación con evidencia sin secretos (IDs truncados, fechas, estados y capturas redaccionadas). Solo entonces cambiar el dictamen a GO, con autorización explícita del propietario.

## Fuentes técnicas oficiales

- https://www.mercadopago.com.ar/developers/es/docs/checkout-bricks/integration-test/test-payment-flow
- https://www.mercadopago.com.ar/developers/es/docs/links-and-debts/additional-content/your-integrations/notifications
- https://www.mercadopago.com.ar/developers/es/docs/split-payments/additional-content/your-integrations/notifications/webhooks

No ejecutar pagos reales ni cambiar credenciales productivas durante una auditoría sin autorización expresa. No declarar homologación externa si faltan pruebas con MP.
