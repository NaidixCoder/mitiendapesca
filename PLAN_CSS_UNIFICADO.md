# Plan maestro — Unificación CSS de MiTiendaPesca

Estado: EN EJECUCIÓN. Documento vivo. Actualizar después de cada etapa.

## Objetivo
Un solo sistema de estilos por ámbito, sin superposición de versiones, sin Tailwind ni utilidades Tailwind en el código, sin estilos legacy de presentación y sin diferencias visuales no previstas. Conservar diseño público, sidebar administrativo, lógica y datos.

## Alcance y decisiones
- Auditar PHP/HTML, plantillas, JS, CSS, cargas condicionales, clases dinámicas y subrutas.
- Admin: consolidar en `assets/css/admin-v3/`, con tokens, base, layout, componentes, páginas.
- Público: conservar diseño e identificar dependencias Tailwind antes de retirar CDN global. No eliminar CSS público funcional por la migración admin.
- Clasificar clases según **uso**, no por nombre: visual, JS hook, estado dinámico, Tailwind, legacy.
- Cero clases Tailwind al concluir y cero duplicación de implementaciones de un mismo componente.
- No borrar estilos globales sin comprobar consumidores efectivos.
- Entregas actuales: cambios directos en C:\xampp\htdocs\mi-pesca mediante Desktop Commander, sin ZIP incrementales; respaldos locales privados fuera del proyecto y trazabilidad en MANIFEST.md. No incluir secretos, DB ni node_modules en entregas.
- Pruebas: build gate, inspección de enlaces/DOM, comprobación de desktop/móvil con navegador cuando exista acceso, y evidencia de inexistencia de dependencias sin migrar.

## Etapas y criterios de salida
1. [EN SEGUIMIENTO: INVENTARIO + MAPA DE DEPENDENCIAS] Auditoría: inventario de archivos y usos; cruces CSS/HTML/JS; hoja de riesgos y clases Tailwind detectadas.
2. [IMPLEMENTADA: BASE ADMIN V3] Arquitectura: esquema `admin-v3`, estándares de nomenclatura, aislamiento y carga; sin alterar diseño.
3. [EN CURSO: COMPONENTES BASE] Biblioteca: componentes reutilizables, interfaces de estados, formularios, tablas y modales.
4. [MIGRADO EN CÓDIGO, FALTA VALIDAR VISUALMENTE] Dashboard: migración integral y remoción verificable de estilos anteriores.
5. [PENDIENTE] Catálogo y subrutas.
6. [PENDIENTE] Pedidos, facturación y operaciones.
7. [MIGRACIÓN DE CLASES ADMIN-* IMPLEMENTADA; ESTILOS GENÉRICOS COMPARTIDOS CONSERVADOS] Resto de módulos administrativos.
8. [IMPLEMENTADA A NIVEL DE CARGA Y BARRIDOS ESTÁTICOS, PENDIENTE VALIDACIÓN VISUAL] Remoción Tailwind en admin y público, eliminación de CSS en desuso tras barrido final de clases dinámicas.
9. [PRUEBAS AUTOMATIZADAS APROBADAS, PENDIENTE VALIDACIÓN VISUAL Y FUNCIONAL] Revisión integral de rutas, regresiones y responsive.

## Entregas
Registrar aquí fecha, archivos, resultados de pruebas y ubicación privada del respaldo local por etapa. La etapa no se cierra con una prueba sintáctica exclusivamente; la validación visual se registra por separado.

## Avance 2026-10-17
- Etapa 1: inventario estático generado (58 CSS, 373 PHP y 116 JS); archivos AUDITORIA_CSS_2026.md/json. No es una auditoría dinámica exhaustiva: falta revisar clases PHP/JS construidas en ejecución y falsos positivos.
- Etapa 2: documento de arquitectura y migración creado en docs/ARQUITECTURA_ADMIN_V3.md. NO se han migrado componentes ni cargado hojas nuevas todavía.
- Pendiente: clasificación efectiva de candidatos Tailwind, mapa JS por ruta y validación visual. No desconectar CDN ni bridges prematuramente.

## Avance adicional 2026-10-17
- Auditoría: 341 clases candidatas distintas; 116 aparecen también en JS (coincidencia textual, no implica selector válido). Detalle en docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json.
- Base admin-v3 instalada en head.php para admin únicamente: tokens.css, components/common.css, pages/dashboard.css.
- Dashboard: clases de presentación cambiadas de mt-* a av3-* y nueva hoja CSS; hooks JS admin-row-link, IDs de tendencia y shell intactos.
- Verificación aún pendiente: render autenticado visual, accesibilidad y limpieza de todos los parciales. NO afirmar que Tailwind desapareció globalmente.
- Etapas 5-9 no iniciadas. CDN Tailwind permanece porque otros módulos lo usan.

## Limpieza Dashboard 2026-10-17
- Retirada la hoja anterior dashboard-2026.css tras verificar desconexión de carga y equivalencia migrada. Contrato estático de Dashboard pasó.
- Sigue pendiente validar la pantalla real en navegador y limpiar parciales aún heredados. No confundir esto con la migración integral de todo admin.

## Consolidación administrativa (2026-10-17)
- Se reemplazaron ocho hojas CSS anteriores por cinco hojas unificadas dentro de `public_html/assets/css/admin-v3/pages/`: `orders.css`, `products.css`, `catalog.css`, `product-editor.css`, `billing.css`.
- Se conservó el orden de las reglas originales y se modificó `head.php` para cargar únicamente las hojas consolidadas de estos módulos.
- Los parciales de rankings del Dashboard recibieron clases `av3-*` y estilos específicos.
- IMPORTANTE: todavía hay clases Tailwind en vistas administrativas y CSS legacy compartido; la consolidación física no equivale a migración completa. Falta inspección visual final.

## Continuación 2026-10-17
- Confirmadas 18 hojas administrativas cargadas y sin referencias ausentes.
- Diez hojas admin-v3 preexistentes verificadas; se trasladaron además tres CSS exclusivos de admin a admin-v3/components sin alterar ni una regla (ui-admin-compat, legacy-bridge, foundations). Se desconectaron y retiraron sus antiguos archivos v2.
- Quedan cinco hojas v2 compartidas por público/admin; NO se movieron para evitar regresiones públicas.
- Tailwind CDN y clases utilitarias siguen activos; falta migración efectiva de plantillas y dependencias JS.

- Se actualizó scripts/build/admin_css_scope.php para comprobar foundations.css en admin-v3 tras su traslado (la primera ejecución del gate detectó ruta antigua).

## Continuación 2026-10-17 — limpieza Tailwind en plantillas y módulos JS
- Migradas las últimas tres plantillas PHP identificadas con utilidades Tailwind: partials/flash_inline.php, partials/catalogo/active-filter.php y pages/auth/reset_password.php.
- Añadido css/v2/components/no-tailwind-public.css a las cargas pública y administrativa para estilos semánticos equivalentes; CSS público visualmente pendiente de validar al final.
- Migrados los elementos dinámicos de recent_searches.js y ui/loading_overlay.js a clases site-*, incluidos estados de visibilidad. No se eliminó aún el CDN global: siguen consumidores JS administrativos.
- audit_css_dependencies.py ahora reporta 0 candidatos en declaraciones class de vistas PHP (alcance de ese escáner). JS conserva clases y selectores utilitarios no renombrados; audit_js_tailwind_classes.py listó 66 parejas archivo/clase candidatas.
- No declarar ausencia completa de Tailwind mientras exista carga global y JS sin migrar.

## Continuación 2026-10-18 — depuración de CSS de compatibilidad
- Auditoría de los tres estilos de compatibilidad: legacy-bridge.css (70 reglas, 72 clases), ui-admin-compat.css (54 reglas, 26 clases), foundations.css (28 reglas, 46 clases).
- Auditadas clases consumidas en plantillas PHP y módulos JavaScript; identificadas 17 reglas simples sin referencias en ui-admin-compat.css y retiradas con respaldo en C:\xampp\mi-pesca-backups.
- No se eliminaron legacy-bridge.css ni foundations.css: conservan consumidores activos o selectores compuestos que requieren validación.
- Scripts reproducibles: scripts/dev/audit_compat_classes_extended.py, audit_compat_removal_candidates.py, prune_unused_admin_css_rules.py y verify_css_unification_integrity.py.
- La herramienta de integridad confirmó 20 CSS administrativos y 37 CSS públicos, sin duplicados ni referencias rotas; CDN Tailwind ausente. Barrido amplio de utilidades identificadas: 0 coincidencias. Una búsqueda estática no equivale a validación visual/funcional.
- La eliminación de compatibilidad legacy completa sigue pendiente de migración de sus consumidores; no declarar terminado el proyecto.

## Control de regresión CSS — 2026-10-18
- Se agregó scripts/build/css_unification_contract.php al gate general en scripts/build/gate.php.
- El contrato verifica: ausencia de CDN Tailwind, existencia de todas las hojas registradas en admin y público, ausencia de rutas duplicadas y presencia del layout admin-v3.
- Prueba individual: [css-unificado] OK. La validación visual final sigue pendiente.

## Mapa de consumidores CSS — 2026-10-18
- docs/MAPA_CONSUMIDORES_LEGACY_CSS.json identifica coincidencias de clases en PHP/JS para las tres hojas antiguas. Precaución: análisis léxico conservador, puede incluir coincidencias no funcionales; no utilizar como criterio único para borrar selectores.
- Gate completo aprobado después de retirar 17 reglas simples y agregar contrato CSS: CSS_UNIFIED_GATE_OK.
- Riesgo pendiente: legacy-bridge.css y foundations.css tienen dependencias distribuidas; antes de retirarlas, migrar componentes reales de sus vistas y comprobar estados.

## CSS público separado por dominio — 2026-10-18
- Reubicadas 30 hojas exclusivas del público a public_html/assets/css/public/{pages,layouts,components}, sin cambiar las declaraciones ni el orden registrado en head.php.
- Se dejó copia de seguridad previa bajo C:\xampp\mi-pesca-backups. No se modificó su presentación intencionalmente.
- Hoja compartida en css/shared y hojas administrativas en css/admin-v3; subsisten CSS legacy y compatibilidad, aún no eliminados.
- Auditoría de rutas resueltas: 20 admin y 37 público, sin faltantes. Gate general en curso al registrar este avance.

## Estructura de carga limpia — 2026-10-18
- CSS exclusivos del público: 30 hojas ubicadas bajo css/public/, respaldadas y verificadas por build gate.
- Arrays de carga renombrados a $adminStyles/$publicStyles; nombre obsoleto home-hero-v2.css reemplazado por home-hero.css sin cambio de reglas.
- Verificados contrato CSS, integridad de referencias y aislamiento de foundations.
- Aún quedan estilos legacy dentro del CSS administrativo y app.css para compatibilidad pública. No eliminados sin comprobación funcional final.

## Limpieza de CSS públicos y agregadores V2 — 2026-10-18
- La hoja de edición de direcciones, antes cargada desde AddressesController.php en css/v2/pages/public, se migró a css/public/pages/account-address-edit.css y se actualizó su controlador.
- Se eliminaron los agregadores sin consumidores: css/v2/index.css, css/v2/admin.css, css/v2/public.css. Se dejó copia en C:\xampp\mi-pesca-backups.
- No se modificaron controladores salvo la ruta estática de CSS; sin cambios de negocio.
- Se mantiene pendiente revisar visualmente /public y admin, así como retirar compatibilidad administrativa cuando sus consumidores estén migrados.

## Consolidación de estructura (2026-10-18 00:37)
- Carpetas CSS operativas: css/shared, css/public y css/admin-v3.
- Módulos compartidos importados desde css/shared; 31 hojas públicas trasladadas a css/public, incluida edición de direcciones desde AddressesController.
- CSS público principal movido a css/public/app.css e importación de Mi Cuenta a css/public/components/account.css. Se verificaron cuatro imports relativos.
- Se removió la importación pública del viejo paquete admin y sus cuatro archivos, sin consumidores directos adicionales.
- Tres agregadores v2 obsoletos eliminados, con respaldos en C:\xampp\mi-pesca-backups; carpeta css/v2 sin archivos activos.
- Los estilos administrativos legacy-bridge y foundations no están eliminados: todavía tienen consumidores. Las 17 reglas sin referencias de ui-admin-compat se retiraron con respaldo.
- Eliminar nombres v2 y el CDN Tailwind no garantiza paridad visual: la revisión funcional y visual de rutas públicas y admin sigue siendo requisito de cierre.

## Consolidación final de compatibilidad CSS (2026-10-18)
- Se consolidaron ui-admin-compat.css, legacy-bridge.css y foundations.css en una sola hoja administrativa: public_html/assets/css/admin-v3/components/admin-components.css. Se preservaron orden y contenido del CSS para no alterar la cascada.
- Se eliminaron las tres hojas de carga anterior y se actualizó app/Views/partials/head.php.
- Se actualizó scripts/build/admin_css_scope.php para exigir carga exclusiva y única de la nueva hoja.
- Se auditó la repetición de selectores por archivo: docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json. NO se borraron reglas repetidas que pueden ser correcciones intencionales de cascada.
- Atención: consolidar físicamente estos archivos NO implica que las clases legacy internas hayan desaparecido. Siguen existiendo selectores admin-* y otros contratos en uso. No declarar completada la migración semántica ni la validación visual.
- No crear ZIP adicional: cambios hechos directamente con Desktop Commander. Respaldos previos en C:\xampp\mi-pesca-backups.

## Etapas 4 y 5 — limpieza de clases (2026-10-18 00:58)
- Migrados 69 nombres de clases de presentación admin-* hacia av3-* en 117 archivos CSS/PHP/JS. Mapa: docs/MIGRACION_CLASES_ADMIN_V3.json.
- Conservado admin-surface: contrato estructural del sidebar/layout, no clase legacy arbitraria.
- Mantener btn, badge, input y otras clases base compartidas como contratos existentes hasta validar componentes visualmente; renombrarlas sin transferir sus estilos rompería la interfaz.
- Comprobación exhaustiva de 69 nombres antiguos: cero referencias remanentes en código PHP/JS/CSS de la aplicación. Prueba scripts/build/admin_classnames_contract.php integrada en el gate.
- Auditoría de selectores sin consumidores y duplicados: scripts/dev/audit_admin_v3_dead_selectors.py, docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json y scripts/dev/audit_exact_css_duplicates.py. No se borraron reglas de cascada cuyo efecto no se pudo demostrar nulo.
- Se resguardó el estado previo a la migración bajo C:\xampp\mi-pesca-backups.
- Validación visual integral expresamente diferida; cualquier diferencia se tratará como regresión.

## Corrección de regresión visual del sidebar — 2026-10-18
- La migración automática de clases admin-* a av3-* creó una colisión entre av3-label/av3-link del sidebar y las clases de formularios/enlaces en admin-components.css.
- Se aislaron las clases del sidebar: av3-sidebar-label, av3-sidebar-link y av3-sidebar-icon en su vista y en admin-v3/layout.css. Sin rediseñar el sidebar.
- Se añadió scripts/build/sidebar_style_contract.php al build gate. Respaldo previo en C:\xampp\mi-pesca-backups. Validación visual pendiente.

## Rediseño completo /admin/analytics — 2026-10-19
- Se reconstruyó app/Views/pages/admin/analytics/index.php con encabezado y contenedor propios.
- Se creó app/Views/partials/admin/analytics/overview.php, sin reutilizar el antiguo parcial sin estilos de Dashboard.
- Se creó public_html/assets/css/admin-v3/pages/analytics.css y se registró en head.php.
- Presentación: indicadores KPI, lectura secuencial de conversión, seis informes en tablas con estados vacíos y responsive.
- Se conservaron las claves originales de $navigationAnalytics y enlaces existentes; no se modifica el backend ni se inventan datos.
- Inspiración de estructura: cuadros de métricas y paneles de Shopify Analytics y Stripe Dashboard; sin copiar logos/identidad.
- No se generó ZIP. Validación de navegador queda pendiente de revisar visualmente.

## Design System Tabler (2026-10-19)
- Creado css/admin-v3/components/design-system.css con componentes av3-ui-* exclusivos del admin.
- Integración inicial en Analíticas; no se migraron todavía Productos ni Pedidos.
- Referencias y contrato: docs/ADMIN_DESIGN_SYSTEM_TABLER.md.
- Sin ZIP ni framework nuevo. Validación visual pendiente.

## Layout fluido 2026-10-19
- Tres contenedores opcionales en admin-v3/layout.css (operativo/detalle/formulario).
- Analíticas usa modo operativo, KPI y tablas fluidos, panel de conversión y resumen lateral.
- Sin cambiar sidebar ni vistas restantes. Sin ZIP.

## Auditoría de encabezados /admin (2026-10-19)
- Ejecutado scripts/dev/audit_admin_headers.py sobre 69 vistas PHP administrativas en 19 módulos y 95 referencias literales de rutas /admin.
- Inventarios: docs/AUDITORIA_ADMIN_ENCABEZADOS.md y docs/AUDITORIA_ADMIN_ENCABEZADOS.json.
- Esta etapa es solo inventario estático: falta inspección de parciales y navegación real antes de implementar/migrar el componente header. No se modificó el diseño ni el sidebar.

## Encabezado unificado /admin — piloto (2026-10-19)
- Auditoría estática: 69 vistas en 19 módulos y 95 referencias de rutas. Inventario en docs/AUDITORIA_ADMIN_ENCABEZADOS.md/.json.
- Componente PHP compartido en app/Views/partials/admin/page_header.php y CSS exclusivo en admin-v3/components/page-header.css.
- Piloto migrado: Pedidos listado, Pedido detalle, Auditoría SEO y Analíticas. Breadcrumb jerárquico, título, descripción y acciones existentes preservadas.
- Pendiente: inspección de rutas reales y migración de Dashboard, Facturación, Productos, Usuarios y restantes parciales, sin introducir encabezados duplicados. Sidebar intacto. Sin ZIP.

## Encabezados y breadcrumbs unificados (continuación 2026-10-19)
- Cobertura: 67 vistas administrativas completas usan app/Views/partials/admin/page_header.php, según scripts/build/admin_page_header_contract.php. Dos vistas de Atributos son fragmentos internos sin encabezado propio.
- Se eliminaron los parciales antiguos app/Views/partials/admin/breadcrumbs.php y app/Views/partials/admin/components/page_header.php y se retiraron las 40 llamadas al breadcrumb anterior. Cero referencias en vistas.
- Facturación: subnavegación desplazada debajo del encabezado en 19 vistas; se conservaron enlaces y estados del componente.
- Eliminadas 8 reglas CSS obsoletas específicas de encabezados (Dashboard y editor de Productos).
- Validaciones: build gate completo OK tras traslado de navegación; contratos focales de CSS/encabezado nuevamente OK tras limpieza de reglas.
- Backups locales de archivos previos: C:\xampp\mi-pesca-backups (sin paquetes incrementales de entrega).
- Pendiente: inspección visual de las rutas en navegador, especialmente el editor de Productos, Auditoría y Facturación; el build gate no equivale a validación visual.

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

## SEO administrativo — migración mobile-first (2026-10-20)

- Auditoría SEO migrada a admin-v3/pages/seo.css con namespace propio; reemplazados filtros/resultados heredados sin superposición incremental de CSS. Aside, Page Header, layout maestro y público intactos.
- Editores de productos, categorías y marcas: clases av3-seo-editor-*, configuración avanzada desplegable, campos y hooks de persistencia conservados. Primitivas av3-* compartidas permanecen porque otros módulos las utilizan; no se inventan overrides legacy globales.
- Responsive: indicadores 2x2 en móvil y cuatro en espacio útil >=600px; filas móviles para ancho útil <780px y tabla desde 780px. Pestañas de filtros rápidas desplazables sin barra visible en 363px. Interacción táctil/teclado para ordenar bloques SEO.
- Las incidencias del score se presentan por separado del contenido opcional; se mantienen consultas, filtros, rutas, paginación y vista previa estimada. Solucionado bind PDO de búsqueda. Validación técnica: contrato SEO, smoke DB de solo lectura y gate completo correctos.
- Capturas en navegadores headless sobre HTML realmente renderizado y CSS real; pendiente prueba interactiva de guardado/diagnósticos en Firefox autenticado. Respaldo local externo, sin ZIP.

## SEO: composición compacta y vista previa permanente (2026-10-20)

- Se reorganizó, sin superponer overrides, la sección de Auditoría en admin-v3/pages/seo.css; el bloque de estilos de editores queda intacto.
- Filtros rápidos, controles compactos y filtros avanzados conservan formulario GET, nombres de campo, IDs y botones reales. Responsive desde móvil, pasando a barra horizontal y filtros en una sola fila cuando el espacio lo admite.
- Tabla desde 1060 px de ancho útil: seis columnas, incluida vista previa SEO permanente; producto, score, incidencias completas, SERP estimado, publicación y acción. El diagnóstico desplegable solo se usa en las tarjetas de ancho reducido; preview compartido en un partial.
- Se elimina de las filas el resumen de contenido opcional (bullets/specs/FAQ) por no afectar score y por redundancia; sigue disponible en los editores. No se alteraron consultas, rutas ni persistencia.

## Marketing — primera etapa (2026-10-20)

- `/admin/marketing/campanas`: migración de listado a `admin-v3/pages/marketing-campaigns.css` (namespace `av3-marketing-*`). Mobile-first; tabla a partir de 980px de ancho útil, sin scroll horizontal móvil, filas compactas, atribución secundaria.
- Navegación compartida `marketing_subnav.php` usa tabs discretas; conserva rutas Campañas / Email. CSS carga sólo en el administrador y usa tokens del contrato.
- Un ViewModel prepara los datos; la vista no calcula tasas, URLs ni formatos. Mantener en detalle las métricas que la tabla compacta no muestra.
- Migración **parcial**: formularios/detalles de campañas y todo Email todavía tienen estilos legacy/utilitarios. No desconectar estilos compartidos ni declarar `/admin/marketing/*` migrado.

- Verificación de primera etapa: contrato Marketing, consultas locales, render con datos sintéticos y vacío, CSS parseado y build gate completo aprobados. Pendiente Firefox autenticado / revisión del usuario.

## Marketing — fase 2: formulario y detalle (2026-10-20)

- Nuevas clases `av3-marketing-editor__*` y `av3-marketing-detail__*` añadidas a la **misma** hoja `pages/marketing-campaigns.css`; no crear CSS adicionales para las mismas pantallas, no depender de clases legacy.
- Formulario: mobile-first, panel de publicación debajo de los campos en móvil y a la derecha en escritorio; UTM source/medium/campaign visibles, content/term/notas plegables (sin deshabilitar campos). Preview enlazado mediante atributo `data-marketing-*` y JS existente.
- Detalle: navegación y encabezado únicos, QR bajo detalle, métricas/tasas/tablas/rankings/actividad completos, con tablas desbordables solamente cuando es necesario y sin límites fijos de alto innecesarios.
- **Email marketing todavía pendiente:** ninguna hoja legacy del editor de emails debe eliminarse hasta migrar sus consumidores y verificar módulos de automatizaciones, campañas, cola y plantillas.

## Email Marketing — fase 1 (2026-10-20)

- Hoja única de etapa `admin-v3/pages/marketing-email.css` exclusiva de las vistas migradas (`.av3-marketing-email`), navegación interna compartida y tokens oficiales. No añadir overrides a las hojas heredadas de Envíos/Cola/Plantillas/editor.
- Resumen: dos paneles de KPI y accesos a herramientas. Suscriptores: tabla a partir de 600px de ancho útil, filas compactas en móvil y paginación 25/50. Automatizaciones: lista de series y pasos con controles visibles e idénticos en móvil/desktop.
- Preservación funcional de endpoints y formularios POST/CSRF. No retirar `admin-components.css` ni estilos compartidos usados por otras rutas pendientes. Migración **parcial** declarada.

## Marketing Email — fase 2 (2026-10-20)

- Ampliada la misma hoja `admin-v3/pages/marketing-email.css` con variantes semánticas de Envíos, Cola y Plantillas. Tablas densas desde 1000px de contenedor; debajo, filas propias de móvil y tablet. KPIs de Cola 2/3/6 columnas. Detalle de plantilla una columna móvil y dos escritorio, sin scroll anidado.
- No se introdujeron `-v2.css` ni estilos inline nuevos; se eliminaron utilidades visuales antiguas de las vistas migradas. La vista previa continúa aislada con sandbox.
- Formulario y detalle de campaña masiva, y editor visual/HTML quedan fuera de esta fase y no se deben tratar como migrados. Mantener compatibilidad JS de borradores, modales y editor.

## Email Marketing — fase 3 editor/detalle (2026-10-20)

- Extensiones semánticas `av3-email-composer__*` y `av3-email-campaign-detail__*` dentro de la misma hoja `admin-v3/pages/marketing-email.css`; sin crear hojas nuevas ni llevar `<style>` inline en parciales de formulario.
- Configuración de campaña y detalle adaptados a contenido móvil; plantillas de composición dentro de `details`, editor de texto y toolbar con dimensiones coherentes, modales encima del contenido, previsualizaciones en `iframe` aislados, cinco métricas responsive.
- Internos del editor avanzado y modales todavía tienen utilidades heredadas compartidas con el JavaScript (toggles `av3-hidden`, estados visuales). Su retirada completa requiere migración atómica y pruebas de interacción. No eliminar CSS legacy global usado por otros módulos sin verificar consumidores.

## Email Marketing — limpieza focal fase 4 (2026-10-20)

- Estados del editor pasan a nombres locales (`is-hidden`, `is-active`, `is-disabled`, `is-complete`) controlados por JS y CSS acotados al componente, sin dependencias de clases visuales Tailwind para activar pestañas, paneles o modales.
- Modales Preview, Test y Picker: nuevos patrones `av3-email-composer__modal/dialog/backdrop/dialog-head/dialog-body/dialog-foot`. La capa de fondo cubre el viewport; el rol `dialog` se ubica **en el panel**, ya que `admin-components.css` limita globalmente la anchura de los diálogos. No crear excepciones globales.
- Se retiró CSS de modales pequeños que dejó de tener consumidores y se concentraron sus reglas en `admin-v3/pages/marketing-email.css`. Las utilidades estructurales del asistente Card/Grid2 y del formulario largo todavía tienen consumidores; eliminar solo después de su conversión y validación.

## Email Marketing — fase 5, editor modular sin utilidades legacy (2026-10-20)

- `templates.php`, `message.php`, `tools.php` y los cuatro modales utilizan clases propias `av3-email-composer__*` o botones `av3-ui-btn`. No reintroducir `av3-pad-*`, `av3-flex`, `av3-bg-*` ni CSS utilitario en esos parciales.
- Consolidación final en `admin-v3/pages/marketing-email.css`: se retiraron los selectores dependientes de la estructura vieja del formulario. Se mantienen tokens oficiales, container queries y estados `is-hidden`, `is-active`, `is-invalid`, `is-disabled`, `is-complete`.
- `body.av3-email-dialog-open` bloquea el fondo cuando hay uno o más diálogos abiertos. `dialog_scroll_lock.js` administra anidamiento Card → Picker. El mensaje de error usa clases locales con atributos accesibles, no CSS inline.
- Únicos estilos `av3-wrap`/`av3-marketing-email` conservados corresponden al layout global y NO son utilidades legacy a eliminar.

## Ciclo de vida de Email Marketing (2026-10-20)

- Sin cambio de identidad ni nuevos overrides CSS: los estados existentes de la campaña se representan con tonos semánticos ya definidos en `marketing-email.css`. El botón Editar se oculta cuando el repositorio determina que la campaña no admite cambios; Programar ahora/Cancelar solo se presentan en los estados correspondientes.
- Confirmación JavaScript activada desde el `pageId` real del detalle. El backend impone la política de transición independientemente de que se omita o desactive JavaScript.
