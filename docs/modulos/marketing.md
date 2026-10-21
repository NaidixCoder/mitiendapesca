# Marketing — seguimiento del chat especializado

**Fecha:** 2026-10-21. **Estado:** listo para revisión de fase E2E Email; pendientes de pruebas avanzadas y coordinación del chat principal. **Responsabilidad exclusiva:** /admin/marketing/* y contratos propios; no otros módulos.

## Entregas y evidencia

**Campañas promocionales:** E2E fase 1 terminado en docs/pruebas/MARKETING_E2E_FASE1_20261020.md: creación, edición, activación, UTMs, first/last touch, deduplicación, atribución sintética, CSV. Tres correcciones Marketing documentadas allí; no equivale a Checkout E2E.

**Email Marketing:** E2E fase 2 terminado en docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md: navegador autenticado y HTTP real contra DB independiente con Mailer simulado y solicitudes externas bloqueadas. Crear, editar, programar a futuro, programar ahora, cancelar, cola, consentimiento al encolar y reprocesar, finalización y protecciones de estado/CSRF. **No correo real ni API de producción.**

## Hallazgo crítico y corrección implementada

- **Alta — edición duplicaba la campaña:** name=id estaba asociado a save-as-template-form y no se incluía en campanas/save. Corregido en app/Views/partials/admin/marketing_email/campaign_form/form_block.php.
- Contrato de regresión añadido a scripts/build/marketing_email_editor_contract.php. E2E HTTP corroboró creación y posterior edición con igual ID.

## CSS / UI / mobile-first, guía V3 v2.0

- Revisión sin edición visual en ocho vistas autenticadas x 363, 390, 768, 1024 y 1600 px = 40 combinaciones.
- Sin overflow lateral, con marketing-email.css cargado, sin excepciones JS; Page Header compartido de 20px y controles av3-ui-btn visibles. Se conservaron navegación y shell existentes.
- Deuda no bloqueante: av3-email-panel y av3-email-field duplican parcialmente patrones av3-ui-card/av3-ui-field; armonizar solo con aprobación del chat principal. Pestañas secundarias móviles desplazables requieren revisar indicación visual de scroll.

## Verificaciones finales

- Contrato de editor, smoke de formulario y contrato de ciclo de vida: OK.
- Build gate integral 2026-10-21: **OK, 826 PHP y 119 JS**, pruebas de Marketing, Email y regresiones.
- Aislamiento: C:\xampp\mi-pesca-backups\marketing_email_e2e_20261021_isolated; MySQL mi_pesca_email_e2e_20261021, cuatro destinatarios ficticios; servidor temporal 127.0.0.1:18088. Mailer mock dentro de la copia, con mail/curl/stream sockets deshabilitados. Archivos de prueba y capturas quedan fuera del repositorio.

## Pendientes

- Segmentación buyers, non_buyers, inactive_buyers y VIP; reintentos, límites de frecuencia y automatizaciones E2E requieren fixtures y nuevos tests aislados.
- No se validó envío a proveedor real (prohibido) ni se ejecutó el POST Cola/Procesar; sí la lógica worker/scheduler sobre MySQL aislado con transporte simulado.
- No se modificaron CSS/layout/shell globales, rutas compartidas, Checkout ni otros módulos; sin commits ni push. El chat principal coordina integración y decides próximos límites.

**Archivos modificados en fase 2:** app/Views/partials/admin/marketing_email/campaign_form/form_block.php, scripts/build/marketing_email_editor_contract.php, docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md y este informe.

**Cierre de seguridad:** se detuvo el servidor PHP de pruebas y se verificó que 127.0.0.1:18088 ya no acepta conexiones. La copia y la base E2E quedan aisladas para futuras pruebas; no se realizó ningún envío de correo comercial.


## Actualización de cierre documental y recursos E2E (2026-10-21)

**Estado: LISTO PARA REVISIÓN de la Fase 2; Marketing integral EN CURSO/PENDIENTE de pruebas avanzadas.** El informe de Fase 2 ya existía, pero faltaba registrar explícitamente el protocolo de cierre de navegadores; se añadió docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md y su referencia en las Fases 1 y 2.

- Auditoría sin lanzar nuevos navegadores ni repetir operaciones funcionales: se verificaron los scripts Edge/Puppeteer headless y finally/browser.close(), evidencias en el backup, puertos 18087/18088 cerrados, **0 procesos** con ruta E2E Email y **0 procesos Edge con indicador Puppeteer**.
- Teardown original basado en browser.close() dentro de finally; sin perfil Firefox personal ni ventana visible. No se verificó retrospectivamente cada directorio temporal de perfil Puppeteer y no se finalizaron procesos de otros chats.
- Se mantuvieron los resultados históricos correctos: E2E de flujo y consentimiento simulado, 40 validaciones visuales, error de ID corregido, build gate OK (826 PHP, 119 JS en su ejecución original). **No se reejecutaron** pruebas/gate para esta documentación.
- Sin nuevas correcciones de código; documentos de Marketing únicamente. Para detalles y límites, consultar el acta nueva. Sin commits, push ni cambios de otros módulos.


## ENTREGA PARA INTEGRACIÓN — Auditoría final de solo lectura (2026-10-21)

**Estado: LISTO PARA INTEGRACIÓN, con alcance limitado a las fases entregadas y aprobación pendiente del chat principal.** No se desarrollaron funciones nuevas ni se modificó código en esta auditoría. Marketing completo NO se declara terminado: segmentación avanzada, reintentos, frecuencia y automatizaciones permanecen reservados para la etapa siguiente.

### 1. Correcciones efectivamente implementadas

| Archivo | Implementación actual comprobada | Evidencia |
|---|---|---|
| app/Repositories/Marketing/MarketingCampaignHitRepository.php | Fallback efectivo de 60 segundos cuando la variable de deduplicación no está definida; conserva límites configurables | Git diff HEAD + dedupeWindowSeconds + E2E fase 1 |
| app/Repositories/Marketing/MarketingCampaignRepository.php | getRecentOrders filtra paid, processing, packed, shipped y delivered; criterio coincidente con getReport y getDailySeries | Git diff + comparación de las tres consultas + fixture de orden pagada/cancelada |
| app/Views/pages/admin/marketing_campaigns/show.php | Usa explícitamente datos preparados de show_view en vez de extract(EXTR_SKIP) que conservaba valores crudos y producía cifras/avisos incorrectos | Git diff + MarketingCampaignsShowViewModel + smoke fase 1 |
| app/Views/partials/admin/marketing_email/campaign_form/form_block.php | El hidden name=id pertenece al formulario principal Guardar campaña, sin form=save-as-template-form; Editar conserva el ID | Git diff + inspección de formulario + E2E HTTP fase 2 |
| scripts/build/marketing_email_editor_contract.php | Contrato adicional que impide regresar a la asociación incorrecta de id | Git diff + contrato estático aprobado |

Los controladores, repositorios y servicios de Email que ya protegen estados, consentimiento, cola, programación y cancelación fueron inspeccionados junto a sus contratos. NO son cambios nuevos por integrar en este lote: ya figuran versionados en HEAD.

### 2. Lista EXACTA para la integración de GitHub

Corte de auditoría: rama main, HEAD fa7c180. Comandos exclusivamente de lectura: git status --short -uall, git diff HEAD --name-status, git diff --cached --name-status, git diff --check y git ls-files. **No hay archivos staged al momento del corte.**

**Archivos versionados con cambios locales (M), 5:**

1. app/Repositories/Marketing/MarketingCampaignHitRepository.php
2. app/Repositories/Marketing/MarketingCampaignRepository.php
3. app/Views/pages/admin/marketing_campaigns/show.php
4. app/Views/partials/admin/marketing_email/campaign_form/form_block.php
5. scripts/build/marketing_email_editor_contract.php

**Archivos nuevos sin seguimiento (??), 5:**

6. scripts/dev/marketing_campaign_detail_smoke.php
7. docs/pruebas/MARKETING_E2E_FASE1_20261020.md
8. docs/pruebas/MARKETING_EMAIL_E2E_FASE2_20261021.md
9. docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md
10. docs/modulos/marketing.md (este informe, con esta sección)

**Total: 10 archivos, y ningún otro.** No incluir cambios concurrentes de Marcas, Categorías o Atributos. Evitar preparar el commit con git add .; el chat principal deberá verificar otra vez el estado de Git antes de staging selectivo y revisar su diff cached.

Los demás archivos de Email/Campañas (vistas completas, JS, CSS, controladores, servicios y repositorios compartidos) están ya versionados en el HEAD observado y no requieren inclusión como cambios nuevos. Excluir siempre la carpeta externa C:\xampp\mi-pesca-backups, credenciales, .env, BD/sesiones, capturas, scripts auxiliares y el Mailer simulado privado de las pruebas. Los avisos Git sobre conversión LF/CRLF en Windows no señalaron errores en git diff --check de los cinco archivos de código.

### 3. Contratos y coincidencia con las pruebas

**Ejecutado en ESTA auditoría, solo lectura de archivos, sin abrir navegador ni acceder a la base comercial:**

- scripts/build/marketing_admin_contract.php: OK.
- scripts/build/marketing_email_editor_contract.php: OK.
- scripts/build/marketing_email_campaign_lifecycle_contract.php: OK.
- git diff --check de los cinco archivos de código: sin errores de whitespace.

**Evidencias anteriores, NO reejecutadas ahora:**

- Fase 1 Campañas: E2E autenticado HTTP/Edge en MySQL independiente, crear/editar/activar, enlaces/UTM, first/last touch, dedupe, CSV y métricas con fixtures. La compra ficticia pagada y la cancelada se insertaron directamente en MySQL: NO fue Checkout E2E. Smoke de detalle y build gate de la etapa OK (824 PHP, 119 JS).
- Fase 2 Email: E2E HTTP/Edge autenticado con BD aislada y Mailer mock para crear/editar, programar futuro/inmediato, encolar, revisar consentimiento antes y después del encolado, worker simulado ejecutado por CLI en la copia aislada, finalizar/cancelar y rechazar CSRF/destinatarios ajenos a la prueba. Build gate documentado entonces OK (826 PHP, 119 JS).
- El POST administrativo Cola/Procesar NO fue invocado en el E2E; tampoco se entregaron correos mediante la API real ni se consultó la BD habitual durante esta auditoría.

Los tres contratos corridos ahora son **estáticos**. Su resultado no equivale a ejecutar de nuevo el E2E ni el build gate completo.

### 4. Design System Admin V3 v2.0 y revisión visual

Se consultaron docs/GUIA_VISUAL_ADMIN_V3.md v2.0, marketing-email.css, marketing-campaigns.css, design-system.css y los parciales del módulo. También se inspeccionaron capturas históricas reales ui_editor_390.png, ui_cola_390.png y ui_editor_1600.png.

**Coherencia general:** Page Header del shell (20 px), paleta petróleo, fondos claros, botones compartidos av3-ui-btn, namespace CSS de Marketing, composición de formularios mobile-first y expansión a columnas mediante container queries. Fase 2 documenta 8 vistas en 363/390/768/1024/1600 px: 40 comprobaciones HTTP autenticadas, sin overflow horizontal ni errores JS. NO se generaron capturas nuevas hoy.

**Deudas visuales NO bloqueantes:**
- av3-email-panel y av3-email-field duplican parcialmente av3-ui-card y av3-ui-field; conservar hasta refactorización coordinada y revisión de consumidores.
- av3-email-nav es desplazable horizontalmente en móvil, pero esconde scrollbar y no presenta una señal evidente de más pestañas; en la captura a 390 px se observan opciones fuera de la primera vista. El patrón de tabs de Campañas también debe revisarse al armonizar.
- En marketing-email.css persisten algunas reglas legacy de estructuras anteriores sin consumidor aparente (ejemplo: selector antiguo para av3-space-top-4 dentro de av3-email-composer__form). Revisar consumidores antes de limpiar. Algunas etiquetas siguen a 12 px: deuda de densidad/tipografía heredada, no nuevo criterio del DS.

**No se declara cumplimiento visual perfecto** ni se propone cambiar CSS durante la integración. Ninguna de esas deudas demostró pérdida de funcionalidad o overflow general en los E2E y capturas existentes.

### 5. Bloqueantes frente a pendientes avanzados

**Bloqueantes del commit de las fases entregadas:** no se encontraron defectos críticos abiertos en las cinco modificaciones revisadas. El chat principal deberá controlar aislamiento de los 10 archivos respecto de los módulos paralelos, revisar staged diff y ejecutar el gate al integrar. La publicación o despliegue NO está aprobada por esta auditoría.

**Pendientes expresamente NO bloqueantes para integrar este alcance:** segmentación buyers, non_buyers, inactive_buyers y VIP; automatizaciones y disparadores; reintentos, excepciones y frecuencia; mejoras de componentes/pestañas móviles; QR externo sin prueba de servicio.

**Límites si se pretende aprobar Marketing COMPLETO para producción:** no se probó Checkout→pedido→pago real, ni recepción SMTP/API productiva (prohibida), ni el botón HTTP Cola/Procesar. Son dependencias de aceptación y pruebas futuras, no evidencias de que esos flujos ya funcionen. Si el negocio exige esas condiciones para desplegar, la decisión de bloqueo del despliegue corresponde al propietario/chat principal, diferenciada de integrar el código al repositorio.

### 6. Protocolo de navegadores, procesos y seguridad

Conforme a docs/pruebas/PROTOCOLO_NAVEGADORES.md y al acta docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md:

- E2E históricos: Edge Chromium **headless**, administrador ficticio y contextos de prueba aislados; scripts Puppeteer con finally/browser.close(). Nunca se empleó Firefox personal.
- Auditoría de SOLO LECTURA actual: puertos locales 18087 y 18088 **CERRADOS**, cero procesos php.exe/node.exe/msedge.exe atribuibles al E2E Email y cero msedge.exe con indicador Puppeteer.
- No se inició navegador ni worker, no se finalizó ningún proceso y no se tocó Explorer/XAMPP en esta entrega.
- Capturas y evidencias siguen fuera del proyecto. No se inventarió retrospectivamente cada perfil/caché temporal de Puppeteer ni se promete reparar entradas fantasma antiguas de Alt+Tab.

### 7. Cierre y responsable siguiente

**ENTREGA PARA INTEGRACIÓN PREPARADA.** Solo se modificó este archivo documental para registrar la auditoría; sin commits, push, cambio de rama, uso de API de correo ni consultas sobre la base habitual.

**Responsable del siguiente paso:** chat principal. Volver a comprobar Git status, incluir exclusivamente la lista de 10 archivos en un commit de Marketing, revisar staged diff y definir integración. Hasta esa revisión, **no iniciar nuevas funciones ni la etapa avanzada en este chat**.
