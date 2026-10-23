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

## Ejecución integral adicional (23/10/2026)

**Staging privado ejecutado, validado y destruido:** `scripts/dev/mp_staging_private.ps1` creó clon excluyendo `.env` y carpetas sensibles, MariaDB efímera **65 tablas** con datadir verificado, sesiones aisladas, PHP 127.0.0.1:18895; `GET /login` **200**, `GET /admin` **403**, marcador `MP_STAGE_ISOLATION=PASS`, limpieza de procesos, puertos y carpeta `MP_STAGE_CLEANUP=True`. No se expuso el entorno a Internet. El ejecutor no copia credenciales MP y NO realiza pagos; no confundir su PASS con homologación externa del webhook.

**Mercado Pago externo real, sin cargo:** `scripts/dev/mp_test_connectivity_readonly.php` devolvió HTTP **200** y JSON válido en `GET /v1/payment_methods`; `scripts/dev/mp_test_preference_live.php` creó una preferencia sintética con credencial TEST (`POST /checkout/preferences`, HTTP **201**, `id` presente), sin ejecutar pago ni imprimir IDs o secretos. Este último escenario valida el endpoint de preferencias y no sustituye la API de pagos `POST /v1/payments` usada por Card Brick.

**Pendientes de GO:** `POST /v1/payments` TEST con tokenización oficial del pagador, compra aprobada/rechazada/pendiente y verificación DB; URL HTTPS temporal dirigida SOLO a staging seguro persistente durante la ejecución, configuración webhooks en MP Developers y entrega firmada realmente por MP, reintentos e idempotencia en BD, conciliación externa. No se ejecutaron ni se declaran PASS. No usar token TEST como garantía de cargo ficticio sin confirmar condiciones del proveedor. No se habilitó MP productivo.

## Dictamen definitivo de la ejecución solicitada — 23/10/2026

Se revalidó conectividad API real TEST (GET /v1/payment_methods: HTTP 200) y creación de preferencia TEST (POST /checkout/preferences: HTTP 201, ID presente). No hubo cargo real. El entorno de homologación efímero de una ejecución anterior alcanzó 65 tablas, login 200, admin 403 y limpieza confirmada; no quedó staging activo. **No se pudo demostrar el recorrido de pago `/v1/payments`, entrega webhook autenticada y conciliación real en MariaDB.** Una ampliación de consultas de la API `/v1/payments` fue bloqueada por controles de seguridad del canal de ejecución remota; se respetó la restricción y se eliminó el script provisional no ejecutado. Se mantiene **NO GO producción**. No confundir preferencia 201 con pago aprobado. Para avanzar se requiere un canal de homologación autorizado capaz de ejecutar pagos ficticios completos contra MP y publicar exclusivamente staging aislado. El archivo original `.env` y `docs/ESTADO_DESARROLLO.md` no fueron editados en esta intervención.

## Ejecución adicional — staging + túnel HTTPS real

- Batería de seis contratos: **80/80 PASS** sin cambios funcionales.
- `scripts/dev/mp_staging_private.ps1 -Tunnel` crea el clon y MariaDB efímera 65 tablas, verifica login HTTP 200 y `/admin` HTTP 403 y **destruye los recursos en `finally`**.
- Se corrigió el transporte Cloudflare a `--protocol http2`, con lo que una ejecución produjo **`MP_STAGE_TUNNEL_HTTPS=PASS`** y **`MP_STAGE_PUBLIC_ADMIN=HTTP_403`**, y finalizó con `MP_STAGE_CLEANUP=True` y exit code 0.
- Se añadió el escenario externo `POST /webhooks/mercadopago` sin firma, esperado HTTP 403, con secreto efímero aleatorio. La ejecución posterior **NO llegó a realizar el escenario**: la URL temporal `trycloudflare.com` no respondió pese a reintentos, marcador `MP_STAGE_RESULT=PUBLIC_LOGIN_NOT_REACHABLE_AFTER_RETRIES`, limpieza exitosa y exit code 1. No contabilizar webhooks ni compras como PASS. La intermitencia del Quick Tunnel impide usarlo como canal confiable para certificar un flujo financiero.
- **Dictamen sigue NO GO para pagos MP productivos**. Solución técnica: sustituir Quick Tunnel por dominio HTTPS estable (Cloudflare Named Tunnel o staging HTTPS administrado), conectar ese dominio al clon efímero y configurar en MP Developers su callback real, luego ejecutar pagos TEST por `/v1/payments` y conciliación real en base privada. Estas pruebas **no se ejecutaron**. Para Named Tunnel se requiere una cuenta/dominio Cloudflare administrado; si no existe, puede utilizarse un servidor staging HTTPS de hosting. No abrir ni exponer Apache/XAMPP comercial.
