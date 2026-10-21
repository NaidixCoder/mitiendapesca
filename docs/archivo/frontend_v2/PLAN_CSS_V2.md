# Plan de consolidación CSS v2 — MiTiendaPesca
Fecha: 2026-10-17. Auditoría en XAMPP; sin modificar CSS productivo.

## Inventario medido
- tokens.css: 59 líneas, variables globales.
- components/ui.css: 71 líneas, 10 selectores estimados, componentes compartidos.
- components/ui-admin.css: 286 líneas, 45 selectores estimados, 3 important.
- layouts/admin-shell.css: 275 líneas, 46 selectores estimados, 5 important.
- components/admin-beta-bridge.css: 535 líneas, 107 selectores estimados, puente de compatibilidad.
- components/buttons.css: 79 líneas, 13 selectores estimados, botones v2.
Las cifras son heurísticas estáticas; repeticiones responsive no son necesariamente defectos.

## Riesgos verificados
1. Conviven tres familias de botones: .ui-btn, .v2-btn y .btn, con diferentes tamaños/radios/estados.
2. El bridge global admin crece y podría tapar problemas de migración por pantalla.
3. admin-shell contiene reglas body generales y ocultación de nav legacy con important.
4. Sólo dashboard, pedidos y parte de facturación tienen CSS específico de página admin.
5. head.php carga Tailwind CDN globalmente y estilos legacy públicos durante la transición.

## Responsabilidad de archivos
- tokens.css: únicamente variables.
- base.css: reset y estilos HTML base.
- ui.css: componentes compartidos entre tienda y admin.
- ui-admin.css: componentes transversales sólo del administrador.
- admin-shell.css: contenedores, navegación, geometría y responsive del shell.
- pages/admin/<modulo>.css: casos específicos de cada módulo.
- admin-beta-bridge.css: compatibilidad temporal; sólo retirar reglas cuando se pruebe que no tienen consumidores.

## Orden de cambios recomendado
1. Inventariar consumidores reales de botones, tablas, tarjetas, formularios y utilidades en vistas/JS.
2. Seleccionar componentes canónicos con mapa de equivalencias, sin cambiar tres convenciones de una vez.
3. Migrar shell y una página piloto autenticada; comparar escritorio y móvil.
4. Extender por módulos conservando la lógica, extraer reglas del bridge progresivamente.
5. Desactivar Tailwind CDN sólo tras verificar sustitución total de clases dependientes.
6. Limpiar CSS público legacy al cerrar su migración.

## Criterios de aceptación
Misma UI durante consolidación, contraste y foco, tablas/formularios/modales/responsive, gate aprobado, cambios incrementales y único MANIFEST.md.
La inspección actual es estática; no valida visualmente pantallas que exigen sesión.
