# MiTiendaPesca — Homologación Mercado Pago desde XAMPP (23/10/2026)

Estado: preparación local. Cobros productivos: NO GO.

## Diagnóstico
- Sitio en Windows XAMPP, no hosting HTTPS.
- Archivo .env original presente: no exponer ni copiar al túnel, no leer credenciales comerciales ni guardar secretos en reportes.
- Ejecutables cloudflared/ngrok no disponibles en PATH.
- La integración real usa POST /v1/payments y GET /v1/payments/{id}, NO Orders API.
- 80/80 verificaciones focales internas aprobadas previamente. Transferencia: 19/19 HTTP y 3/3 concurrencia. No repetir sin modificaciones que lo justifiquen.

## Plan de homologación
1. Clonar a carpeta temporal privada el proyecto excluyendo .env, uploads y archivos sensibles; comprobar datadir MariaDB desechable y sesiones separadas. El runner de Checkout privado demuestra el patrón, pero NO equivale todavía a servidor MP de homologación.
2. Colocar credenciales de prueba MP y secretos webhook únicamente en la configuración local temporal y fuera de Git. Jamás escribir tokens en consola o logs.
3. Crear un túnel HTTPS temporal solamente hacia el servidor aislado, sin exponer Apache/XAMPP original, phpMyAdmin o su panel administrativo real.
4. Configurar Webhooks de evento payment en Tus integraciones de la aplicación de prueba, con validación x-signature y consulta al proveedor de cada payment_id.
5. Ejecutar pago de prueba aprobado, rechazado y pendiente; verificar payments, checkout_intents, orders, stock y estados del frontend.
6. Reenviar/reordenar webhook y enviar firma inválida. Confirmar idempotencia y rechazo seguro.
7. Simular un pago aprobado sin pedido y validar el protocolo de conciliación manual, sin volver a cobrar automáticamente.
8. Exportar resultados sanitizados; apagar servidor, cerrar túnel, eliminar clon y comprobar puertos liberados.

## Dependencias pendientes del propietario
- Acceso propio a Mercado Pago > Tus integraciones, aplicación de prueba y sus credenciales TEST.
- Aprobar el uso temporal de un túnel HTTPS para el clon descartable, NO para la base comercial.
- Confirmar quién gestionará pagos aprobados sin pedido, con consulta periódica, verificación de pago por ID y resolución registrada.

## Criterios de GO
Pago aprobado -> una fila de pago, una orden y descuento único de stock; rechazo -> no marcar aprobado; pendiente -> estado consistente; webhook duplicado -> sin doble operación; error posterior al cargo -> conciliación visible sin recobro. Sin credenciales reales en evidencia.

Fuentes oficiales:
https://www.mercadopago.com.ar/developers/es/docs/links-and-debts/additional-content/your-integrations/notifications
https://www.mercadopago.com.ar/developers/es/docs/checkout-bricks/integration-test/test-payment-flow
