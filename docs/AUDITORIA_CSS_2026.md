# Auditoría CSS — etapa 1

Auditoría estática de hojas cargadas y clases declaradas en plantillas. No equivale a validación visual.

## Totales
- CSS: 58
- Vistas PHP: 373
- Archivos JS: 116
- Coincidencias candidatas Tailwind: 1371 en 133 archivos PHP.
- Dentro de admin: 1294 en 115 archivos.

## CSS compartidos
- css/v2/base.css
- css/v2/components/buttons.css
- css/v2/components/ui.css
- css/v2/tokens.css
- css/v2/utilities.css

## CSS exclusivos de admin
- css/admin-v3/components/common.css
- css/admin-v3/components/primitives.css
- css/admin-v3/layout.css
- css/admin-v3/pages/billing.css
- css/admin-v3/pages/catalog.css
- css/admin-v3/pages/dashboard.css
- css/admin-v3/pages/orders.css
- css/admin-v3/pages/product-editor.css
- css/admin-v3/pages/products.css
- css/admin-v3/tokens.css
- css/v2/components/admin-beta-bridge.css
- css/v2/components/admin-foundations.css
- css/v2/components/ui-admin.css

## Vistas administrativas con candidatos Tailwind
- app/Views/partials/admin/marketing_email/campaign_form/form_block.php: 149 coincidencias; absolute, bg-black, bg-black/40, bg-black/50, bg-indigo-50, block, border-indigo-200
- app/Views/partials/admin/products/tab_seo.php: 84 coincidencias; block, border-[color:var(--border)]/60, col-span-1, col-span-4, col-span-6, gap-5, h-10
- app/Views/partials/admin/products/variants/_editor.php: 66 coincidencias; absolute, bg-black, bg-slate-50/70, bg-white/90, col-span-2, hidden, max-w-[180px]
- app/Views/partials/admin/products/tab_basicos.php: 58 coincidencias; absolute, bg-[color:var(--panel-subtle)], bg-black/40, block, border-dashed, fixed, h-10
- app/Views/partials/admin/products/variants/sale_fragment.php: 51 coincidencias; absolute, bg-emerald-500/50, bg-slate-900/50, block, border-slate-300, fixed, h-1.5
- app/Views/partials/admin/seo/entity_panel.php: 49 coincidencias; bg-gray-50, block, border-[color:var(--border)]/60, col-span-1, col-span-5, h-2, h-2.5
- app/Views/partials/admin/products/tab_imagenes.php: 38 coincidencias; absolute, bg-brand, bg-white/60, bg-white/70, block, border-dashed, border-slate-300/80
- app/Views/pages/admin/catalog_tags/index.php: 37 coincidencias; block, md:col-span-2, overflow-x-auto, w-24, w-full
- app/Views/pages/admin/settings/home_sections.php: 35 coincidencias; absolute, bg-black/40, block, fixed, h-10, h-12, h-24
- app/Views/partials/admin/products/tab_venta.php: 31 coincidencias; block, border-amber-300, h-5, mt-7, w-5, w-full
- app/Views/partials/admin/users/index/table.php: 28 coincidencias; block, overflow-hidden, overflow-x-auto, p-0, px-0, py-0, py-8
- app/Views/partials/admin/imports/index/preflight.php: 22 coincidencias; hidden, md:w-72, ml-2, mt-0.5, overflow-auto, overflow-hidden, pt-2
- app/Views/pages/admin/attributes/form.php: 21 coincidencias; inline, md:col-span-1, md:col-span-3, md:w-40, md:w-44, mx-1, overflow-x-auto
- app/Views/partials/admin/processing_overlay.php: 20 coincidencias; absolute, bg-black/30, bg-gray-900, border-2, border-gray-300, border-t-gray-900, fixed
- app/Views/partials/admin/settings/envios/sender_card.php: 20 coincidencias; block, md:col-span-2
- app/Views/pages/admin/settings/home_hero.php: 19 coincidencias; absolute, bg-black/40, fixed, hidden, md:col-span-2, overflow-auto, w-[min(94vw,900px)]
- app/Views/partials/admin/marketing_campaigns/show/diagnostics_card.php: 18 coincidencias; overflow-auto, pl-2, pr-2, sticky
- app/Views/partials/admin/attributes/index/sets_card.php: 18 coincidencias; absolute, bg-black/30, fixed, hidden, max-w-2xl, md:col-span-2, md:col-span-6
- app/Views/partials/admin/settings/envios/checkout_card.php: 17 coincidencias; block
- app/Views/pages/admin/facturacion/comprobantes/show.php: 17 coincidencias; bg-red-50, border-red-200, lg:col-span-2, max-w-6xl, overflow-auto, px-2.5, text-red-800
- app/Views/partials/admin/facturacion/reportes/resumen/content.php: 16 coincidencias; max-w-6xl, ml-2, overflow-auto, sticky, w-full
- app/Views/pages/admin/facturacion/libro_iva_compras.php: 15 coincidencias; max-w-6xl, md:col-span-5, overflow-auto, overflow-hidden, p-0, sticky, w-full
- app/Views/partials/admin/settings/pagos/commercial_card.php: 14 coincidencias; block, max-w-md, pt-4, w-full
- app/Views/partials/admin/products/form/unsaved_modal.php: 14 coincidencias; absolute, bg-black/50, border-black/5, fixed, hidden, mt-24, overflow-hidden
- app/Views/partials/admin/marketing_campaigns/index/cards.php: 14 coincidencias; h-28, h-[120px], inline, max-w-[55%], max-w-[65%], mt-0.5, w-28
- app/Views/pages/admin/inventario/index.php: 14 coincidencias; max-w-6xl, mt-0.5, overflow-auto, overflow-hidden, sticky, w-[110px], w-[140px]
- app/Views/pages/admin/facturacion/compras/index.php: 14 coincidencias; inline, max-w-6xl, md:col-span-2, md:col-span-4, overflow-auto, overflow-hidden, p-0
- app/Views/partials/admin/seo/auditoria/results.php: 13 coincidencias; bg-gray-50, min-w-[320px], min-w-[56px], overflow-auto, overflow-hidden, p-0, py-10
- app/Views/partials/admin/imports/index/history.php: 13 coincidencias; inline, max-w-[320px], ml-2, overflow-auto, overflow-hidden, overflow-x-auto, p-0
- app/Views/partials/admin/imports/view/rows.php: 13 coincidencias; bg-[color:var(--admin-card)], ml-2, mx-1, overflow-auto, sticky, w-[110px], w-[130px]
- app/Views/partials/admin/products/tab_publicacion.php: 12 coincidencias; border-white/10, h-16, h-4, h-7, px-2.5, shadow-sm, w-16
- app/Views/pages/admin/marketing_email/queue.php: 12 coincidencias; block, inline, max-w-6xl, md:w-56, overflow-auto, sticky, w-full
- app/Views/pages/admin/orders/index.php: 12 coincidencias; block, overflow-x-auto, w-full
- app/Views/partials/admin/orders/arca_box.php: 11 coincidencias; hidden, inline, ml-2, overflow-auto, sticky
- app/Views/partials/admin/products/variants/_feedback.php: 11 coincidencias; absolute, bg-black/40, fixed, hidden, max-w-[560px], relative, shadow-xl
- app/Views/pages/admin/facturacion/comprobantes/index.php: 11 coincidencias; max-w-6xl, md:col-span-1, md:col-span-2, overflow-auto, sticky, w-full, z-10
- app/Views/partials/admin/confirm_modal.php: 10 coincidencias; absolute, bg-black/40, fixed, flex-col-reverse, hidden, max-w-lg, shadow-xl
- app/Views/partials/admin/settings/general/social_card.php: 10 coincidencias; block, w-full
- app/Views/partials/admin/attributes/index/header.php: 10 coincidencias; bg-amber-500, bg-emerald-500, bg-sky-500, h-2, pt-1, w-2
- app/Views/partials/admin/settings/envios/correo_card.php: 9 coincidencias; block
- app/Views/partials/admin/seo/auditoria/filters.php: 9 coincidencias; md:col-span-2, w-full
- app/Views/partials/admin/imports/index/upload_form.php: 9 coincidencias; border-2, border-dashed, md:col-span-2, md:min-w-[320px], ml-2, ml-6, mt-0.5
- app/Views/pages/admin/marketing_email/templates_index.php: 9 coincidencias; border-red-200, hover:bg-red-50, inline, lg:px-8, max-w-6xl, overflow-auto, overflow-hidden
- app/Views/pages/admin/facturacion/libro_iva.php: 8 coincidencias; max-w-6xl, overflow-auto, sticky, w-full
- app/Views/pages/admin/marketing_email/campaign_show.php: 8 coincidencias; bg-indigo-600, bg-rose-600, hover:bg-indigo-700, hover:bg-rose-700, max-w-6xl, max-w-none, overflow-auto
- app/Views/pages/admin/facturacion/reportes/mensual.php: 8 coincidencias; max-w-6xl, mr-3, overflow-auto, sticky, w-full
- app/Views/pages/admin/marketing_email/subscribers.php: 7 coincidencias; bg-[color:var(--admin-surface)], overflow-auto, overflow-hidden, p-0, sticky, w-80, w-full
- app/Views/pages/admin/facturacion/alertas/index.php: 7 coincidencias; max-w-6xl, overflow-auto, sticky, w-full
- app/Views/partials/admin/products/tab_inventario.php: 6 coincidencias; block, mt-7, w-full
- app/Views/partials/admin/settings/email_marketing/automation_delays_card.php: 6 coincidencias; border-[color:var(--line)], hidden, overflow-auto, sticky, w-full
- app/Views/partials/admin/products/variants/table.php: 6 coincidencias; overflow-auto, overflow-hidden, p-0, py-10, sticky, w-full
- app/Views/partials/admin/products/variants/_warning.php: 6 coincidencias; bg-amber-100, block, h-9, ml-1, mt-0.5, w-9
- app/Views/pages/admin/categories/index.php: 6 coincidencias; inline, overflow-x-auto, py-10, w-72, w-full
- app/Views/pages/admin/proveedores/form.php: 6 coincidencias; w-full
- app/Views/partials/admin/settings/fiscal/arca_card.php: 5 coincidencias; block, pt-7
- app/Views/partials/admin/imports/view/errors.php: 5 coincidencias; bg-[color:var(--admin-card)], overflow-auto, sticky, w-[110px], w-full
- app/Views/partials/admin/imports/view/warnings.php: 5 coincidencias; bg-[color:var(--admin-card)], overflow-auto, sticky, w-[110px], w-full
- app/Views/pages/admin/marketing_email/templates_show.php: 5 coincidencias; lg:col-span-1, lg:col-span-2, lg:px-8, max-w-6xl, sm:px-6
- app/Views/partials/admin/settings/fiscal/regime_card.php: 4 coincidencias; block
- app/Views/partials/admin/products/form/tabs_nav.php: 4 coincidencias; h-2.5, inline-block, ml-2, w-2.5
- app/Views/partials/admin/marketing_campaigns/show/recent_card.php: 4 coincidencias; overflow-auto, pl-2
- app/Views/partials/admin/facturacion/reportes/saldo_iva/header_filters.php: 4 coincidencias; md:col-span-3
- app/Views/partials/admin/facturacion/compras/form/attachments.php: 4 coincidencias; inline, overflow-auto, sticky, w-full
- app/Views/partials/admin/attributes/index/attributes_card.php: 4 coincidencias; md:col-span-3, overflow-auto, sticky, w-full
- app/Views/pages/admin/facturacion/reportes/comprobantes_pendientes.php: 4 coincidencias; max-w-6xl, ml-2, overflow-auto, sticky
- app/Views/partials/admin/products/_gallery_item.php: 3 coincidencias; absolute, w-full, z-10
- app/Views/partials/admin/settings/email_marketing/limits_card.php: 3 coincidencias; block
- app/Views/partials/admin/orders/show/order_header_card.php: 3 coincidencias; hidden, w-full
- app/Views/partials/admin/marketing_email/templates_show/details.php: 3 coincidencias; border-red-200, hover:bg-red-50, w-full
- app/Views/partials/admin/marketing_campaigns/show/link_card.php: 3 coincidencias; h-[160px], max-w-[520px], w-[160px]
- app/Views/partials/admin/marketing_campaigns/show/performance_card.php: 3 coincidencias; overflow-auto, pl-2
- app/Views/partials/admin/facturacion/reportes/saldo_iva/table.php: 3 coincidencias; overflow-auto, sticky, w-full
- app/Views/partials/admin/facturacion/compras/form/main_form.php: 3 coincidencias; md:col-span-2
- app/Views/partials/admin/facturacion/cierres_mensuales/index/table.php: 3 coincidencias; overflow-auto, sticky, w-full
- app/Views/pages/admin/attributes/value_set_edit.php: 3 coincidencias; bg-[color:var(--admin-bg)], min-w-[220px], overflow-auto
- app/Views/pages/admin/attributes/value_set_items.php: 3 coincidencias; bg-gray-50, overflow-auto, pr-1
- app/Views/pages/admin/marketing_email/automations.php: 3 coincidencias; max-w-6xl, overflow-auto, sticky
- app/Views/pages/admin/proveedores/index.php: 3 coincidencias; sticky, w-full
- app/Views/pages/admin/facturacion/reportes/duplicados_exactos.php: 3 coincidencias; max-w-6xl, overflow-auto, sticky
- app/Views/pages/admin/facturacion/reportes/pedidos_no_facturables.php: 3 coincidencias; max-w-6xl, overflow-auto, sticky

## Riesgos
- Revisar falsos positivos, clases dinámicas y selectores JS antes de cambiar nada.
- Conservar geometría del sidebar y proteger frontend público.
- El inventario completo está en AUDITORIA_CSS_2026.json.
