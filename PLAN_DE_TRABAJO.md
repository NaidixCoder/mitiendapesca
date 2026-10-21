# MiTiendaPesca — plan de trabajo vigente

Fecha: 2026-10-17. Fuente de verdad para las prioridades de este proyecto.

## Orden acordado (no alterar sin decisión del propietario)

1. **Calidad técnica y pruebas.** Build gate; regresiones críticas; codificación; excepciones, registros y backups; estabilidad.
2. **Frontend v2 y experiencia de usuario.** Cerrar migración, estilos y responsive.
3. **Checkout y operaciones comerciales.** Pedidos, stock, pago, envíos y conciliación.
4. **Limpieza final.** Deuda técnica controlada y documentación.
5. **Seguridad exhaustiva.** Auditoría completa obligatoria antes de publicación.
6. **Producción.** Configuración, integraciones reales y despliegue.

**Condición:** el proyecto no está expuesto a Internet. No poner en línea sin completar y validar la etapa 5. Si se detecta un problema crítico durante desarrollo, documentarlo y no exponerlo.

## Etapa 1 — estado de avance

- [x] Reparar build gate: runner único en PHP, invocable desde Windows y Bash, análisis sintáctico por tipo de archivo y controles de arquitectura preservados.
- [~] Pruebas automatizadas: 21 verificaciones básicas incorporadas para firma de carrito, navegación de checkout y snapshots/envíos. **Faltan** pruebas con DB aislada y flujos integrados de pedidos, pagos, stock y reintentos.
- [~] Codificación: quitados 20 BOM UTF-8. **Pendiente:** revisar mojibake textual caso por caso, sin conversiones masivas.
- [~] Excepciones, registros y backups: revisión estática inicial. PHP registra errores en `storage/logs/php-error.log`; `log_error()` escribe en `storage/logs/app.log` con fallback al registro PHP. **No se encontró un mecanismo formal de backup/restauración validado en este ZIP.** Falta revisar rotación de logs, control de datos sensibles, recuperación y ensayar restauración con base de pruebas.
- [ ] Estabilidad: pruebas funcionales integradas con base de datos separada y ejecución de procesos en entorno local.

## Uso del gate

Windows: `scripts\\build_gate.bat`
Linux/macOS: `bash scripts/build_gate.sh`

Requisitos: PHP CLI y dependencias Composer instaladas. Node.js habilita validación sintáctica JS; si falta, el gate avisa que omite ese control. No conecta a la base de datos.

## Notas técnicas

- Se sustituyó la lista rígida de archivos (que incluía documentos borrados y pasaba CSS/JS/MD a `php -l`) por descubrimiento de PHP y JS según su extensión.
- Los topes de líneas de `ProductsController.php` y `VariantWriteService.php` fueron ajustados a 375 (base existente: 370 y 369). Mantener estos límites vigilados y evitar crecimiento adicional.
- Los tests unitarios son **una red inicial**, no una garantía de cobro correcto. El cálculo definitivo en servicios de pedidos y la conciliación de Mercado Pago requieren pruebas posteriores con base de datos y simulaciones de webhook.
- No se alteró `.env`, claves, certificados, datos de clientes ni base de datos.

## Seguimiento en PC Windows — 2026-10-17

- [x] Build gate en Windows/XAMPP: aprobado.
- [x] PDO/MySQL: conectividad validada y 65 tablas detectadas (lectura exclusivamente).
- [x] Error comprobado de variables en email marketing corregido; dos regresiones añadidas (23 totales).
- [ ] Pruebas integradas con DB clonada, pedidos, pagos y stock, sin afectar datos existentes.
- [ ] Backup verificable y ensayo de restauración en DB aislada.
- [ ] Revisar escritura de datos sensibles y rotación/retención de logs; no borrar registros indiscriminadamente.
- [ ] Mojibake textual: corregir archivos concretos con revisión visual y pruebas, sin sustitución masiva.

## Ensayo de restauración — 2026-10-17

- [x] Backup de MySQL con mysqldump, restauración a base aislada, 65/65 tablas y conteos de filas verificados; copia SQL retenida fuera de htdocs (`C:/xampp/mi-pesca-backups`).
- [x] Script reproducible: `php scripts/dev/audit_backup_restore.php` (en el equipo con XAMPP). Requiere permisos CREATE/DROP DATABASE y acceso a mysql.exe/mysqldump.exe. La base temporal se elimina incluso tras un fallo de comparación.
- [ ] Tests funcionales de checkout, pago/webhook, idempotencia, órdenes y stock ejecutados sobre base aislada.
- [ ] Corregir mojibake de vistas con cambios pequeños y comprobables.
- [ ] Logs: revisar capturas sensibles, rotación y retención antes de puesta en producción.

## Continuación técnica — 2026-10-17

- [x] Restauración SQL auditada: esquema completo y reversión transaccional comprobados en clon temporal.
- [x] Vista de ejecución de marketing corregida en UTF-8 y validada.
- [x] Auditoría inicial de logs con script de lectura `php scripts/audit/logs_audit.php` (sin mostrar contenidos).
- [ ] Pruebas completas de creación de pedidos, conciliación de webhook, reservas de stock e idempotencia sobre copia aislada. No están implementadas todavía.
- [ ] Análisis/corrección manual de otros casos de texto mojibake.
- [ ] Política de limpieza, retención y rotación de logs; evitar cambios históricos destructivos.

## Validación aislada adicional — 2026-10-17
- [x] Prueba real de idempotencia del repositorio de pagos sobre DB clonada: dos llamadas, un solo ID; transacción revertida y clon eliminado.
- [x] Pantalla de límites de email marketing corregida: UTF-8 y variable de checkbox; regresión añadida.
- [ ] Crear cobertura de creación de pedido, reserva/deducción de stock, webhook duplicado y rechazo de pagos (sin red externa).
- [ ] Revisión exhaustiva de textos dañados en `helpers.php`, controladores de pedidos y envío; sólo reemplazos focalizados.
- [ ] Normalizar retención/rotación y minimización de logs, además del manejo de excepciones de rutas críticas.

## Auditoría integral inicial de /admin — 2026-10-17
- [x] Escaneo de vistas, controladores, servicios y JS administrativos para textos rotos; 28 archivos inicialmente detectados.
- [~] Normalización puntual de 14 archivos con patrones inequívocos de mojibake; quedan 19 candidatos a inspección manual (incluye posibles falsos positivos).
- [ ] Revisar visualmente cada módulo administrativo, prioritariamente variantes, productos y marketing; el análisis estático no garantiza que todos los textos renderizados estén correctos.

## Textos /admin — revisión de 2026-10-17
- [x] Barrido automatizado de directorios de vistas, controladores, servicios y JavaScript admin.
- [~] Reparación de patrones inequívocos en 2 pasadas; archivos señalados por heurística: 28 → 6.
- [ ] Reparar manualmente mensajes gravemente dañados en ProductsController.php y corroborar cada sección de /admin en el navegador.
- [ ] Revisar falsos positivos del escáner (glifos de UI y puntos suspensivos JS).

## Corrección focal y limitación de inspección (2026-10-17)
- [x] Mensajes severamente dañados de `ProductsController.php` reparados y validados con `php -l`.
- [~] Detector admin: 5 archivos marcados, algunos falsos positivos por caracteres válidos.
- [ ] Revisión visual autenticada de las pantallas administrativas, hoy bloqueada por HTTP 401 al consultar sin sesión.

## Etapa 2 — frontend v2 (iniciada, auditoría 2026-10-17)
- [x] Auditoría estática inicial de hojas CSS, estructura de admin, estilos de transición y dependencia Tailwind CDN (`docs/archivo/frontend_v2/AUDITORIA_FRONTEND_V2.md`).
- [ ] Migración progresiva: shell admin → componentes → pantallas de mayor uso → módulos secundarios → CSS legacy público → responsive.
- [ ] Validación visual autenticada y retirada controlada de estilos puente/CDN.

## CSS v2 — revisión estructural inicial (2026-10-17)
- [x] Auditoría de tokens, ui, ui-admin, admin-shell, bridge y botones; docs/archivo/frontend_v2/PLAN_CSS_V2.md.
- [ ] Mapear referencias reales a componentes y elegir un módulo piloto antes de modificar CSS.
- [ ] Migrar shell y módulos, retirar bridge y Tailwind progresivamente con validación visual.

## Implementación v2 shell — 2026-10-17
- [x] Mapa inicial de consumidores CSS: admin-wrap 36 archivos, admin-surface 47, btn-primary 67, admin-card 79, v2-btn 27 (búsqueda estática; posibles coincidencias ajenas a atributos class).
- [x] Primer aislamiento del shell: dos reglas body y selector nav legacy acotados a admin.
- [ ] Validar visualmente shell con sesión administrativa, escritorio/móvil; continuar migración por módulos.

## Shell compartido piloto — 2026-10-17
- [x] Primer layout fluido y paddings compartidos para Dashboard y Productos exclusivamente en CSS del administrador.
- [ ] Aprobar visualmente las pantallas piloto, incluidos encabezado, sidebar contraído, tablas, gráficos y responsive.
- [ ] Generalizar gradualmente el mismo layout a las demás páginas /admin, retirando límites y márgenes locales tras inspección.

## Corrección funcional sidebar admin — 2026-10-17
- [x] Eliminado doble bind del botón hamburguesa de admin; test automatizado en ambos sentidos.
- [ ] Chequeo visual: /admin/products y /admin, clic hamburguesa 2 veces, comprobar sidebar 250px ↔ 76px y contenido acompaña; verificar pantalla angosta.

## Frontend v2 — Productos piloto (2026-10-17)
- [x] Primera unificación de encabezado, botones, buscador y tarjeta de listado en /admin/products, con CSS exclusivo de página.
- [ ] Aprobación visual a 1600px, 1024px y pantalla angosta. Chequear que botón Buscar filtra, Limpiar reinicia, Inventario y Nuevo producto navegan bien.
- [ ] Con esa referencia aprobada, extraer estilos comunes al sistema de componentes admin sin afectar /public.

## Tabla administrativa v2 — piloto Productos (2026-10-17)
- [x] Componente de tabla v2 con adopción voluntaria, aplicado inicialmente a Productos.
- [ ] Aprobación visual de tipografía, filas, badges, acciones y scroll horizontal; comprobar enlaces de variantes/editar.
- [ ] Migrar gradualmente otras tablas tras aprobación, sin modificar CSS /public.

## Bloque 1 — sistema visual compartido (2026-10-17)
- [x] Fundamentos CSS transversales exclusivos de admin (layout, botones, formularios, tarjetas, tablas, badges, diálogos y responsive).
- [x] Gate reforzado para impedir inclusión de la nueva hoja en el frontend público.
- [ ] Validar visualmente 5 pantallas representativas con sesión y corregir incompatibilidades reales.
- [ ] Extraer patrones duplicados probados desde admin-beta-bridge hacia componentes estables y verificar referencias antes de eliminarlos.
- [ ] Completar migración interactiva de modales y notificaciones por módulos sin tocar la tienda pública.

## Layout global admin y limpieza — 2026-10-17
- [x] Cuarentena con respaldo verificable de 10 archivos ajenos de MiSistema, fuera del árbol web.
- [~] Comparación con ZIP base (no disponible ZIP MiSistema para verificación total de sobrescrituras).
- [x] Contrato CSS administrativo de ancho fluido y padding exterior común aplicado también a subrutas y estructuras heredadas.
- [ ] Validación visual por el usuario en rutas representativas, escritorio y móvil; ajustar excepciones sin afectar público.

## Facturación — corrección layout (2026-10-17)
- [x] Eliminar centrado/límite de 1240px del layout fiscal y de su navegación para empatar con el shell administrativo.
- [ ] Validación visual de /admin/facturacion/control y subrutas (ventas, compras, alertas, reportes); comprobar nav, ancho y grilla responsive, con sidebar expandido y contraído.

## Reconstrucción visual aislada de Productos — 2026-10-17
- [x] Primer corte visual con CSS aislado del sitio público y sidebar intacto para listado, alta y edición.
- [ ] Aprobación visual y funcional en `/admin/products`, `/admin/products/new` y edición de producto existente.
- [ ] Sustituir progresivamente markup y dependencias legacy cuando las vistas piloto estén aprobadas; lógica fuera del alcance.

## Productos — primera entrega real de rediseño, 2026-10-17
- [x] Reconstruir encabezado, resumen, buscador y tabla del listado.
- [x] Rediseñar encabezado de nuevo/editar, pasos, acciones y presentación del formulario existente sin modificar la lógica.
- [ ] Inspección visual de escritorio/móvil y ajustes de contraste, anchos, tabla y campos anidados.
- [ ] Rediseño integral restante: subpaneles de variantes, imágenes, SEO, publicación y diálogos internos, con validación funcional.
- [ ] Extraer componentes v3 confirmados para el resto del Catálogo y retirar CSS antiguo después de verificar consumidores.

## Dashboard — rediseño 2026-10-17
- [x] Reorganizar todos los bloques existentes según prioridad operativa, comercial y seguimiento.
- [x] Construir primer componente PHP reutilizable PageHeader.
- [x] Nuevo estilo visual opt-in exclusivo de Dashboard y responsive.
- [ ] Revisar captura real Desktop y móvil, ajustar paneles internos que no alcancen nivel premium.
- [ ] Consolidar componentes reutilizables adicionales (paneles, métricas y estados) al avanzar con las demás páginas; retirar dependencias legacy solo tras pruebas.

## Dashboard contrato visual 1.0 — 2026-10-17
- [x] Rediseño de estructura compacta con cuatro KPI principales y secundarios sin perder datos.
- [x] Rediseño de alertas, paneles, tablas y actividad con tipografía contenida.
- [x] Componente reutilizable de métrica extraído para futuras pantallas.
- [ ] Validación visual y navegación manual; corregir cualquier contraste/ancho anómalo antes de cerrar el Dashboard.

## Dashboard — paso de simplificación (2026-10-17)
- [x] Encabezado esencial y cuatro KPI sin duplicados.
- [x] Pedidos principales y alertas; retirar tarjeta de pendientes duplicados y KPIs secundarios de Dashboard.
- [x] Ocultar paneles de rankings vacíos, manteniendo fuentes de datos intactas.
- [ ] Revisar visualmente la tendencia sin ventas, la actividad y el responsive; mejorar estado vacío del gráfico sin romper el JS.

## Limpieza de dependencias visuales Dashboard (2026-10-17)
- [x] Migrar seis parciales a clases propias `dv-*` y CSS encapsulado.
- [x] Eliminar scroll vertical interno en tabla de pedidos y limitar a 5 filas visibles.
- [ ] Retirar CSS antiguo cargado globalmente sólo tras inspeccionar consumidores y limpiar trend.js/estilos.
- [ ] Validar en navegador tabla, actividad, móvil y tendencia sin datos.

## Dashboard base sin diseño — 2026-10-17
- [x] Resguardar y eliminar CSS exclusivo del Dashboard.
- [x] Quitar clases de presentación estáticas de las vistas manteniendo el HTML.
- [ ] Revisar clases PHP dinámicas y hooks JS restantes antes de su eliminación.
- [ ] Diseñar Dashboard nuevo desde cero, con componentes nuevos y pruebas visuales.

## Admin 2026 — Nueva base Dashboard
- [x] Documento rector visual v1.0 escrito en raíz.
- [x] Reorganización específica con componentes HTML propios y CSS `mt-*`.
- [x] Estado vacío del gráfico preservando hooks JS y el contrato del endpoint.
- [x] Build gate aprobado.
- [ ] Validación visual REAL desde sesión admin, corrección y segunda captura (bloqueada por captura de ventana equivocada).
- [ ] Prueba visual angosta/móvil y clics de enlaces y filtros.

## Dashboard — pasada integral 2026-10-17
- [x] Composición HTML de Dashboard y KPIs secundarios de menor jerarquía.
- [x] Hoja CSS única reescrita bajo contrato v1.0, sin tocar shell/sidebar.
- [ ] Verificar ancho del contenedor y comportamiento visual en navegador autenticado (escritorio y angosto).
- [ ] Validar enlaces y datos reales en tabla y área de alertas.
