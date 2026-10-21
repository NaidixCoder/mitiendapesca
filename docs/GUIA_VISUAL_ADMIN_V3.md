# MiTiendaPesca — Estándar de diseño del Admin V3

> **Norma operativa v2.0 · 2026-10-21 · Obligatoria para TODOS los chats que trabajan sobre `/admin/*`.**
> Aplica también a rutas, subrutas, estados, modales, listados, formularios y componentes dentro de cada módulo.
> No autoriza cambios en archivos ajenos. La implementación debe contrastarse siempre con el código real.

## 0. Autoridad documental y propósito

- **Objetivo aprobado:** administrador moderno, premium, sobrio y productivo para operar un e-commerce durante horas. Prioridades: **consistencia visual + funcionalidad + responsive + mantenibilidad**. No buscar espectacularidad ni fondos decorativos.
- **Decisión de negocio/alcance:** la última instrucción expresa del propietario prevalece. Este documento es la **guía operativa consolidada de CSS/UX**; `CONTRATO_VISUAL_ADMIN_2026.md` preserva contrato formal e historial de decisiones; `docs/ARQUITECTURA_ADMIN_V3.md` define límites de arquitectura; `PLAN_CSS_UNIFICADO.md` registra estrategia y migración. Los archivos CSS realmente cargados definen el estado implementado.
- Si un texto histórico contradice una decisión posterior, prevalece la decisión posterior y debe documentarse la discrepancia. No asumir que un plan de 17/10 es el estado actual de 21/10.
- **No** editar sidebar, shell, CSS compartido, tokens, rutas, scripts globales o `/public` desde módulos especializados sin reserva/autorización del chat principal.

## 1. Referencias aprobadas: qué tomamos de cada una

| Prioridad | Referencia | Qué se adopta conceptualmente | Qué NO se adopta |
|---|---|---|---|
| Principal — visual | [Tabler](https://tabler.io/) · [componentes](https://docs.tabler.io/ui/components) · [GitHub](https://github.com/tabler/tabler) | KPIs compactos, tarjetas, formularios, filtros, tablas, badges, estados y comportamiento responsive consistente | No instalar Tabler/Bootstrap ni copiar su identidad |
| Principal — flujos | [falcolee/e-commerce-dashboard](https://github.com/falcolee/e-commerce-dashboard) | Organización de flujos de productos, categorías, clientes, pedidos y marketing | No integrar React, Ant Design, Tailwind ni sus componentes |
| Complementaria — UX | [Shopify Polaris](https://polaris.shopify.com/) | Jerarquía de información, acciones primarias/secundarias, legibilidad y productividad | No replicar interfaces ni incorporar su stack |
| Complementaria — comercio | Tiendanube / Nimbus | Flujos operativos comerciales, patrones cercanos al comercio latinoamericano | No copiar diseño o branding de Tiendanube |
| Complementaria — operaciones | [Medusa](https://medusajs.com/) | Listados, filtros, estados y acciones del flujo operativo | No usar su backend o framework |
| Complementaria — proceso | [Saleor](https://saleor.io/) y [Macaw UI](https://github.com/saleor/macaw-ui) | Diferenciar pedido, pago, preparación y envío; formularios de variantes y estados | No importar librería o implementación |

**Investigadas, no elegidas como base:** Vuexy (ThemeForest), Vuesy PHP, Panelux, ejemplos de Behance y Envato. Pueden servir como referencias adicionales puntuales, **no** cambiar automáticamente la identidad ni crear un sistema paralelo.

**Síntesis del diseño aprobado:** claridad de Shopify + consistencia de Tabler + orientación comercial de Tiendanube + identidad propia de MiTiendaPesca. Inspiraciones no equivalen a código, dependencias ni aprobación de cada patrón.

## 2. Identidad visual y tokens reales

| Uso | Valor oficial | Variable existente (en `public_html/assets/css/admin-v3/tokens.css`) |
|---|---|---|
| Primario — acción y foco | `#006B6B` | `--av3-color-primary` |
| Texto principal | `#182B39` | `--av3-color-text` |
| Texto secundario | `#647786` | `--av3-color-muted` |
| Fondo de aplicación | `#F6F8FA` | `--av3-color-background` |
| Superficies | `#FFFFFF` | `--av3-color-surface` |
| Bordes | `#DFE5E9` | `--av3-color-border` |
| Radio de tarjeta | `11px` | `--av3-radius-card` |
| Espaciado base de panel | `18px` | `--av3-space-panel` |
| Alto base de controles | `40px` | `--av3-control-height` |

- Los tokens se definen bajo `body[data-page^="admin"]`; el Design System existente incorpora una capa `--av3-ui-*` en `components/design-system.css`. **Ambos existen**: no inventar una tercera paleta ni replicar sus valores por página. Usar el token que ya consume el componente.
- El petróleo identifica acciones importantes: **no** pintar todos los elementos de verde. Superficies claras, bordes sutiles, sombra mínima, espacios constantes.
- Colores de éxito, aviso, error e información: semánticos, moderados, con texto explicativo. Nunca indicar estado exclusivamente por color.
- No introducir gradientes decorativos, tarjetas enormes, saturación de badges ni valores hex arbitrarios que dupliquen un token existente.

## 3. Tipografía, escala y densidad

- Fuente principal: **Segoe UI / Inter / system-ui**, según configuración activa del shell; no cargar fuentes nuevas por cada módulo.
- Texto operativo: **14 px** como referencia; etiquetas **13–14 px**; ayudas y metadatos **12–13 px** si son legibles. Evitar nuevos textos funcionales de 11 px solo porque algunos componentes de transición los contienen.
- Título de sección: **16 px / 600**; componentes: alrededor de **14 px / 600**; KPI: **24–28 px / 600**. No usar mayúsculas decorativas para encabezados de toda la interfaz.
- **Page Header efectivo actual:** encabezado **compacto de 20 px / 600** con breadcrumb debajo y acciones a la derecha (`components/page-header.css`). El diseño anterior de 24 px en el historial del contrato **fue superado**. Un módulo no crea su propio Page Header ni otro título redundante.
- Paneles **16–20 px** de padding, radio **10–12 px**, separaciones consistentes; controles **36–40 px** (variantes compactas solo donde mejoran la operación). Tablas de filas compactas y valores numéricos alineados.
- Preferir densidad equilibrada: datos útiles a primera vista, sin huecos artificiales, KPI gigantes, paneles vacíos o bloques con alturas igualadas que dejan espacios muertos.

## 4. Arquitectura de estilos y separación de responsabilidades

| Responsabilidad | Ubicación / patrón | Regla |
|---|---|---|
| Layout maestro y aside | `app/Views/layouts/admin.php`, `components/master-shell.css`, `layout.css` | El shell gestiona gutters, sidebar, drawer móvil y Page Header; módulos **no** aplican offsets al aside |
| Tokens Admin V3 | `public_html/assets/css/admin-v3/tokens.css` | Fuente de variables de color/medidas Admin; modificar solo de forma coordinada |
| Componentes transversales | `public_html/assets/css/admin-v3/components/` | Reutilizar existentes; agregar/cambiar únicamente con coordinación y pruebas de consumidores |
| Composición del módulo | `public_html/assets/css/admin-v3/pages/<modulo>.css` | CSS acotado a un namespace: paneles, disposición y casos genuinamente propios |
| Estructura PHP y presentación | `app/Views/pages/admin/` y parciales | Las vistas ensamblan componentes y escapan salidas; no deben concentrar SQL ni lógica de negocio |
| Datos presentacionales | ViewModels/servicios | Preparan valores, enlaces y **estados semánticos**; no transportan nombres de clases CSS |
| Interacción | JS del módulo con hooks `data-*` | Mantiene IDs, `name`, CSRF y contratos funcionales; no depende de nombres de clases de apariencia |

- Prefijo de nuevas clases visuales: **`av3-`**, con namespace del módulo (`av3-brands__*`, `av3-categories__*`); reutilizar `av3-ui-*` para componentes compartidos. Sin selectores globales nuevos que alcancen `/public`.
- **Una definición por componente, un sistema visual:** nada de CSS `-v2`, `-v3` superpuesto, nuevos bridges, duplicación de hojas, Tailwind, Bootstrap ni utilidades de presentación dispersas. Los archivos de transición existentes no legitiman crear otros.
- **No apilar overrides al final.** Si cambia una estructura, corregir la regla original, consolidar y eliminar solo código obsoleto cuyo uso se haya comprobado. No eliminar dependencias legacy globales sin auditar consumidores.
- Cargar el CSS **una única vez** y verificar que se aplica en todas las subrutas. Algunos módulos lo inyectan por vista y otros por `extraHead`; no alterar `head.php` (archivo compartido) sin coordinación.

## 5. Inventario real de componentes reutilizables

Estos selectores **existen actualmente** en `public_html/assets/css/admin-v3/components/design-system.css`. Antes de crear controles visuales de un módulo, inspeccionar su implementación y estados:

| Uso | Clases `av3-ui-*` existentes |
|---|---|
| Botones | `av3-ui-btn`, `av3-ui-btn--primary`, `av3-ui-btn--danger`, `av3-ui-btn--small` |
| Contenedores de acciones | `av3-ui-actions` |
| Campos | `av3-ui-field`, `av3-ui-field-label`, `av3-ui-field-hint`, `av3-ui-control` |
| Tarjetas | `av3-ui-card`, `av3-ui-card-header`, `av3-ui-card-title`, `av3-ui-card-body` |
| Tablas | `av3-ui-table`, `av3-ui-table-scroll` |
| Estados | `av3-ui-badge`, `av3-ui-badge--neutral`, `av3-ui-badge--warning`, `av3-ui-badge--danger` |
| Filtros y vacíos | `av3-ui-filters`, `av3-ui-empty` |
| Utilitarios propios del DS | `av3-ui-divider`, `av3-ui-page` |

- Componentes existentes tienen **imperfecciones heredadas** (por ejemplo `font-size: 11px` en ayudas y cabeceras; `--av3-ui-border: #dfe6e9` frente al token base `--av3-color-border: #DFE5E9`). Se deben reutilizar sin normalizar esas deficiencias como estándar para nuevos módulos; proponer la armonización global al chat principal con prueba de regresión.
- Evitar recrear `av3-categories__button`, `av3-brands__field` u otros patrones visualmente equivalentes a componentes `av3-ui-*` salvo diferencia funcional demostrable. Las clases de composición locales siguen siendo válidas.
- Page Header único: `app/Views/partials/admin/page_header.php`. Usar sus props/contratos existentes; no dibujar una copia dentro de cada pantalla.

## 6. Mobile-first efectivo (no simplemente responsive)

1. La hoja base se diseña para el **ancho más estrecho**; formularios en una columna, anchuras fluidas y `min-width:0`. Mejorar composición desde ese punto, no reparar después un layout desktop con decenas de `max-width`.
2. Elegir `@container (min-width:...)` para elementos sensibles al espacio **disponible dentro del panel**, que cambia con el aside; `@media (min-width:...)` si depende del **viewport/shell**. Los `max-width` pueden ser necesarios para excepciones justificadas, pero no como estrategia única.
3. Listados: en desktop tablas legibles; en móvil filas/tarjetas de acciones claras, priorizando identificador, estado y operación. Scroll horizontal **solo si es imprescindible** y contenido no puede rediseñarse sin perder significado.
4. Filtros: una columna o controles fluidos en móvil, ampliación progresiva a varias columnas; botones alineados y accesibles. Sin cortes, campos aplastados ni toolbars que desborden.
5. Formularios: etiquetas visibles, grupos lógicos, mensajes concretos, estados hover/focus/invalid/disabled, uploads y previews adaptables; acción principal clara y cancelar secundaria.
6. Modales: cabecera, contenido desplazable, pie si aplica, foco y cierre por teclado, Escape y backdrop según contrato; altura acotada al viewport y sin diálogos más anchos que el área útil.
7. Sidebar móvil (hasta **767 px**) es drawer del shell. Los módulos no duplican menú ni compensaciones. En desktop verificar sidebar abierto y reducido cuando exista.
8. Validación mínima **363, 390, 768, 1024 y 1600 px**, sin scroll lateral accidental, truncamiento significativo, doble paginación ni huecos visuales enormes. Probar también textos largos, datos vacíos, errores y múltiples acciones.
9. Aceptación visual exige **navegador real/autenticado** cuando sea posible; si solo hay HTML sintético, indicarlo. Una captura no equivale a aprobar la interacción.

## 7. Jerarquía UX y patrones operativos

- Orden general: Page Header y acción principal → navegación secundaria cuando corresponde → filtros/búsqueda → contenido operativo → acciones por registro. **Solo una acción primaria dominante por contexto**.
- KPIs solo si ayudan a decidir; tamaños contenidos y agrupación que no fuerce tarjetas de altura excesiva. No añadir métricas decorativas para llenar espacio.
- Tablas: columnas esenciales, estados con etiquetas, números alineados, acciones claras; paginación y vacíos explícitos. Evitar usar un color genérico para significados diferentes.
- Estados coherentes en toda la app: **éxito, advertencia, error, información, vacío, cargando y deshabilitado**. Se expresa semántica en la capa de datos, presentación en vista/estilos e interacción en JS.
- Estados operativos de comercio **no deben confluir**: pedido, pago y entrega/preparación son dimensiones diferentes cuando el dominio lo requiere (patrón conceptual de Saleor/Medusa, sujeto al backend real).
- Las vistas ensamblan componentes; los ViewModels preparan datos y estados semánticos; CSS define apariencia y JS gestiona interacciones. No transportar clases Tailwind/legacy desde servicios o consultas.

## 8. Seguridad, pruebas y condición de entrega

- Antes de editar: inventariar rutas, subrutas, views, CSS, consultas, JS y hooks. Registrar archivos asignados y verificar bloqueos en `docs/COORDINACION.md`.
- Cambios de presentación **no** autorizan cambios en negocio, pagos, inventario, DB, migraciones ni catálogo público sin aprobación. Si la auditoría detecta fallas funcionales, documentar impacto y coordinar antes de alterar contratos compartidos.
- Revisar sintaxis PHP/JS, CSS y build gate, contratos específicos, rutas, `name`/CSRF/`data-*`, accesibilidad básica, rendimiento y ausencia de colisiones entre vistas.
- Comparar **antes/después** en los cinco anchos, con capturas o evidencia precisa. Diferenciar prueba de código, render HTML sintético, smoke de DB de solo lectura/rollback y flujo HTTP autenticado real.
- **Limpieza obligatoria de pruebas de navegador** según `docs/pruebas/PROTOCOLO_NAVEGADORES.md`: headless por defecto y cierre en `finally`/teardown de páginas, contextos, navegador y procesos propios, también ante errores. No generar ventanas visibles persistentes en la PC compartida.
- Sin pruebas destructivas ni envíos reales para validar UI. Sin commits/push ni cambios de rama desde chats especializados.
- Registrar en `docs/modulos/<modulo>.md`: hallazgos, problemas corregidos, pruebas ejecutadas, problemas pendientes, evidencias y archivos modificados. Solo chat principal modifica documentación transversal y publica en Git.
- No declarar módulo terminado por pasar contrato estático; falta inspección visual real y segunda pasada, o estado explícito **pendiente de validación**.

## 9. Lista rápida de control antes de entregar

- [ ] Referencias utilizadas como inspiración, sin cambiar la identidad ni instalar librerías ajenas.
- [ ] Tokens oficiales y fuente del shell; sin paleta paralela ni nuevos hardcodes redundantes.
- [ ] `av3-` y `data-*` separados; CSS de módulo limitado a composición; componentes existentes reutilizados.
- [ ] Page Header/sidebar del shell, sin títulos o padding redundantes.
- [ ] Mobile-first real, container queries cuando corresponda; 363/390/768/1024/1600 px revisados.
- [ ] Operatividad: tabla/filas móvil, filtros, vacíos, carga, fallas, formularios, modales, acciones y estados.
- [ ] Sin Tailwind nuevo, CSS legacy nuevo, duplicaciones, overrides en cascada ni `!important` no justificado.
- [ ] Funcionalidad, seguridad y pruebas documentadas; sin afirmar aprobaciones de flujos no ejecutados.
- [ ] Informe exclusivo `docs/modulos/<modulo>.md` completo; cambios de shell/global coordinados.

**Regla final:** no buscamos pantallas de equipos distintos. Buscamos **un solo administrador**: claridad de Shopify, consistencia de Tabler, operativa de Tiendanube y nuestra identidad propia.
