# Plan de Ataque - Rediseno Admin V2

## Objetivo
Aplicar un rediseno consistente en todas las vistas admin usando el shell v2 (sidebar + area de contenido centrada), sin romper rutas ni comportamiento.

## Reglas
- No cambiar rutas HTTP ni contratos JSON.
- No cambiar logica de negocio en esta etapa (solo UI/layout/clases).
- Mantener `admin-surface` + `admin-wrap` como layout base obligatorio.
- Evitar CSS por vista salvo ajustes minimos.
- Si una regla se repite en 2+ vistas, subirla a `components`.

## Arquitectura CSS objetivo
1. `assets/css/v2/tokens.css`
- Variables de color, tipografia, spacing, radios, sombras.

2. `assets/css/v2/base.css`
- Base global (tipografia, reset suave, estructura minima).

3. `assets/css/v2/components/*.css`
- Componentes reutilizables:
  - cards
  - forms
  - tables
  - buttons
  - badges
  - kpis
  - alerts

4. `assets/css/v2/pages/*.css`
- Solo ajustes puntuales por modulo/pagina.
- El layout shell vive en `assets/css/v2/layouts/admin-shell.css`.

## Lotes de migracion (orden)
1. Lote A: Dashboard + Orders
- `/admin`
- `/admin/orders`
- `/admin/orders/show`

2. Lote B: Catalogo base
- `/admin/products`
- `/admin/brands`
- `/admin/categories`
- `/admin/proveedores`

3. Lote C: Atributos + Variantes
- `/admin/attributes`
- fragmentos/editores de variantes

4. Lote D: Sistema
- `/admin/audit`
- `/admin/users`
- `/admin/settings`

5. Lote E: Modulos restantes
- SEO
- Marketing
- Facturacion (subvistas)
- Importaciones

## Metodo por lote
1. Auditoria rapida
- Inventario de vistas y partials afectados.
- Identificar clases legacy a reemplazar.

2. Migracion UI
- Reemplazar estructura por componentes v2.
- Normalizar espaciados y tipografia.
- Mantener HTML semantico y accesibilidad basica.

3. Limpieza
- Eliminar clases legacy sin uso en las vistas del lote.
- No tocar vistas fuera del lote.

4. Validacion
- Revisar visual desktop/mobile.
- Ejecutar `scripts\\build_gate.bat`.

5. Cierre de lote
- Documentar cambios y riesgos.
- Actualizar checklist en este documento.

## Checklist de Done por vista
- Usa `admin-surface` + `admin-wrap`.
- No depende de `nav-admin` legacy.
- Inputs/botones/tablas usan componentes v2.
- Espaciado y ancho consistentes con shell.
- Sin estilos inline nuevos.
- Sin regresion funcional visible.

## Enforce (cuando cerremos 2 lotes)
- Gate: alerta por clases legacy admin en vistas ya migradas.
- Gate: alerta por CSS inline en admin.
- Gate: alerta por duplicacion obvia de reglas entre pages.

## Estado actual
- [x] Shell v2 global (sidebar, colapsado, layout base admin).
- [x] Grupos de navegacion + iconos + tooltip en sidebar.
- [x] Lote A completo (baseline visual: Dashboard + Orders index/show)
- [x] Dashboard operativo: bloque de analiticas separado a `/admin/analytics`.
- [ ] Lote B completo
- [ ] Lote C completo
- [ ] Lote D completo
- [ ] Lote E completo

## Proximo paso inmediato
Ejecutar Lote B (Catalogo base) y reutilizar el baseline visual del Lote A.
