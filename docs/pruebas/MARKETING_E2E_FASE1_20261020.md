# MiTiendaPesca — E2E Marketing, fase 1 (2026-10-20)

**Responsable:** chat especializado Marketing.
**Estado:** Campañas, enlaces, UTMs, atribución y reportes verificados con alcance acotado. No equivale a la aprobación integral de Checkout o Email.

## Seguridad y aislamiento

- Copia privada externa: C:\xampp\mi-pesca-backups\marketing_e2e_20261020_isolated.
- Servidor de pruebas: 127.0.0.1:18087; reproduce la subcarpeta /mi-pesca/public_html de XAMPP.
- Base independiente mi_pesca_marketing_e2e_20261020 con 65 tablas copiadas SOLO en estructura; datos comerciales originales no copiados.
- Administrador ficticio, sesiones de navegador separadas, tráfico externo del navegador bloqueado.
- Servidor PHP con allow_url_fopen=0 y funciones curl, mail, fsockopen y stream_socket_client deshabilitadas. Credenciales de correo y pagos NO incluidas en el .env de pruebas.
- NO se ejecutaron workers, procesos de correo, servicios de pago ni llamadas a la API de correo de producción.
- No se tocaron config/routes.php, router real, Checkout, Git ni archivos globales de otros módulos.

## Resultados E2E con HTTP real, navegador y MySQL aislado

| Flujo | Resultado |
|---|---|
| Acceso sin sesión / con administrador ficticio | 401 / 200, correcto |
| Crear campaña y comprobar persistencia | OK |
| Editar nombre y consultar listado/detalle | OK |
| Desactivar campaña y abrir enlace | 404 correcto |
| Activar campaña, abrir enlaces cortos y comprobar destino UTM | 302 correcto, ambas variantes |
| Atribución de visitante por sesión | OK |
| First touch: visitar A y luego B | Se mantiene campaña A |
| Last touch: cambiar ajuste y visitar A y luego B | Queda campaña B |
| Deduplicación de hits en la misma sesión | Error encontrado, corregido y revalidado |
| Exportación CSV de lista y detalle | 200, contenido correcto |
| Errores JavaScript de navegador | 0 |

Nota: los UTMs se consumen al registrar la primera página útil y quedan registrados en analytics_events.meta_json; no deben permanecer indefinidamente en la sesión.

## Verificación de compras e ingresos mediante fixtures (NO checkout E2E)

En la base aislada se insertaron un evento add_to_cart, una orden pagada de $12.500 y una cancelada de $7.000. El reporte, la serie diaria, el detalle autenticado y su CSV mostraron un carrito, una compra válida y $12.500. La compra cancelada quedó fuera de Compras atribuidas.

Este control NO representa una compra procesada mediante el Checkout ni un pago real. Las órdenes se insertaron directamente en MySQL de pruebas, sin Mercado Pago.

## Defectos reales corregidos dentro de Marketing

1. app/Repositories/Marketing/MarketingCampaignHitRepository.php: si la variable de deduplicación faltaba, getenv devolvía false y la ventana quedaba en 0 segundos. Se corrigió el fallback efectivo a 60 segundos, respetando valores configurados.
2. app/Views/pages/admin/marketing_campaigns/show.php: la extracción EXTR_SKIP permitía que los datos crudos desplazaran a los preparados por el ViewModel, generando warnings y cifras incorrectas. Ahora se usan explícitamente las estructuras preparadas.
3. app/Repositories/Marketing/MarketingCampaignRepository.php: las compras recientes incluían canceladas, a diferencia del KPI. Ahora usan el mismo filtro de estados confirmados.

Regresión añadida: scripts/dev/marketing_campaign_detail_smoke.php. No realiza escrituras ni envíos.
Respaldo privado de los archivos anteriores y las capturas/pruebas: C:\xampp\mi-pesca-backups\marketing_e2e_20261020_isolated.

## Límites y pendientes

- E2E de Checkout → orden → confirmación de pago → atribución final requiere coordinación con su responsable. No tocar Checkout desde Marketing.
- Código QR requiere servicio QR externo; no probado por la prohibición de salida de red en pruebas.
- El formulario de campañas acepta URLs externas http aunque la UI recomienda https; definir la política comercial antes de cambiar compatibilidad.
- Email Marketing: pendiente E2E HTTP autenticado de guardado, programación, cola y cancelación con transporte simulado. La API de correo en producción permanece expresamente fuera de alcance.
- Validación manual en Firefox autenticado del sitio habitual: pendiente. Las pruebas se ejecutaron con HTTP real en copia aislada y Edge automatizado.
- Git, commits, push, MANIFEST.md y documentos de coordinación corresponden al chat principal; no se modificaron en esta etapa.

## Archivos del proyecto modificados

- app/Repositories/Marketing/MarketingCampaignHitRepository.php
- app/Repositories/Marketing/MarketingCampaignRepository.php
- app/Views/pages/admin/marketing_campaigns/show.php
- scripts/dev/marketing_campaign_detail_smoke.php
- docs/pruebas/MARKETING_E2E_FASE1_20261020.md (este documento)

No se modificaron archivos transversales, bases comerciales ni código de otros módulos. Informar al chat principal para integración documental y commit cuando corresponda.

## Cierre reanudado tras interrupción (2026-10-20)

- Reejecutados contra el proyecto local: `scripts/dev/marketing_campaign_detail_smoke.php`, `scripts/dev/marketing_campaign_forms_smoke.php`, `scripts/dev/marketing_readonly_smoke.php` y `scripts/build/marketing_admin_contract.php`: todos **OK**.
- Reejecutado el build gate integral `scripts/build/gate.php`: **OK**, **824 PHP** y **119 JS**, contratos y regresiones incluidos. Se ejecutó mientras otros chats trabajaban en Marcas/Categorías/Atributos: no se editaron sus archivos.
- Repetido E2E de la instancia aislada: métricas del detalle y CSV, dedupe en sesión y ambas variantes de redirección promocional **OK**.
- `git status --short --branch` de solo lectura confirmó `main` con ediciones paralelas de otros módulos. **Sin operaciones Git de modificación ni commits**. El coordinador deberá integrar únicamente los cinco archivos enumerados anteriormente.
- Los tres errores corregidos de Marketing están comprobados; el E2E de conversión comercial continúa limitado a fixtures en base aislada, sin cobro ni pedido creado por Checkout.
- Al cerrar esta etapa se finalizó el servidor PHP temporal y se verificó que el puerto 127.0.0.1:18087 dejó de aceptar conexiones. La base MySQL y copia de código permanecen aisladas, disponibles para próximas pruebas controladas.


## Estado posterior de seguimiento (2026-10-21)

El pendiente histórico de esta fase «Email Marketing: E2E HTTP de guardado, programación, cola y cancelación» **fue abordado posteriormente**, en la **Fase 2** documentada en docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md. Su acta adicional de cierre y protocolo de navegadores se encuentra en docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md.

Esto no implica que se haya probado la API de correo de producción: el transporte fue estrictamente simulado, y los flujos restantes de segmentación, reintentos, límites de frecuencia y automatizaciones permanecen pendientes. La Fase 1 de Campañas promocionales conserva su alcance original; no se agregaron pruebas a esa fase durante este seguimiento.
