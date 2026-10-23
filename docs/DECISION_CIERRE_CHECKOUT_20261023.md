# Checkout — decisión de cierre condicionado (23/10/2026)

**Decisión del propietario:** Desarrollo interno de Checkout validado para escenarios ejecutados; homologación externa de Mercado Pago pospuesta hasta el despliegue en hosting definitivo, antes de publicar productos reales o abrir operaciones. No contratar infraestructura solo para homologar.

## Evidencia
- Checkout transferencia: HTTP autenticado 19/19 PASS, concurrencia auténtica 3/3 PASS. Fuente: docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md.
- Pagos MP: 80/80 controles internos PASS (contratos y lógica, no compras reales).
- API Mercado Pago TEST: GET payment_methods HTTP 200; POST preferences HTTP 201 sin cobro.
- Clon privado QA: 65 tablas, login HTTP 200 y admin HTTP 403. Quick Tunnel HTTPS intermitente. No equivale a homologación financiera.

## Pruebas obligatorias tras despliegue, antes de habilitar la tienda
1. Hosting HTTPS con base de homologación, productos/clientes sintéticos, acceso público restringido y credenciales TEST.
2. Cobros ficticios aprobados, rechazados y pendientes a través de la integración real /v1/payments y frontend.
3. Webhook real de Mercado Pago con firma válida; intentar firma falsa, duplicados, reintentos y eventos fuera de orden.
4. Verificar payments, checkout_intents, orders, order_items, stock, idempotencia y pagos aprobados sin pedido con conciliación manual.
5. Validar configuración productiva separada, HTTPS, monitoreo, procedimiento de conciliación y aprobación del propietario antes de habilitar cobros.

**Estado:** Transferencia probada y aprobada para escenarios internos. Mercado Pago: desarrollo interno validado dentro de su alcance, homologación externa pendiente, NO GO para cobros en producción.
