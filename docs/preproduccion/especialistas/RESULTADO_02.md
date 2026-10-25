# RESULTADO 02 — Catálogo y variantes — Preproducción
Fecha: 2026-10-25. Especialista 02. Árbol local: C:\\xampp\\htdocs\\mi-pesca. Commit base observado: `2a1f692`.
**Dictamen: PARCIAL / NO GO.** No se declara E2E integral. Este documento separa evidencia histórica de pruebas nuevas efectivamente realizadas.

## Seguridad del entorno y coordinación
- Se verificó `git status --short`: existen modificaciones ajenas en Auditoría, Ajustes, Facturación, documentación y capturas. No se alteraron.
- Reserva propia: exclusivamente este archivo `docs/preproduccion/especialistas/RESULTADO_02.md`. Controladores compartidos, rutas, migraciones, servicios y CSS quedan sin escritura por falta de reserva.
- No se iniciaron MariaDB, navegadores, servidores HTTP, pruebas con datos comerciales, servicios externos ni sesión real. Por lo tanto no hay datadir/puerto de instancia nueva ni teardown destructivo. No hubo commits.
- El runner `scripts/dev/run_transversal_e2e_private.ps1` existe, pero `docs/EJECUCION_E2E_MARIADB_PRIVADA.md` lo declara de ejecución exclusivamente local por el propietario, por bloqueos de seguridad de ejecución remota. No se intentó eludir esa restricción.
- Protocolos examinados: Plan Maestro, Checklist 21 áreas, Matriz E2E, Coordinación, Git Multichat, V2/V3, E2E MariaDB privada y navegadores; controles de seguridad y documentación de módulos aún requieren profundización focal.

## Evidencia previa reutilizable (NO se confunde con pruebas nuevas)
| Dominio | Evidencia de Plan Maestro | Límite |
|---|---|---|
| Categorías/B1/SEO | Contratos 27/27 MAIN SQLite; regresiones anteriores | Sin E2E completo autenticado/checkout |
| Marcas | SQL/HTTP histórico y 8/8 portadas públicas | No acredita todos los flujos actuales |
| Atributos | 25 HTTP MariaDB privados; 18/18 SQLite MAIN | Revalidar UNIQUE física, variantes y consumidores |
| Tags | 32/32 HTTP, 17/17 integridad/concurrencia, 13/13 consumidores | Falta asociación-filtro público-pedido en flujo completo |
| Importaciones/variantes | 76 HTTP/SQL documentadas | Falta CSV-producto-pedido E2E |
| Integración SQL transversal | 40/40 PASS ejecutado por propietario el 22/10/2026 | No incluyó compra HTTP con sesiones/CSRF ni pedido |

## Inventario inicial observado (lectura real de `config/routes.php`)
- 61 declaraciones GET/POST con prefijos `/admin/products`, `/admin/brands`, `/admin/categories`, `/admin/attributes`, `/admin/catalog-tags`; **cero claves método+ruta duplicadas** dentro de este conjunto, mediante `Select-String` + `Group-Object` PowerShell.
- Productos: listado, nuevo, edición, wizard-status, save, update-iva, toggle, siete operaciones sobre imágenes, más variantes.
- Variantes: JSON, fragmentos, atributos, valores, plantillas/conjuntos, generación, guardado, estado, eliminación y limpieza.
- Marcas: listar/formularios, save, toggle, quick. Categorías: listar/formularios, save, toggle, quick.
- Atributos: listar/formularios, catalog.json, save, quick, valores y conjuntos de plantillas.
- Tags: listar, guardar grupo, guardar etiqueta.
- **Importante:** el recuento se restringe a esos prefijos. No equivale a inventario total de rutas públicas, SEO, AJAX fuera de prefijo, consumidores, CLI, cron o webhooks.

## Matriz de resultados — checklist y seguridad ASVS
| Caso | Ruta/área | ASVS/amenaza | Evidencia | Resultado | Defecto |
|---|---|---|---|---|---|
| CAT-S01 | 61 rutas admin del conjunto | V4 control de acceso / V3 sesión | lectura `config/routes.php` + agrupación PowerShell | PASS: sin claves duplicadas observadas | Ninguno confirmado |
| CAT-S02 | 6 controladores admin principales | V14 calidad de código | `C:\xampp\php\php.exe -l` sobre Products, ProductVariants, Categories, Brands, Attributes, CatalogTags | PASS: 6/6 sin errores de sintaxis | Ninguno confirmado |
| CAT-S03 | Products/Variants/Attributes | V4 autorización y V13 API | inspección de wrappers: dos controladores delegan en traits/concerns | PARCIAL: revisión de traits requerida | Sin conclusión |
| CAT-03.01/02/03 | `/admin/products/save`, toggle, update-iva | V4 / V5 / V13, integridad SQL | rutas localizadas, sin escritura | PENDIENTE HTTP+SQL | No evaluado |
| CAT-03.05 | `/admin/products/image-*` | V5 validación, subida segura, path traversal | rutas localizadas | PENDIENTE multipart/CSRF/MIME/FS en clon | No evaluado |
| CAT-03.06, 04.* | `/admin/products/variants/*` | V4/V5/V13 y transacciones | rutas y traits identificados, evidencia histórica | CONTRATO HISTÓRICO, falta HTTP actual | No evaluado |
| CAT-03.07, 05.03/04 | productos/categorías/marcas públicos, SEO/B1 | V4 filtrado, V5 entrada | evidencia histórica 27/27 y 8/8 | CONTRATO HISTÓRICO, falta E2E público | No evaluado |
| CAT-05.01/05/08 | marcas/categorías/tags | V4/V5/V13 | rutas y suites previas | HISTÓRICO, no reejecutado | No evaluado |
| CAT-04.07/08/09 | valores/plantillas/variantes | V4/V5, integridad y concurrencia | 25 HTTP + 18 SQLite históricos | HISTÓRICO, falta regresión | Riesgo UNIQUE físico documentado |
| CAT-E2E | producto publicado→ficha→variante→carrito→pedido | V4/V5/V13 e integridad de stock | 40/40 SQL transversal previo | **NO PROBADO HTTP E2E** | Bloqueo de aprobación |

## Ejecución nueva y comandos observables
1. `git -C C:\xampp\htdocs\mi-pesca status --short` → working tree con cambios de otros especialistas; no acciones destructivas.
2. `git -C C:\xampp\htdocs\mi-pesca rev-parse --short HEAD` → `2a1f692`.
3. `Select-String config/routes.php` + agrupación por método/ruta → 61 rutas de alcance Admin; 0 claves duplicadas.
4. `C:\xampp\php\php.exe -l` para seis controladores → seis veces «No syntax errors detected». No es prueba funcional.
5. Lectura de `AttributesController.php` y `ProductVariantsController.php`: ambos son wrappers con traits; no inferir controles CSRF/permisos únicamente de los archivos raíz.

## Riesgos y pendientes prioritarios
- **P1 potencial, sin defecto confirmado:** validar en HTTP+MariaDB privada, con sesiones/CSRF originales y roles distintos, autorización por objeto, edición/publicación, IVA, imágenes, variantes, rollback, concurrencia e idempotencia.
- **P1 de cobertura:** no hay recorrido completo admin→público→pedido comprobado en esta intervención; coordinación con especialistas 03 stock y 04 checkout.
- **P2 de cobertura:** responsive 363/390/768/1024/1600 con navegador headless aislado y capturas; SEO, slugs/redirecciones, filtros y paginación.
- Leer detalladamente cada trait y repositorio y mapear consumidores/SQL, API/cron, con pruebas de seguridad focales. La falta de pruebas NO constituye vulnerabilidad confirmada.
- No se corrigió código: cero defectos confirmados y ningún archivo de implementación reservado para escritura.

## Teardown y entrega
No se iniciaron recursos externos ni navegadores ni MariaDB por este especialista; cero procesos propios pendientes conocidos. Este informe es únicamente evidencia de revisión estática parcial, **NO** una certificación de preproducción. MAIN debe mantener NO GO hasta pruebas HTTP/E2E, integridad, permisos y navegación real.

## Segunda pasada de QA focal — ejecutada sobre el árbol actual
**Suites invocadas:** PowerShell `& 'C:\\xampp\\php\\php.exe' (Join-Path 'C:\\xampp\\htdocs\\mi-pesca\\scripts\\build' $nombre)`; 12 ejecuciones independientes, todas `exit=0`:
1. `categories_admin_contract.php` 28 contratos.
2. `attributes_admin_contract.php` contrato V3 OK (no publica cantidad).
3. `brands_admin_contract.php` contrato Admin OK (no publica cantidad).
4. `brands_public_cover_contract.php` 8 assertions.
5. `categories_checkout_b1_contract.php` 31 contratos.
6. `categories_integral_contract.php` 18 checks.
7. `categories_product_visibility_contract.php` 28 checks.
8. `categories_public_visibility_contract.php` 24 checks.
9. `categories_seo_b1_contract.php` 28 checks.
10. `categories_seo_redirects_contract.php` 35 checks.
11. `categories_seo_redirects_head_tracking_contract.php` 31 checks.
12. `transversal_catalog_checkout_contract.php` 27 checks usando SQLite en memoria y normalizador.

**Regresiones adicionales:** `scripts/tags_repository_sqlite.php` → `APROBADOS 15`, exit=0. Verifica entre otros rechazo de etiqueta desconocida en variante, preservación de variante tras error, conteos, filtro de inactivos. `scripts/tags_view_fixture.php` → exit=0 y HTML sintético, **sin aserciones verificadas sobre resultado visual**. `categories_checkout_b1_contract.php` se volvió a ejecutar con exit=0, sin contarlo doble.

**Sintaxis:** 6/6 controladores Admin principales + 6/6 traits/concerns de Attributes/Variants sin errores PHP; 7 controladores públicos de `app/Controllers/Catalog` revisados con `php -l` sin error informado por el comando. Los traits examinados son `AttributesCatalogActions`, `AttributesValueSetActions`, `AttributesWriteActions`, `VariantAttributeActions`, `VariantLifecycleActions`, `VariantReadActions`. Estos checks son estáticos, no HTTP.

**Auditoría focal de seguridad:** `BaseController::__construct()` llama `require_admin()` si la función existe; los handlers mutantes examinados de `VariantLifecycleActions` invocan `verify_csrf_or_abort()` si existe. `generate()` abre transacción si no hay una, hace commit y rollback en error. `saveVariant` conserva `product_id` y `variant_id` en el servicio. Es necesario probar en HTTP real que las funciones existen durante el bootstrap, que verifican todos los roles y que los servicios validan ownership y serialización bajo concurrencia. **No se confirmó bypass**, y no se ha clasificado ninguna vulnerabilidad como demostrada.

**Conclusión de esta segunda pasada:** 13 scripts de contrato o regresión con exit=0 (12 contratos + tags SQLite); 1 renderizado de fixture con exit=0. Los recuentos de checks no son sumables sin revisar duplicaciones. No se ejecutaron HTTP autenticado ni MariaDB privada nuevos. Los resultados históricos y el runner exclusivo del propietario no se presentan como resultados E2E nuevos.

## Tercera intervención (mandato MAIN 25/10) — priorización E2E HTTP real

**Base actual y revisión:** al retomar, el HEAD local avanzó a `bffd58c` (la evidencia histórica anterior queda identificada por `2a1f692`); se leyó `docs/preproduccion/REVISION_MAIN_RESULTADOS_01_04_20261025.md`. MAIN acepta los contratos previos pero no los considera E2E. No se repitieron suites estáticas.

### Preflight verificable para MariaDB/HTTP aislados
Comando: PowerShell `Get-NetTCPConnection -State Listen -LocalPort <puerto>` y `Test-Path (Join-Path $env:TEMP <carpeta>)`, sobre puertos 18997/18897 (checkout), 18998/18898 (tags), 18989 (SQL transversal) y 18828/18969 (imports). **Resultado observado:** ninguno escuchando; las carpetas privadas `mi_checkout_http_private_qa`, `mi_tags_private_qa_20261023`, `mi_transversal_qa_20261022` no existen. No se conectó a servicios productivos ni se hizo ninguna escritura.

La infraestructura auténtica ya existente (`scripts/dev/run_checkout_http_private.ps1`, `tags_qa_private.ps1` y fixtures asociados) utiliza clon sin `.env`, base privada y puertos/datadir guardados. **Restricción de ejecución:** `run_checkout_http_private.ps1` y `docs/EJECUCION_E2E_MARIADB_PRIVADA.md` prohíben ejecución remota y reservan inicialización/CREATE DATABASE al propietario local; hay antecedente de rechazo por el control de seguridad del acceso remoto. No se invocaron ni alteraron esos runners, no se reutilizó la base comercial y no se intentó sortear esa restricción. No se encontró instancia privada existente para una prueba HTTP legítima. **Resultado: BLOQUEADO E2E nuevo, NO PASS.**

### Hallazgo focal nuevo: destino de redirección no validado en imágenes
- Fuente observada: `app/Controllers/Admin/ProductsController.php` método privado `jsonOrRedirect()` (al final de archivo). Para solicitudes no-AJAX, toma `$_POST['next'] ?? $_SERVER['HTTP_REFERER'] ?? url('/admin/products')` y entrega directamente a `redirect($next)`.
- Fuente observada: `app/Support/helpers.php` función global `redirect()` admite expresamente URLs `http(s)://` como `Location` de respuesta. Existe `sanitize_next_url()` para rutas internas, **no aplicada en este flujo**.
- Rutas consumidoras: `POST /admin/products/image-upload`, `image-delete`, `image-cover`, `image-assign`, `image-bulk-assign`, `image-bulk-delete`, `image-sort`, mediante `jsonOrRedirect()`. Control CSRF previo observado, por lo cual NO se afirma explotación anónima ni salto de CSRF.
- **Clasificación: P2, defecto estático reproducible en el flujo de código; redirección externa HTTP pendiente de reproducción.** Riesgo de navegación hacia dominio externo tras una acción autorizada, en especial si `next` procede de formulario manipulado. Mitigación propuesta a MAIN: aceptar solo destino relativo seguro y fallback fijo `/admin/products`, con función de sanitización apropiada sin tocar el helper global. Solicitar reserva exclusiva de `ProductsController.php` antes de corregir; este especialista no lo editó.
- Otro punto sujeto a validación HTTP/rendimiento: `ProductsController::index()` recibe `per` y `page` con mínimo 1 pero sin máximo; usa ambos para `LIMIT` y offset. **Riesgo P2 potencial** de consultas excesivas/overflow; no hay benchmark ni prueba de explotación. Definir cap y probar paginación en MariaDB privada antes de clasificar defecto.

### Matriz real todavía no acreditada
| Familia | Endpoint/s | Validación requerida | Estado de esta intervención |
|---|---|---|---|
| Productos CRUD/publicación | `/admin/products/save`, `toggle`, `update-iva.json` | sesión real, CSRF, roles, campos, SQL antes/después y B1 | BLOQUEADO: sin instancia privada |
| Relaciones / precios | guardar producto y variantes | SKU, IVA, costo, marca, categoría, etiquetas, FK, rollback | BLOQUEADO |
| Imágenes | siete `image-*` POST | multipart MIME/bomba, asociación, ownership, portada, orden, FS rollback, redirección | BLOQUEADO; P2 estático anotado |
| Variantes | `/admin/products/variants/*` | crear/generar/editar/eliminar, permisos, bloqueo de pedidos, carrera | BLOQUEADO |
| Público | `/productos`, ficha, categorías/marcas, sitemap | producto publicado→despublicado, B1, filtros, canonical/404 | BLOQUEADO |
| Responsive real | panel y público | Edge/Chromium headless 363/390/768/1024/1600, sesiones aisladas, teardown | NO EJECUTADO |

**Correcciones:** 0 (sin reserva de controlador). **E2E nuevos aprobados:** 0. **Teardown:** no se creó servidor, BD, navegador, datadir, perfil ni servicio externo; preflight mostró puertos privados libres. Se escribió exclusivamente `RESULTADO_02.md`. Mantener **PARCIAL / NO GO** hasta habilitar ejecución legítima del runner con MariaDB privada y finalizar HTTP+UI+SQL; coordinar stock con 03 y checkout con 04.


## Fase final MAIN — 25/10/2026 14:09 (Argentina)
**Dictamen formal de entrega: BLOQUEADO.** No es APTO PARA REVISIÓN MAIN como E2E integral. Esta sección prevalece sobre expectativas de cierre anteriores; no se inventan resultados de pruebas.

### Preflight ejecutado en esta intervención
- `git -C C:\xampp\htdocs\mi-pesca rev-parse --short HEAD`: `bffd58c`.
- `git status --short -- docs/preproduccion/especialistas/RESULTADO_02.md app/Controllers/Admin/ProductsController.php`: solamente `?? docs/preproduccion/especialistas/RESULTADO_02.md`; ningún cambio de implementación propio.
- `Get-NetTCPConnection -State Listen -LocalPort` para 18997, 18897, 18998, 18898, 18989, 18828, 18969: **ninguno escuchando**.
- Sin variables de entorno QA `MI_QA*` ni `TAGS_QA*` visibles. En `%TEMP%` se detectaron directorios `mi_auditoria_visual_qa_20261023` y `mi_settings_http_qa_20261023` (de otros ámbitos), no reutilizados ni modificados.
- Se comprobó nuevamente el mandato MAIN y la prohibición expresa `EJECUTAR SOLO LOCALMENTE por el propietario` de `scripts/dev/run_checkout_http_private.ps1`, más `docs/EJECUCION_E2E_MARIADB_PRIVADA.md`. Crear o activar base QA mediante ejecución remota sería contrario a esa restricción vigente; no se inició ni se buscó una alternativa para evadirla.

### Criterios de salida solicitados vs observados en esta fase
| Circuito | HTTP autenticado original | MariaDB privada + SQL | Navegador real | Dictamen |
|---|---|---|---|---|
| Alta/edición/publicación/desactivación producto | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Precios, IVA, categorías, marcas, tags | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Subida, ordenación y eliminación de imágenes | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Creación, edición y eliminación variantes | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Publicación en catálogo público/SEO/B1 | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Roles, CSRF, entrada inválida y rollback | No ejecutado | No ejecutado | No ejecutado | BLOQUEADO |
| Responsive móvil/tablet/escritorio | No aplica | No aplica | No ejecutado | BLOQUEADO |

**Riesgos abiertos:** no hay P0/P1 nuevos demostrados en esta fase; **no puede afirmarse ausencia de P0/P1** sin E2E. P2 ya documentado: redirección externa potencial en respuestas de imágenes; P2 potencial: paginación de productos sin tope. Ninguno se modificó por falta de reserva. Historial SQL 40/40, QA Tags y contratos permanecen acreditaciones históricas, no reemplazan E2E.

**Integridad/teardown:** no se creó ni se eliminó BD, datadir, servidor, browser, perfil ni archivo comercial. Ningún recurso de QA fue iniciado por este especialista. No hubo commits ni refactor. Solo se actualizó este informe.

**Para destrabar:** MAIN/propietario debe ofrecer un entorno HTTP+MariaDB QA ya inicializado con credenciales sintéticas y sesión/CSRF originales, con asignación y reglas de teardown, o habilitar expresamente un procedimiento local aprobado para ejecutar la suite de Catálogo. Recién entonces ejecutar y registrar aserciones HTTP+SQL+UI completas. La mera disponibilidad de Desktop Commander no revoca el veto de ejecución remota de los runners locales.

## Cierre local focal conforme Plan Maestro V2 — 25/10/2026
**Fuente rectora leída:** `docs/preproduccion/PLAN_DOS_ETAPAS_LOCAL_STAGING.md` (Plan Maestro V2, prevalece para reparto local/staging) y `docs/preproduccion/CONTROL_VIVO.md` (v2.0). HEAD observado `2291527`. No se repitieron suites anteriores ni se elaboraron runners.

### Reserva de código: solicitada a MAIN, pendiente de asignación
**SOLICITUD RES-02-PC:** reservar exclusivamente `app/Controllers/Admin/ProductsController.php`, métodos `jsonOrRedirect()` e `index()`, al especialista 02, sin extender edición a otros métodos/archivos. El archivo estaba sin diff al preflight, pero **CONTROL_VIVO no asigna una reserva explícita al especialista 02**. Conforme coordinación y mandato «solicitá reserva antes de editar», **no se modificó el controlador**. Solicitar que MAIN registre explícitamente la reserva, o que aplique MAIN el parche focal; no afectar al especialista 03/04.

### CAT-LOC-01 — Redirección externa (P2 justificado por flujo de código)
- Estado: confirmado por inspección de código, impacto HTTP no reproducido en base privada.
- `ProductsController::jsonOrRedirect()` líneas ~349–357 recibe `next` o `HTTP_REFERER` sin validación; `app/Support/helpers.php::redirect()` acepta destinos `http(s)://`. Afecta las respuestas no-JSON posteriores a las operaciones de imágenes.
- **Corrección propuesta al obtener reserva:** descartar `HTTP_REFERER` como destino configurable. Fallback fijo `/admin/products`. Aceptar `$_POST['next']` solo si es string de longitud razonable (por ejemplo ≤1024), **ruta absoluta local** bajo `/admin/products` (listado, edición y variantes) más query permitida; sin esquema, host, fragmento, slash doble inicial, backslash ni caracteres de control (incluidos tras decodificar una vez). Verificar estrictamente la ruta con allowlist (`/admin/products`, `/admin/products/edit`, `/admin/products/variants`), usando `parse_url` tras controles de entradas peligrosas; ante cualquier duda usar el fallback. Construir redirect únicamente con ruta local validada, **nunca URL externa ni cabecera Referer cruda**. Mantener respuestas JSON actuales intactas.
- Regresión focal requerida: `next=/admin/products/edit?id=7` redirige allí; sin `next` vuelve al listado; rechazar `https://example.test/`, `//example.test/`, `\\example.test`, `/%5c%5cexample.test`, `%0d%0a`, y Referer externo; verificar código HTTP 302 y Location mismo origen, flash y CSRF de operaciones de imagen. **PENDIENTE FIX/REGRESIÓN (sin reserva)**.

### CAT-LOC-02 — Paginación ilimitada (P2 rendimiento/correctitud)
- Estado: confirmado por lectura de `ProductsController::index()` líneas ~29–35: `page` y `per` tienen mínimo 1 pero ningún máximo; `$off=($page-1)*$per` antes de `COUNT(*)`, y se interpolan enteros en `LIMIT`. Riesgo de `LIMIT` excesivo, OFFSET inválido y desajuste de página solicitada respecto a páginas reales. No se ha confirmado incidente SQL/DoS.
- **Corrección propuesta bajo la misma reserva:** `$per=min(100,max(1,(int)($_GET['per']??20)))` (tope explícito 100); ejecutar COUNT usando filtros actuales y calcular `$pages=max(1,(int)ceil($total/$per))`, luego `$page=min(max(1,(int)($_GET['page']??1)),$pages)` y recién entonces `$off=($page-1)*$per`. Conservar el SQL parametrizado de búsqueda, orden y contrato del ViewModel. Así se limita el tamaño y se evita OFFSET de páginas fuera de rango sin inventar un máximo fijo de páginas que oculte resultados.
- Regresión focal requerida: `per=0,-1,20,100,101,999999999`; `page=0,-1,1,999999999`, listado vacío, última página, filtro sin resultados; comprobar `rows≤100`, que la página efectiva corresponda a total y que no haya SQL 500 ni overflow. **PENDIENTE FIX/REGRESIÓN (sin reserva)**.

### Traslado FORMAL a STAGING: ST-03 + ST-09 (y coordinación ST-04/ST-05)
Todos los casos siguientes están en estado **DIFERIDO STAGING**, no PASS. Precondiciones comunes: commit de MAIN congelado, staging HTTPS privado con PHP seguro, MariaDB sintética no comercial, datos/credenciales de ensayo, correo/pagos/ARCA/Correo Argentino deshabilitados o sandbox autorizado, logs HTTP/SQL/capturas sin secretos. Guardar SQL antes/después, método, URL, status, body seguro, sesión/rol, timestamp, navegador/viewport, limpieza y SHA.

| ID | Prioridad | Pasos en staging (sesión auténtica y CSRF original) | Criterios objetivos de aprobación |
|---|---|---|---|
| ST-03-PROD-01 | P1 | Admin crea producto con SKU/slug único, categoría y marca válidas; inspeccionar SQL y formulario; editar nombre/descripción/precio/costo | POST éxito/redirect válido, producto único persistido, datos coherentes GET Admin y SQL; duplicados/campos inválidos rechazados sin escrituras parciales |
| ST-03-PROD-02 | P1 | Producto borrador → publicar → consultar ficha y listado público → desactivar → repetir consultas | Activo visible con URL, precio, filtros/SEO correctos; inactivo excluido y ficha no accesible públicamente; B1 OFF/ON coherente con categoría/ancestros |
| ST-03-PRICE-03 | P1 | Cambiar IVA 21/10.5 y precios, categoría, marca, tags; leer Admin, SQL y ficha pública; probar IDs ajenos/inexistentes | Cálculos IVA/markup conforme regla comercial, FK válidas, asociaciones coherentes, sin alteraciones sobre otros productos ni precios stale |
| ST-03-IMG-04 | P1 | Cargar imagen válida y fallida (MIME/extensión/tamaño), asociar general/variante, cambiar portada, ordenar, borrar y recargar | Original seguro, MIME/tamaño validados, archivo y DB íntegros, portada única por alcance, orden persistente, sin orphan tras rollback, errores HTTP y UI correctos |
| ST-03-VAR-05 | P1 | Asignar atributos y valores, generar variantes, editar SKU/precio/stock/etiquetas, desactivar/eliminar; probar duplicado y variante con pedido asociado | Combinaciones únicas, FK y stock consistente, precios válidos, borrado bloqueado o pausa cuando hay pedidos, rollback íntegro ante error |
| ST-03-SEC-06 | P1 | Sin login, cliente y admin: GET/POST; CSRF ausente/cambiado; product_id/variant_id/image_id de otro producto; payloads límite; dos POST concurrentes | Anónimo redirigido/401/403, cliente 403, CSRF 419, IDs cruzados sin efecto, SQL y FS sin mutaciones indebidas, concurrencia sin duplicación/pérdida de actualización |
| ST-09-CAT-07 | P2 | Navegar Admin listado/formulario/variantes/imágenes y público catálogo/ficha en Edge/Chromium headless 363,390,768,1024,1600; probar modales, subir y ordenar | Capturas por viewport, sin overflow horizontal, controles accesibles por teclado, imágenes cargadas, interacción funcional, consola/red sin errores críticos y teardown de browser |
| ST-03-CROSS-08 | P1 | Compartir fixture de producto publicado y variante con especialistas 03 stock y 04 checkout; compra sintética con stock variable y doble intento | Stock/decremento de 1 sola orden e idempotencia acreditados por ST-05, sin tocar permisos ni código de 03/04; evidencia cruzada y acta MAIN |

**Responsables:** ST-03 especialista 02 + MAIN; ST-09 MAIN/02; ST-03-CROSS-08 MAIN coordina 03 y 04; ST-04 corresponde principalmente a especialista 03, ST-05 a 04/MAIN. **Gate:** S (STAGING VALIDADO), no L ni P anticipados. Ante P0/P1 confirmado allí, bloquear hasta corrección en fuente y regresión.

### Estado local al cerrar esta intervención
- Dos riesgos focales diagnosticados (P2), soluciones concretas preparadas; **sin reserva exclusiva no se aplicaron**. No se ejecutó lint ni contratos post-fix porque no hubo fix; sí quedó identificado el `git diff --check` exigible tras la futura edición.
- Único archivo modificado por 02: `docs/preproduccion/especialistas/RESULTADO_02.md`. No commits, no cambios SQL/comerciales, ni servicios externos.
- **Resultado: PENDIENTE RESERVA PARA CIERRE LOCAL; E2E DIFERIDOS STAGING.** No se declara «APTO» hasta verificar las correcciones locales autorizadas; estado global NO GO según Plan V2 y CONTROL_VIVO.
