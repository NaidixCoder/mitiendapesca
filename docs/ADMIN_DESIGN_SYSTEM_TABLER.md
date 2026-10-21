# Referencias del Design System — Admin V3

> Registro de investigación consolidado el 2026-10-21. **La norma de implementación es [`GUIA_VISUAL_ADMIN_V3.md`](GUIA_VISUAL_ADMIN_V3.md)** junto con el contrato visual y la arquitectura. Este archivo registra las fuentes e inspiración, no tareas pendientes actuales.

## Referencias aprobadas

- **Tabler** — referencia visual principal por componentes, tarjetas, KPI, formularios, filtros, tablas y responsive. [Web](https://tabler.io/) · [Componentes](https://docs.tabler.io/ui/components) · [Repositorio](https://github.com/tabler/tabler).
- **falcolee/e-commerce-dashboard** — referencia principal de flujos comerciales (productos, categorías, pedidos, clientes, marketing): [GitHub](https://github.com/falcolee/e-commerce-dashboard).
- **Shopify Polaris** — jerarquía, acciones y claridad de tareas: [Sitio](https://polaris.shopify.com/).
- **Tiendanube / Nimbus** — funcionamiento comercial y experiencia de gestión latinoamericana.
- **Medusa Admin** — estados, operaciones, tablas y filtros: [Medusa](https://medusajs.com/).
- **Saleor Dashboard / Macaw UI** — separación de estados de pedido/pago/entrega, componentes y variantes: [Saleor](https://saleor.io/) · [Macaw UI](https://github.com/saleor/macaw-ui).

## Referencias evaluadas, no adoptadas

Vuexy (ThemeForest), Vuesy PHP, Panelux, ejemplos de Behance y Envato. No son base visual aprobada ni dependencias del proyecto.

## Implementación propia, no plantilla copiada

- CSS de Design System existente: `public_html/assets/css/admin-v3/components/design-system.css` con clases opt-in `av3-ui-*` (botones, controles, tarjetas, tablas, filtros, badges y vacíos).
- Arquitectura modular y CSS propio; **no incorporar Bootstrap, React, Tailwind, componentes de terceros ni alterar `/public`** al tomar referencias.
- Paleta y tokens oficiales en `public_html/assets/css/admin-v3/tokens.css`, contractualmente descritos en [`GUIA_VISUAL_ADMIN_V3.md`](GUIA_VISUAL_ADMIN_V3.md).
- Las pantallas fueron evolucionando después de la primera integración en Analytics. No usar las antiguas listas de «pendiente adaptar» como estado actual: revisar `docs/ESTADO_DESARROLLO.md`, informes de módulo y el código.

**Síntesis aprobada:** claridad de Shopify, consistencia visual de Tabler y orientación comercial de Tiendanube, con identidad propia de MiTiendaPesca.
