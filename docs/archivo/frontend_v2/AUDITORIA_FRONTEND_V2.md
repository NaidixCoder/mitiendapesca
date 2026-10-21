# Auditoría de frontend v2 — MiTiendaPesca
Fecha: 2026-10-17
Ruta inspeccionada: C:\xampp\htdocs\mi-pesca

## Hallazgos verificados
- 69 vistas PHP bajo app/Views/pages/admin y 48 archivos CSS v2 en total.
- El head.php diferencia admin y público: admin carga tokens, base, ui, buttons, ui-admin, admin-beta-bridge, utilities, admin-shell y CSS específicos.
- Las hojas v2 específicas admin cargadas son dashboard, dashboard-trend, orders y tres de facturación (shared, nav, control). El resto depende sobre todo de utilidades, puente visual y estilos inline/clases Tailwind.
- Se carga https://cdn.tailwindcss.com globalmente (también en admin), no hay migración autónoma todavía.
- El público sigue habilitando explícitamente CSS legacy con $enableLegacyPublicCss=true, junto con v2.
- Los tokens v2 están centralizados en public_html/assets/css/v2/tokens.css; existe CSS de componentes reutilizables ui-admin.css y admin-beta-bridge.css.
- Las solicitudes HTTP anónimas a /admin devuelven 401. Esta auditoría es estática; no implica aprobación visual o responsive de páginas autenticadas.

## Riesgos
1. Dependencia real de clases Tailwind CDN: retirarlo prematuramente rompería layouts.
2. Puente v2 muy amplio que cubre markup antiguo: estilos específicos podrían tener efectos colaterales.
3. Duplicación/superposición entre utilidades v2, puente y clases CSS legadas presentes en markup.
4. Ausencia de validación visual autenticada, especialmente tablas grandes, formularios, modales y móvil.

## Orden recomendado de migración
1. Congelar contrato visual: tokens, botones, inputs, selects, tablas, modales, mensajes y espaciado. Conservar rutas/controladores/repositorios.
2. Shell admin: navegación lateral, encabezado, contenedores y responsive. Registrar capturas antes/después.
3. Pantallas de mayor uso: dashboard, productos, edición de productos/variantes, pedidos.
4. Operación: stock, categorías, marcas, atributos, clientes y proveedores (según rutas existentes).
5. Facturación, marketing, importaciones, auditoría y configuración.
6. Retirar gradualmente puente admin-beta-bridge, estilos y clases sin uso sólo después de revisar referencias.
7. Sustituir dependencia Tailwind CDN cuando los componentes y layouts ya estén cubiertos por CSS v2 compilado o nativo; no eliminar de forma global ahora.
8. Después, cerrar CSS legacy del sitio público por página y verificar responsive.

## Criterio de aceptación por pantalla
- Layout escritorio/tablet/móvil y estados vacíos/cargando/error.
- Formularios, tablas, filtros, mensajes y diálogos legibles y operativos.
- Accesibilidad de teclado/foco, contraste y controles.
- Sin warnings PHP ni errores JS nuevos; build gate aprobado.
- Registro en MANIFEST.md del conjunto mínimo de archivos alterados.

## Siguiente implementación sugerida
Empezar por shell administrativo + componentes transversales, no por cambiar todas las páginas juntas. Antes de hacer una migración extensa, comparar visualmente al menos una pantalla autenticada representativa.
