# Proveedores — mandato integral Admin V3 (23/10/2026)

Estado: asignado a especialista, MAIN CHAT coordina e integra.

## Inventario (confirmar con código)
/admin/proveedores (index/new/edit/save/delete); ProveedoresController; app/Views/pages/admin/proveedores (2 vistas).

## Alcance
Listado/filtros, alta-edición, contactos/CUIT, duplicados, asociaciones con compras, baja con referencias, permisos, CSRF y responsive.

## Reglas de ejecución
Leer docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/COORDINACION.md, docs/GUIA_VISUAL_ADMIN_V3.md, docs/ARQUITECTURA_ADMIN_V3.md. Una auditoría inicial completa y una ventana grande de implementación: reparar lógica, seguridad, SQL/servicios y todas las vistas; rediseño HTML/CSS Admin V3 con /admin/seo/auditoria como referencia (cards, botones, jerarquía, peso de fuente, tablas, responsive). Reutilizar tokens/componentes, CSS acotado al módulo; sin Tailwind ni overrides globales. No modificar shell/sidebar, CSS compartido ni archivos de otros especialistas sin reserva MAIN CHAT. Probar en DB aislada las operaciones destructivas; verificar desktop/tablet/móvil. Registrar QA real y riesgos, sin microventanas.

## Primer prompt para el especialista
MiTiendaPesca — especialista Proveedores. Leé docs/modulos/proveedores.md y toda la documentación obligatoria. Auditá UNA vez el módulo entero con sus vistas derivadas; implementá integralmente calidad de código, seguridad, HTML/CSS Admin V3 con referencia visual Auditoría SEO y QA responsive/funcional. Sin microventanas ni solicitudes de autorización por cada cambio propio. Actualizá este documento con evidencia. **Terminá en una única respuesta breve:** MÓDULO | ESTADO (CERRADO/PARCIAL) | CAMBIOS | QA con resultados | ARCHIVOS/COMMIT | BLOQUEO + SOLUCIÓN. Ese resumen será auditado por MAIN CHAT.


## Ejecución especialista — 23/10/2026

### Implementación aplicada
- Controlador: filtros `q`, `status=active|inactive|all`, `per=25|50`, `page` y cálculo de paginación con totales reales. Verificación explícita CSRF en guardar y desactivar, manejo de excepción en baja, control de ID inexistente en edición.
- Repositorio: `count()`, búsqueda parametrizada con placeholders independientes de PDO, orden estable por nombre+ID, `LIMIT/OFFSET` enlazados como enteros. La baja lógica pasa a ser idempotente y devuelve si cambió efectivamente el registro.
- Servicio: validación de longitud de nombre/email/teléfono, allowlist de condición IVA/estado, conserva regla fiscal de CUIT, no altera compras ni elimina físicamente proveedores.
- UI: listado con tabla desktop y filas móviles, estado explícito, filtros y controles de paginación; acciones de editar/desactivar con CSRF; formulario con campos identificados, validación y design system Admin V3; CSS de composición exclusiva del módulo.
- No se modificaron rutas, shell, sidebar, componentes globales, esquema SQL ni código ajeno.

### Evidencia real de QA
- `scripts/dev/proveedores_smoke_private.php`: **19/19 PASS** en PDO SQLite `:memory:` (incluye validaciones, alta, modificación, unicidad de CUIT, búsqueda, baja lógica, segunda baja idempotente, filtros activos/inactivos/todos, reactivación). El ensayo usa una tabla descartable y `NOW()` simulado. **No acredita MariaDB real ni HTTP**.
- `php -l` aprobado para controlador, servicio, repositorio, listado, formulario y parcial de acciones.
- Gate global invocado al cierre; consultar resultado efectivo antes de integrar.
- **Pendiente:** QA HTTP autenticado con sesión y CSRF original sobre MariaDB privado; flujos integrados con compras; prueba browser responsive 363/390/768/1024/1600. Sin estas verificaciones no declarar aprobación integral ni de producción.

### Archivos del módulo modificados
`app/Controllers/Admin/ProveedoresController.php`
`app/Repositories/Suppliers/ProveedorRepository.php`
`app/Services/Suppliers/ProveedorService.php`
`app/Views/pages/admin/proveedores/index.php`
`app/Views/pages/admin/proveedores/form.php`
`app/Views/pages/admin/proveedores/row_actions.php` (nuevo)
`public_html/assets/css/admin-v3/pages/proveedores.css` (nuevo)
`scripts/dev/proveedores_smoke_private.php` (nuevo)
`docs/modulos/proveedores.md`

**Estado: PARCIAL hasta QA HTTP MariaDB y browser responsive real.** Sin commits, reservados al MAIN CHAT.


### Seguimiento QA del gate
- Primer gate: 2 fallas reales detectadas; `admin_page_header_contract.php` identificó un parcial de acciones tratado incorrectamente como página y `gate.php` detectó closure en la vista de listado.
- Reparación: el parcial pasó a `app/Views/partials/admin/proveedores/row_actions.php` y el controlador calcula `prevUrl/nextUrl`; `admin_page_header_contract.php` volvió a pasar (67 vistas).
- 19/19 smoke privado reejecutado tras ambas reparaciones y 6 archivos PHP del módulo con lint aprobado; `git diff --check` sin fallas.
- Nota sobre inventario: la referencia anterior a `app/Views/pages/admin/proveedores/row_actions.php` fue reemplazada por `app/Views/partials/admin/proveedores/row_actions.php`.

### Resultado del segundo gate global
- `C:\xampp\php\php.exe scripts/build/gate.php`: **[gate] OK**, registrado en `%TEMP%\proveedores_gate_final.log`. Se corrigieron las dos fallas detectadas por el primer intento antes de reejecutar.
- El gate es una verificación técnica/contratos, no sustituye el pendiente de HTTP real MariaDB, permisos y navegador responsive.

## Cierre QA final — 23/10/2026, especialista Proveedores

**Dictamen técnico del dominio: CERRADO para integración selectiva**, con bloqueo transversal ajeno detallado más abajo. Esta sección prevalece sobre las notas preliminares del mismo documento.

### 1. HTTP autenticado y MariaDB real (36/36)
- Suite reproducible `scripts/dev/proveedores_http_mariadb_private.py` con servidor PHP real, bootstrap auténtico, sesiones y CSRF originales, usuarios sintéticos admin/customer y MariaDB descartable **independiente de XAMPP** en puerto 18979, clon del proyecto sin `.env` comercial, datadir verificado por SQL. HTTP en 18879.
- Comprobado: anonimato GET/POST denegado; formulario de login y CSRF nativos; login admin; autorización de listado; alta; edición con persistencia; validaciones IVA; unicidad de CUIT; CSRF inválido en save/delete; búsqueda CUIT y filtros; paginación real con 27 proveedores sobre páginas 1 y 2 y normalización de página 999; listado inactivo; baja lógica idempotente; reactivación; login cliente; usuario customer no puede leer/escribir módulo Admin.
- Las 26 filas adicionales de paginación se retiraron antes de la inspección browser; totalidad de fixtures descartada después del ensayo.
- Integración Compras: se insertó factura de compra de ensayo en `purchase_invoices` con `supplier_id` auténtico y FK a `suppliers`; verificada persistencia de la relación al desactivar, repetir la desactivación y reactivar. Sin modificar código del módulo Compras ni Facturación.
- **Resultado final real:** `PROVEEDORES_AUTHENTIC_HTTP_MARIADB_PASS 36/36`, exit code 0. Teardown verificó cierre de procesos MariaDB+PHP privados y eliminación del datadir/copia privada. No se tocó XAMPP SQL ni datos comerciales.

### 2. Responsive — Edge headless real (10/10)
- `scripts/dev/proveedores_browser_private.py`, Microsoft Edge local Chromium headless controlado con Playwright instalado en carpeta temporal privada. Cookie de **la misma sesión admin real** de la suite HTTP, sin simular autorización ni HTML.
- Páginas: `/admin/proveedores` y `/admin/proveedores/new`, cada una en **363, 390, 768, 1024 y 1600 px**. Resultado: **10/10** HTTP 200, contenido del módulo visible, controles visibles y ausencia de scroll horizontal en documento (scrollWidth <= viewport). Capturas generadas en `%TEMP%\mi_proveedores_responsive_evidence_20261023\proveedores_{listado|formulario}_{ancho}.png` más `result.json`.
- Inspección visual real de capturas: tarjeta de proveedor y acciones móviles, formulario en columna, distribución de tabla/filtros desktop; estilos globales presentes.
- Nota relevante: el servidor HTTP de ensayo basado en `php -S` **no resuelve las rutas de assets duplicadas en slash** que genera el bootstrap/URL en el contexto del router privado. El navegador normalizó **solamente las peticiones de assets del host QA** (`//assets/` -> `/assets/`) y el router privado los entregó con sus MIME correctos, sin modificar helpers, head, sidebar o CSS compartido del proyecto. La prueba final sí cargó los estilos originales del administrador. No se extrapola esta particularidad del servidor de pruebas a un servidor Apache de producción.
- Browser cerrado en `finally`; carpetas de sesiones/clon eliminadas y capturas sintéticas conservadas solo en %TEMP%.

### 3. Correcciones derivadas de QA
- Servicio y formulario alineados con los tamaños físicos de MariaDB: `email <=190`, `telefono <=64`; CUIT único y validaciones del dominio conservadas.
- CSS de módulo `public_html/assets/css/admin-v3/pages/proveedores.css`: selectores de tabla móvil/desktop con especificidad suficiente frente al design system global, sin `!important` ni cambios en componentes compartidos.
- Vistas: se mantuvo **un solo archivo CSS del módulo enlazado mediante `asset()`**. Los intentos intermedios de estilos embebidos se descartaron; no forman parte del código final.
- QA final sintético anterior de SQLite: 19/19; reemplazado como evidencia principal por MariaDB/HTTP real y browser.
- Archivos nuevos de QA: `scripts/dev/proveedores_http_mariadb_private.py` y `scripts/dev/proveedores_browser_private.py`; ejecución segura exclusivamente privada, guardas de puerto y datadir, teardown incluso en fallo.

### 4. Gates y coordinación
- PHP lint de dominio aprobado en intervención anterior; `git diff --check` sin errores de formato en archivos Proveedores.
- **Gate global del árbol compartido NO APROBADO por incidencia externa**: `scripts/audit/encoding_scan.php --bom-only` señaló exactamente `public_html/assets/css/admin-v3/pages/audit.css` con UTF-8 BOM (pertenece al módulo Auditoría de otro especialista). Evidencia: `%TEMP%\proveedores_final_gate_20261023.log`, `[gate] ERROR (1): UTF-8 sin BOM (codigo 2)`. Archivos propios comprobados sin BOM. **No intervenir audit.css ni declarar gate global aprobado**; MAIN CHAT debe coordinar con el propietario de ese archivo su corrección y repetir gate tras integrar.
- Sin commits/push: reservados al MAIN CHAT. Nada de SQL comercial, Facturación, shell, sidebar, rutas, migraciones ni otros especialistas alterado.

**Estado recomendado al MAIN CHAT:** Proveedores técnicamente cerrado y verificable para integración selectiva; gate global transversal condicionado por BOM ajeno. La aprobación estética final del propietario continúa separada de QA técnico.
