# Contrato visual — MiTiendaPesca Admin 2026 (v1.0)
Aprobado: 2026-10-17 · Densidad: **equilibrada**.

## Alcance
- Conservación absoluta del **aside existente** y de la identidad verde petróleo.
- Solo se rediseña el área de contenido de administración. **/public no se modifica**.
- Lógica, queries, cálculos, rutas, validaciones, controladores, DB y comportamiento de negocio se preservan.
- Prohibida la superposición incremental de CSS legacy; cada vista rediseñada debe usar un namespace claro de clases y eliminar reglas exclusivas obsoletas.
- Referencias conceptuales: Shopify Admin / Tiendanube Nimbus. Minimalismo funcional, sin copiar interfaces.

## Tokens visuales iniciales
| Token | Valor |
|---|---|
| primario | #006B6B |
| fondo | #F6F8FA |
| superficie | #FFFFFF |
| texto | #182B39 |
| texto secundario | #647786 |
| borde | #DFE5E9 |
| error | rojo semántico contenido |
| advertencia | ámbar semántico contenido |

Valores provisionales a armonizar con los tokens oficiales del sitio; no modificar los compartidos con /public.

## Tipografía / densidad
- Inter o Segoe UI fallback; título vista **24px / 600**, título sección **16px / 600**, título componente **14px / 600**.
- Texto operativo **14px / 400–500**, etiqueta **13–14px / 500**, auxiliar **12–13px / 400**.
- Valores KPI **24–28px / 600**. Sin mayúsculas decorativas ni énfasis indiscriminados.
- Padding página escritorio **24px**, entre secciones **24px**, tarjetas **16–20px**, campos **16px**.
- Botones/controles **36–40px** de alto; radios **8px controles y 10–12px paneles**; líneas 1px; sombras mínimas.

## Componentes
- Un único botón primario por grupo de acciones.
- Formularios con labels visibles, estados foco, error, deshabilitado.
- Tablas: filas 44–52px, encabezados discretos, alineación numérica consistente, scroll horizontal solo cuando sea necesario.
- Badges semánticos discretos y de color suave. La información de estado también se transmite con texto.
- Modales: encabezado, contenido y acciones coherentes; accesibles por teclado.
- Estados vacíos: título y mensaje breves; no mostrar gráficos enormes sin datos.
- Responsive pensado por componente, no mediante parches finales.
- Componentes PHP/HTML compartidos, estilos aislados y contratos claros.

## Dashboard: decisión funcional de alcance
- Encabezado compacto «Resumen general», sin botones que dupliquen el aside.
- Cuatro indicadores operativos: facturación hoy, pedidos pendientes, productos activos, stock bajo.
- Pedidos por gestionar con prioridad real y acceso a detalle; mostrar como máximo cinco en el resumen, sin doble scroll.
- Lista de alertas con prioridad y enlaces; no duplicar un panel «Pendientes hoy».
- Rendimiento: gráfico con datos; en cero o sin resultados, estado vacío breve y honesto.
- Stock bajo y actividad reciente, con legibilidad y sin nombres técnicos en la interfaz cuando exista una etiqueta traducible.
- Rankings solo cuando existan datos, sin dedicar una gran superficie a bloques vacíos.
- Datos secundarios de usuarios/inactivos permanecen en sus módulos, no en el resumen principal.

## Criterio de aceptación
1. Inspección de contrato de datos / DOM hooks.
2. Implementación completa HTML/CSS semántica y reusable.
3. Build gate y pruebas de funcionalidad relevantes.
4. Revisión visual real escritorio y ancho reducido, corrección y segunda revisión.
5. No declarar calidad visual aprobada si no se verificó el navegador.

## Tipos de contenedor (2026-10-19)
- Operativo, clase av3-layout--operational: ancho fluido disponible con gutter del shell, para listados y analíticas.
- Detalle, clase av3-layout--detail: ancho máximo 1280px.
- Formulario, clase av3-layout--form: ancho máximo 860px centrado.
- Modos opt-in; aside y padding de 24px exclusivamente propiedad del layout global. Sin alterar páginas existentes.

## Layout maestro del administrador (2026-10-20)
- Creado app/Views/layouts/admin.php: un único aside, encabezado blanco y área de contenido.
- render_page() en app/Support/helpers.php captura la salida de vistas /admin/* y la coloca dentro del layout, conservando el contenido y todas las variables actuales.
- El parcial page_header.php registra el encabezado durante el renderizado maestro en lugar de imprimirlo dentro del módulo; subnav.php evita duplicar el aside mientras se captura.
- CSS nuevo en public_html/assets/css/admin-v3/components/master-shell.css, cargado último en admin para mantener el shell como propietario del layout. Gutter global y tres modos de ancho preservados.
- Contrato scripts/build/admin_master_layout_contract.php integrado en build_gate.
- Sin ZIP. Validación visual real del navegador pendiente: comprobar Dashboard, editor de Productos, Control fiscal, Pedidos y Auditoría.

## Page Header opción B (2026-10-20)
- Establecido diseño profesional equilibrado para todo /admin: breadcrumb con separador chevron, título 24 px peso 600, descripción opcional 13 px y acciones funcionales a la derecha.
- Solo modificados el componente compartido page_header.php, su CSS, padding del master-shell y texto del encabezado de Dashboard.
- Dashboard: subtítulo de actividad y rendimiento; retirado texto estático de 'Estado de la tienda' por carecer de señal real.
- Aside, contenido y backend inalterados. Sin ZIP. Validación visual pendiente.

## Page Header compacto horizontal (2026-10-20)
- Implementado sobre el componente único `app/Views/partials/admin/page_header.php`: título de 20 px, breadcrumb debajo y acciones a la derecha en una misma barra; se elimina la descripción de la barra, sin borrar los datos de las vistas.
- CSS aislado en `admin-v3/components/page-header.css`. Padding vertical maestro reducido a 12 px arriba y abajo (`master-shell.css`), altura nominal cercana a 60–64 px; responsive conserva acciones y breadcrumbs.
- El aside, el backend y las 67 vistas con encabezado común no cambian. Sin ZIP. Requiere validación visual de navegador.

## Dashboard mobile-first — 2026-10-20
- Rediseñada la presentación de `/admin` bajo contrato visual, sin cambios de backend: cuatro KPIs independientes, pedidos y stock responsive, alertas, rendimiento y actividad reciente.
- Eliminada del Dashboard la franja de métricas secundarias de usuarios/inactivos y la segunda franja duplicada de pendientes. Rankings solo si hay datos; conservados IDs y hooks del gráfico.
- Reemplazado por completo `public_html/assets/css/admin-v3/pages/dashboard.css`: no se añadió CSS encima del legado. Nuevo CSS único del módulo, responsive desde móvil con cambios a tablas a partir de 700px y paneles de dos columnas a partir de 1050px.
- Añadido `scripts/build/dashboard_responsive_contract.php` al build gate. Respaldo local fuera del proyecto en `C:\xampp\mi-pesca-backups`.
- La limpieza de CSS legacy es específica del Dashboard; no se eliminaron componentes de otros módulos. Pendiente validación visual real a 390/768/1024/1600px con aside expandido/contraído.
- No se generó ZIP incremental.

## Navegación móvil overlay del administrador — 2026-10-20
- En <=767px se elimina el estado visual de aside comprimido: el contenido ocupa todo el viewport (offset 0).
- Botón hamburguesa en el Page Header compartido; abre el aside como drawer fijo por encima del layout con backdrop; no desplaza tarjetas, tablas ni header.
- Cierre mediante backdrop, Escape y navegación; botón con aria-controls/aria-expanded, foco inicial y ciclo de Tab en el drawer. El control de escritorio se conserva a partir del breakpoint.
- Cambios: layouts/admin.php, sidebar_v2.php, admin-v3/components/master-shell.css, js/admin/core/sidebar.js. Contrato scripts/build/admin_mobile_drawer_contract.php añadido al gate.
- Ningún ZIP incremental. Validación visual manual a 363px y escritorio pendiente.

## Dashboard definitivo — implementación 2026-10-20

- Alcance: únicamente `/admin` (presentación). No se modificaron controlador, SQL, queries, rutas, estados de negocio ni JS de tendencia.
- Presentación mobile-first y `container-type:inline-size` en `.av3-dashboard`: composición basada en espacio real disponible, no en el ancho del navegador.
- Jerarquía final: cuatro KPIs; pedidos y avisos (2/3 + 1/3 con espacio útil >=1050 px); stock y actividad (mitades según espacio); rendimiento comercial; rankings condicionales al final.
- Móvil: KPIs 2x2, avisos antes que pedidos, pedidos y stock como filas compactas enlazadas y accesibles, sin tabla horizontal. A partir de 680 px útiles se activan KPIs 4 columnas y tablas. A partir de 980 px se pueden mostrar stock/actividad lado a lado; a partir de 1050 px también pedidos/alertas 2/3 + 1/3.
- Paleta derivada de tokens del admin: primario #006B6B, fondo #F6F8FA, superficies #FFFFFF, texto #182B39, secundario #647786, borde #DFE5E9; semántica limitada para stock crítico, advertencias y fallas.
- Tipografía 14 px operativa, 13 px etiquetas, 12 px auxiliares y 28 px KPI. Paneles de 11 px de radio, 18 px padding en escritorio, filas con división sutil, alineación tabular numérica.
- Se eliminaron del markup y CSS la franja de pendientes repetidos y cálculos no usados de métricas extra. Se conservan todos los hooks DOM y los controles del gráfico.
- Rendimiento: se mantiene el JS existente, que oculta SVG sin actividad; el CSS compacta el estado vacío y suprime el título interno redundante sin esconder filtros ni controles.
- `public_html/assets/css/admin-v3/pages/dashboard.css` fue sustituido como hoja única del módulo, no se sumaron override CSS legacy nuevos. Se corrigió el padding heredado de `.av3-card`.
- Prueba permanente `scripts/build/dashboard_responsive_contract.php` actualizada con ausencia de bloques duplicados, orden de elementos y adaptación con container queries.
- Respaldo privado de archivos anteriores bajo `C:\xampp\mi-pesca-backups\dashboard_final_design_20261020_020843`. No se creó ZIP incremental de entrega.
- **Pendiente:** validación visual real en Firefox autenticado (363/768/1024/1600 px) y retoques focales si aparecen colisiones externas. La aprobación técnica no sustituye la revisión visual.

## Dashboard: columnas independientes y filas móviles (2026-10-20)
- Vista `app/Views/pages/admin/dashboard/index.php`: cuatro KPIs; columnas independientes reales: principal [Pedidos por gestionar, Stock bajo] y secundaria [Requiere atención, Actividad reciente]. Rendimiento comercial y rankings condicionales permanecen abajo.
- CSS `public_html/assets/css/admin-v3/pages/dashboard.css`: eliminado el grid de filas acopladas (`.av3-work`/`.av3-bottom`). En escritorio ancho (viewport >=1200px) dos columnas 7/5 con tarjetas apiladas independientemente; no se fijan alturas ni se dejan huecos intermedios.
- KPI: cuatro columnas desde 900px de viewport como regla robusta; dos columnas en móvil. La regla anterior basada solo en contenedor no se activaba de manera fiable en el navegador del usuario.
- Móvil: el orden es KPIs -> Atención -> Pedidos -> Stock -> Actividad -> Rendimiento. Las columnas usan `display:contents` y `order` sin duplicar datos ni elementos.
- Filas móviles de pedidos rehechas en tres niveles: [pedido + total], [cliente + fecha], [estados]; enlace único a detalle conservado.
- Tablas: `.av3-orders` y `.av3-stock` tienen `container-type:inline-size`; tabla si el panel supera 620px, filas compactas si es más angosto; fallbacks por viewport en escritorio. Sin `!important` añadidos.
- Actualizado `scripts/build/dashboard_responsive_contract.php` para evitar regresión al layout anterior. CSS analizado mediante tinycss2 sin errores de sintaxis.
- Se preservan JS, rutas, valores, Page Header, aside y componentes administrativos ajenos al Dashboard. Respaldo local bajo `C:\xampp\mi-pesca-backups\dashboard_columns_20261020_023120`; no se creó ZIP incremental.
- Validación visual pendiente: capturas en Firefox con 363px y 1650px tras recarga forzada.

## /admin/orders — listado operativo rediseñado (2026-10-20)
- UI terminada: tabs de trabajo `Todos`, `Por cobrar`, `Para preparar`, `En camino`, `Finalizados` con recuentos del backend; barra de búsqueda + orden; filtros avanzados desplegables para estado, método, fechas y tamaño de página; filtros activos removibles; estado vacío y paginación con anterior/siguiente.
- Columnas de tabla desktop: pedido y fecha/hora, cliente, estado del pedido, estado de pago con método debajo, etapa de entrega y total; cada registro permite navegar al detalle.
- Móvil: enlace accesible para cada pedido y tres filas de información (número/importe; cliente/fecha; estado/pago). Vista adaptada mediante container queries sobre el ancho útil. Anchos medios omiten columna Entrega antes de pasar a filas móviles.
- Backend: `AdminOrderService::listForAdmin` ahora cuenta resultados reales y pagina 25/50; admite vista, búsqueda, estado, método de pago, fechas, orden y página, con valores de entrada parametrizados o validados. El antiguo `LIMIT 200` fijo se eliminó.
- Datos: último estado de `payments` y de `order_shipments` se consultan independientemente del estado del pedido. Falta de registro se diferencia de pago confirmado. Query de envíos adaptada al esquema real (no hay `order_shipments.id`; se usa `updated_at`).
- ViewModel presenta etiquetas y tonos semánticos españoles, con estados de pago y entrega separados. Cambios de estado, cobros, ARCA, inventario, envíos y resto de la lógica transaccional siguen exclusivamente en el detalle sin cambios.
- Reescritos `app/Views/pages/admin/orders/index.php`, `admin-v3/pages/orders.css` (CSS previo sustituido, sin legacy nuevo), `js/admin/orders/index.js` (clic de fila más accesible, orden automático). Se eliminó el botón redundante "Volver al dashboard" del Page Header.
- Pruebas reales solo lectura: `scripts/dev/orders_readonly_smoke.php`, 14 pedidos en base local, vistas all/collect/prepare, búsqueda exacta y sin resultados; `scripts/dev/orders_render_smoke.php` verifica HTML completo y misma cantidad en móvil. Ninguna fila se modificó.
- Contrato permanente `scripts/build/orders_index_contract.php` integrado a `scripts/build/gate.php`; verifica filtros, paginación, datos independientes, ausencia de CSS viejo, JS y responsive.
- El resto del layout, aside, Page Header y demás vistas permanecen intactos. Respaldo privado de los archivos anteriores en `C:\xampp\mi-pesca-backups\admin_orders_redesign_20261020_025713`. Sin ZIP incremental.
- **Pendiente:** revisión visual en Firefox, con la sesión autenticada y anchos 363/768/1024/1600px. La validación de sintaxis, consulta y HTML no equivale a aprobación visual.

## Auditoría SEO y editores — contrato visual (2026-10-20)

- Jerarquía: resumen del catálogo activo, búsqueda compacta, filtros avanzados y resultados. Un solo botón primario por área.
- El score es índice interno de completitud; no representa ranking real de Google. Bajo 0–64, mejorable 65–84, bueno 85–100; indicadores globales sobre activos exclusivamente.
- Incidencias puntuadas separadas de puntos clave, especificaciones y FAQ (contenido complementario sin efecto en score). Vista previa Google estimada dentro de un diagnóstico expandible; sin tarjeta permanente en cada fila.
- Resultados como tabla a partir de 780px de ancho útil y filas específicas mobile por debajo. Sin scroll de tabla móvil. Botones, tipografía, estados, espaciados y bordes de identidad oficial.
- Editores reutilizan estilos SEO, muestran avanzados plegados y conservan los contratos existentes de formulario y JS; reordenamiento mediante arrastre, teclas de cursor o botones táctiles.
- Revisión visual de snapshot HTML con hojas CSS de XAMPP en Firefox headless 363/390 y Edge headless 768/1600. Falta validación de interacciones reales en Firefox autenticado.

## Ajuste específico de Auditoría SEO (2026-10-20)

- Prevalece este criterio sobre la mención anterior de vistas previas expandibles: **en escritorio la SERP estimada es una columna propia permanente**, sin desplegable por fila. En ancho reducido, el diagnóstico sigue siendo un desplegable de contenido mínimo.
- Tabla compacta: nombre/SKU, score, todas las incidencias puntuadas, snippet estimado enlazado, publicación y Editar SEO. No repetir las incidencias ni los contadores opcionales dentro de la vista previa.
- Bloque Buscar y filtrar compacto, sin grandes espacios verticales al expandir filtros. El botón Aplicar se alinea con la fila de controles cuando existe ancho suficiente. CSS único sin overrides redundantes.

## Marketing — criterios de implementación aprobados para primera etapa (2026-10-20)

- El header maestro es propietario de título, breadcrumbs y acciones; `Marketing` utiliza tabs inferiores Campañas / Email marketing.
- El listado de campañas es operativo: tabs por estado, búsqueda, período y cantidad por página; atribución de conversiones en franja discreta, sin tarjeta gigante.
- Escritorio: tabla con nombre/enlace, visitas, carrito, compras, ingresos, estado y acciones. Móvil/tablet: filas propias con información esencial; sin tabla comprimida ni QR duplicado.
- Estado sin campañas: título, explicación breve y creación de la primera campaña. No inventar indicadores ni ocultar errores reales como un estado vacío.
- Color primario `#006B6B`, superficies blancas, `#F6F8FA` fondo, texto `#182B39`; densidad equilibrada, sin botones saturados.

## Marketing — edición y detalle de campaña (2026-10-20)

- Edición: información básica y seguimiento agrupados; publicación/estado/link corto en panel lateral escritorio, inferior en móvil. Inputs 40px, títulos 16px, contenido 14px, auxiliares 12–13px, bordes suaves y CTA principal de guardado.
- Detalle: estado y slug compactos bajo Page Header; link/QR operativo, KPIs de rendimiento, evolución, top páginas/productos y visitas/compras recientes sin duplicar breadcrumbs ni encadenar tarjetas anidadas.
- Criterio responsive basado en espacio útil: campos de una columna en móvil, UTM en tres columnas cuando hay ancho, métricas 2/3/5 según breakpoint y diagnósticos 1/2/3 columnas. Los datos ausentes tienen mensajes explícitos, nunca métricas inventadas.

## Email Marketing — fase 1 (2026-10-20)

- Page Header maestro y doble navegación discreta: Marketing principal (Campañas/Email) y herramientas de Email (Resumen, Suscriptores, Automatizaciones, Envíos, Cola, Plantillas). Un único control activo por nivel.
- Mobile-first con tipografía 14px operativa, títulos de sección 15–16px, secundario 12px, controles de 40px, tablas solo con ancho útil suficiente y filas móviles sin scroll lateral.
- Resumen sin tarjetas anidadas ni acciones potencialmente peligrosas de procesamiento masivo al nivel principal. Automatizaciones por serie y pasos, estado semántico moderado y controles POST explícitos.
- Sin nuevos componentes de UI global ni CSS legacy superpuesto; las otras rutas Email todavía no están migradas y no deben considerarse aprobadas.

## Email Marketing — fase 2 (2026-10-20)

- Envíos masivos: tabla desktop con métricas diferenciadas (apertura aproximada) y filas móviles con estado, enviados, clics y compras.
- Cola: estadísticas compactas, formulario de filtros y logs separados; acceso al procesamiento real manual **no** como CTA principal. Advertencia expresa de que puede enviar mensajes.
- Plantillas: biblioteca compacta de filas/tablas, acciones Ver/Usar/Eliminar secundarias y confirmación obligatoria; detalle con metadatos y previsualización HTML aislada, sin perder navegación.
- Los estados visuales se preparan como tonos semánticos en ViewModels; ningún componente debe transportar clases de color legacy.

## Email Marketing — editor y detalle (2026-10-20)

- Primero la información del envío (nombre, asunto, texto previo, audiencia, CTA, estado y fecha). Los atributos VIP/inactividad son contextuales. Plantillas plegadas por defecto. Editor Visual/HTML y Herramientas identificables con controles táctiles y sin anchos rígidos.
- Acciones peligrosas: Programar ahora y Cancelar envío requieren confirmación explícita, independientes de Ver/Editar. Vista previa HTML ajena al DOM administrador y sin privilegios de script.
- Contenido de email con tamaños de editor independientes de su ancho y modal responsive; evitar tablas forzadas y CSS inline en vistas. Conservar accesibilidad, datos y acciones.

## Email Marketing — diálogos y estados (2026-10-20)

- Diálogos: fondo sobre la ventana completa, panel interior centrado con ancho máximo de 960px y 16px de margen en móvil, scroll interno solo cuando no entra el contenido; no desplazar el layout ni el aside.
- El rol de diálogo se aplica al panel interior, no a la capa de fondo. Pestañas y acciones mantienen color primario, bordes suaves y semántica visual moderada; controles de al menos 34–40px. Previsualización del HTML aislada.
- Las clases activas y ocultas son estados del componente (`is-*`), no utilidades visuales legacy del administrador. 

## Email Marketing — cierre de limpieza del editor (2026-10-20)

- Formulario mobile-first: configuración compacta; plantillas colapsadas; mensaje Visual/HTML y herramientas en paneles separados; botones, campos y diálogos usando `av3-email-composer__*` y `av3-ui-btn`, sin utilidades legacy.
- Errores de guardado: clase `is-invalid`, atributo `aria-invalid`, rojo semántico moderado y enfoque visible. No aplicar estilos en línea desde controladores JS.
- Modales anidados deben mantener bloqueado el desplazamiento de fondo hasta cerrar el último. Fondo a todo el viewport; rol `dialog` exclusivamente en el panel; estado `is-hidden` compartido.

## Campañas de email — acciones operativas por estado (2026-10-20)

- **Borrador:** permite Editar y Programar ahora (con confirmación). **Programada:** permite editar si todavía no tiene destinatarios en cola y cancelar (con confirmación); no permite otra acción Programar ahora. **Enviando:** cancelar pendientes, no editar. **Enviada/Cancelada:** solo consulta, sin editar ni volver a programar.
- Un mensaje inline debe informar si el estado cambió entre abrir la pantalla y enviar un formulario. No mostrar acciones que el backend rechazará.
