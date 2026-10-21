# Frontend Release Plan (Publico -> Admin)

## Objetivo
Cerrar el frontend con foco en produccion: consistencia visual v2, UX estable y sin regresiones funcionales.

Referencia de auditoria base: `docs/archivo/frontend_v2/public_v2_audit.md`.

## Fase 1 - Publico (prioridad alta)
1. Unificar layout/base visual en vistas publicas clave:
   - home
   - catalogo/listado
   - producto
   - checkout
   - cuenta/pedidos
2. Migrar clases legacy recurrentes a componentes v2 reutilizables.
3. Validar responsive (mobile/tablet/desktop) y contraste/accesibilidad basica.
4. Smoke funcional:
   - navegar catalogo
   - ver producto
   - agregar al carrito
   - checkout completo

## Fase 2 - Admin (prioridad media)
1. Completar lotes visuales pendientes del plan admin v2:
   - Catalogo
   - Sistema
   - Modulos restantes (SEO/Marketing/Facturacion/Importaciones)
2. Mantener shell/spacing consistentes y eliminar estilos legacy remanentes.
3. Consolidar componentes reutilizables en `components/ui-admin.css`.

## Fase 3 - Ajustes menores (continuo)
1. Bugs de UX puntuales.
2. Micro-mejoras visuales sin cambiar contratos.
3. Refuerzo de textos y estados vacios.

## Criterio de cierre frontend
- UX consistente en publico y admin.
- Sin regressions de rutas/contratos.
- Gate en verde en cada entrega.
