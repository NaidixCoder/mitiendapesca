# Tags — mandato integral Admin V3 (23/10/2026)

Estado: asignado a especialista, MAIN CHAT coordina e integra.

## Inventario (confirmar con código)
/admin/catalog-tags y POST group/save, tag/save; CatalogTagsController; app/Views/pages/admin/catalog_tags.

## Alcance
Grupos y tags, slug/unicidad, CRUD real, integridad de relaciones, persistencia, AJAX/modales, estados/errores, responsive. No inventar endpoints.

## Reglas de ejecución
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md, docs/ARQUITECTURA_ADMIN_V3.md. Una auditoría inicial completa y una ventana grande de implementación: reparar lógica, seguridad, SQL/servicios y todas las vistas; rediseño HTML/CSS Admin V3 con /admin/seo/auditoria como referencia (cards, botones, jerarquía, peso de fuente, tablas, responsive). Reutilizar tokens/componentes, CSS acotado al módulo; sin Tailwind ni overrides globales. No modificar shell/sidebar, CSS compartido ni archivos de otros especialistas sin reserva MAIN CHAT. Probar en DB aislada las operaciones destructivas; verificar desktop/tablet/móvil. Registrar QA real y riesgos, sin microventanas.

## Primer prompt para el especialista
MiTiendaPesca — especialista Tags. Leé docs/modulos/tags.md y toda la documentación obligatoria. Auditá UNA vez el módulo entero con sus vistas derivadas; implementá integralmente calidad de código, seguridad, HTML/CSS Admin V3 con referencia visual Auditoría SEO y QA responsive/funcional. Sin microventanas ni solicitudes de autorización por cada cambio propio. Actualizá este documento con evidencia. **Terminá en una única respuesta breve:** MÓDULO | ESTADO (CERRADO/PARCIAL) | CAMBIOS | QA con resultados | ARCHIVOS/COMMIT | BLOQUEO + SOLUCIÓN. Ese resumen será auditado por MAIN CHAT.

## Intervención especialista — 23/10/2026

**Estado: PARCIAL, no apto para cierre integral.** Se leyeron los protocolos V2/V3, coordinación, guía visual y arquitectura; se inspeccionaron controlador, repositorio y vista principal. La implementación existente ofrece formularios de creación/edición de grupos y etiquetas, pero no rutas de eliminación en el controlador inspeccionado.

### Correcciones efectivas
- `CatalogTagsRepository.php`: rechaza edición de IDs inexistentes, pertenencia a grupos inexistentes y slugs duplicados de grupos o dentro del mismo grupo de etiquetas. Son comprobaciones de aplicación; no sustituyen UNIQUE físico ni eliminan carreras concurrentes.
- `CatalogTagsController.php`: ya no muestra detalles internos de excepciones de base de datos al usuario.

### QA ejecutado
- `C:\xampp\php\php.exe -l` sobre controlador, repositorio y vista `catalog_tags/index.php`: **3/3 sin errores de sintaxis**.
- `git diff --stat` de dos archivos PHP: 21 líneas añadidas y 2 reemplazadas.
- **NO ejecutado:** HTTP autenticado, SQL aislado, concurrencia, seguridad integral, render responsive en navegador, build gate, regresión de consumidores ni revisión visual contra `/admin/seo/auditoria`.

### Pendiente antes de cierre
Auditar esquema e índices y todos los consumidores de etiquetas, probar reglas y persistencia en DB descartable, completar rediseño de la vista, QA responsive 363/390/768/1024/1600 y pruebas reales de autorización/CSRF. Evitar tocar `syncProductTags`/`syncVariantTags` sin coordinación por pertenecer a consumidores compartidos. No hubo commit (integración exclusiva MAIN CHAT).

## Continuación integral — 23/10/2026 (último estado verificable)

**Resultado: implementación funcional y rediseño realizados; QA final PARCIAL.** Esta sección sustituye el estado de tareas pendiente registrado en la intervención preliminar anterior.

### Código y diseño implementados
- Controlador: validación de grupos, nombres (hasta 80 caracteres), slugs normalizados no vacíos (hasta 120), orden e IDs; CSRF existente preservado. Se oculta al usuario el detalle de las excepciones SQL.
- Repositorio: comprueba existencia de registros antes de editar, nombres y slugs duplicados según la unicidad física declarada en `database/migrations/20260315_02_catalog_tags.sql` (índices UNIQUE para grupos y nombres/slugs de etiqueta por grupo); exige grupo existente. Los índices SQL continúan como garantía de concurrencia.
- Sincronización de etiquetas de productos y variantes: validación previa de IDs reales antes de borrar asociaciones y transacción propia cuando no existe transacción del llamador; si la operación se inserta en una transacción externa, respeta al dueño de esa transacción. Errores previos a modificación preservan las relaciones.
- Conteo correcto, independiente de etiquetas en productos y en variantes (`products_count` y `variants_count`); no se altera el comportamiento público de los filtros.
- Rediseño de `app/Views/pages/admin/catalog_tags/index.php` con tarjetas de resumen, formularios etiquetados y accesibles, inputs limitados, tablas, badges de estados, vacíos, enlaces editar y CTA; CSS local nuevo `public_html/assets/css/admin-v3/pages/catalog-tags.css` basado en tokens/DS oficial; desktop dos columnas y una en tablet, campos verticales en móvil. No se modificaron shell/sidebar/tokens/rutas.
- Solo rutas preexistentes: GET index, POST group/save y POST tag/save. **No hay endpoint de eliminación previo**. No se agregaron eliminaciones destructivas: el esquema usa ON DELETE CASCADE sobre relaciones de productos y variantes, por lo que hacerlo requiere un contrato de borrado explícito para evitar pérdidas de asociaciones.

### Evidencia de QA (realmente ejecutada)
- `C:\xampp\php\php.exe scripts\tags_repository_sqlite.php`: **15/15 PASS**, persistencia SQLite privada en memoria, sin acceso a base comercial: creación, duplicados, IDs inexistentes, pertenencia a grupos, asignación a producto/variante, asociaciones preservadas ante IDs inválidos, conteos de uso e inactividad.
- Lint PHP controlador, repositorio y vista: **3/3 PASS** (repetido tras últimas modificaciones).
- `git diff --check`: sin errores reportados (solo advertencias de futuros finales de línea LF/CRLF).
- `C:\xampp\php\php.exe scripts\build\gate.php`: **OK, exit 0**. El gate fue ejecutado antes de los dos últimos ajustes focales en el repositorio y vista; sus validaciones de sintaxis y suite focal SQLite sí fueron repetidas después.
- `scripts/tags_view_fixture.php` emitió una página HTML sintética de 15.170 bytes con los componentes y CSS locales, sin warnings/fatal en ese ensayo. **No es prueba visual en navegador ni HTTP autenticado**.
- `mb_strlen` disponible en PHP local.

### Límites del QA y condición de aprobación
- **Pendiente:** E2E HTTP autenticado con sesión/CSRF originales y base MariaDB privada, ejercicios de carrera de transacciones/UNIQUE, inspección visual real a 363, 390, 768, 1024, 1600 px y regresión completa de formularios de Productos/Variantes y facetas públicas. No atribuir esos ensayos como aprobados.
- La suite SQLite reproduce únicamente tablas relevantes en memoria, no constituye réplica exacta del SQL MariaDB, y la fixture HTML tampoco prueba el shell autenticado.
- No se tocó ninguna base real, migración, servicio de otro propietario ni archivo Git compartido. No hubo commit, push ni cambio de rama. Integración exclusiva del MAIN CHAT.
- Archivos: `app/Controllers/Admin/CatalogTagsController.php`, `app/Repositories/Admin/CatalogTagsRepository.php`, `app/Views/pages/admin/catalog_tags/index.php`, `public_html/assets/css/admin-v3/pages/catalog-tags.css`, `scripts/tags_repository_sqlite.php`, `scripts/tags_view_fixture.php`, `docs/modulos/tags.md`.

**Dictamen:** PARCIAL por pruebas integrales pendientes, no por ausencia de implementación. No declarar cerrado ni aprobar producción.

### Verificación adicional (última pasada)
- Se optimizó `listTags()` para calcular uso en variantes con una subconsulta indexada en lugar de combinar tablas producto×variante (evita multiplicación de filas). Suite SQLite repetida después: **15/15 PASS**, lint repositorio OK.
- Ensayo visual sintético Edge headless (perfil aislado, salida temporal): capturas generadas con exit 0 a 390 y 1024 px (`%TEMP%\\mitags_qa\\tags_390.png`, `tags_1024.png`). La de 1024 presenta tarjetas y formularios sin recortes evidentes; la de 390 está recortada horizontalmente en la captura y **no constituye validación mobile aprobada** porque el navegador headless podría imponer un viewport efectivo mínimo mayor al tamaño pedido. Las capturas son fixtures sin shell ni autenticación, no E2E visual del sitio real. En el HTML de fixture los acentos se transcodificaron al redirigir stdout desde PowerShell; este resultado no demuestra corrupción de la vista PHP original.
- El navegador de cada ensayo se ejecutó con perfil independiente, salida exit 0 y cierre esperado mediante `Start-Process -Wait`. No se alteraron navegadores de usuario.
- **Estado sigue PARCIAL** por QA HTTP y visual autenticado pendiente; no declarar aprobación global.

## Cierre QA definitivo — 23/10/2026

**Estado técnico funcional/visual: CERRADO PARA INTEGRACIÓN SELECTIVA MAIN CHAT.** Sin publicación ni aprobación de producción. Esta sección reemplaza las advertencias de ensayos pendientes en secciones históricas precedentes.

### Entorno de pruebas aislado y trazable

Se implementó el runner `scripts/dev/tags_qa_private.ps1`. Crea y destruye exclusivamente `%TEMP%/mi_tags_private_qa_20261023/`; instala **MariaDB 10.4.32 privada** (puerto **18998**, nombre `mi_tags_private_qa_20261023`, carpeta `data` de ese temporal), clona temporalmente aplicación y dependencias **sin .env original, Git, storage ni uploads**, crea `.env` sintético con credenciales aleatorias en el clon, verifica puerto y `@@datadir`, importa `database/schema.sql`, crea usuarios sintéticos admin/cliente y ejecuta un servidor PHP privado en **18898**. CSRF, login, sesión con regeneración, permisos, controladores, repositorios y layout son originales. Restringe correo a SMTP local inválido, sin proveedores externos. En `finally` cierra exclusivamente los PID privados reconocidos, elimina el directorio propio, libera ambos puertos y limpia variables de entorno. **Última corrida: `Cleanup=True`, proceso exit 0.** Ninguna escritura en MariaDB XAMPP comercial.

**Hallazgos y correcciones durante QA:** al ejecutar el navegador en PHP para Windows con `SCRIPT_NAME=/index.php`, `dirname()` devuelve separador raíz `\\`, originando URLs de CSS defectuosas al montar el servidor desde la raíz. No es un defecto de Tags ni se editó helper compartido. El router de QA `scripts/dev/tags_qa_router.php` reproduce el montaje habitual bajo prefijo `/qa` y entrega archivos de assets propios del clon. CSS respondió HTTP 200 `text/css` y se verificó la pantalla autenticada con layout V3 aplicado. La captura sintética previa de 390 px se corrigió adicionalmente en el módulo: **tabla convertida en tarjetas móviles** con metadatos y acción visible mediante `data-label` y CSS en media query local, sin overrides globales. `scripts/dev/tags_cdp_responsive.py` usa Edge headless con protocolo de depuración, contexto/perfil aislado y cierre `TEARDOWN True`.

### Matriz de pruebas ejecutadas

- **HTTP real sobre aplicación clonada y MariaDB privada: 32/32 PASS** — `scripts/dev/tags_qa_http.py`. Anónimo rechazado en GET/POST; login admin/cliente originales; token CSRF real y alterado rechazado (419); GET del módulo (200); creación, edición y persistencia de grupo y etiqueta; manejo de duplicados, grupo inexistente, edición de ID inexistente y flash de error; cliente sin permisos (403). Sesiones regeneradas por login real, sin stubs de auth/CSRF.
- **Integridad MariaDB y concurrencia: 17/17 PASS** — `scripts/dev/tags_qa_integrity.php`. Producto y variante reales de fixture; sincronización de etiquetas por repositorio original; preservación de asociaciones frente a IDs inválidos; inicio de transacción externa y `ROLLBACK` revierte modificaciones tanto de producto como de variante; índices UNIQUE efectivos de grupo y tag; conteo separado producto/variante; consulta SQL equivalente a UNION de filtros públicos; dos conexiones PDO concurrentes al mismo slug, espera/timeout del segundo escritor, comprobación de UNIQUE tras COMMIT y exactamente un registro ganador.
- **Regresión HTTP focal: 13/13 PASS** — `scripts/dev/tags_qa_consumers.py`. GET `/productos` sin filtro, filtro por grupo/tag y tag ausente, sin excepción visible; login original admin y GET de formulario de edición de Productos, pantalla de Variantes y endpoint JSON de Variantes, todos 200 sin errores fatales. Esto verifica disponibilidad de rutas/consumidores, **no es un E2E exhaustivo de guardado de productos ni una validación de inclusión de cada producto en la grilla pública**.
- **Visual responsive integrada real:** Edge headless y sesión auténtica de administrador en `/admin/catalog-tags`; 363, 390, 768, 1024 y 1600 px. En cada medida se comprobó que `document.documentElement.scrollWidth <= clientWidth`, `av3-master-shell` y `av3-catalog-tags` presentes, y datos reales creados/actualizados visibles. Resultado **5/5 tamaños sin desborde global**; captura de 390 inspeccionada: encabezado, KPI, formulario y tarjetas de grupo legibles, acciones móviles sin recorte. Evidencia local temporal: `%TEMP%/mitags_qa/tags_real_390.png` (no datos comerciales). Perfil Edge de QA eliminado: `TEARDOWN True`.
- **Suite focal SQLite:** `scripts/tags_repository_sqlite.php`, 15/15 PASS, repetida para descartar regresiones básicas. PHP lint de controlador, repositorio, vista y scripts propios: sin errores. `git diff --check`: sin errores (solo aviso de normalización CRLF). **Gate global:** ejecución final documentada en adenda inmediata posterior, según salida real.

### Archivos propios y delimitación
`app/Controllers/Admin/CatalogTagsController.php`; `app/Repositories/Admin/CatalogTagsRepository.php`; `app/Views/pages/admin/catalog_tags/index.php`; `public_html/assets/css/admin-v3/pages/catalog-tags.css`; `scripts/tags_repository_sqlite.php`; `scripts/tags_view_fixture.php`; `scripts/dev/tags_qa_private.ps1`; `scripts/dev/tags_qa_private_fixture.php`; `scripts/dev/tags_qa_http.py`; `scripts/dev/tags_qa_integrity.php`; `scripts/dev/tags_qa_router.php`; `scripts/dev/tags_cdp_responsive.py`; `scripts/dev/tags_qa_consumers.py`; `docs/modulos/tags.md`.

No se editaron archivos compartidos de Productos, Variantes, Facetas, shell, rutas ni tokens, ni se ejecutaron migraciones comerciales. No se agregó endpoint DELETE: las rutas existentes tienen solo GET y los dos POST de guardar, y el esquema usa ON DELETE CASCADE para relaciones; no se introduce un borrado destructivo no contemplado en contratos. **Sin commit ni push**, integración Git exclusiva MAIN CHAT.

**Dictamen:** comportamiento probado y listo para integración selectiva del MAIN CHAT. El QA no implica despliegue a producción ni homologación externa.

### Resultado gate global final
- `C:\\xampp\\php\\php.exe scripts\\build\\gate.php` ejecutado después de todas las correcciones del módulo: **`[gate] OK`, exit code 0**, runtime 72,20 s; analizados 904 archivos PHP y 120 JavaScript, incluidos contratos visuales Admin V3, Productos/Checkout/Importaciones/Marcas/Atributos. No se modificó el gate.
- Revalidación focal final previa: SQLite **15/15**, lint PHP de controlador, repositorio, vista y router/ensayo propio sin errores; `git diff --check` sin errores, advertencias no bloqueantes de fin de línea.
- **Cierre QA final: CERRADO / LISTO PARA INTEGRACIÓN MAIN CHAT**. No hay bloqueo técnico confirmado. Las pruebas de consumidores incluyen HTTP 200 y ausencia de errores fatales, no garantizan un E2E comercial completo de guardado de Productos/Variantes ni producción.
