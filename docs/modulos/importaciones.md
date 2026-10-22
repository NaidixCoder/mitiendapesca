# MiTiendaPesca — Importaciones Admin V3 · informe vivo del módulo

> Inicio de coordinación: 2026-10-21 · Responsable de las implementaciones: **nuevo chat especializado de Importaciones**.
> **Estado:** VENTANA 1B **IMPLEMENTADA Y VALIDADA TÉCNICAMENTE EN ENTORNO AUTENTICADO AISLADO**, pendiente de aprobación visual del chat principal y del propietario. **P0 real de conflicto de rollback documentado**, sin autorización de cambio backend. V2/V3 pendientes.

## Documento rector

Leer primero [Auditoría y plan](../PLAN_IMPORTACIONES_ADMIN_V3.md), además de `docs/COORDINACION.md` y `docs/GUIA_VISUAL_ADMIN_V3.md`. La auditoría inicial corresponde al chat principal; el especialista **no debe volver a escribirla completa**. Su primera tarea es ejecutar la Ventana 1 autorizada y mantener este documento actualizado.

## Situación inicial verificada

- Pantallas: `/admin/imports` (carga/historial), `POST /admin/imports/preview` (dry-run), `POST /admin/imports/apply`, `/admin/imports/show?id=...` (detalle/auditoría), `POST /admin/imports/rollback`, `/admin/imports/template` y `/admin/imports/export`; rutas antiguas redirigen o delegan.
- Solo CSV implementado en endpoint (máximo 10MB), aunque la vista inicial dice «CSV o Excel». El CSV oficial usa separador `;` y template/export actuales de 32 encabezados. No alterar.
- Arquitectura con `ImportsController.php` 787 líneas, `ImportService.php` 586, importadores especializados y repositorios; ViewModels y presenter; JS `preview.js`. Sin CSS de página `imports.css`.
- Problemas de UI y coherencia: 67 caracteres literales U+FFFD en cuatro parciales index, clases heredadas y 3 superficies descoordinadas; previews, tablas y badges sin patrón Admin V3.
- Riesgos funcionales catalogados en plan rector I05–I12; **ninguno implementado ni probado E2E en esta auditoría**.
- `php -l` exitoso en 7 archivos PHP focales. GET sin sesión devolvió HTTP 401; no hubo navegador autenticado, ejecución de importación, modificación DB ni gate global.
- Baseline visual exigido: Dashboard y Pedidos, además de `docs/GUIA_VISUAL_ADMIN_V3.md`. Atributos y Marcas tienen antecedentes de resultados técnicos correctos pero visuales no aprobados; **no copiar sus pantallas como patrón por defecto**.

## Alcance operativo de VENTANA 1

El nuevo chat implementará composición real mobile-first, tres estados (carga / preview / detalle), accesibilidad, normalización de warnings, corrección de textos, CSS único `av3-imports__*` y contrato específico sin tocar backend. Ver [sección 5](../PLAN_IMPORTACIONES_ADMIN_V3.md).

**Reservas:** vistas y parciales Importaciones, JS `admin/imports/preview.js`, CSS nuevo exclusivo y pruebas del módulo. Presenter/ViewModels propios solo para ajustes estrictamente presentacionales con justificación. No Controller/motor/importadores/repositorios, ni infraestructura/globales sin segunda ventana.

## Bitácora de implementación — a completar por nuevo chat

| Etapa | Estado | Evidencia exigida |
|---|---|---|
| Ventana 1: HTML/CSS/UX | **Implementada; aceptación visual pendiente** | Capturas sintéticas de 363/390/768/1024/1600, PHP/JS/contrato focal; falta navegador autenticado |
| Ventana 2: refactor no semántico | **No autorizada** | Contrato export/import, compatibilidad y rendimiento |
| Ventana 3: integridad y rollback | **No autorizada** | MySQL descartable, pruebas de fallos y decisión comercial previa |
| Ventana 4: QA final | **No autorizada** | HTTP autenticado seguro, diseño aceptado por propietario |

## Entrega requerida al nuevo chat

Al cerrar cada ventana: enumerar archivos propios modificados; explicar reglas y contratos preservados, pruebas ejecutadas **y** no ejecutadas, capturas real/sintético, errores, bloqueos, resultados de gate, cumplimiento del protocolo de navegadores y pendientes. **No hacer commits/push** ni declarar aceptación visual por pasar tests.

## Bitácora — Ventana 1 ejecutada (2026-10-21)

### Implementación efectiva

- **A · Carga:** un único panel principal; selector CSV visible con plantilla/exportación secundarios, grupos lógicos de creación automática y campos de actualización, cálculo opcional expandible, acción «Analizar archivo». Se mantienen exactamente los `name`/checkboxes/valores predeterminados, CSRF, `job_type`, `delimiter=auto`, acción POST de preview y archivo CSV de 10 MB. No se agregaron campos de separador/slug ni un paso adicional.
- **B · Preview:** un panel «Vista previa de seguridad», resumen compacto y advertencias, muestra expandible de marcas/categorías, detalle por fila dentro de la misma card (disclosure HTML nativo), filtros Todos/Errores/Avisos/OK/Con cambios, búsqueda por SKU y SKU padre, contador accesible y estado sin coincidencias. Conserva `existing_file`, `opt_b64`, CSRF, frase opcional `confirm_phrase` y el bloqueo de aplicación cuando hay errores.
- **C · Detalle:** estado, operador, origen, fechas, recuentos, resumen de cambios, warnings, errores, seguridad LIFO, incompatibilidades de snapshot y conflicto de edición. Las diferencias `campo: anterior → nuevo` se leen como bloques dentro de cada fila, incluso en 363 px. No se relaja `can_rollback` ni el formulario de reversión.
- **Coherencia:** corregidos los caracteres dañados U+FFFD en los 4 parciales identificados (por reescritura contextual), las descripciones ya no prometen Excel/XLSX, y la presentación entiende `warning` y `warn` como el mismo estado sin mutar el resultado del importador. Los textos del costo aclaran que es neto sin IVA.
- **Visual:** nueva hoja única `public_html/assets/css/admin-v3/pages/imports.css`, namespace `av3-imports__*`, DS `av3-ui-*`, tipografía/paleta/tokens del shell. Sin Tailwind nuevo, `!important`, bridges ni edición de CSS común. Responsive mobile-first: la misma tabla pasa a tarjetas/fila con etiquetas de campo usando **container queries**, sin duplicar datos ni perder notas.
- **Advertencia de integridad preservada:** importaciones terminadas con errores podrían haber aplicado cambios parciales según el motor vigente (I05). El detalle no se presenta como una mera validación cuando hay errores; la semántica de ejecución queda para Ventana 3.

### Archivos propios editados (sin modificar otros módulos)

1. `app/Views/pages/admin/imports/index.php`, `view.php`.
2. `app/Views/partials/admin/imports/index/{header,flashes,upload_form,preflight,history}.php`.
3. `app/Views/partials/admin/imports/view/{header,notices,summary,applied_changes,warnings,errors,rows}.php`.
4. `public_html/assets/css/admin-v3/pages/imports.css` (**nuevo**, se carga exactamente una vez por vista).
5. `public_html/assets/js/admin/imports/preview.js` (filtros, búsqueda, contador y `aria-pressed`).
6. `app/Services/Admin/Imports/ViewModels/ImportsIndexViewModel.php` (solo normalización `warning/warn` en contadores y filas, clases legacy de badges reemplazadas para ese estado).
7. `app/Services/Admin/Imports/ImportsViewPresenterService.php` (solo normalización del estado y exposición semántica `level` para detalle; conversión de notas a diferencias sin cambios).
8. `scripts/build/imports_admin_contract.php` (**nuevo**, pruebas de solo lectura, fixture pura sin DB).
9. `docs/modulos/importaciones.md` (este informe).

**No tocados:** `ImportsController.php`, `app/Services/Import/**`, repositorios, base/esquema, rutas, `head.php`, `app.js`, shell/tokens/componentes globales, otros módulos, Git/ramas.

### Verificaciones reales

- **PHP lint:** todos los PHP de ambas páginas, 12 parciales, Index/View ViewModels y presenter: OK.
- **JS lint:** `node --check public_html/assets/js/admin/imports/preview.js`: OK.
- **Contrato focal:** `C:\xampp\php\php.exe scripts/build/imports_admin_contract.php` → **68 verificaciones OK**. Incluye campos POST y valores del formulario, CSRF, rutas/acciones, CSV, hoja CSS, aviso y warning legado, contadores y diferencias de costo antes/después.
- **Capturas sintéticas, NO sesión autenticada:** Edge headless aislado sobre HTML generado mediante PHP real de parciales con datos **sintéticos**, tamaños exactos **363/390/768/1024/1600 px** en las tres superficies (**15 escenarios**). Se utilizó CDP para fijar `window.innerWidth` exacto, sin depender de la limitación de captura por `--window-size`. **En los 15 casos `documentElement.scrollWidth <= clientWidth` y sin elementos que salieran del viewport**.
- **Interacción de filtros en navegador:** filtro inicial Errores, pestaña Avisos (`warning`), búsqueda sin coincidencias, estado vacío, reinicio a Todos, `aria-pressed` y número de filas: OK. **Teclado:** disclosure nativo de cálculo de precios e historial se abren con Enter desde su `summary` enfocado (CDP con eventos de teclado reales): OK. No se efectuaron POST.
- **Evidencia externa (fuera del repo):** `C:\Users\matii\AppData\Local\Temp\mi-pesca-imports-v1-evidence\{upload,preview,show}-{363,390,768,1024,1600}-cdp.png` y `viewport-report.json`. Estas son maquetas renderizadas con parciales reales, no capturas autenticadas de producción. Se conservan como referencia para la revisión visual.
- **Teardown:** navegador Edge de prueba, perfil CDP y contextos propios cerrados; sin ventanas visibles ni perfiles residuales. Se eliminaron fixtures PHP/HTML y scripts temporales (quedaron solo 15 capturas CDP + informe JSON, fuera del repo). XAMPP, Firefox personal y procesos de los otros chats no fueron alterados.
- **Control de cambios:** `git diff --check` en archivos propios: sin problemas; `git status --short` revisado, se conservaron intactas las modificaciones concurrentes de otros chats. Sin commit, push ni cambio de rama.

### Pendientes y condiciones de cierre

- **Pendiente revisión visual del propietario**: evaluar densidad, jerarquía y legibilidad contra Dashboard y Pedidos en sesión real. El resultado técnico no equivale a aprobación visual.
- **Pendiente HTTP autenticado y E2E seguro:** ausencia de sesión de administrador propia y la prohibición de escritura sobre la base habitual impiden afirmar pruebas reales de selección/upload→preview→apply, reversión LIFO y colisiones de stock/precios. No se ejecutaron Apply ni Rollback, no hubo cambios de DB.
- **Ventanas 2/3/4 no autorizadas:** se preservan los hallazgos I05/I06/I07/I08/I09/I10/I12 del plan rector, que requieren base sintética descartable y nueva reserva. No resolverlos desde esta ventana.
- **Gate global:** `C:\xampp\php\php.exe scripts/build/gate.php` → **OK**, finalizó con `[gate] OK`. Se verificaron 831 PHP, 120 JS y contratos de shell, Dashboard, Pedidos, SEO, CSS, regresión y UTF-8; sin escrituras de base de datos ni modificaciones a otros módulos.


## Bitácora — Ventana 1B (QA autenticado y ajuste estético, 2026-10-21)

**Estado:** implementación V1B ejecutada sobre archivos exclusivos de Importaciones. **Aceptación estética final pendiente del chat principal y del propietario.** Ninguna autorización para V2/V3.

### Entorno de pruebas: aplicación REAL, pero aislada (no la base habitual)

- Copia privada del código en `C:\Users\matii\AppData\Local\Temp\mi_imports_v1b_isolated\site`, fuera del árbol XAMPP compartido; almacenamiento y variables de entorno propios. El proyecto original se utilizó solo para copiar código y consultar la estructura SQL (DDL), **sin importar datos reales**.
- MySQL temporal `mi_import_v1b_fec05146`: **65 tablas** generadas por DDL, **sin datos comerciales**. Cuenta admin sintética con contraseña aleatoria, sesión de navegador exclusiva, login auténtico con CSRF/antibot. Ejecutado bajo PHP 8.2.12, servidor de prueba **127.0.0.1:18766**, y Edge/Chromium headless con perfil CDP independiente.
- Ajuste exclusivo de la copia privada: el helper base_url del entorno PHP -S sobre Windows requería normalización de `dirname('/index.php')` a `/`, porque retornaba barra invertida y rompía los assets/redirects. **No se editó** el helper global del proyecto original, ni rutas/entrypoint compartidos. Las capturas finales corresponden a la aplicación integral con **sidebar + Page Header + assets compartidos efectivos**, no a HTML ficticio.
- Las pruebas de navegación cubrieron los tamaños **363, 390, 768, 1024 y 1600 px** para Dashboard, Pedidos y las tres superficies de Importaciones (**25 capturas por pasada**, antes y después para comparación; 50 capturas). El DOM confirmó en los 25 casos de cada pasada `scrollWidth <= clientWidth`, Page Header/sidebar reales y CSS de aplicación (18 hojas Admin V3 y 19 en Importaciones). No se verificó sesión comercial real; sí sesión sintética administrativa contra la aplicación completa.

### Pruebas HTTP y de persistencia realizadas exclusivamente en la BD aislada

| Caso | Resultado real |
|---|---|
| Login con usuario admin sintético, sesión y cookie exclusiva | OK |
| GET `/admin`, `/admin/orders`, `/admin/imports` | HTTP 200; comparación visual real dentro del shell |
| GET plantilla CSV | UTF-8 con BOM, **32 columnas** en orden vigente, separador `;`; OK |
| CSV inválido sin SKU ni código interno | Preview HTTP 200, error por fila, **sin acción Apply** |
| CSV válido: producto con Ñ, marca/categoría/proveedor nuevos y proveedor sin CUIT | Preview HTTP 200 con botón Apply |
| Preview sin efectos comerciales | Antes/después idénticos en products, variants, brands, categories, suppliers e import_jobs; OK |
| Apply con POST/CSRF/`existing_file`/`opt_b64` | HTTP 302 a detalle; 1 producto + 1 marca + 1 categoría + 1 proveedor creados (todos sintéticos) |
| Show con status, actor, cambios y control Revertir | HTTP 200, datos y notas reales del importador |
| Segundo Apply + LIFO de UI | Import anterior sin botón Revertir; el nuevo import sí permite reversión |
| Revertir la última importación sintética | HTTP 302; registro marcado `reverted`, producto nuevo eliminado; conteos restaurados |
| Modificación manual de producto tras otro Apply y luego Rollback | **FALLA P0**: rollback NO bloqueado por conflicto; ver abajo |
| Preview con CSRF inválido | **HTTP 419**, sin cambios de catálogo |
| Extensión .xlsx no permitida | POST rechazado/redirigido, sin cambios comerciales |
| CSV superior a 10 MB | POST rechazado/redirigido, sin cambios comerciales |
| JS en preview con sesión real | Filtro Errores/Restaurar Todos, búsqueda por SKU, estado vacío y `aria-pressed` funcionales |
| Capturas responsive reales con shell | 363/390/768/1024/1600, sin overflow horizontal en las tres superficies |
| Contrato PHP/JS del módulo | **71/71 PASS**, incluye representación de metadatos costo/precio y ubicación nueva de formulario de reversión |

**Hallazgo P0 reproducido — revertir puede destruir ediciones manuales posteriores cuando falta `updated_at`:** la tabla real `products` de la estructura copiada no posee columna `updated_at`. `ImportRevertRepository::getUpdatedAt('product',id)` devuelve null cuando la columna no existe; `ImportRevertService::detectConflicts()` omite así el conflicto. Secuencia sintética: importar producto con precio 2000; aplicar actualización a 2480; modificar manualmente a **2499** en MySQL aislado; invocar la acción Rollback. **El servidor devolvió 302 sin bloquear**, el job quedó `reverted` en vez de `blocked_conflict` y el precio final volvió a **2000** (se perdió la edición posterior). Hallazgo comprobado en ejecución; **backend NO modificado**, requiere autorización y pruebas de política de integridad (Ventana 3 o hotfix acordado por principal). No autorizar pruebas destructivas en base habitual.

**Hallazgo P1 de métricas:** el motor registró importaciones terminadas con `total_rows=1`, `ok_rows=0`, `err_rows=0` y creaciones reales. V1B etiqueta el valor conservado como **«OK reportadas»**, sin inventar un conteo, pero la semántica del contador deberá aclararse en backend y pruebas de reporte (fase autorizada posterior).

### Mejoras visuales V1B implementadas en el proyecto original

- `imports.css`: se eliminó el estiramiento forzado del tercer fieldset **Variantes** en escritorio (`align-items:start`); cada grupo ocupa la altura realmente necesaria. Se refinaron exclusivamente estilos de metadatos/diferencias y acción de reversión contextual; no se añadió nueva hoja.
- `view/header.php` y `view/summary.php`: la acción destructiva **Revertir** (con CSRF, `data-confirm`, `id` y mismo POST) se trasladó del Page Header al resumen operativo, para que el encabezado móvil mantenga su jerarquía. Se conserva «Volver a importaciones» en Page Header.
- `view/applied_changes.php`: se muestran solo las categorías de operaciones con recuentos positivos; si están todas en cero, vacío explícito. Las opciones usadas se pliegan en un disclosure nativo con cantidad, reduciendo scroll móvil y priorizando cambios reales.
- `ImportsViewPresenterService.php` + `view/rows.php`: metadatos entregados por el motor como `costo_sin_iva: ... · iva: ... · precio_ars: ...` ahora se descomponen en pares visibles de **Costo neto, IVA, Costo con IVA, Precio y Precio de lista**, preservando todos los valores originales y las diferencias `antes → después` existentes. Solo transformación de presentación.
- `scripts/build/imports_admin_contract.php`: actualizado para el formulario de reversión en summary y tres comprobaciones de metadatos; de **68 a 71 verificaciones**.
- Sin cambios a `ImportsController.php`, `app/Services/Import/**`, repositorios, rutas, DB comercial, schema original, head, app.js, shell ni CSS globales. Sin commits ni push.

### Evidencias visuales y pendientes

- Capturas **50 imágenes auténticas aisladas** y dos informes JSON de viewport: `C:\Users\matii\AppData\Local\Temp\mi-pesca-imports-v1b-evidence\antes` (25) y `...\despues` (25), almacenados fuera del repo. Edge headless CDP con aplicación real y administrador/BD sintéticos. Se revisaron visualmente escritorio y móvil: grupo de variantes y espacios, CTA revert, resúmenes, metadatos, tablas y fichas móviles.
- **Gate global tras V1B:** `C:\xampp\php\php.exe scripts/build/gate.php` → **OK**, 831 PHP, 120 JS y contratos transversales. `scripts/build/imports_admin_contract.php` → **71/71 OK**.
- El flujo auténtico **no** ha certificado permisos de otros roles, reintentos concurrentes, CSV con todas las variantes, carga y exportación roundtrip, compatibilidad con snapshots legacy ni conflictos con `product_variants`; estas son pruebas adicionales que requieren posterior autorización.
- El motor heredado mantiene otros riesgos I05/I06/I07/I08/I09/I10/I12; no confundir un happy-path autenticado con aprobación operativa o de producción.
- **Pendiente revisión visual final del propietario y chat principal.** V2/V3 siguen sin autorización.


### Cierre operativo y liberación de recursos V1B

- **MySQL descartable** `mi_import_v1b_fec05146` destruida y ausencia en `information_schema.SCHEMATA` comprobada. No se modificó la base habitual.
- **Servidor PHP propio** `127.0.0.1:18766` detenido; puerto sin proceso LISTENING. Se destruyeron la copia privada de código, sus credenciales de QA, sesión, almacenamiento de prueba y scripts auxiliares.
- **Edge headless**: sin perfiles `imports_v1b_edge_*` remanentes ni navegadores propios abiertos. Firefox personal, XAMPP y sesiones ajenas permanecieron intactos.
- **Evidencia conservada fuera del repo:** `C:\Users\matii\AppData\Local\Temp\mi-pesca-imports-v1b-evidence\antes` (25 PNG + informe) y `...\despues` (25 PNG + informe). Corresponden a aplicaciones completas autenticadas sobre datos sintéticos; no a navegación en la base habitual.
- **Salida:** Gate global OK, contrato 71/71 OK, sin commits/push/cambio de rama. Ventana 1B detenida para revisión visual del propietario; el hallazgo P0 de rollback requiere decisión del principal y no permite asumir que el importador está listo para producción.

## Bitácora — Ventana 1C · Hotfix P0 de reversión (2026-10-21)

**Estado:** corrección focal implementada y comprobada en MySQL descartable. Pendiente revisión y autorización del chat principal. Diseño V1B continúa a la espera de aprobación del propietario. V2/V3 generales NO autorizadas.

### Reproducción ANTES de los cambios

Se ejecutó `C:\xampp\php\php.exe scripts/dev/imports_rollback_integrity_isolated.php --baseline` con BD propia aleatoria `mi_import_v1c_*` y **65 tablas copiadas solo como estructura**, sin datos comerciales. Dos CSV sintéticos sobre producto `V1C-001`: primera importación con precio 2000, segunda con precio 2480, luego SQL de edición manual a **2499**. Con el motor v2: `revertJob()` devolvió éxito, el job quedó `reverted` y el precio terminó **2000**. La BD de reproducción fue destruida.

### Solución acotada

1. `app/Repositories/Imports/ImportRevertRepository.php` añade `fingerprint(entity,id,lock)`: tabla y clave primaria determinadas por lista blanca para **product, variant, inventory_product, inventory_variant, brand, category, supplier, attribute, attribute_value**. Se calcula SHA-256 del **registro completo SQL** con claves ordenadas, incluyendo columnas nulas, precios, costos, stocks, variantes, atributos y auxiliares, sin convertir decimales a float ni redondear. Cuando `lock=true` exige transacción y usa `SELECT * ... FOR UPDATE`. Añade locks de job y consulta LIFO bajo transacción. **Refuerzo de borrado**: `dependentFingerprint()` verifica recursivamente el árbol de relaciones FK conocido (productos→variantes→valores de atributos/inventario, marcas, categorías, proveedores y atributos) antes de eliminar altas, y bloquea si aparecen FKs desconocidas, filas nuevas o modificaciones en dependencias (incluidos cascades). Cada consulta de dependencias toma locks en el rollback.
2. `app/Services/Import/ImportService.php` exclusivamente en la sección de registro de rollback: **snapshot v3** versionado `{version:3,job_id,complete,ops:[{entity,action,pk,before?,after_sha256,after_rel_sha256?}]}`. Para acciones `create` se exige además `after_rel_sha256` del árbol FK al final de la importación. Se leen huellas de **postestado final dentro de la transacción del apply, antes de commit**, de cada entidad documentada. Colapsa rollback ops duplicadas por entidad/id preservando el estado `before` original; si una operación es desconocida, faltan datos o no se puede leer, registra `complete=false` y razón para **inhabilitar** el revert. No cambia CSV, impuestos, cálculos, precio, stock ni importadores. Continúa el problema independiente I06 de persistencia de archivo post-COMMIT sin garantía duradera; si falta archivo, la reversión se rechaza.
3. `app/Services/Import/ImportRevertService.php`: `isSnapshotSafe()` rechaza todo v1/v2, v3 sin `complete=true`, ops vacías, ids/acciones inválidos, updates sin `before`, hashes ausentes o duplicados. Durante un **único BEGIN/COMMIT**: bloquea por LIFO la última importación pendiente (incluidos estados `blocked_*`), bloquea job, bloquea y compara **TODAS** las filas objetivo en orden estable mediante `FOR UPDATE`. Cualquier diferencia, registro ausente o imposibilidad de verificación bloquea **ANTES** de la primera escritura. Si falla RESTORE o DELETE después de la validación, **ROLLBACK completo**, sin restauraciones parciales ni inactivaciones residuales. Estados `blocked_conflict` / `blocked_not_last` y auditoría persistidos; las reversiones simultáneas se serializan y el segundo llamado obtiene `already_reverted`.
4. `app/Services/Admin/Imports/ImportsViewPresenterService.php` verifica el snapshot v3 completo y el job_id, y calcula el último job pendiente desde DB, sin confiar en el cálculo v2 legado del controlador. Deshabilita la acción en detalle e historial si el snapshot no es confiable o el job no es último; no saltea bloqueos de un job más nuevo. `app/Views/partials/admin/imports/view/notices.php` informa incompatibilidad o ausencia con necesidad de recuperación manual supervisada, sin rediseñar HTML/CSS.
5. **No se alteraron snapshots antiguos v1/v2**, ni se convierten silenciosamente. La opción automática desaparece para ellos, manteniendo la información histórica. No se modificó `ImportsController`, importadores, categorías, catálogo, rutas, esquemas, migrations, CSS, JS ni DB comercial.

### Pruebas reales V1C (solo DB descartables)

- **Pre-fix:** caso P0 precio 2000 → 2480 → edición manual 2499 → revert incorrecto 2000: **reproducido**.
- **Post-fix:** mismo caso devuelve `ok=false`, `blocked_conflict`; precio **2499 intacto**.
- **Operación normal:** una nueva alta sin ediciones posteriores se revierte, elimina su producto y marca job `reverted`.
- **LIFO estricto:** un job nuevo `blocked_conflict` impide revertir el anterior; `blocked_not_last` registrado.
- **Edición posterior de costo:** valor 9999 preservado y revert rechazado.
- **Inventario/stock:** edición a 777 preservada y revert rechazado.
- **Variante:** fila de fixture con snapshot v3 y edición posterior de precio a 1987 preservada; bloqueo correcto.
- **Dependencia posterior no registrada por un importador:** una variante añadida manualmente después de crear un producto impide su borrado automático; no se pierde por cascada. V3 de alta sin hash `after_rel_sha256` también queda bloqueado.
- **Producto recién creado editado:** no se borra; nombre manual preservado.
- **Legacy v2:** snapshot degradado sintético a v2 → rechazo, sin convertir el archivo. Los v3 preliminares que no incluyan huella de dependencias para operaciones `create` también quedan bloqueados (no hay cambios históricos sobre sus archivos).
- **Snapshot v3 incompleto / hash AFTER ausente**, **snapshot no existente**: bloqueo sin alteración de catálogo.
- **Concurrencia de dos conexiones y procesos PHP separados sobre el mismo job:** exactamente uno aplicó restauración; el otro recibió `already_reverted`. Evento de auditoría de bloqueo/reversión persistió en `admin_audit_log` de la base sintética.
- **El fixture verifica identidad de DB con regex `mi_import_v1c_*` y `SELECT DATABASE()`; siempre elimina la base temporal y CSV en `finally`.** Se ejecutó `scripts/dev/imports_rollback_integrity_isolated.php --verify`.

### HTTP autenticado sobre copia real y aislada

Se creó otra BD de estructura pura (`mi_import_v1c_http_*`) y copia privada de app, config, assets, vendor y storage; administrador sintético con contraseña aleatoria y sesión propia sobre PHP -S `127.0.0.1:18768`. Se corrigió solo en la **copia temporal** el separador Windows de URL base de PHP -S (no en el proyecto compartido). Comprobado:

- Login real HTTP 302 y endpoints Admin; CSV oficial de **32 columnas**, subida real /preview y Apply de dos importaciones (jobs 2 y 3).
- Antes del conflicto: detalle de job v3 muestra formulario seguro Revertir.
- POST /admin/imports/rollback con token CSRF inválido devuelve **419**.
- Modificación manual SQL en base aislada a precio **2499**, luego POST con CSRF válido: devuelve 302 a detalle y estado **Reversión bloqueada**, sin botón Revertir; consulta SQL posterior conserva **2499**.
- Detalle del job anterior tampoco ofrece la acción: LIFO visible correctamente.
- No se usó sesión de Firefox del propietario ni datos reales.

### Hallazgos y deuda fuera de la reserva

- **P1 preexistente de variante/importador:** `ProductVariantImportRepository.php` referencia `ship_weight` en `product_variants`, pero la estructura SQL real copiada no contiene esa columna; una importación de variante por el importador actual lanzó error SQL 1054. No se modificó ese repositorio (sin reserva). La protección de la **reversión de variantes** sí fue testeada con una variante insertada directamente en el fixture y snapshot v3 verificable.
- Persistencia del archivo rollback **después** del commit y errores de archivo silenciados (I06) sigue sin reparación; ausencia de archivo bloquea revertir. La recuperación manual de v1/v2 no está automatizada. No se adoptó política all-or-nothing para APPLY (I05), ni se corrigió auditoría del controlador (I07), ni se refactorizó import/export.
- Cualquier diferencia de la fila completa, incluso un campo no utilizado por rollback, produce bloqueo conservador. Esto puede provocar **falsos positivos seguros**, nunca sobrescritura silenciosa.
- No se realizaron pruebas destructivas ni migraciones sobre la BD comercial; no se solicitó expandir la reserva. Sin commits/push ni cambios de rama.


### Cierre y teardown de V1C

- La suite aislada se amplió para comprobar también **inserción manual de una variante dependiente tras el import del producto**: el padre no se eliminó por rollback; y **v3 con `after_rel_sha256` faltante** quedó bloqueado. La captura recursiva se limita a relaciones FK conocidas del esquema, validando en `information_schema` que no existan dependencias nuevas o con PK alternativa sin verificación.
- HTTP autenticado se **re-ejecutó luego del refuerzo FK** en una copia privada: login, preview, Apply, v3 disponible, CSRF 419, modificación manual a 2499, bloqueo funcional y UI sin botón; también LIFO del job anterior. Todos OK.
- MySQL temporal de HTTP `mi_import_v1c_http_*` **eliminada**, copia privada y cuenta sintética destruidas, servidor PHP `127.0.0.1:18768` detenido, puerto sin LISTENING. Bases temporales de la suite CLI `mi_import_v1c_*` se destruyeron dentro del `finally` de cada ejecución.
- El frontend visual V1B no fue rediseñado; solo se cambió `notices.php` para expresar claramente cuándo faltan datos para una reversión automática.

### Evidencia final y puerta técnica

- `scripts/build/imports_revert_contract.php`: **21/21 OK** (contratos v3, ausencia de `after`/dependencias, duplicados, listas blancas, locks y orden de verificación).
- `scripts/build/imports_admin_contract.php`: **71/71 OK** (sin regresión de presentación/POST).
- `scripts/dev/imports_rollback_integrity_isolated.php --verify`: **suite integral PASS**, incluyendo reproducciones de precio/costo/stock/variante, alta normal, alta editada, dependencias por FK, snapshots antiguos/incompletos/ausentes, LIFO estricto, audit y concurrencia real de dos procesos. Cada ejecución crea una BD aleatoria de estructura sin contenido y la destruye en `finally`.
- **Gate global tras hotfix:** `C:\xampp\php\php.exe scripts/build/gate.php` → **OK** (836 PHP, 120 JS, CSS unificado, contratos de admin, checkout, regresiones).
- Lint PHP de `ImportService`, `ImportRevertService`, `ImportRevertRepository`, presenter, vista de avisos y scripts focales: OK. `node --check admin/imports/preview.js`: OK.
- **Teardown definitivo:** se eliminó la segunda copia de app, credenciales, CSV y MySQL HTTP; servidor 18768 detenido, sin perfil temporal propio ni proceso en ese puerto. Los scripts transitorios y archivos temporales `imports_v1c_*` se eliminaron fuera del repositorio. El repositorio conserva exclusivamente código, contrato y fixture automatizado autorizados.
- **No entregar ni integrar a producción sin validación del principal.** V1C cerrada técnicamente; V2 y V3 generales siguen sin autorización. El diseño continúa a la espera del visto bueno del propietario.

## Bitácora — Ventana 1D I06: implementación focal probada, **integración BLOQUEADA por reserva** (2026-10-21)

**Estado inequívoco:** el servicio de persistencia de snapshot v3 antes del commit está implementado y probado aisladamente, pero **la Ventana 1D NO queda aprobada ni cerrada para integración**. Se detectó una incompatibilidad con el manejo de estados del controlador, explícitamente en solo lectura, que impide cumplir el requisito de no dejar un job `finished` cuando falla su snapshot. Se detuvo el alcance; **NO se tocó el controlador**, ni se solicita autorización implícita para V1E/V2/V3.

### Implementación realizada exclusivamente dentro de la reserva

- `app/Services/Import/ImportSnapshotStore.php` (**nuevo**): escritor v3 estricto, acepta solo snapshot `complete=true`, operaciones verificables y mismo `job_id`; CSV existente dentro de `storage/imports` en modo aplicación (directorios descartables explícitos en tests CLI). Serializa con `JSON_THROW_ON_ERROR`, revalida versión/ops, reserva temporal de nombre aleatorio con `fopen('x+b')` en el mismo directorio, escribe hasta completar bytes, ejecuta `fflush` y `fsync` cuando PHP lo ofrece (en XAMPP Windows se comprobó disponible). Compara tamaño, SHA-256, integridad JSON y número de job.
- **Publicación atómica sin sobrescritura:** en NTFS PHP 8.2 se verificó que `link(temporal,destino)` crea un hard link con **igual identidad de archivo**, sin reemplazar un destino existente. Se eligió esta publicación **no-clobber** en lugar de `rename` potencialmente reemplazante; si no hay soporte de enlaces físicos, el import falla cerrado. El temporal permanece hasta conocer el commit para poder identificar el destino propio. No se modifican snapshots existentes de otros jobs. La compensación tras rollback SQL confirmado elimina el destino únicamente si aún coincide con el **inode del temporal propio**, incluso si hay corrupción de contenido; no borra un inode ajeno.
- `app/Services/Import/ImportService.php`: **solo persistencia/commit** en `run()`. Continúa calculando huellas v3/postestado dentro de su transacción, pero ahora exige payload v3 completo y operaciones. Secuencia normal: `prepare → publish → verificar → beforeCommit → PDO::commit → retirar temporal`. Un fallo antes del commit provoca `PDO::rollBack` y limpieza de sus nombres temporales y publicados (no otros). Los fallos de escritura ya **no se silencian**. Si se intenta una aplicación sin `job_id`, sin ops verificables, o sin capacidad de publicación, **no confirma SQL**.
- **Caso conservador sin cambios:** las ejecuciones no-dry con cero operaciones de rollback documentadas se rechazan, aun si la fila aparenta no-op. Con los importadores actuales no se puede probar que no hayan existido otros efectos no capturados sin ampliar archivos; permitir commit sin snapshot comprometería el contrato fail-closed. El dry-run conserva su comportamiento sin snapshot.
- **Resultado de commit ambiguo:** si `PDO::commit()` puede haber confirmado en MySQL pero lanza una excepción o se pierde el ACK, el servicio **retiene el snapshot ya publicado** y retira solo su temporal. No borra la posible recuperación. Si la transacción sigue activa y se consigue `rollBack()`, compensa ambos archivos. Un error del servicio se informa como `ImportResult`, no como éxito.
- `scripts/build/imports_snapshot_contract.php` (**nuevo**) y `scripts/dev/imports_snapshot_durability_isolated.php` (**nuevo**). Se adaptó exclusivamente el fixture `scripts/dev/imports_rollback_integrity_isolated.php` para inyectar la carpeta sintética autorizada de cada prueba sin utilizar `storage/imports` de la aplicación comercial.

### Pruebas efectivamente ejecutadas

- **MySQL descartable `mi_import_v1d_*`**, 65 tablas copiadas **solo como DDL** y datos 100% sintéticos; `SELECT DATABASE()` validado. Import real de producto, stock, marca, categoría y proveedor con CSV de 32 columnas: OK. Snapshot v3 JSON legible por el verificador, `job_id` correcto, sin temporal residual.
- **Fallos inyectados**, separados por ejecución: `open`, `write`, `flush`, `sync`, `verify`, `publish`, `published`, `before_commit`. Todos provocaron rollback SQL: **cero productos, stock, marcas, categorías y proveedores del fixture confirmado; cero snapshots finales/temporales de ese job**. También se etiquetaron escenarios sintéticos de permisos, disco lleno, publicación y sincronización (simulados mediante hooks; **no son fallos reales del hardware ni ACL**).
- Archivo temporal truncado tras validación: el destino no se publica con éxito ni se confirma SQL. Archivo publicado deliberadamente corrupto antes de la comprobación final: error, rollback y compensación por inode propio. No se altera otro snapshot.
- Commit simulado fallido **antes** de llegar a MySQL: SQL revertido y archivo publicado propio retirado. **ACK perdido después de commit verdadero** (subclase PDO aislada): queda producto confirmado, snapshot v3 válido preservado, sin temporal; el estado debe tratarse como **incierto** y reconciliarse manualmente.
- **Reintento del mismo CSV** tras falla de publicación con job diferente: correcto, una sola alta y snapshot asociado al nuevo job. **Concurrencia:** dos procesos CLI/PDO con jobs distintos finalizaron simultáneamente, ambos con snapshots v3 y sus registros; el contrato FS confirmó que dos publicaciones rivales por el mismo destino no lo sobrescriben.
- Colisión con snapshot preexistente: rechazo y hash original intacto. `dry-run`: ningún snapshot. `no-op` sin operaciones verificables: cancelación conservadora sin tocar catálogo.
- `scripts/dev/imports_rollback_integrity_isolated.php --verify` **PASS**, conservando íntegros los escenarios V1C de precio, costo, stock, atributos/variantes de fixture, FK, snapshots legacy, LIFO y carreras. `scripts/build/imports_snapshot_contract.php`: **19/19 PASS** (FS, JSON, rutas, conflictos, inode, concurrencia, corrupción). `imports_revert_contract.php`: **21/21 PASS**; `imports_admin_contract.php`: **71/71 PASS**.
- Los tests SQL eliminan su base aleatoria y sus archivos en `finally`. No se abrieron navegadores ni servidores HTTP para esta ventana; no se tocó la base habitual ni las sesiones del usuario.

### **Bloqueador de integración — requiere autorización del chat principal**

La lectura del flujo real de `app/Controllers/Admin/ImportsController.php::apply()` detecta que, inmediatamente después de `$real = $service->run(..., false, ...)`, llama **incondicionalmente** `$repo->markFinished($jobId, ...)`, incluso si `$real->errors > 0`; también emite `import_finished`. El nuevo `ImportService` hace rollback comercial correctamente ante falla de snapshot, pero **devuelve un `ImportResult` con error** por el contrato existente. El controlador lo marca falsamente `finished`, y puede mostrar al usuario «Importación aplicada con errores». Los tests aislados de `ImportService` marcan `failed` expresamente en el fixture; **NO prueban el manejo de estado del controlador real**.

**No se puede cumplir `sin falso finished` conservando simultáneamente el contrato de retorno de `run()` y el flujo del controlador vigente, sin ampliar la reserva.** Por instrucción expresa se detuvo ese cambio, sin modificar `ImportsController.php`, `ImportJobRepository.php`, rutas, schema ni reglas comerciales. Se requiere **autorización puntual** para ajustar el resultado de errores de durabilidad en `ImportsController::apply()` (y, si correspondiera, su estado/feedback); distinguirlo de errores por filas preexistentes I05 y del ACK de commit incierto. No saltar a 1E ni refactor general.

### Límites físicos y recuperación de huérfanos

- Publicar el snapshot antes del commit evita el intervalo normal *SQL confirmado sin snapshot*, pero **MySQL y NTFS no comparten ACID**. Fallo de energía o interrupción entre `link` y `COMMIT` puede dejar `_rollback.json` y `.snapshot_<job>_*.tmp` huérfanos aunque el SQL se haya revertido. Una caída después de un commit confirmado puede dejar el temporal. La sincronización del directorio/entrada del hard link, cachés del dispositivo y pérdida física de energía no pueden garantizarse plenamente desde PHP/Windows.
- **No purgar automáticamente** archivos `_rollback.json` ni temporales de jobs `queued/processing/failed`: el estado SQL puede ser incierto. Recuperación supervisada: detener nuevas importaciones; identificar job por `job_id` y ruta en `import_jobs`, cotejar JSON v3, `complete`, hash/bytes, entidades y postestado SQL bajo lectura consistente; revisar auditoría y estado transaccional; decidir si corresponde marcar finalizado, anular operación o preservar el archivo. Borrar solo temporales/huérfanos que se haya comprobado que pertenecen al job y no son necesarios para recuperar SQL confirmado. Nunca reemplazar snapshots legacy ni inferir que un archivo publicado implica commit.
- Si el filesystem no soporta hard links seguros, `fsync` falla o el directorio no está autorizado, el servicio bloquea commit: no utiliza `rename` con posible overwrite. Queda pendiente prueba de caída física/kill real, y el reparo del controlador descrito antes de habilitar a usuarios.

**Ventana 1D detenida para decisión del principal. Sin commits ni push. V1E (SQL 1054 de `ship_weight`) continúa estrictamente fuera de alcance.**

## Bitácora — VENTANA 1D-B · Integración de outcome SQL y estados de importación (2026-10-22)

**Estado:** integración 1D-B implementada, pruebas autenticadas aisladas ejecutadas. **Pendiente revisión del chat principal; no habilitar producción**. No se abrió la Ventana 1E. El alcance se limitó estrictamente a resultados de `apply()`, DTO, repositorio de jobs (solo nuevo estado), presenter/aviso, contratos y fixtures del módulo.

### Contrato nuevo y comportamiento

- **`app/Services/Import/ImportResult.php`**: `applyOutcome: string` tipado con cuatro constantes validadas por `setApplyOutcome()`: `not_started` (default seguro / dry-run), `committed`, `rolled_back` y `commit_uncertain`. Se serializa en `toArray()['apply_outcome']`. **Independiente de `errors`, `ok`, `skipped` y warnings**; un error individual por fila puede coexistir con `committed` según I05 vigente.
- **`app/Services/Import/ImportService.php`**: asigna `committed` solo después de `PDO::commit()` confirmado; `rolled_back` únicamente tras `rollBack()` verdadero; `commit_uncertain` ante intento de confirmación sin ACK ni rollback comprobable (o transacción en estado incierto), y `not_started` si el proceso no alcanzó una transacción. **No se tocan importadores, precio, CSV, stock, snapshot v3 ni estrategia I05.** Una incidencia al retirar temporal después de commit confirmado **no se reclasifica falsamente como rolled_back**.
- **`app/Controllers/Admin/ImportsController.php` — exclusivamente tramo posterior a `$service->run(...false...)` de `apply()`:** usa **solo `applyOutcome`** para el estado transaccional. `committed` → `markFinished()` y `import_finished`; si hay errores por fila conserva el aviso heredado de aplicación parcial. `rolled_back` o `not_started` → `markFailed()`, `import_apply_failed`, mensaje «No se aplicaron cambios», sin `import_finished`. Resultado incierto o desconocido → `markReviewRequired()`, `import_commit_uncertain`, mensaje de conciliación supervisada, sin afirmar éxito/fracaso SQL. El redirect a `/admin/imports/show?id=...` y CSRF se conservan. Se corrigió únicamente en ese bloque el metadato `file_name` de auditoría, usando `$name` real en lugar de la variable `$job` indefinida.
- **Diagnóstico `_errors.json`:** se escribe el resultado **real** después de Apply, con `apply_outcome` y opciones, sin sobrescribir el snapshot v3. Los errores globales `row=0` se representan con texto público genérico (sin rutas ni stack traces). Si el archivo de diagnóstico no pudiera escribirse, los estados/auditoría SQL prevalecen; no se infiere éxito de su presencia.
- **`app/Repositories/Imports/ImportJobRepository.php`:** únicamente `markReviewRequired(id,total,ok,errors)`, guarda `status='review_required'`, contadores y fecha en columnas existentes (`VARCHAR(20)`, sin migraciones). `markFailed()`, `markFinished()`, métodos de reversión y consultas previas quedan intactos.
- **`app/Services/Admin/Imports/ImportsViewPresenterService.php`:** historial etiqueta `review_required` como «Revisión requerida», con badge warning; detalle muestra «Revisión requerida» y no habilita su rollback. Por prudencia, **mientras exista cualquier `review_required` sin resolver, tampoco ofrece acciones Revertir en el resto de la interfaz** (lectura SQL para disponibilidad visual). **`app/Views/partials/admin/imports/view/notices.php`** añade aviso inequívoco de conciliación manual y aclara el estado `failed`. Sin CSS/JS, cambios de layout ni rutas.
- Los scripts de prueba HTTP usaron **fault injection solamente en la copia PRIVADA** de `ImportSnapshotStore` y `ImportService`. En el repositorio de aplicación NO se agregó ningún interruptor de prueba.

### Verificación E2E autenticada con aplicación completa y DB exclusivamente privada

El servicio MySQL habitual de XAMPP estaba **desconectado** al comenzar. Para no iniciarlo ni interferir con otras sesiones se creó **MariaDB 10.4 separada** en `127.0.0.1:18869`, con datadir propio bajo `C:\Users\matii\AppData\Local\Temp\mi_imports_v1db_isolated\mysql-data`. Se creó `mi_import_v1db_test` desde `database/schema.sql` (**65 tablas**, sin datos comerciales; **esquema de archivo fuente, no copia viva del esquema comercial**). Copia privada de la aplicación, vendor, assets, sesiones y `.env` propios; administrador sintético con contraseña aleatoria y PHP -S en `127.0.0.1:18778`. La única corrección de URL Windows y la inyección de fallos quedaron dentro de la copia descartable; ningún archivo compartido global se tocó.

**Casos y evidencia verificada en HTTP + SQL privado:**

| Caso | Evidencia y resultado |
|---|---|
| Inicio de sesión admin / CSV oficial | Login HTTP 302, acceso auténtico al shell, plantilla UTF-8 32 columnas |
| CSV inválido / Preview sin escrituras | Formulario Apply ausente para CSV sin SKU; sin nuevos productos, marcas, categorías, proveedores ni jobs |
| CSRF inválido en Apply | HTTP **419**, sin jobs nuevos |
| `committed` normal | Job #1 `finished`, evento `import_finished`, snapshot v3 existente y válido, `_errors.json` `apply_outcome=committed`; producto sintético existente |
| Escritura `open` fallida | Job #2 `failed`; cero cambios de catálogo, snapshot no publicado, auditoría exclusiva `import_apply_failed`; detalle indica que no se aplicó |
| Publicación `publish` fallida | Job #3 `failed`; misma integridad SQL y auditoría, sin `import_finished` |
| ACK perdido **después del commit real** | Job #4 `review_required`; producto SQL confirmado en fixture, snapshot v3 conservado, evento `import_commit_uncertain`, cero eventos `import_finished`, detalle con conciliación manual y sin botón Revertir |
| Error por fila en transacción confirmada (QA sintético) | Job #5 `finished` aunque `err_rows=1`, snapshot v3 y `import_finished`; **NO se confundió error por fila con rollback**. Caso instrumentado solo en copia QA (no alteración del importador en el repositorio). |
| Segunda confirmación incierta + UI conservadora | Job #6 `review_required`, snapshot conservado; interfaz de job confirmado anterior sin botón Revertir e historial con «Reversión suspendida: revisión pendiente» |

El controller usa para los fallos previos `markFailed()` heredado, que deja los contadores SQL nulos; el detalle de error proviene de `_errors.json`. Esto no afecta la veracidad de `status=failed`. No se modificó `ImportJobRepository::markFailed()` fuera de la reserva.

### Seguridad LIFO residual — **requiere reserva adicional, NO cerrar como resuelto**

**Detectado por prueba en DB sintética:** `ImportRevertRepository::lockLatestPendingJobId()` en el backend de reversión consulta exclusivamente `status IN ('finished','ok')`, omitiendo `review_required`. Por lo tanto puede considerar como último al job confirmado **anterior** a un commit incierto. La interfaz V1D-B deja de ofrecer la acción por prudencia, **pero esto no bloquea necesariamente un POST directo**. En el fixture se ofrecía un botón para el job anterior antes del ajuste visual; el intento HTTP se rechazó por una dependencia FK al eliminar una categoría (`No se pudo eliminar category...`), **NO por barrera de `review_required`**. No se puede certificar el bloqueo transaccional LIFO correcto para esa situación sin modificar `ImportRevertService/Repository`, que son **SOLO LECTURA** en esta ventana. **El principal debe autorizar un hotfix puntual de backend para incluir commits inciertos como barrera LIFO antes de habilitar producción**. No se ocultará esta limitación bajo una validación visual satisfactoria.

### Contratos, suites y restricciones

- **Contrato nuevo:** `scripts/build/imports_apply_outcome_contract.php` — **30/30 PASS** (DTO tipado, serialización, guards controller, estados, audit y avisos).
- `scripts/build/imports_snapshot_contract.php` — **19/19 PASS**; `imports_revert_contract.php` — **21/21 PASS**; `imports_admin_contract.php` — **71/71 PASS**.
- Suite `scripts/dev/imports_snapshot_durability_isolated.php`: reejecutada en MySQL **privado** con asserts de outcomes para `committed`, `rolled_back`, `commit_uncertain`, `not_started`, errores FS, ACK perdido, retry, concurrencia y eliminación del fixture; **PASS**.
- Suite `scripts/dev/imports_rollback_integrity_isolated.php --verify`: **PASS** en otra DB aleatoria de la instancia privada (V1C: precio 2499 preservado, costo, stock, variantes/atributos de fixture, FK, snapshots legacy/incompletos, LIFO sobre job `blocked_conflict` y concurrencia).
- No se modificaron servicios/repositorios de reversión, importadores de variantes, Catalog, SEO, Category, Cart, CSS, JS, rutas, base habitual ni esquema; sin migraciones, commits, push o cambios de rama. Error SQL 1054 `ship_weight` reservado para V1E no autorizada.
- **Límites físicos I06:** publicación antes de commit + fsync y hard link sin sobrescritura no equivale a transacción ACID MySQL/NTFS. Orfandad de snapshot tras caída y resultado incierto requieren conciliación supervisada. Se preserva snapshot en `review_required`, no auto-retry ni auto-rollback.

**Estado de salida:** integración de los tres outcomes de 1D-B implementada y probada. Queda pendiente autorización y corrección focal de barrera LIFO en servicio para revisión incierta; aprobación global/producción NO concedida.


### Gate final, limpieza y decisión de cierre 1D-B

- **Gate global coordinado:** `C:\xampp\php\php.exe scripts/build/gate.php` → **PASS**. Recorrido: **845 PHP, 120 JS**, contratos sidebar, layout, Dashboard, Pedidos, SEO, marketing, checkout/cart, CSS Admin V3, UTF-8 y regresiones.
- Lint de los seis PHP de implementación/presentación: OK. Contratos focales: **30/30 resultado de ejecución**, **19/19 snapshot**, **21/21 reversión**, **71/71 Admin**.
- Instancia MariaDB privada (18869) **apagada con mysqladmin** y PHP -S privado (18778) detenido; ambos puertos sin LISTENING. Directorio `mi_imports_v1db_isolated` con data directory, DB, código clonado, uploads, sesiones y credenciales **eliminado**. Los **17 archivos/fixtures temporales** propios también fueron eliminados. XAMPP/MySQL comercial —que estaba fuera de línea— no fue iniciado ni modificado.
- **Pendiente seguridad LIFO:** `review_required` suspende botones de reversión en UI, pero el servicio de reversión no lo considera en su criterio de último pendiente. No se modificó ese servicio sin reserva. Se necesita decisión/autorización puntual del chat principal, no dar V1D-B como garantía de operación integral segura.
- **Sin commits, push ni cambios de rama**; liberar archivos tras revisión del chat principal, no comenzar V1E ni refactorizaciones.
## Bitácora — Ventana 1D-C: barrera LIFO global transaccional (2026-10-22)

**Estado:** Implementación y validación SQL/HTTP privada completadas; pendiente aprobación del chat principal. I06 y producción NO aprobados automáticamente. V1E y V2/V3 no autorizadas.

### Código implementado (reserva estricta)

- `app/Repositories/Imports/ImportRevertRepository.php`: método `lockPendingReconciliationJobIds()`, exige transacción y recorre el índice PRIMARY de `import_jobs` completo en orden DESC con `FOR UPDATE`. Detecta cualquier `status=review_required`, sin filtrar por fecha, ID, revert_status ni snapshot. El barrido completo bajo InnoDB REPEATABLE READ protege filas existentes y sus gaps (next-key locks), serializando updates de estado e inserciones concurrentes. Sin modificaciones al cálculo de hashes, borrados, FK, catálogo ni esquema.
- `app/Services/Import/ImportRevertService.php`: aplica `SET TRANSACTION ISOLATION LEVEL REPEATABLE READ` solo a la transacción siguiente, BEGIN, lock global, lock LIFO y `lockJob(jobId)`; revalida status objetivo bajo lock. Con cualquier revisión incierta devuelve bloqueo `review_required_pending` y mensaje «Reversión suspendida: existen importaciones con confirmación SQL pendiente de conciliación supervisada». Antes de cualquier restore/delete, sin tocar registro comercial, snapshot ni revert_status del objetivo. Solo registra evento `import_revert_blocked` (sin falso `import_reverted`). Sin revisión conserva LIFO previo, detección de conflictos, idempotencia, v3 y rechazo v1/v2.
- **Costo de seguridad:** el guard recorre y bloquea `import_jobs` entero, O(N) y con contención de inserts mientras se revierte. Los gap locks requieren InnoDB RR; se establece aislamiento local explícito. No promete atomicidad absoluta ni protege transacciones en otros motores.

### Pruebas SQL en MariaDB completamente privado

Entorno: MariaDB 10.4 en `127.0.0.1:18879`, datadir exclusivamente temporal `C:\Users\matii\AppData\Local\Temp\mi_imports_v1dc_isolated\mysql-data`, base sintética `mi_import_v1dc_test` creada desde **65 tablas DDL** de `database/schema.sql`, sin ningún registro comercial copiado. Guard de fixture en `scripts/dev/imports_lifo_uncertain_isolated.php` exige DSN local y prefijo `mi_import_v1dc_*`.

1. Jobs #1 y #2 `finished`, #3 `review_required` y #4 posterior `finished`: llamadas directas al servicio para **los cuatro** bloqueadas por conciliación. SQL: precios **1000 intactos**, `revert_status=not_reverted`, SHA-256 de snapshots idénticos. Auditoría solo `import_revert_blocked`, nunca `import_reverted`.
2. Cambio manual `review_required → failed` **exclusivamente en fixture**, simulando conciliación humana: ya no bloquea globalmente; LIFO sigue rechazando anteriores mientras exista un posterior vigente. Reversión normal del último restaura precio **1000 → 900**; segunda ejecución devuelve `already_reverted`; luego el siguiente job puede revertirse.
3. Snapshot v2 sigue bloqueado; alteración manual del producto después de snapshot v3 preservada; job último con conflicto no puede saltarse en LIFO.
4. **Carrera con UPDATE previo:** conexión A cambia un job a `review_required` sin commit; otro proceso intenta revertir y espera el lock. Cuando A confirma, el rollback detecta la revisión y bloquea sin alterar producto.
5. **Carrera con INSERT posterior:** A bloquea producto; B toma locks sobre import_jobs y queda esperando producto (observado en `information_schema.INNODB_LOCK_WAITS`); C intenta insertar nuevo `review_required` y queda esperando el next-key lock. A libera, B revierte primero, luego C inserta. La siguiente reversión queda bloqueada. No hubo deadlocks persistentes ni alteración fuera del orden serializado.

### HTTP autenticado, CSRF y regresiones

Clon privado completo del Admin PHP con cuenta y sesión sintéticas en `127.0.0.1:18788`. POST real a `/admin/imports/rollback` con CSRF incorrecto → **419**. POST directo válido a job anterior, posterior y al propio `review_required` → **302** a detalle con mensaje de conciliación; SQL confirmó precios/estados intactos, hashes de snapshots idénticos, auditoría de bloqueos y ninguna de éxito. La interfaz no ofreció botón mientras hubo reviews. Tras cambiar **todas** las revisiones sintéticas a `failed`, el POST de reversión normal restauró precio a 900 y repetirlo fue idempotente. La primera prueba de reanudación permaneció bloqueada porque todavía existía otra revisión creada por la carrera; esta evidencia confirma que el bloqueo es global.

- Contratos: nuevo `scripts/build/imports_lifo_uncertain_contract.php` **19/19 PASS**; rollback **21/21**, snapshot **19/19**, outcome **30/30**, Admin **71/71**.
- Suite V1C `scripts/dev/imports_rollback_integrity_isolated.php --verify`: **PASS**, con precios, costo, stock, variantes de fixture, FK, legacy y dos reversiones concurrentes.
- Suite V1D `scripts/dev/imports_snapshot_durability_isolated.php`: **PASS**, con fallos de disco simulados, publicación, ACK incierto, retry y concurrencia de jobs.

**Límites:** sin conciliación automática, sin cambios de esquema, productos, variantes, controlador, importadores, DTO, job repo, vistas ni servicios compartidos. La protección prioriza seguridad e introduce contención sobre tabla de jobs; no sustituye política de recuperación de commit incierto ni garantiza ACID SQL+FS. Sin commits ni push; V1E sigue pendiente.
### Cierre verificable y liberación de entorno 1D-C

- **Build gate coordinado:** `C:\xampp\php\php.exe scripts/build/gate.php` → **PASS**; 847 PHP, 120 JS, contratos funcionales y visuales transversales. Lint de servicio, repositorio, contrato y suite focal: sin errores.
- **Contratos reproducidos:** LIFO incierto 19/19, rollback 21/21, snapshot 19/19, outcome 30/30, Admin 71/71, todos PASS. Suites aisladas V1C y V1D: ambas PASS.
- **Teardown:** PHP -S privado 18788 detenido; MariaDB aislado 18879 apagado mediante `mysqladmin shutdown`; comprobados ambos puertos sin LISTENING. Se eliminó completamente `C:\Users\matii\AppData\Local\Temp\mi_imports_v1dc_isolated` (MySQL datadir, DB, app clonada, uploads, credenciales, sesiones y snapshots de prueba) y 7 scripts auxiliares privados. Se conservaron únicamente contrato y suite focales del repositorio para futuras regresiones.
- **Git:** sin commits, push, cambio de rama ni escrituras sobre BD comercial. Archivos cambiados por esta ventana: `ImportRevertRepository.php`, `ImportRevertService.php`, nuevo `scripts/build/imports_lifo_uncertain_contract.php`, nuevo `scripts/dev/imports_lifo_uncertain_isolated.php` y esta bitácora.
- **Decisión pendiente:** chat principal debe revisar y aprobar 1D-C/I06; barrera de locks completa puede incrementar latencia de rollback en catálogos con muchos jobs. V1E (ship_weight) continúa NO abierta.

## Bitácora — Ventana 1E · Compatibilidad SQL de variantes (2026-10-22)

**Estado:** corrección focal del SQL 1054 y validación temprana de dimensiones implementadas y probadas. **No considerar el circuito completo de variantes aprobado para producción**: las pruebas expusieron problemas preexistentes de atributos y trazabilidad del padre implícito fuera de esta reserva. Requieren decisión/autorización del principal. No se inició I05 ni refactorización general.

### Cambios estrictamente autorizados

- `app/Repositories/Imports/ProductVariantImportRepository.php`: `findBySku` ya no consulta `ship_weight/height/width/length` de `product_variants` (columnas inexistentes); incluye las columnas reales `cost_currency_id` y `margin_pct` junto con SKU/código interno/título/precios/costo/estado. `insert` mantiene `price_updated_at`, moneda, margen y campos reales, genera placeholders dinámicos para `price_wh1_override`/`price_wh2_override` únicamente si esas columnas existen físicamente. `updateById` elimina `ship_*` de la whitelist, conserva actualizaciones legítimas y overrides condicionales. Ninguna escritura sobre `products` ni cambio de esquema.
- `app/Services/Import/VariantImporter.php`: antes de buscar/crear padre o cambiar `sale_mode`, si `update_ship_dims=ON` y algún campo `ship_weight/height/width/length` de la variante no está vacío (incluye valor `0`), retorna error explícito de dimensiones incompatibles. `update_ship_dims=OFF` ignora esas columnas por política; ON con campos vacíos permite importar. Se eliminó la asignación de `ship_*` a campos de `product_variants`, sin trasladarlos al padre. Se preservó inventario, price cache, precios, mayoristas, atributos y lógica general de upsert (solo su código ya existente).
- Nuevos `scripts/build/imports_variant_ship_contract.php` (35/35) y `scripts/dev/imports_variant_ship_isolated.php` con guard DSN local `mi_import_v1e_*`. Ningún otro archivo de aplicación modificado por esta ventana.

### Evidencia SQL y HTTP privada

- Entorno: MariaDB 10.4 propia en `127.0.0.1:18909`, BD `mi_import_v1e_test` creada con las **65 tablas DDL** de `database/schema.sql`, sin copia de datos comerciales; clon completo privado del Admin con sesiones propias, `vendor`, assets y `storage/imports` descartable. Un admin QA con credenciales aleatorias. El MySQL comercial y navegador personal no se utilizaron.
- Reproducción inicial de la consulta anterior `SELECT ship_weight FROM product_variants` sobre el esquema privado: **SQLSTATE 42S22 / 1054**. Con el repositorio corregido, `INSERT`, `findBySku` y `UPDATE` funcionan, conservan `cost_currency_id`, `margin_pct`, precio, costo y lista; no se añadieron campos de envío.
- CSV oficial de **32 columnas**, `update_ship_dims=OFF` con peso/alto de variante presentes: preview válido, Apply real `committed`; la variante queda con precio **2200**, costo **950**, margen **0.2500**, moneda ARS e inventario de variante **4**, sin tocar `products.ship_weight` del padre (**700**). Snapshot **v3 completo**, incluyendo variante e inventario; rollback seguro `ok=true`, eliminó las 2 altas.
- `update_ship_dims=ON` con peso **700** y alto **10**, padre inicialmente inexistente: preview/dry-run ERROR **antes de crear padre/variante/inventario**. Opción OFF con mismos campos pasa. Opción ON con celdas vacías pasa. Se comprobaron contadores SQL antes/después del dry-run.
- Otro CSV oficial con fila producto padre nuevo + fila variante: ambos creados y asociados con `sale_mode=variants`, estado `committed` y snapshot v3 `complete=true` que contiene padre, marca, proveedor, inventario producto, variante e inventario variante.
- Reimportación del mismo SKU de variante: **misma PK**, sin duplicados, precio **2400→2900**, stock **4→7**, snapshot de UPDATE v3 completo. Precio cambiado manualmente a **2999** → rollback bloqueado, precio intacto; restaurado manualmente en fixture a **2900** → rollback de actualización exitoso, restauró precio **2400** y stock **4**.
- **Límite de seguridad conservadora reproducido:** después de la importación de padre+variante, una segunda importación actualizó la variante y se revirtió; el rollback de la importación original padre+variante fue bloqueado por diferencias de postestado (no hubo borrados ni datos perdidos). Traza: `Reversión bloqueada: un registro fue modificado o no puede verificarse desde la importación.` La huella completa puede cambiar por `price_updated_at`/cachés al hacer updates. No se debilitó la verificación de snapshots. Requiere análisis de motor v3 en otra reserva si el negocio exige revertir ese encadenamiento.
- **P0 de trazabilidad fuera de alcance:** con `allow_parent_from_variants=ON` una sola fila variante puede crear un padre implícito. El test privado produjo `committed` y snapshot v3 `complete=true` con ops `variant` + `inventory_variant` **pero sin la creación del producto padre**. Es una omisión real del rollback del padre implícito, no atribuible al SQL 1054. El método `VariantImporter` llama a `ProductImporter` para crear el padre pero no propaga el rollback del padre a `ImportService`. **No se corrigió**: el contrato 1E solo habilita validación de dimensiones en `VariantImporter` y `ImportService/ProductImporter` siguen en solo lectura. No habilitar reversión de ese caso sin un hotfix autorizado.
- **Atributos de CSV oficial fuera de alcance:** los encabezados son `attr_1/valor_1`, pero `RowNormalizer` (solo lectura) busca `atributo_1/valor_1`. Se incluyó `attr_1=Color` en CSV; SQL confirmó **0 registros en `attributes` y `product_variant_values`**, es decir, se ignora el atributo sin que la variante falle. No se modificaron normalizador ni `AttributeImporter`; requiere reserva para evitar pérdida silenciosa de datos CSV.
- HTTP autenticado aislado sobre `127.0.0.1:18798`: login real 302, shell Admin, descarga plantilla 32 columnas, preview de variante inválida con dimensiones ON rechazado y SQL sin variante, preview con OFF válido, POST Apply 302 a detalle, job `finished`, variante única con precio **2750**, detalle accesible y POST CSRF inválido **419**. Las pruebas HTTP se ejecutaron sobre el clon aislado sin datos reales.
- Regresiones V1C `imports_rollback_integrity_isolated.php --verify` **PASS** y V1D `imports_snapshot_durability_isolated.php` **PASS** en bases adicionales descartables, con conflictos, LIFO, auditoría, snapshot/ACK y dos procesos; bases de esos fixtures destruidas.

### Estado de aprobación

La reparación SQL focal funciona en creación/actualización; **existen dos riesgos independientes sobre el flujo de atributos/parent implícito**. Según la reserva V1E, no se alteró `ImportService`, `RowNormalizer`, `ProductImporter`, rollback, schema, controller, rutas ni CSS/JS. Se solicita al principal decidir una nueva ventana de integridad para esos puntos antes de considerar Variante completa apta para producción. Tampoco se habilita I05 ni refactorización general. Sin commits ni push.

### Cierre técnico de la reserva V1E y teardown

- **Contratos reejecutados:** `imports_variant_ship_contract.php` **35/35 OK**, LIFO incierto **19/19**, rollback **21/21**, snapshot **19/19**, outcome **30/30**, Admin **71/71**. PHP lint de ambos archivos modificados y los dos scripts focales: OK.
- **Gate global coordinado:** `C:\xampp\php\php.exe scripts/build/gate.php` → **OK**, 850 archivos PHP, 120 JavaScript, layout, CSS, flujo comercial y regresiones transversales.
- **Recursos eliminados:** PHP -S privado del Admin (18798) detenido, MariaDB aislado (18909) apagado con `mysqladmin shutdown`, verificados ambos puertos sin LISTENING. Se eliminó la carpeta privada `C:\Users\matii\AppData\Local\Temp\mi_imports_v1e_isolated`, incluida DB, datadir, credenciales sintéticas, sesiones, uploads y snapshots de test, y cinco scripts temporales de preparación. Ningún dato de XAMPP MySQL habitual se utilizó para escribir.
- **Integridad de edición:** `git diff --check` sobre los archivos reservados **OK**, sin commits, push ni cambios de rama. Solo se editaron `ProductVariantImportRepository.php`, `VariantImporter.php`, nuevos contrato y suite específicos y esta bitácora.
- **Decisión que debe adoptar el principal:** mantener la corrección focal 1054/dimensiones, pero **no habilitar todavía el flujo integral** de variantes con `allow_parent_from_variants` ni importación automática de atributos `attr_1` hasta aprobar su tratamiento en servicios reservados de otras ventanas. No se corrigió I05 ni se inició refactorización.

## Ventana 1F-A — P0 rollback del padre implícito (2026-10-22)

**Estado:** corrección funcional implementada y validada con SQL sintético en MariaDB aislada. **El E2E HTTP autenticado específico de 1F-A no se completó** y requiere aceptación/repetición por parte del principal antes de aprobar la ventana integralmente. No se inició 1F-B ni I05.

### Código (solo archivos autorizados)

- app/Services/Import/VariantImporter.php: al crear padre implícito por ProductImporter::upsert(), reúne rollback_ops de altas de marca/categoría/proveedor, verifica IDs, tipos, unicidad y presencia de cada catálogo declarado como creado; incorpora a continuación rollback product:create y verifica que el producto releído por SKU sea el recién creado. Devuelve parent_product_id, parent_snapshot_complete y rollback_ops ordenadas, además del rollback habitual de variante. Si la creación del padre o posteriormente de su variante falla, se aborta SQL (sin dejar catálogos huérfanos). Con padre preexistente no devuelve falsas altas. Se conservan dimensiones de envío 1E y toda la semántica de variantes.
- app/Services/Import/ImportService.php: exclusivamente en rama variante, valida parent_created / parent_snapshot_complete, identidad de padre, tipos de entidades e IDs, ausencia de duplicados, operación product:create obligatoria al final de operaciones del padre y operación propia de variante. Ante inconsistencias lanza RuntimeException para ejecutar rollback SQL sin confirmar snapshot. Agrega primero catálogos y padre, luego variante y finalmente el inventario de variante existente; el generador v3 preexistente firma postestado y deduplica por entidad/ID. No se editaron v3 builder, controlador, ProductImporter, repositorios ni motor de rollback.
- Contrato nuevo scripts/build/imports_variant_parent_snapshot_contract.php (25 comprobaciones) y fixtures scripts/dev/imports_variant_parent_snapshot_isolated.php y scripts/dev/imports_variant_parent_fault_isolated.php. Pruebas de inyección únicamente dentro de un clon privado; no se incorporaron interruptores de QA al código de aplicación.

### Pruebas reales MySQL

- MariaDB 10.4 privada en 127.0.0.1:18929, esquema de 65 tablas de database/schema.sql (solo DDL); base mi_import_v1fa_test, archivos y sesiones completamente aislados. Confirmación explícita SELECT DATABASE() y rechazo de DSN no privado; sin datos comerciales.
- CSV variante con padre inexistente que crea marca, categoría, proveedor, padre, dos variantes e inventarios; snapshot v3 complete=true con 8 operaciones: brand:create → category:create → supplier:create → product:create → variant:create → inventory_variant:create → variant:create → inventory_variant:create. Todas incluyen after_sha256 y after_rel_sha256. Solo un product:create y una operación por entidad creada.
- Reversión original ok=true, deleted=8, orden de borrado inverso compatible con FK. Se verificó que contadores de productos, variantes, marcas, categorías, proveedores e inventario regresaron al valor anterior y que categoría/marca preexistentes no se tocaron. Segundo rollback idempotente already_reverted.
- Dry-run válido sin ninguna escritura. Padre preexistente: solo variant:create e inventory_variant:create; rollback conservó producto padre. Dos importaciones consecutivas sobre mismo padre: primera crea padre/auxiliares, segunda registra solo variante/inventario, sin duplicar operaciones o padre. Edición manual posterior de precio bloquea rollback sin cambios comerciales; LIFO impide saltar el job conflictivo.
- Fault injection en CLON PRIVADO: omitimos deliberadamente product:create del retorno después de que ProductImporter creó padre y catálogos. ImportService rechazó el snapshot, resultado rolled_back, cero cambios de productos/variantes/marcas/categorías/proveedores/inventario y ningún archivo de recuperación publicado. La copia se restauró a continuación.
- V1C y V1D suites MySQL privadas: PASS. Regresión SQL V1E sobre segunda base aislada mi_import_v1e_test: PASS, incluida confirmación product:create ahora presente en snapshot del padre implícito. El bloqueo conservador heredado al intentar revertir creación después de actualizar/revertir otra importación permanece: no se debilitó huella v3.

### Límites

- **No se certifica HTTP autenticado 1F-A.** No se consiguió completar una sesión admin de QA propia para repetir preview/Apply/CSRF del nuevo camino, aunque ese flujo había pasado para variante existente en 1E. La prueba SQL directa no sustituye este requisito de aceptación.
- Atributos attr_1/valor_1 y BEFORE de relaciones de AttributeImporter quedan intactos y reservados a 1F-B. No activar solo alias, ni iniciar I05, refactor general, producción o migraciones. Sin commits, push ni edición de otros módulos.

### Cierre de pruebas y limpieza 1F-A

- Contratos: padre implícito **25/25**, variante 1E **35/35**, LIFO incierto **19/19**, rollback **21/21**, snapshot **19/19**, outcome **30/30**, Admin **71/71**. Lint PHP de los dos archivos de implementación y los tres fixtures/contratos nuevos: **OK**.
- Gate coordinado `C:\xampp\php\php.exe scripts/build/gate.php`: **OK**, 856 PHP, 120 JavaScript, contratos de layout/marketing/sidebar, UTF-8 y regresiones globales.
- Regresiones SQL V1C, V1D y V1E: **PASS** sobre bases descartables. Suite nueva V1F-A SQL: **PASS**. Fault-injection de pérdida de product:create: **PASS**, rollback SQL íntegro. Ningún cambio en I05 ni atributos.
- Limpieza: MariaDB privada apagada con mysqladmin, **puerto 18929 libre**; directorio completo `C:\Users\matii\AppData\Local\Temp\mi_imports_v1fa_isolated` eliminado (datadir de MariaDB, DB, upload de QA, snapshots, vendor clonado y sesiones privadas), junto a dos scripts auxiliares del directorio temporal. No se levantó servidor HTTP de QA en 1F-A ni se crearon credenciales privadas de Admin; **HTTP autenticado/CSRF permanece pendiente**. No se tocaron procesos/navegadores de otros especialistas.
- `git diff --check` focal **OK**; sin commits, push, cambios de rama ni escrituras en base comercial. Reserva editada exclusivamente: `VariantImporter.php`, `ImportService.php` (rama variante), contratos/fixtures nuevos y esta bitácora.
- La **integridad SQL del P0 quedó corregida**; la aceptación integral 1F-A requiere completar HTTP autenticado aislado, más revisión del chat principal. Próxima 1F-B de atributos **NO autorizada aquí**.

## Ventana 1F-A2 — QA HTTP autenticada del padre implícito (2026-10-22)

**Estado:** E2E autenticado REAL de 1F-A2 **PASS**; prueba dedicada para cerrar el faltante HTTP de 1F-A. No hay cambios de implementación ni autorización implícita de producción o 1F-B. La reparación SQL de 1F-A permanece tal como fue aprobada por el principal.

### Entorno estrictamente privado

- Código original de la aplicación, vendor, assets, rutas, repositorios y servicios copiados a `C:\Users\matii\AppData\Local\Temp\mi_imports_v1fa2_isolated\site`, con storage, sesiones, `.env` y URL de desarrollo propios. Solo dentro de esta copia se normalizó el separador de ruta PHP -S en Windows; el archivo compartido `app/Support/helpers.php` no fue modificado.
- MariaDB 10.4 independiente en `127.0.0.1:18939`, datadir privado en el mismo directorio temporal, base `mi_import_v1fa2_test`, creada con las **65 tablas DDL** de `database/schema.sql`. `SELECT DATABASE()` y nombre/host/puerto protegidos antes de ejecutar cualquier escritura; ni datos ni credenciales comerciales copiados. PHP 8.2 real sobre `127.0.0.1:18808` usando router **solo en el clon**.
- Administrador sintético `qa-1fa2@example.test` creado exclusivamente en esa BD con contraseña de QA independiente, verificado con login auténtico `/login` (CSRF y anti-bot); ninguna sesión real ni cookie del usuario se utilizó. Los scripts de QA reciben el secreto sintético mediante la variable de entorno `MI_IMPORTS_V1FA2_QA_PASSWORD`, sin persistirlo en el repositorio.
- Únicos scripts nuevos permanentes, focales y permitidos: `scripts/dev/imports_variant_parent_http_isolated.py` y `scripts/dev/imports_variant_parent_http_fault_isolated.py`; uso de `requests` y `BeautifulSoup` para enviar solicitudes HTTP **reales** a endpoints locales y del cliente de MariaDB para cotejar SQL. Fuera de estos dos scripts y la presente documentación no se editó ningún archivo del proyecto original.

### E2E Preview → Apply → detalle → reversión (117 comprobaciones PASS)

1. GET `/login` **200**, POST auténtico **302**, GET `/admin/imports` **200**, GET `/admin/imports/template` **200**; plantilla oficial UTF-8 BOM, **32 columnas**. POST Apply sin CSRF válido **419** y **cero escrituras SQL**. El CSV contiene únicamente datos sintéticos.
2. Preview de variante con padre inexistente y `allow_parent_from_variants` **OFF**: error, sin Apply, SQL sin escrituras. Preview con `update_ship_dims` ON y `peso_envio_g=850`: error, sin Apply y sin escrituras. Preview válido con `update_ship_dims` OFF, `allow_parent_from_variants`, `create_missing_products`, y creación de marca/categoría/proveedor ON: **200**, sin cambios en productos, variantes, catálogo, inventario ni `import_jobs`.
3. Aplicación auténtica de CSV con **dos filas variante y un padre implícito compartido**: POST `/admin/imports/apply` **302** hacia `/admin/imports/show?id=1`; job sintético **#1** con `status=finished`, `apply_outcome=committed` en `_errors.json`, **una** auditoría `import_finished`. Productos, variante e inventario tienen las FK correctas y un único padre.
4. Snapshot job #1 **v3 `complete=true`**, **2883 bytes**, SHA-256 de archivo `6dd66b8a5aeb6b864f747374a0063f57bf22e6dfddd2b138cd443d7de32a6859`; orden de ops exacto: `brand:create → category:create → supplier:create → product:create → variant:create → inventory_variant:create → variant:create → inventory_variant:create`. Ocho huellas `after_sha256` + `after_rel_sha256`, ambas de 64 dígitos hexadecimales en cada operación.
5. GET detalle **200** con acción Revertir. POST de rollback con CSRF inválido **419**, sin cambios. POST válido `/admin/imports/rollback` **302** → `revert_status=reverted` y auditoría `import_reverted`; se eliminan las ocho altas en orden inverso sin FK huérfanas. Los contadores SQL vuelven a la línea base de productos, variantes, marcas, categorías, proveedores e inventarios; marca/categoría preexistentes permanecen. Segundo rollback HTTP es idempotente.
6. Padre preexistente: segundo job sintético **#2** `finished`, variante ligada por PK original; snapshot v3 SOLO `variant:create` + `inventory_variant:create`, sin `product:create` falso. Reversión HTTP **302** dejó el producto padre preexistente intacto.
7. Edición manual sintética posterior: tercer job **#3** `finished` creó padre/variante. Modificación directa de precio de variante a **2999** en la BD de QA. Intento de revertir por HTTP **302**, queda `revert_status=blocked_conflict`, precio 2999 y padre/variante intactos, sin cambios parciales, con auditoría `import_revert_blocked`. Las restricciones LIFO y estado de jobs ya revertidos se conservaron.

### Inyección de defecto, review_required y auditoría (27 comprobaciones PASS)

- Se sustituyó **solo en el clon privado** la emisión de `product:create` por una omisión intencional después de que `ProductImporter` había creado padre y auxiliares; `VariantImporter.php` original de la carpeta del proyecto NUNCA se tocó. Dry-run HTTP pasó sin escrituras; Apply por POST auténtico devolvió **302** a detalle del job sintético **#4**, que quedó `status=failed`, `apply_outcome=rolled_back`, auditoría `import_apply_failed` y **ningún** `import_finished`, sin snapshot `_rollback.json` y con cero cambios comerciales del fixture. El detalle presentó el aviso «Importación no aplicada». La copia instrumentada se restauró con `finally`.
- Con job #4 marcado `review_required` **solo como estado manual de prueba en MySQL descartable**, un POST directo válido de rollback al job #3 fue bloqueado globalmente; HTTP **302**, mensaje de conciliación supervisada, cero modificaciones comerciales. Luego se restauró #4 a `failed` únicamente en el fixture. No se implementó conciliación automática.
- SQL final de E2E: #1 `finished/reverted` (2 filas), #2 `finished/reverted` (1 fila), #3 `finished/blocked_conflict` (1 fila), #4 `failed/not_reverted`. Auditorías del fixture: `import_finished=3`, `import_reverted=2`, `import_revert_blocked=2`, `import_apply_failed=1`. Las 144 comprobaciones HTTP/SQL fueron PASS.

### Regresiones y límites

- Suites reales SQL V1C, V1D, V1E y V1F-A reejecutadas en **bases privadas adicionales** sobre la instancia MariaDB 18939: **todas PASS**; la suite V1F-A siguió verificando 8 altas reversibles, padre existente, dos variantes, conflicto manual, LIFO e idempotencia.
- Contratos focales: padre implícito **25/25**, variantes **35/35**, LIFO **19/19**, rollback **21/21**, snapshot **19/19**, outcome **30/30**, Admin **71/71**, todos OK. Syntax Python de ambos scripts: OK.
- El hallazgo independiente de atributos `attr_1` y BEFORE de reemplazos de relaciones sigue SIN corregir y reservado exclusivamente para 1F-B. Ningún ajuste de semántica parcial I05, CSS/JS o arquitectura general. Esta ventana valida el circuito de padre implícito, **no la aprobación global/producción**.

### Gate final y limpieza completa 1F-A2

- Build gate coordinado `C:\xampp\php\php.exe scripts/build/gate.php` → **PASS**, **858 archivos PHP**, **120 JavaScript**, contratos transversales y regresiones. Lint PHP del contrato de padre 1F-A: OK. Ambos scripts Python de QA 1F-A2 validados con `ast.parse`: OK.
- Los servicios fueron apagados: PHP -S QA en puerto **18808** detenido y MariaDB QA en puerto **18939** apagado ordenadamente con `mysqladmin shutdown`; verificación `netstat` confirmó **ningún LISTENING** en esos puertos.
- Directorio privado **`C:\Users\matii\AppData\Local\Temp\mi_imports_v1fa2_isolated` eliminado íntegramente**, incluyendo datadir MariaDB, bases temporales (`mi_import_v1fa2_test`, `mi_import_v1fa_test`, `mi_import_v1e_test` y bases aleatorias V1C/V1D), clon Admin, sesiones, CSV, snapshots y credenciales sintéticas. Se eliminaron también **4 scripts/archivos auxiliares temporales** de preparación, entre ellos el SQL de alta del usuario de QA. Verificado que no quedan auxiliares de esta ventana en Temp.
- No se inició ni reinició la base habitual de XAMPP, no se utilizaron datos reales ni servicios externos, no se dejaron perfiles de navegador ni procesos de ensayo. Solo quedaron en el repositorio los **dos scripts focales** `imports_variant_parent_http_isolated.py`, `imports_variant_parent_http_fault_isolated.py` y este documento.
- **Conclusión:** se completó la cobertura HTTP autenticada pendiente de 1F-A con **144 comprobaciones y suites de regresión PASS**; P0 de padre implícito cubierto por CLI SQL y por Preview/Apply/rollback reales, CSRF, auditoría y falla transaccional. La aprobación integral de 1F-A queda a decisión del chat principal. **No comenzar 1F-B/I05 ni refactorización/producción** sin autorización posterior. Sin commits ni push.

## Cierre integral del módulo — Protocolo V2 (2026-10-22)

**Estado:** Importaciones preparado para auditoría final del chat principal/propietario, **NO aprobado para producción**. No se reimplementó el P0 del padre implícito de 1F-A/1F-A2, ya aprobado. Este cierre registra solo cambios nuevos, evidencias y decisiones pendientes.

### Cambios efectuados dentro del dominio de Importaciones

- **Atributos CSV y rollback íntegro.** RowNormalizer reconoce attr_1..3/valor_1..3 oficiales y atributo_1..3 legacy; RowValidator rechaza parejas incompletas si se habilita actualizar atributos. AttributeImporter registra maestros nuevos y BEFORE de los conjuntos de product_variant_values, product_attributes y product_attribute_values, antes de cualquier DELETE/INSERT; aborta SQL ante fallas. Nuevo ImportAttributeRelationsRepository (Imports, no Catálogo): lectura completa ordenada, huella SHA-256, FOR UPDATE, restauración exacta y validación de claves/scope. ImportRevertRepository integra los tres tipos; ImportService conserva primeras operaciones BEFORE y deduplica scopes sin omitir cambios. Snapshots siguen en v3, y v1/v2/incompletos siguen bloqueados.
- **Cambios invisibles del padre preexistente.** VariantImporter también modifica sale_mode y caché de precio Desde del padre; ahora registra product:update ANTES de variante, comprueba pertenencia del SKU antes de escribir y aborta si una escritura posterior falla. Nuevo ImportProductStateRepository (Imports): SELECT * FOR UPDATE con BEFORE de sale_mode, price_from_cache y list_price_from_cache, a diferencia del SELECT parcial del repositorio general. ImportRevertRepository los restaura sin editar ProductRepository. SQL de QA confirmó reversión exacta del padre. La rama explícita de productos registra las altas auxiliares antes del producto y evita duplicar operaciones de inventario. Los padres implícitos mantienen sus operaciones y firmas previamente aprobadas.
- **UI Admin V3.** Cambios propios en ImportsViewPresenterService y vistas de historial, resumen, detalles y avisos: historial reciente desplegado; estados comprensibles (Revertida, Reversión bloqueada, Revisión requerida, No aplicada); explicación concreta de conflictos; el detalle distingue revertida/fallida/resultado incierto. Preview incluye los pares de atributos detectados. Se mantiene design system y CSS existente sin overrides ni modificaciones del shell global.
- **QA y contratos nuevos.** scripts/build/imports_attribute_relations_contract.php, imports_parent_cache_contract.php; scripts/dev/imports_attributes_integral_isolated.php, imports_parent_cache_integral_isolated.php, imports_i05_semantics_isolated.php, imports_lifo_performance_isolated.php, imports_integral_http_isolated.py, imports_integral_visual_isolated.cjs. Fixture de padre implícito y contrato original actualizados para exigir product:update cuando el padre preexiste; no se relajó la verificación de firmas. Capturas de QA en docs/modulos/capturas-importaciones/ (12 PNG de Dashboard, Pedidos, listado y detalle, mobile 390, tablet 768, desktop 1440).

### QA real — aplicación y bases 100% privadas

- **MariaDB descartable (65 tablas de DDL sin datos comerciales)**: creación de maestros y relaciones desde CSV oficial attr_1; reemplazo Rojo→Azul con retorno íntegro a Rojo, atributos existentes/nuevos, restitución de sort/is_required, dos variantes compartiendo padre y atributos con scopes deduplicados, reimportación de SKU sin duplicados, conflicto por edición manual sin escritura parcial, rechazo de pareja incompleta, rollback de maestros nuevos y asociaciones sin huérfanos. Todo PASS. Snapshot v3 completo e integridad de FKs comprobados.
- **HTTP autenticado real:** administrador sintético, login GET 200 y POST 302, Admin/template GET 200 y plantilla de 32 columnas, Preview POST 200 sin escrituras, Apply POST 302 con finished, apply_outcome=committed y auditoría import_finished; rollback POST 302 con import_reverted, datos repuestos, segundo rollback idempotente y CSRF inválido HTTP 419 tanto en Apply como en Revertir. Invalid attr_1 sin valor_1 bloqueado antes de Apply. **54 comprobaciones HTTP OK** tras agregar los pares de atributos al preview.
- **Padre y caché:** fixture SQL PASS de padre preexistente simple→variants, modificación de cache de precios, snapshot product:update → variant:create y restauración EXACTA de modo/cache/precio antes de eliminar variante.
- **Regresiones MySQL privadas:** V1C (conflictos, precio/costo/stock, LIFO y carreras), V1D (filesystem/publicación y commit incierto), V1E (variantes, envíos, inventario), 1F-A (padre implícito y 8 altas) PASS; la expectativa de 1F-A se actualizó para contemplar un product:update legítimo del padre ya existente. Contratos: relaciones 30/30; padre/cache 13/13; padre implícito 25/25; variantes 35/35; LIFO 19/19; rollback 21/21; snapshot 19/19; outcome 30/30; Admin 71/71.
- **Rendimiento:** barrido transaccional LIFO de 5.000 jobs sintéticos, 20 ejecuciones: mediana 5,98 ms; p95 6,95 ms; máximo 7,52 ms en equipo de QA. Las next-key/gap locks pueden causar esperas bajo concurrencia; no extrapolar estos tiempos a producción.
- **Visual real:** Edge headless con perfil aislado, comparación con Dashboard y Pedidos en 390/768/1440; sin desbordamiento horizontal de Importaciones en las tres resoluciones. Capturas registradas en docs/modulos/capturas-importaciones/. Estilo conservado dentro del Admin V3. Navegador QA cerrado con finally.
- **Gate global coordinado:** C:\xampp\php\php.exe scripts/build/gate.php → **OK**, 867 archivos PHP, 120 JS, contratos de layout, sidebar, Admin, Marketing, Checkout, CSS unificado y regresiones. Lint PHP focal, sintaxis JS y Python y diff --check propios OK.

### Decisión comercial I05 no adoptada unilateralmente

**Reproducción:** un CSV con una variante válida y otra inválida provoca errors=1 en dry-run, que el controlador HTTP bloquea; al llamar directamente ImportService::run(...,dry=false), el servicio heredado devuelve committed con errors=1, crea la variante válida y genera snapshot. La política de la interfaz y del servicio no es uniforme ante esa entrada.

**Propuesta al principal/propietario:** adoptar **atomicidad total por defecto** (una fila inválida = SQL rollback del CSV entero, job failed, ninguna escritura comercial); conservar todos los diagnósticos por fila. Si se necesita aplicar solo filas válidas, habilitarlo mediante opción comercial explícita/confirmada, snapshot íntegro, estado y reporte de aplicación parcial claramente visibles. **No se modificó la política heredada sin aprobación.** Esta decisión es requisito de aceptación final si se pretende garantizar semántica consistente también en invocaciones directas del motor.

### Límites reales que requieren supervisión

- Commit uncertain/review_required exige conciliación humana de SQL/snapshot; la barrera LIFO global bloquea todos los rollback mientras exista alguno. No ejecutar reintentos ni restituciones automáticos ante incertidumbre.
- Huellas completas y dependencias FK son conservadoras; modificaciones posteriores y actualizaciones revertidas pueden bloquear un snapshot más antiguo por cambios de estado. Nunca relajar el bloqueo solo para forzar una eliminación.
- MySQL y NTFS no comparten ACID; una caída física en la ventana de publicación/commit podría dejar archivos huérfanos. Seguir la recuperación supervisada I06, no borrarlos por nombre sin conciliación.
- Ninguna prueba escribió la base comercial, modificó producción, migró esquema, utilizó credenciales reales o ejecutó pagos/envíos externos. Sin commits, push ni cambios de rama. Los cambios de Importaciones se dejan en el working tree compartido para revisión del principal.

### Limpieza final verificada

Se apagó ordenadamente la MariaDB privada 127.0.0.1:18969 y se detuvo exclusivamente el PHP -S QA 127.0.0.1:18828. Netstat confirmó ambos puertos **sin LISTENING**. Se eliminó íntegramente C:\Users\matii\AppData\Local\Temp\mi_imports_integral_isolated (datadir, todas las BD descartables, clon completo, sesiones/credenciales sintéticas, CSV/snapshots y perfil Edge propio), junto con seis auxiliares temporales propios. En storage/imports del árbol de trabajo se eliminaron exactamente los archivos de QA de I05 y caché; no se tocaron datos comerciales ni archivos de otro especialista. Quedan solo los scripts focales, capturas y la documentación del encargo. Estado final detenido para auditoría del principal, sin commit/push.

## Cierre definitivo I05 — todo o nada aprobado por el propietario (2026-10-22)

**Esta sección PREVALECE sobre las referencias históricas a I05 pendiente o aplicación parcial.** El propietario adoptó expresamente opción A, **atomicidad total obligatoria sin excepción por fila**, conforme adendas finales de PLAN_IMPORTACIONES_ADMIN_V3.md / COORDINACION.md. Importaciones se entrega para auditoría central; **NO implica autorización de producción**.

### Cambio de comportamiento verificable

- `app/Services/Import/ImportService.php`: tras recorrer el CSV y acumular errores y diagnósticos de todas las filas, antes de construir/publicar snapshot v3 e intentar COMMIT, cualquier `errors>0` obliga a abortar. PDO::rollBack confirmado => `apply_outcome=rolled_back`; si se pierde certeza sobre SQL, `commit_uncertain` y conciliación supervisada. No se duplica como error de fila el rechazo global I05; `warnings` siguen no bloqueantes. Se ajustó contador heredado de productos: altas/updates reportan fila válida, no `skipped`.
- `app/Controllers/Admin/ImportsController.php`: HTTP continúa comprobando Preview y revalidando Apply; la primera validación negativa guarda `_errors.json` (no asume visita previa a Preview), registra `import_dry_failed` para atribuir un operador cuando realmente existe auditoría. Un Apply cancelado persiste `failed` y `import_apply_failed`, nunca `import_finished`. Defensa ante regresión imposible por contrato (`committed` con errores): registra `review_required` y `import_inconsistent_commit` en lugar de declarar `finished` o revertir sin conciliación. `show()` comprueba compatibilidad entre outcome y status antes de presentar un `_errors.json` potencialmente obsoleto.
- `app/Repositories/Imports/ImportJobRepository.php`: `markFailed()` acepta opcionalmente `total/ok/errors` para conservar **las estadísticas reales de filas analizadas** de una transacción cancelada; mantiene compatibilidad para llamadores históricos.
- **Detalle Admin V3**, sin rediseño ni CSS redundante: `summary.php` traduce `products` a «Productos y variantes», distingue «Filas válidas · no aplicadas» en fallas y muestra operador solo si una auditoría auténtica lo identifica; `ImportsViewPresenterService.php` distingue operaciones previstas frente a ejecutadas en jobs fallidos; `applied_changes.php`, `rows.php`, ViewModel y Presenter distinguen diagnósticos **ausentes** de «sin cambios». Si existe histórico revertido se muestran su recuento original y sus diferencias; no se inventa detalle cuando falta `_errors.json`. Ajuste focal en `public_html/assets/css/admin-v3/pages/imports.css` para impedir partir «variante» en columnas desktop.
- **Causa raíz de la captura observada:** el fixture anterior de importación revertida carecía de `_errors.json`; el snapshot `_rollback.json` no es el archivo de diagnósticos. En E2E real, el servicio/controller guardaron ambos por separado; el detalle revertido conservó exactamente dos filas y sus cambios. Se ensayó eliminar intencionalmente `_errors.json` y reemplazarlo por un Preview desactualizado **solo en clon QA**: la UI advierte ausencia y no presenta falsos cambios/counters, conservando estado SQL y sin inventar historial.

### Evidencia focal final privada

- **MariaDB 10.4.32** privado `127.0.0.1:18979`, BD de esquema real `mi_import_i05_test` y clon completo de la app en Temp. `scripts/dev/imports_i05_semantics_isolated.php` reemplaza **completamente** el fixture heredado de aplicación parcial por rechazo obligatorio: válida→inválida e inválida→válida; actualización de producto preexistente, producto nuevo, catálogos auxiliares, variantes, atributos, precios, stock e inventarios; trigger SQL de falla tardía; conservación de los diagnósticos de fila; ausencia total de escrituras y snapshots ante `rolled_back`; warning informativo con commit/snapshot v3 y rollback posterior verificado. **22 verificaciones SQL PASS**, identificador `I05_ATOMIC_ALL_OR_NOTHING_ISOLATED_PASS`.
- `scripts/dev/imports_i05_http_isolated.py`: **76 comprobaciones HTTP/SQL PASS** con sesión de administrador sintético, plantilla oficial 32 columnas, GET 200, login 302, Preview 200 sin SQL, Apply 302, POST directo intentando eludir Preview, CSRF inválido 419, snapshot v3 y auditoría, rollback HTTP 302, dos filas restauradas en detalle, missing/stale `_errors.json`, importación mixta `dry-failed` sin cambios ni snapshot, inyección SQL posterior a una escritura en Apply => `failed/rolled_back`, 2/1/1 contadores, `import_apply_failed` sin `import_finished`.
- **Regresión SQL privada:** V1C integridad/concurrencia, V1D durabilidad/ACK incierto, V1E variantes, 1F-A padre implícito, atributos/relaciones y LIFO transaccional concurrente: **6 suites PASS** luego de cambios I05, en BD descartables separadas `mi_import_v1c_*`, `mi_import_v1d_*`, `mi_import_v1e_test`, `mi_import_v1fa_test`, `mi_import_integral_test`, `mi_import_v1dc_test` (DDL sintético, sin datos comerciales).
- **Contratos:** I05 propio `scripts/build/imports_i05_atomic_contract.php` **21/21**; atributos 30/30; cache del padre 13/13; padre implícito 25/25; variantes 35/35; LIFO 19/19; rollback 21/21; snapshots 19/19; outcome 30/30; Admin 71/71. PHP lint focal, sintaxis Python y Node, `git diff --check` del perímetro propios PASS.
- **Visual auténtico:** `scripts/dev/imports_i05_visual_isolated.cjs`, Edge headless perfil QA aislado, **9 capturas** adicionales en `docs/modulos/capturas-importaciones/i05-detalle-{revertida,validacion-fallida,apply-fallido}-{390,768,1440}.png`. En los tres tamaños, cada estado muestra diagnóstico coherente y **scrollWidth = innerWidth**; la importación revertida conserva ambos SKUs y no informa «sin cambios». No se modificó shell global.
- **Gate general:** ejecutar al final tras última corrección y anotar resultado en cierre.

### Límites / condiciones de operación

`review_required` conserva protocolo manual de conciliación transaccional, barrera LIFO y snapshot; MySQL y filesystem siguen sin transacción ACID conjunta. El flujo de errores por fila usa atomicidad obligatoria, **sin opción de importación parcial**. Los contadores de filas válidas de un job fallido son diagnósticos de trabajo no confirmado, nunca inventario aplicado. La UI no reconstruye información histórica cuando el archivo de diagnóstico falta: solo informa ausencia, estadísticas SQL disponibles y auditoría.

No se usaron credenciales reales ni base comercial, ni pagos/servicios externos. No se hicieron commits, push, migraciones ni ediciones de archivos de otros propietarios.

### Gate y teardown definitivos I05

Después de la última corrección (conteos SQL del job fallido y etiqueta «Filas válidas · no aplicadas»), se repitieron PHP lint, contrato I05 **21/21**, outcome **30/30**, Admin **71/71**, contrato de atributos y `git diff --check`. **Gate coordinado final: 876 PHP, 120 JS y regresiones globales, `[gate] OK`, exit 0**. E2E HTTP privado repetido después de la corrección: **76 verificaciones PASS**. Capturas finales 390/768/1440 revisadas otra vez con Edge cerrado correctamente.

**Limpieza verificada:** PHP privado 127.0.0.1:18838 detenido; MariaDB privada 127.0.0.1:18979 apagada mediante mysqladmin (`exit 0`); ambos puertos sin procesos `LISTENING`. Eliminados completamente el clon, MariaDB/datadir, bases temporales, credencial sintética, sesiones y perfil aislado de `C:\Users\matii\AppData\Local\Temp\mi_import_i05_final_isolated`, más los cuatro auxiliares `imports_i05_*` propios. No queda entorno QA ejecutándose ni credencial de prueba persistente. Se conservan exclusivamente código, contratos y capturas de Importaciones en el proyecto compartido para auditoría. Sin escrituras comerciales ni commits/push. **Estado: entregado para auditoría final del chat principal, no aprobado para producción.**
