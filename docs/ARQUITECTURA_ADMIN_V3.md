# Arquitectura objetivo — admin-v3

> **Guía normativa de UI (2026-10-21):** [`GUIA_VISUAL_ADMIN_V3.md`](GUIA_VISUAL_ADMIN_V3.md). Los bloques de implementación por fecha más abajo son **históricos** y pueden haber quedado superados por etapas posteriores; comprobar siempre código actual e informes de módulo.

## Estado actual de arquitectura
Admin V3 está en adopción progresiva con shell maestro, Page Header compacto, tokens, componentes propios y hojas CSS por página **ya implementados en varios módulos**. La limpieza integral de todos los consumidores legacy no se considera terminada. No alterar apariencia pública ni geometría del sidebar. El trabajo de Marcas, Categorías y Atributos sigue a cargo de chats especializados.

## Árbol objetivo (esquema conceptual; no todos los archivos existen aún)
- public_html/assets/css/admin-v3/tokens.css
- public_html/assets/css/admin-v3/base.css
- public_html/assets/css/admin-v3/layout.css
- public_html/assets/css/admin-v3/components/{buttons,fields,tables,cards,badges,modals,feedback}.css
- public_html/assets/css/admin-v3/pages/{dashboard,catalog,orders,billing,...}.css

## Reglas de implementación
1. Prefijo obligatorio `av3-` para clases de presentación nuevas, excepto contratos explícitos del shell que se migrarán al final.
2. Un único selector funcional estable `data-*` para comportamiento JS, en lugar de reutilizar clases de presentación; el cambio exige actualizar el JS y pruebas.
3. Una definición por componente. Nada de archivos `-v2`, `-v3` superpuestos, bridges nuevos ni correcciones globales.
4. CSS por página sólo para la composición de la página. Controles reutilizables viven en components.
5. Admin aislado por `body[data-page^="admin"]`, preferentemente sin afectación global del público.
6. Layout: compensación de sidebar exclusivamente en `admin-surface`, nunca aplicada desde vistas internas.
7. No retirar los cinco CSS compartidos hasta verificar su uso en público y adaptar los consumidores.
8. Tailwind: eliminar **todas** las utilidades de templates y JS, migrando sin cambios de apariencia y con pruebas. El CDN se retira una vez que no queden consumidores en ambos ámbitos.

## Checklist de migración por módulo
- [ ] Inventario de rutas, subrutas, estados, clases y selectores JS
- [ ] Componentes creados/reutilizados en admin-v3
- [ ] Plantillas sin clases Tailwind ni presentación legacy
- [ ] Hojas CSS anteriores de ese módulo desconectadas y sin consumidores
- [ ] Build gate, validación funcional y revisión responsive
- [ ] Evidencia en MANIFEST y actualización de plan

## Dependencias que bloquean eliminación total
La auditoría identificó coincidencias masivas en PHP: antes de eliminar el CDN o los bridges, se requiere completar la clasificación de esas coincidencias y revisar clases ensambladas dinámicamente.

## Implementación SEO administrativa (2026-10-20)

- Ruta /admin/seo/auditoria: vista ensambladora y parciales de filtros/resultados, controlador SeoAuditController, consulta SeoAuditQuery y SeoAuditIndexViewModel responsable de clases/etiquetas/URLs.
- Resumen agregado de productos activos calculado a partir de la misma expresión SQL del score del listado. Contenido complementario separado en el ViewModel, sin cambiar puntuación.
- CSS en admin-v3/pages/seo.css cargado por head.php, namespace av3-seo, componentes de UI av3-ui y tokens V3.
- Editores de productos y entidades conservan todos los names y data-seo-* como contrato con product_score.js, entity_score.js y product_blocks.js. Persistencia y endpoints no modificados.
- Contrato SEO en scripts/build/seo_admin_contract.php (sin base), prueba focal manual de consultas/render scripts/dev/seo_readonly_smoke.php (sólo lectura).

## Actualización de presentación SEO (2026-10-20)

- results.php integra la vista compartida seo/auditoria/serp_preview.php como celda permanente desktop y dentro de seo/auditoria/diagnostic.php solo en mobile/tablet. La vista de diagnóstico no recalcula ni repite el score o el contenido opcional.
- ViewModel añade has_meta_description para diferenciar descripción configurada de texto estimado. El CSS único admin-v3/pages/seo.css concentra la distribución responsive; no se modificó la consulta ni las rutas.
- El contrato seo_admin_contract.php comprueba la presencia de vista previa desktop y ausencia de detalles desktop; el smoke readonly comprueba el número de previews, filas y detalles móviles en el HTML.

## Marketing — listado inicial y flujo seguro (2026-10-20)

- `MarketingCampaignsController::index` prepara filtro estado/consulta/días/per/página y usa `countForAdmin` + `listForAdmin` paginada; `MarketingCampaignsIndexViewModel` normaliza etiquetas, enlaces y paginador para vistas sin cálculos.
- Vistas de `marketing_campaigns/index/*` migradas al espacio CSS `admin-v3/pages/marketing-campaigns.css`; `marketing_subnav.php` compartido entre Campañas y Email, con clase propia.
- El motor de emails: `EmailBroadcastSchedulerService` excluye de nuevas tandas la audiencia ya encolada por campaña; `shouldFinalize` revisa destinatarios aún no encolados y trabajos pendientes; `EmailQueueWorkerService` verifica consentimientos y estado de campaña al despachar; se cancelan trabajos pendientes de campaña al cancelar.
- Verificaciones sin envíos: contrato `scripts/build/marketing_admin_contract.php` y smoke `scripts/dev/marketing_readonly_smoke.php`. No usar fixtures contra producción ni ejecutar el worker para probar presentación.

## Marketing — fase 2: Nuevas/Editar y detalle (2026-10-20)

- `marketing_campaigns/form.php` conserva contrato POST /save y names originales. El módulo JS `admin/marketing/campaigns.js` conserva comportamiento Copiar y añade preview de slug mediante `data-marketing-campaign-form`, `data-marketing-slug`, `data-marketing-preview-url` y `data-marketing-preview-copy`.
- `marketing_campaigns/show.php` ensambla parciales del detalle, utiliza `MarketingCampaignsShowViewModel` (identidad, ratios, series, rankings, referrer normalizado, acciones QR/exportación) sin mover cálculos a la vista. Los referrers pasan de clases Tailwind a tonos semánticos.
- CSS solo en `admin-v3/pages/marketing-campaigns.css`; funcionalidad y rutas de reporting intactas. Verificación focal `scripts/dev/marketing_campaign_forms_smoke.php` genera HTML con datos simulados sin escrituras DB y el contrato general Marketing cubre parciales, hooks y CSS.

## Email Marketing V3 — fase 1 (2026-10-20)

- `marketing_email/index.php`, `subscribers.php`, `automations.php` se ensamblan con Page Header maestro, `marketing_subnav.php` y nuevo `marketing_email_nav.php`; CSS acotado a `av3-marketing-email`.
- `EmailMarketingController::subscribers` aplica q/status/per/page validados y usa `EmailSubscriberRepository::countForAdmin` + `listForAdmin(..., offset)`. `MarketingEmailSubscribersViewModel` prepara totales, paginación, badges y enlaces. El parcial `marketing_email_subscriber_action.php` reutiliza el POST idéntico en tabla y móvil, protegido por CSRF.
- Automatizaciones conservan `MarketingEmailAutomationsViewModel` y sus endpoints toggle_automation / toggle_step. El listado solo presenta; los servicios scheduler/worker y reglas de consentimiento no fueron modificados en esta fase.
- Protección en `scripts/build/marketing_email_v3_contract.php` y smoke `scripts/dev/marketing_email_v3_smoke.php`; pruebas de solo lectura y HTML sintético sin ejecutar envíos.

## Email Marketing V3 — fase 2 (2026-10-20)

- Envíos masivos y Cola usan repositorios con `countForAdmin` y `offset` para paginar 25/50; Plantillas usa `EmailTemplateRepository::countForAdmin` y `listLite(limit,offset)`. Orden estable por fecha e ID. Paginación presentacional centralizada en `MarketingEmailPagination::build()` y parcial `marketing_email_pagination.php`.
- `MarketingEmailCampaignsIndexViewModel` y `MarketingEmailQueueViewModel` devuelven `status_tone`/`result_tone` (sin clases CSS en datos), `MarketingEmailTemplatesIndexViewModel` devuelve `notice.type`; la vista asigna CSS de los tonos. Formulario de eliminación reutilizable y CSRF sin alteraciones.
- Vista previa de HTML en `templates_show/preview.php` conserva `sandbox=""` y `referrerpolicy="no-referrer"`; solo contenido escapado en `srcdoc`. Contrato de fase 2 en `scripts/build/marketing_email_v3_phase2_contract.php`, smoke de DB y HTML `scripts/dev/marketing_email_v3_phase2_smoke.php`.
- UI nueva limitada a rutas listadas; los editores y detalle de campaña masiva aún requieren migración independiente, con pruebas del JavaScript y de las rutas críticas de programación.

## Marketing Email V3 — fase 3: campañas masivas (2026-10-20)

- `marketing_email/campaign_form.php` ensambla `campaign_form/{pre_form,form_block,post_form}.php`. `MarketingEmailCampaignFormViewModel` entrega estado/segmentación, plantillas y rutas, y `campaign_editor/index.js` mantiene hooks históricos; `data-email-audience-condition` controla solo visibilidad en cliente. Formulario externo `apply-template-form` y `save-as-template-form` siguen independientes.
- `campaign_show.php` usa `MarketingEmailCampaignShowViewModel` para estados semánticos, KPIs, pedidos y enlaces; el contenido HTML pasa como texto escapado a `iframe srcdoc sandbox=""`. `campaign_show.js` implementa confirmación de POSTs sensibles desde el cliente, sin sustituir autorizaciones o validación de backend.
- CSS sobre el namespace de Email V3; verificación `marketing_email_editor_contract.php` + smoke sintético `marketing_email_editor_smoke.php` + interacción browser sin acciones reales. No se cambió el scheduler/worker ni el guardado de campañas.

## Email Marketing V3 — fase 4: modales y estado del editor (2026-10-20)

- `campaign_form/form_block.php` termina el único formulario de campaña después de cerrar el contenedor del editor. `post_form.php` conserva formularios externos `apply-template-form`/`save-as-template-form` dentro del bloque principal y monta los cuatro modales desde `campaign_form/modals` fuera de `av3-wrap`.
- El JS de `campaign_editor` usa estados locales en lugar de clases de utilidad globales para visibilidad, pestañas y checklist. `product_picker.js` genera clases propias; los servicios de catálogo, guardado, subida y envío no se alteraron.
- Los tres modales reescritos usan backdrop y panel accesible `role="dialog"`/`aria-modal`, aislados del contenedor de consultas CSS, y conservan IDs/datos consumidos por los scripts. `iframe` con sandbox vacío.
- Refuerzo en `scripts/build/marketing_email_editor_contract.php` y smoke sin persistencia; pruebas de geometría 363/390/768/1440 y modales con navegador automatizado.

## Marketing Email V3 — fase 5: editor completamente modular (2026-10-20)

- Composición: `campaign_form.php` → `campaign_form/content.php` → `pre_form.php` + `form_block.php` (configuración y ensamblado de `templates.php`, `message.php`, `tools.php`) + `post_form.php` (formularios externos de plantillas, cuatro diálogos independientes). Las acciones POST y `csrf_field()` no cambian.
- Estado modal compartido en `campaign_editor/dialog_scroll_lock.js` mediante Set de diálogos, que protege el scroll frente a aperturas anidadas; los eventos originales y IDs se conservan. `form_validation.js` aplica `is-invalid`/`aria-invalid` y delega los estilos al CSS unificado.
- El contrato `scripts/build/marketing_email_editor_contract.php` evita reintroducción de utilidades legacy, modificaciones de estado `av3-overflow-hidden` e invalidación inline. El smoke focal de render y los navegadores de prueba no realizan escrituras ni invocan workers de email.

## Email Marketing — política de mutaciones protegidas (2026-10-20)

- `EmailCampaignRepository::canEdit` verifica estado `draft/scheduled` y ausencia de filas para la campaña en `email_queue`. `update` repite la condición en SQL para evitar cambios después del encolado; `scheduleNowIfDraft` y `cancelIfActive` validan transiciones atómicas por estado.
- Ambos POSTs de cambios (`EmailCampaignsController::save` y `EmailTemplatesController::applyToCampaign`) comprueban esa política. El scheduler sigue separado y no se invoca en las pruebas de ciclo de vida. La vista `campaign_show` recibe `pageId=admin-email-campaign-show` para inicializar confirmaciones.
- Contrato de build sin DB `scripts/build/marketing_email_campaign_lifecycle_contract.php`; smoke transaccional con `ROLLBACK` `scripts/dev/marketing_email_campaign_lifecycle_smoke.php`. Pruebas de confirmación de borrador/programada/enviada con navegador automatizado, sin POST reales.
