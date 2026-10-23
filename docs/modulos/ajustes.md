# Ajustes — mandato integral Admin V3 (23/10/2026)

Estado: asignado a especialista, MAIN CHAT coordina e integra.

## Inventario (confirmar con código)
/admin/settings, POST /admin/settings y /design/home-hero, /design/home-sections y todas sus rutas; SettingsController; app/Views/pages/admin/settings (9 vistas).

## Alcance
Primero mapa y agrupación de configuraciones reales: tienda, comercio/checkout, logística, fiscal, integraciones, comunicaciones, visual/web y seguridad (según claves existentes). Detectar duplicadas/huérfanas/efectos; luego navegación por grupos, formularios, validaciones, persistencia y todas las subvistas. No cambiar claves de configuración ni exponer secretos.

## Reglas de ejecución
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md, docs/ARQUITECTURA_ADMIN_V3.md. Una auditoría inicial completa y una ventana grande de implementación: reparar lógica, seguridad, SQL/servicios y todas las vistas; rediseño HTML/CSS Admin V3 con /admin/seo/auditoria como referencia (cards, botones, jerarquía, peso de fuente, tablas, responsive). Reutilizar tokens/componentes, CSS acotado al módulo; sin Tailwind ni overrides globales. No modificar shell/sidebar, CSS compartido ni archivos de otros especialistas sin reserva MAIN CHAT. Probar en DB aislada las operaciones destructivas; verificar desktop/tablet/móvil. Registrar QA real y riesgos, sin microventanas.

## Primer prompt para el especialista
MiTiendaPesca — especialista Ajustes. Leé docs/modulos/ajustes.md y toda la documentación obligatoria. Auditá UNA vez el módulo entero con sus vistas derivadas; implementá integralmente calidad de código, seguridad, HTML/CSS Admin V3 con referencia visual Auditoría SEO y QA responsive/funcional. Sin microventanas ni solicitudes de autorización por cada cambio propio. Actualizá este documento con evidencia. **Terminá en una única respuesta breve:** MÓDULO | ESTADO (CERRADO/PARCIAL) | CAMBIOS | QA con resultados | ARCHIVOS/COMMIT | BLOQUEO + SOLUCIÓN. Ese resumen será auditado por MAIN CHAT.
