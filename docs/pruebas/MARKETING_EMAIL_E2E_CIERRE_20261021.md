# MiTiendaPesca — Email Marketing: acta de cierre E2E y navegadores

**Fecha:** 2026-10-21. **Responsable:** chat especializado Marketing. **Estado:** LISTO PARA REVISIÓN DEL CHAT PRINCIPAL.
**Relación con etapas:** este documento complementa la Fase 2 existente en docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md. No es una Fase 3 funcional; no se ejecutó una tercera tanda de E2E.

## 1. Motivo del acta y alcance comprobado

El informe de Fase 2 ya documentaba los recorridos funcionales, corrección y build gate. Faltaba registrar el cierre de recursos conforme a docs/pruebas/PROTOCOLO_NAVEGADORES.md, leído durante este control documental, y resolver la desactualización del pendiente Email en la Fase 1.

En esta revisión de cierre **no se volvieron a ejecutar pruebas funcionales** ni se modificó código. Se inspeccionaron los archivos/evidencias originales de E2E, los scripts de navegador y el estado actual de procesos/puertos en el equipo autorizado por Desktop Commander.

## 2. Implementación realmente corregida en Fase 2

**Defecto ALTO corregido:** editar una campaña de Email creaba otra en lugar de actualizarla. El input oculto name=id de app/Views/partials/admin/marketing_email/campaign_form/form_block.php declaraba form="save-as-template-form", por lo que el POST de Guardar campaña no recibía el ID. Se quitó esa asociación del campo ID del formulario principal. El E2E de crear y editar mantuvo el mismo ID.

Se amplió el contrato scripts/build/marketing_email_editor_contract.php para fallar si se vuelve a asociar incorrectamente ese input o deja de existir en el formulario de guardado.

**No se realizaron otros cambios funcionales ni visuales en Fase 2.** Hubo un error inicial en el automatizador (pulsaba Guardar como plantilla por un selector amplio); se corrigió solamente el script de prueba y no se contabiliza como defecto del producto.

## 3. Pruebas históricas efectivamente ejecutadas en Fase 2 (no reejecutadas en este cierre)

| Flujo | Evidencia histórica y resultado |
|---|---|
| GET de vistas de Email con administrador ficticio | browser_probe.js: HTTP 200 |
| Crear borrador, editar mismo ID y consultar detalle | campaign_flow.js: aprobado después de corrección |
| Programar fecha futura; cancelar y bloquear edición cancelada | campaign_flow.js: aprobado |
| Programar ahora con destinatarios activos | immediate_e2e.js: campaña sending y dos jobs queued |
| Excluir contactos pending y unsubscribed | immediate_e2e.js: correcto |
| Dar de baja a un destinatario ya encolado por POST autenticado | immediate_e2e.js: correcto |
| Revalidar consentimiento al procesar | mock_worker_check.php: un job cancelled, un sent **simulado** |
| Finalizar campaña al vaciar la cola | mock_worker_check.php: estado sent **simulado** |
| Cancelar una campaña sending y cancelar pendientes | security_cancel_e2e.js: correcto |
| Bloquear reprogramación tras cancelar | security_cancel_e2e.js: correcto |
| CSRF inválido | security_cancel_e2e.js: HTTP 403 |
| Correo de prueba con Mailer mock | @invalid.test aceptado, dominio externo rechazado |
| E2E visual | visual_responsive.js: ocho vistas x cinco anchos = 40 comprobaciones |

**Límite de alcance:** estos son resultados históricos de HTTP/navegador y DB aislada, no del entorno comercial. El procesamiento de worker/scheduler fue PHP aislado por CLI, no el POST administrativo Cola/Procesar. No hubo conexión ni envío al proveedor real.

**Build gate al cierre de la implementación:** scripts/build/gate.php OK, 826 PHP y 119 JavaScript, según ejecución registrada al terminar la Fase 2. **No se reejecutó el build gate en esta actualización documental.**

## 4. Aislamiento y barreras de correo — comprobadas por la fase original

- Copia E2E fuera del proyecto: C:\xampp\mi-pesca-backups\marketing_email_e2e_20261021_isolated.
- BD independiente: mi_pesca_email_e2e_20261021 con 65 tablas copiadas solo en estructura y usuarios/correos ficticios @invalid.test.
- Servidor local de pruebas en 127.0.0.1:18088, replicando /mi-pesca/public_html.
- Mailer sustituido únicamente en la copia privada: registra el envío en mail_sink.jsonl y rechaza destinatarios no ficticios. PHP con mail y conexiones salientes deshabilitadas; Puppeteer interceptó conexiones externas.
- No se ejecutaron workers sobre BD comercial ni se utilizó API de correo de producción. El registro mail_sink.jsonl aún existe en el respaldo privado y no es una entrega por SMTP.
- La base de datos de pruebas permanece conservada para auditoría; **no** se la confundió con la base comercial ni se la eliminó.

## 5. Cumplimiento de Design System Admin V3 v2.0

En la Fase 2 se verificaron mediante Edge autenticado ocho vistas (Resumen, Envíos, Editor, Detalle, Cola, Suscriptores, Automatizaciones y Plantillas) a 363, 390, 768, 1024 y 1600 px.

**Resultados obtenidos:** HTTP 200 en todas las 40 combinaciones, sin overflow horizontal del documento ni errores JavaScript, CSS de Marketing cargado y Page Header compartido de 20 px. Se utilizan el shell, navegación y botones av3-ui-btn; se mantuvo la composición responsive/mobile-first. No se modificó CSS transversal.

**Excepciones conocidas:** av3-email-panel y av3-email-field se superponen parcialmente a av3-ui-card y av3-ui-field. La navegación secundaria en móvil estrecho desplaza opciones fuera de la primera vista y podría necesitar affordance de scroll. No se resolvieron esas deudas visuales en la prueba funcional.

**Evidencia:** capturas existentes ui_editor_390.png, ui_cola_390.png, ui_editor_1600.png, ui_cola_1600.png y otras ocho capturas autenticadas, en el directorio de pruebas privado. Este cierre comprobó la existencia de esos archivos; **no** capturó nuevas pantallas.

## 6. Teardown según PROTOCOLO_NAVEGADORES.md

**Motor y modo:** Edge Chromium con Puppeteer, exclusivamente headless. Ninguna ventana visible de navegador se abrió intencionalmente. Sesión HTTP de administrador **ficticio autenticado** contra base E2E; no se empleó perfil personal de Firefox ni sesión de comercio.

**Ciclo de vida demostrado en los scripts:**
- browser_probe.js, campaign_flow.js, immediate_e2e.js, security_cancel_e2e.js y visual_responsive.js contienen finalmente/finally y cierre explícito de browser.close() en su teardown, incluso ante excepción.
- Los scripts crean páginas con browser.newPage() dentro del contexto administrado por Puppeteer. En visual_responsive.js también se cierra cada página por ancho; en los restantes, cerrar el navegador destruye sus páginas y contexto propios.
- Cada proceso Node que terminó exitosamente figura en la salida original de la fase. Las primeras ejecuciones fallidas del automatizador también tenían finally/browser.close; no se usó taskkill por nombre ni se afectaron navegadores ajenos.

**Auditoría de recursos ejecutada en esta actualización, 2026-10-21 01:08–01:10 (hora del equipo):**
- 127.0.0.1:18088: CERRADO. 127.0.0.1:18087 (Fase 1): CERRADO.
- Consulta Win32_Process: **cero** procesos php.exe/node.exe/msedge.exe con ruta de copia E2E de Email en la línea de comando.
- Consulta específica de procesos msedge.exe con indicador Puppeteer: **cero** al comprobar.
- No se identificaron procesos de estos E2E que requieran terminación. No se interrumpieron otras aplicaciones ni se tocaron procesos de otros chats.
- Los archivos de evidencia (scripts, capturas, mail_sink.jsonl, respaldo del formulario) están en carpeta privada externa. No se generaron HTML ni perfiles temporales nuevos en public_html del proyecto durante este cierre. Los scripts originales no declaraban un perfil persistente propio y no se encontraron procesos abiertos; **no se hizo inventario retrospectivo de cada directorio temporal autogenerado por Puppeteer**.

**Conclusión de protocolo:** navegadores cerrados por finally según scripts y sin procesos/puertos de E2E identificables al momento de auditoría. No afirmar que se verificó físicamente cada posible archivo de caché/perfil temporal ni entradas fantasma antiguas de Alt+Tab.

## 7. Flujos no validados / pendientes reales

- Segmentación por buyers, non_buyers, inactive_buyers y VIP con pedidos sintéticos: pendiente.
- Reintentos y fallos del transporte, frecuencia por día/semana, automatizaciones por disparadores y secuencias: pendiente.
- Ejecutar directamente el POST administrativo Cola/Procesar: no realizado. Se probó el worker aislado por CLI.
- Entrega al proveedor real, bandeja real, rendimiento de API, cron productivo: **no se realizaron y quedan fuera de alcance por prohibición de correo real**.
- Integración de un Checkout/pago real y atribución comercial de extremo a extremo: no se probó en esta fase, requiere coordinación con Checkout.
- Armonización CSS transversal y UX de pestañas móviles: coordinar con chat principal.

## 8. Archivos, estado y entrega

**Archivos funcionales realmente modificados en Fase 2:**
- app/Views/partials/admin/marketing_email/campaign_form/form_block.php
- scripts/build/marketing_email_editor_contract.php

**Documentos del módulo escritos/actualizados:**
- docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md
- docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md (esta acta)
- docs/pruebas/MARKETING_E2E_FASE1_20261020.md (anexo de estado posterior)
- docs/modulos/marketing.md

**No se alteraron:** módulos ajenos, layout, rutas compartidas, CSS global, base comercial, Git ni documentos de coordinación central. Sin commits ni push.

**Estado final:** Fase 2 de Email Marketing LISTA PARA REVISIÓN por el chat principal, con flujo básico E2E autenticado y transportador simulado aprobado; **Marketing completo no se declara terminado** por los pendientes anteriores. La presente acta completa el control documental/navegadores, no añade pruebas funcionales nuevas.
