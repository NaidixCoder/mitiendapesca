# Auditoria Fase 1 - Frontend Publico (migracion total a v2)

Fecha: 2026-02-17
Objetivo: inventariar estado actual de clases/CSS en frontend publico y definir backlog de migracion completa a v2.

## 1) Hallazgos criticos

1. El frontend publico hoy depende de stack legacy global en `app/Views/partials/head.php`:
   - Tailwind CDN (`https://cdn.tailwindcss.com`)
   - `css/app.css`
   - `css/brand.css`
   - `css/aside.css`
   - `css/product.css`

2. `css/v2/index.css` hoy se carga solo en admin (`$pageId` empieza con `admin-` o `admin`).
   - Resultado: publico no usa sistema v2 como base real.

3. Hay alta dispersion de clases/estilos:
   - Clases unicas detectadas (vistas publicas + partials publicos): **625**
   - Utility-like (tailwind/atomicas): **390**
   - Legacy/domain-specific (`tp-`, `acc-`, `catalog-`, `btn*`, etc.): **118**

4. Duplicacion de primitivas UI (riesgo de inconsistencia):
   - `btn`/`btn-primary`/`btn-secondary` aparecen en varios CSS con definiciones distintas.
   - `card`, `input`, `pill` tambien se repiten entre archivos.

## 2) Superficie de migracion (impacto)

### Pages (class attrs)
- `app/Views/pages/cuenta/index.php`: 153
- `app/Views/pages/cuenta/lealtad.php`: 126
- `app/Views/pages/legal/terminos.php`: 75
- `app/Views/pages/carrito/index.php`: 70

### Partials (class attrs)
- `app/Views/partials/checkout/step4.php`: 130
- `app/Views/partials/checkout/shipping_options.php`: 32 (337 lineas)
- `app/Views/partials/catalogo/*`: 143 acumulado
- `app/Views/partials/home/*`: 116 acumulado
- `app/Views/partials/cuenta/*`: 185 acumulado
- `app/Views/partials/header/*`: 55 acumulado

Conclusión: los frentes mas costosos son **Cuenta**, **Checkout**, **Catalogo**.

## 3) CSS legacy activo en publico

Archivos con mayor peso actual:
- `public_html/assets/css/brand.css` (320 lineas)
- `public_html/assets/css/product.css` (164 lineas)
- `public_html/assets/css/app.css` (116 lineas)
- `public_html/assets/css/components/account.css` (287 lineas)
- `public_html/assets/css/components/catalog-card.css` (365 lineas)
- `public_html/assets/css/components/offcanvas.css` (77 lineas)

Problema: son la base real del publico; v2 aun no entra en cascada publica.

## 4) Riesgos si migramos sin orden

1. Regresiones visuales masivas por cascada (tailwind CDN + legacy + v2).
2. Inconsistencias de botones/inputs/cards entre modulos.
3. Aumento de deuda tecnica si se mezclan clases nuevas con utilidades legacy en la misma vista.

## 5) Estrategia recomendada (sin implementar aun)

## Paso A - Habilitacion v2 publica controlada
- Crear gate de carga en `head.php` para publico v2 por bandera de rollout (por ruta o flag global).
- Definir `v2/layouts/public-shell.css` y `v2/components/ui-public.css`.
- Mantener legacy temporalmente, pero con prioridad clara de imports.

## Paso B - Migracion por verticales
1. Home + header/footer
2. Catalogo (listado/filtros/cards/quick-add)
3. Producto
4. Carrito + checkout
5. Cuenta
6. Legal/Ayuda

Regla: al cerrar cada vertical, remover clases legacy del bloque migrado.

## Paso C - Retiro legacy
- Quitar Tailwind CDN del publico.
- Quitar `app.css/brand.css/product.css/components/*` cuando queden sin uso.
- Mantener `v2/index.css` como entrypoint unico.

## 6) Checklist de "migrado completo a v2" (publico)

- [ ] Publico carga `v2/index.css` como base principal.
- [ ] No depende de Tailwind CDN en runtime.
- [ ] Botones unificados en una sola definicion v2 (`btn`, `btn-primary`, `btn-secondary`).
- [ ] Inputs/cards/tables/badges unificados en componentes v2.
- [ ] Clases legacy `tp-*`, `acc-*`, `catalog-*` reemplazadas o encapsuladas en v2 con convención final.
- [ ] Cada modulo publico tiene CSS en `v2/pages/public/*`.
- [ ] `scripts\\build_gate.bat` en verde tras cada bloque.

## 7) Recomendacion inmediata para iniciar Fase 1

1. Definir el contrato de clases v2 publico (ui-public + shell-public).
2. Migrar primero `header/footer/home` para establecer baseline visual.
3. Luego catalogo y producto (impacto comercial directo).
4. Dejar checkout/cuenta para ultimo bloque por criticidad funcional.
