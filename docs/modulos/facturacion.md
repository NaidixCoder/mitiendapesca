# Facturación — mandato integral Admin V3

Estado: asignado al especialista. Main coordina; no solapar archivos.

## Alcance
Todas las rutas /admin/facturacion/*: control, comprobantes/detalle/PDF, consultas y reintentos, libro IVA ventas/compras y exportaciones, compras/adjuntos, reportes fiscales, desvíos, pendientes, duplicados, alertas, cierres mensuales. Controladores: FacturacionControlController, ComprobantesFiscalesController, LibroIvaController, LibroIvaComprasController, ComprasController, ReportesFiscalesController, ReporteMensualIvaController, ReporteSaldoIvaController, FacturacionAlertasController, AccountingClosuresController. Vistas: app/Views/pages/admin/facturacion (20).

## Mandato
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md y docs/ARQUITECTURA_ADMIN_V3.md. Auditar UNA vez rutas, lógica, consultas, seguridad, permisos, integridad fiscal, ARCA, exportaciones y vistas derivadas; implementar correcciones y rediseño integral en ventana grande. Referencia estética /admin/seo/auditoria: cards compactas, botones, tipografías y densidad; reutilizar tokens y componentes Admin V3. HTML bien estructurado, CSS solo de módulo, responsive integral. Prohibido operaciones fiscales reales, modificar shell/shared sin coordinación o tocar BD comercial destructivamente. QA aislado con evidencia y entrega final compacta. Registrar resultados aquí.

## Prompt inicial del especialista
MiTiendaPesca: asumí Facturación de punta a punta bajo este documento. Analizá una sola vez e implementá TODO el módulo y las vistas derivadas, salud de código + rediseño HTML/CSS V3 tomando SEO Auditoría como referencia; QA integral sin microventanas. Respetá coordinación y archivos ajenos. Cierre breve para MAIN CHAT: MÓDULO | ESTADO | CAMBIOS | QA | ARCHIVOS/COMMIT | BLOQUEO Y SOLUCIÓN.
