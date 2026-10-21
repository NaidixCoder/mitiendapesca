# MiTiendaPesca — E2E de Email Marketing, fase 2 (2026-10-21)

**Responsable:** chat especializado Marketing. **Estado:** listo para revisión; pruebas autenticadas en entorno independiente. **Producción de correo:** no utilizada.

## 1. Preparación y alcance

Se leyeron docs/GUIA_VISUAL_ADMIN_V3.md v2.0, docs/README.md, docs/COORDINACION.md, docs/ESTADO_DESARROLLO.md, docs/PROTOCOLO_GIT_MULTICHAT.md y docs/modulos/README.md. Se reutilizó el enfoque de docs/pruebas/MARKETING_E2E_FASE1_20261020.md.

Objetivo: comprobar mediante HTTP y navegador autenticado los flujos de crear, editar, programar, cancelar, encolar, estado final y consentimiento. No se modificaron archivos ni datos de otros módulos.

## 2. Seguridad y aislamiento de la API de correo

- Copia temporal del proyecto, fuera del repositorio: C:\xampp\mi-pesca-backups\marketing_email_e2e_20261021_isolated.
- Servidor PHP local: 127.0.0.1:18088. Router reproduce /mi-pesca/public_html; no toca el router del sitio.
- Base independiente mi_pesca_email_e2e_20261021; 65 tablas clonadas solo en estructura; no se importaron usuarios, pedidos ni campañas comerciales.
- Fixture: administrador ficticio y cuatro emails @invalid.test (2 activos, 1 pendiente, 1 dado de baja); luego se dio de baja a un activo mientras esperaba en cola.
- Mailer sustituido **solo en la copia E2E**: acepta exclusivamente destinatarios @invalid.test, escribe un JSON por entrega en mail_sink.jsonl y no se conecta a SMTP/API ni llama mail(). Los adjuntos se rechazan.
- PHP ejecutado con allow_url_fopen=0 y mail, curl_init, curl_exec, curl_multi_init, fsockopen, pfsockopen y stream_socket_client deshabilitados. Navegador con peticiones externas abortadas.
- La copia no contiene credenciales de proveedores de correo o pagos en su .env. No se ejecutó ningún worker/scheduler contra la base comercial. El procesamiento comprobado se realizó exclusivamente en la copia aislada y con Mailer simulado.

## 3. Resultado de E2E real (HTTP, Edge autenticado y MySQL independiente)

| Recorrido | Resultado | Prueba |
|---|---|---|
| Acceso autenticado a vistas Email | 200 OK | browser_probe.js |
| Crear borrador desde Guardar campaña | OK | campaign_flow.js |
| Editar el mismo ID de campaña | **Defecto detectado, corregido y revalidado** | campaign_flow.js |
| Editar para programar fecha futura | OK | campaign_flow.js |
| Cancelar programada | OK | campaign_flow.js |
| Impedir editar una cancelada | OK | campaign_flow.js |
| Programar ahora desde borrador | Enviando + 2 en cola | immediate_e2e.js |
| Consentimiento al encolar | Solo dos activos; pendientes/bajas excluidos | immediate_e2e.js |
| Baja después de encolar | POST HTTP autenticado OK | immediate_e2e.js |
| Procesamiento con Mailer simulado y consentimiento actualizado | 1 sent simulado + 1 cancelled | mock_worker_check.php |
| Finalización luego de procesar toda la cola | Campaña sent | mock_worker_check.php |
| Cancelar campaña Enviando y sus jobs pendientes | Campaña cancelled, cola cancelled | security_cancel_e2e.js |
| Reprogramar tras cancelación | Bloqueado | security_cancel_e2e.js |
| POST sin CSRF a guardar | HTTP 403 | security_cancel_e2e.js |
| send_test simulado a email ficticio | 200 y registro local únicamente | security_cancel_e2e.js |
| send_test hacia dominio no ficticio | Rechazado; ninguna entrega | security_cancel_e2e.js |

**Interpretación:** las filas sent y el registro del Mailer significan solamente entregas simuladas. No se afirma recepción real ni funcionamiento de la API de producción. La prueba del worker y scheduler se hizo con los servicios del código sobre base E2E, nunca sobre la base comercial.

## 4. Defecto real corregido (Marketing)

**Prioridad alta: Editar duplicaba una campaña.** En app/Views/partials/admin/marketing_email/campaign_form/form_block.php, el campo oculto name=id tenía form="save-as-template-form". Por eso el POST principal campanas/save recibía ID 0 y creaba un registro nuevo.

Se eliminó el atributo form de ese input para asociarlo al formulario Guardar campaña. No se alteraron las asociaciones propias de Aplicar/Guardar plantilla.

Se amplió scripts/build/marketing_email_editor_contract.php para impedir la regresión. El navegador comprobó que crear y editar mantienen el mismo ID.

**Problema exclusivo del automatizador, no del producto:** al principio la prueba pulsó Guardar como plantilla porque usaba un selector genérico. Se corrigió para pulsar Guardar campaña y se repitió el flujo completo.

## 5. Guía visual V3 v2.0 — 40 comprobaciones autenticadas

Se revisaron ocho pantallas reales: Resumen, Envíos, Nuevo editor, Detalle, Cola, Suscriptores, Automatizaciones y Plantillas; a **363, 390, 768, 1024 y 1600 px** (40 combinaciones).

- Todas HTTP 200, CSS marketing-email.css cargado y sin desbordamiento horizontal; cero errores JavaScript y cero solicitudes externas.
- Page Header compartido a 20px; shell, navegación y botones av3-ui-btn reutilizados; mobile-first y tablas/filas adaptables conservadas.
- Capturas guardadas fuera del repositorio: ui_editor_390.png, ui_cola_390.png, ui_editor_1600.png, ui_cola_1600.png, además de otras de filas y listados.
- **Deuda visual:** los componentes locales av3-email-panel y av3-email-field tienen cierta superposición con av3-ui-card / av3-ui-field globales. Requieren armonización coordinada, no refactor transversal desde Marketing durante el E2E.
- En móviles estrechos la segunda barra de navegación es horizontal y parte de sus opciones quedan fuera de la primera vista; revisar affordance de desplazamiento en futura mejora UX.

## 6. Contratos, regresiones y build gate

- scripts/build/marketing_email_editor_contract.php: OK, incluye la nueva aserción de asociación del ID.
- scripts/dev/marketing_email_editor_smoke.php: OK (HTML y modelos sin escrituras).
- scripts/build/marketing_email_campaign_lifecycle_contract.php: OK.
- Build gate completo scripts/build/gate.php: **OK; 826 PHP y 119 JavaScript**, sidebar, Page Header, CSS, Marketing, Email, checkout y regresiones.
- La prueba visual se realizó en Edge automatizado autenticado con datos reales de la instancia aislada, NO en Firefox comercial.

## 7. Archivos del proyecto cambiados

1. app/Views/partials/admin/marketing_email/campaign_form/form_block.php
2. scripts/build/marketing_email_editor_contract.php
3. docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md (este documento)
4. docs/modulos/marketing.md (seguimiento exclusivo)

Backup del formulario antes de la corrección: C:\xampp\mi-pesca-backups\marketing_email_e2e_20261021_isolated\form_block_before_fix.php. No se modificaron layouts, rutas globales, CSS compartido, head.php, otros módulos ni Git. No se hicieron commits, merges ni push.

## 8. Pendientes y límites para coordinación

- Audiencias específicas buyers, non_buyers, inactive_buyers y VIP requieren fixtures de órdenes y tests E2E adicionales.
- Pruebas funcionales de frecuencia/retardos, reintentos y automatizaciones separadas no están incluidas en esta fase.
- El POST administrativo Cola/Procesar no se invocó: se verificó la misma lógica de procesamiento mediante CLI aislado con transporte simulado.
- No hubo envío real, confirmación SMTP o proveedor de correo. No se autorizan conexiones a la API de producción para completar las pruebas.
- La etapa se entrega para revisión y eventual integración por el chat principal. La base de ensayo conserva solo datos ficticios; el servidor se detiene al cerrar.

**Cierre de seguridad:** se detuvo el servidor PHP de pruebas y se verificó que 127.0.0.1:18088 ya no acepta conexiones. La copia y la base E2E quedan aisladas para futuras pruebas; no se realizó ningún envío de correo comercial.


## 9. Acta de cierre de navegadores y evidencia (2026-10-21)

El cierre detallado exigido por docs/pruebas/PROTOCOLO_NAVEGADORES.md quedó registrado en **docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md**; ese documento no constituye una nueva fase funcional.

- Scripts Puppeteer en **Edge headless**: browser_probe.js, campaign_flow.js, immediate_e2e.js, security_cancel_e2e.js y visual_responsive.js. Se verificó presencia de bloques finally y browser.close() en los cinco. Páginas/contextos asociados a cada navegador se cierran con browser.close(); visual_responsive.js también cierra cada página por ancho.
- Auditoría de procesos actual: **0** procesos php.exe/node.exe/msedge.exe identificables por ruta E2E Email y **0** Edge con indicador Puppeteer. Puertos 18088 y 18087 cerrados.
- No se abrió Firefox personal ni se ejecutaron navegadores visibles. No se terminó ningún proceso ajeno. No se creó nueva sesión de navegador para esta verificación documental.
- Los registros y las 12 capturas originales existen en el backup externo; no se realizó inventario retrospectivo de cada directorio de perfil/caché temporal Puppeteer. Conservar ese límite al describir el teardown.
- Los E2E y gate descritos arriba corresponden a la ejecución original de la Fase 2: **no fueron reejecutados en esta actualización documental**.
