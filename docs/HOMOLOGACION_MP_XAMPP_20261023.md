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

## Avance de implementación — cloudflared instalado (23/10/2026)

Autorizado por el propietario. Se instaló con Winget (paquete Cloudflare.cloudflared, versión 2026.10.3, hash del instalador verificado, salida 'Instalado correctamente'). Fuente oficial Cloudflare: https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/ . Quick Tunnel provee URL HTTPS temporal, sin apertura de puertos entrantes; la URL es pública para quien la conozca y cambia entre sesiones. **Instalación NO equivale a túnel iniciado**.

Aislamiento verificado conceptualmente con referencia existente `scripts/dev/run_checkout_http_private.ps1`: es un runner temporal local que excluye `.env`, `.git`, uploads y storage y verifica un datadir MariaDB separado. Sin embargo este runner destruye su entorno al terminar, no admite credenciales MP ni exposición HTTPS. **No se debe exponer el runner ni el XAMPP comercial directamente**. Para homol. MP se necesita otro lanzador seguro de staging, persistente durante las pruebas y con apagado explícito, y cargar TEST credentials únicamente en secreto local. Hasta tenerlo probado, no arrancar túnel.

Dependencia siguiente: acceder a Mercado Pago > Tus integraciones para configurar app de prueba, claves TEST y webhook. Nunca incorporar tokens productivos, no reproducir claves en logs, chat o commits. Antes de abrir el túnel verificar que el origin 127.0.0.1 corresponde al clon privado con DB y sesiones privadas; deshabilitar phpMyAdmin y rutas administrativas sensibles en la exposición.

## Evidencia API externa real (23/10/2026)

Con permiso del propietario se realizó desde el equipo local una solicitud HTTPS **de solo lectura** a `GET https://api.mercadopago.com/v1/payment_methods`, autenticada con el token TEST ya existente en `.env`. Script `scripts/dev/mp_test_connectivity_readonly.php`: `HTTP 200`, JSON válido, `MP_TEST_CONNECTIVITY=PASS`. No imprimió token ni JSON de la respuesta y **no generó ningún cargo**. La suite interna focal de seis programas volvió a aprobar 80/80.

**Alcance**: conectividad de red, TLS y lectura autenticada. Este resultado NO acredita creación/confirmación de pagos `/v1/payments`, callbacks públicos ni reconciliación externa. Sigue NO GO producción. No iniciar túnel sobre XAMPP original. Crear y verificar un staging aislado antes de emitir una URL de webhook público.
