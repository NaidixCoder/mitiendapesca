# Auditoría de encabezados /admin/*

Inventario estático de vistas y rutas referenciadas. Las rutas dinámicas y parciales requieren revisión.

- Vistas PHP administrativas: 69
- Rutas /admin citadas en código: 95
- Vistas con h1: 38
- Con referencia a breadcrumb: 39

## Matriz

| Vista | H1 | Breadcrumb | Subnav | Acciones |
|---|---:|---|---|---|
| analytics/index.php | 1 | No detectado | Sí | Sí |
| arca/test.php | 1 | Sí | Sí | Sí |
| attributes/form.php | 1 | Sí | Sí | Sí |
| attributes/index.php | 0 | No detectado | Sí | No detectadas |
| attributes/value_set_edit.php | 0 | No detectado | No | Sí |
| attributes/value_set_items.php | 0 | No detectado | No | No detectadas |
| audit/index.php | 0 | No detectado | No | No detectadas |
| audit/logins.php | 0 | No detectado | Sí | No detectadas |
| audit/show.php | 0 | No detectado | Sí | Sí |
| brands/form.php | 1 | Sí | Sí | Sí |
| brands/index.php | 1 | Sí | Sí | Sí |
| catalog_tags/index.php | 1 | Sí | Sí | Sí |
| categories/form.php | 1 | Sí | Sí | Sí |
| categories/index.php | 1 | Sí | Sí | Sí |
| dashboard/index.php | 1 | No detectado | Sí | Sí |
| facturacion/alertas/index.php | 1 | Sí | Sí | Sí |
| facturacion/cierres_mensuales/index.php | 0 | Sí | Sí | No detectadas |
| facturacion/cierres_mensuales/show.php | 1 | Sí | Sí | Sí |
| facturacion/compras/form.php | 0 | Sí | Sí | No detectadas |
| facturacion/compras/index.php | 1 | Sí | Sí | Sí |
| facturacion/comprobantes/index.php | 1 | Sí | Sí | Sí |
| facturacion/comprobantes/show.php | 1 | Sí | Sí | Sí |
| facturacion/control/index.php | 0 | Sí | Sí | No detectadas |
| facturacion/libro_iva.php | 1 | Sí | Sí | Sí |
| facturacion/libro_iva_compras.php | 1 | Sí | Sí | Sí |
| facturacion/reportes/comprobantes_pendientes.php | 1 | Sí | No | Sí |
| facturacion/reportes/desvios.php | 0 | Sí | No | No detectadas |
| facturacion/reportes/duplicados_exactos.php | 1 | Sí | No | Sí |
| facturacion/reportes/home.php | 1 | Sí | Sí | Sí |
| facturacion/reportes/index.php | 0 | No detectado | No | No detectadas |
| facturacion/reportes/mensual.php | 1 | Sí | Sí | Sí |
| facturacion/reportes/pedidos_no_facturables.php | 1 | Sí | No | Sí |
| facturacion/reportes/pedidos_sin_factura.php | 1 | Sí | Sí | Sí |
| facturacion/reportes/resumen.php | 0 | No detectado | No | No detectadas |
| facturacion/reportes/saldo_iva.php | 0 | Sí | Sí | No detectadas |
| imports/index.php | 0 | Sí | Sí | No detectadas |
| imports/view.php | 0 | Sí | Sí | No detectadas |
| inventario/index.php | 1 | No detectado | No | Sí |
| marketing_campaigns/form.php | 1 | Sí | Sí | Sí |
| marketing_campaigns/index.php | 0 | Sí | Sí | No detectadas |
| marketing_campaigns/show.php | 0 | No detectado | Sí | No detectadas |
| marketing_email/automations.php | 1 | No detectado | Sí | Sí |
| marketing_email/campaign_form.php | 0 | No detectado | No | No detectadas |
| marketing_email/campaign_show.php | 1 | No detectado | Sí | Sí |
| marketing_email/campaigns.php | 1 | No detectado | Sí | Sí |
| marketing_email/index.php | 1 | No detectado | Sí | Sí |
| marketing_email/queue.php | 1 | No detectado | Sí | Sí |
| marketing_email/subscribers.php | 1 | Sí | Sí | Sí |
| marketing_email/templates_index.php | 1 | No detectado | No | Sí |
| marketing_email/templates_show.php | 0 | No detectado | No | No detectadas |
| orders/index.php | 1 | No detectado | Sí | Sí |
| orders/show.php | 1 | No detectado | Sí | Sí |
| products/form.php | 0 | No detectado | Sí | No detectadas |
| products/index.php | 0 | No detectado | Sí | No detectadas |
| products/variants.php | 0 | No detectado | Sí | No detectadas |
| proveedores/form.php | 1 | No detectado | Sí | Sí |
| proveedores/index.php | 1 | No detectado | Sí | Sí |
| seo/auditoria.php | 1 | No detectado | Sí | Sí |
| settings/email_marketing.php | 0 | Sí | Sí | No detectadas |
| settings/envios.php | 0 | Sí | Sí | No detectadas |
| settings/fiscal.php | 0 | Sí | Sí | No detectadas |
| settings/general.php | 0 | Sí | Sí | No detectadas |
| settings/home_hero.php | 1 | Sí | Sí | Sí |
| settings/home_sections.php | 1 | Sí | Sí | Sí |
| settings/index.php | 0 | Sí | Sí | No detectadas |
| settings/mayoristas.php | 0 | Sí | Sí | No detectadas |
| settings/pagos.php | 0 | Sí | Sí | No detectadas |
| users/index.php | 0 | No detectado | Sí | No detectadas |
| users/show.php | 0 | No detectado | Sí | No detectadas |

## Criterios
- La ausencia de H1/breadcrumb en vista no implica ausencia en parcial.
- Revisar subnavegación y acciones antes de modificar.
- Jerarquía de breadcrumb funcional, no derivada ciegamente del URL.
- Preservar sidebar y estructura global.
- Implementación de un único componente después del inventario.

## Encabezados y breadcrumbs unificados (continuación 2026-10-19)
- Cobertura: 67 vistas administrativas completas usan app/Views/partials/admin/page_header.php, según scripts/build/admin_page_header_contract.php. Dos vistas de Atributos son fragmentos internos sin encabezado propio.
- Se eliminaron los parciales antiguos app/Views/partials/admin/breadcrumbs.php y app/Views/partials/admin/components/page_header.php y se retiraron las 40 llamadas al breadcrumb anterior. Cero referencias en vistas.
- Facturación: subnavegación desplazada debajo del encabezado en 19 vistas; se conservaron enlaces y estados del componente.
- Eliminadas 8 reglas CSS obsoletas específicas de encabezados (Dashboard y editor de Productos).
- Validaciones: build gate completo OK tras traslado de navegación; contratos focales de CSS/encabezado nuevamente OK tras limpieza de reglas.
- Backups locales de archivos previos: C:\xampp\mi-pesca-backups (sin paquetes incrementales de entrega).
- Pendiente: inspección visual de las rutas en navegador, especialmente el editor de Productos, Auditoría y Facturación; el build gate no equivale a validación visual.
