# RESULTADO 08 — Tienda pública — QA LOCAL
Fecha: 25/10/2026. HEAD observado: `6063ae7`. Estado: **PARCIAL LOCAL**.

## Alcance y seguridad
Se leyeron PLAN_QA_LOCAL_05_10.md, 08_publico.md, CONTROL_VIVO.md y COORDINACION.md. Se comprobó git status: existen cambios ajenos en Ajustes, Auditoría, Facturación y evidencias. Ninguno fue modificado. Sin commits, sin proveedores externos, sin cambios de backend ni Checkout.

## Evidencia ejecutada
Comandos ejecutados desde `C:\xampp\htdocs\mi-pesca` con `C:\xampp\php\php.exe`:
- `scripts/build/categories_checkout_b1_contract.php`: PASS 31 contratos, exit 0.
- `scripts/build/categories_product_visibility_contract.php`: PASS 28 checks, exit 0.
- `scripts/build/categories_public_visibility_contract.php`: PASS 24 checks, exit 0.
- `scripts/build/categories_seo_b1_contract.php`: PASS 28 checks, exit 0.
- `scripts/build/categories_seo_redirects_contract.php`: PASS 35 checks, exit 0.
- `scripts/build/transversal_catalog_checkout_contract.php`: PASS 27 checks, exit 0 (SQLite/normalizer).
- `scripts/build/transversal_publication_stock_contract.php`: PASS 16 checks, exit 0 (SQLite memory).
- `scripts/build/brands_public_cover_contract.php`: PASS 8 assertions, exit 0.
- `python scripts/dev/verify_public_css_imports.py`: PUBLIC_APP_IMPORTS_OK 4, exit 0. Confirma referencias de imports, **no** descarga real en navegador.
Total: **197 checks/contratos PASS**, con alcances sintéticos/estáticos propios de cada runner. No equivalen a E2E HTTP.
- `git diff --check`: sin errores de whitespace (advertencias de normalización LF/CRLF en archivos ajenos).
- `Invoke-WebRequest http://localhost/mi-pesca/public_html/ -UseBasicParsing -TimeoutSec 5`: **NO EJECUTADO HTTP**; resultado: «No es posible conectar con el servidor remoto». No iniciar ni modificar servicios XAMPP compartidos.

## Matriz de cobertura
| Área | Resultado | Evidencia / límite |
|---|---|---|
| B1, visibilidad de productos y categorías | PASS LOCAL (contratos) | 31+28+24 checks; no prueba visual |
| SEO, redirecciones y URLs | PASS LOCAL (contratos) | 28+35 checks; no respuesta HTTP real |
| Integración catálogo–checkout, stock y marcas | PASS LOCAL (contratos) | 27+16+8; sin transacciones de navegador |
| Referencias CSS públicas | PASS LOCAL (estático) | PUBLIC_APP_IMPORTS_OK 4 |
| Inicio, búsqueda, filtros, paginación | NO EJECUTADO | servidor localhost inaccesible; requiere HTTP y navegador |
| Fichas, variantes, precio, disponibilidad | NO EJECUTADO (UI) | contratos B1 no sustituyen verificación de render |
| Carrito, cuenta y navegación a checkout | NO EJECUTADO (UI) | se reutiliza evidencia previa de especialista 04, sin repetir concurrencia |
| Vacíos, 404, enlaces, errores visibles | NO EJECUTADO (UI/HTTP) | servidor inaccesible |
| Responsive 363/390/768/1024/1600 y descarga CSS/JS | NO EJECUTADO | sin HTTP; no hay capturas ni comprobación de recursos |
| Teclado, contraste, foco, etiquetas y formularios | NO EJECUTADO | requiere inspección de interfaz operativa |

## Defectos y pendientes
**Defectos P0/P1 confirmados nuevos: ninguno.** El fallo de conexión local es un **bloqueo de entorno**, no un defecto demostrado del producto.

**Pendiente local practicable:** cuando MAIN confirme que XAMPP sirve la aplicación sin interferir con otras sesiones, recorrer home→catálogo→filtros→ficha/variante→carrito→cuenta→checkout con datos sintéticos; probar vacíos, ocultos, sin stock, 404, navegación teclado, formularios, foco y contraste; repetir 363/390/768/1024/1600, capturar red CSS/JS y consola, verificar overflow. No efectuar compra ni pagos reales. Registrar URL, captura y estado por caso.

**Pendiente externo/entorno:** dominio, HTTPS público, proveedores y compatibilidad multi-navegador en infraestructura definitiva, con comprobación futura de enlaces absolutos, canonical, cookies seguras y recursos. Esperado: navegación sin fallos, sin exposición de ocultos y sin peticiones a servicios no autorizados. Riesgo: comportamiento diferente fuera de localhost.

## Cambios / teardown
Solo se creó este informe. Sin cambios en código, sin scripts ni servicios iniciados, sin procesos propios persistentes. Se conservan evidencias históricas de Checkout 04 (19/19 + 3/3 + 75/75 + 20/20) sin reejecución.

**Estado final: PARCIAL LOCAL.** No implica GO de producción.

## Segunda intervención MAIN — 25/10/2026 — resultado de aislamiento
Se leyó el diagnóstico `DIAGNOSTICO_ENTORNO_QA_05_08_20261025.md` y se inspeccionó `scripts/dev/run_checkout_http_private.ps1` completo, junto con `checkout_http_real_fixture.php` y `checkout_04_visual_private.mjs`. **197 contratos previos no reejecutados**.

### Procedimiento intentado y comprobación real
- Se verificó el sistema privado aprobado de Checkout: copia por robocopy excluyendo `.env`, almacenamiento y subidas; generación exclusiva de .env de QA; MariaDB con datadir temporal y validación SQL de `@@datadir`, puertos locales y router PHP que devuelve assets estáticos y delega rutas a index.php.
- Se preparó en **TEMP fuera del repositorio** una variante corta del ejecutor para hacer GET sintéticos a inicio, catálogo, búsqueda, vacío, producto, carrito, login y 404, reemplazando sus suites de Checkout; ninguna prueba de concurrencia se repitió. El primer intento tuvo un error de sintaxis del archivo temporal, corregido antes de ejecutar.
- El intento posterior **NO INICIÓ** el servidor: guarda `REFUSED: private test directory already exists; review manually` y `REFUSED: private port still occupied`. No se ejecutó fixture ni se alteró BD.
- Inspección posterior: carpeta `%TEMP%\\mi_checkout_http_private_qa` existente; listeners TCP 18897 PID 18656 (PHP), 18896 PID 7824 (PHP), 18997 PID 8004 (MariaDB), 18998 PID 23364 (depuración de navegador). Línea de comandos de MariaDB apunta al datadir temporal original y PHP al router/clon QA original. **No se comprobó quién es propietario de esos procesos; no se intervinieron ni se usó su entorno.**
- No hubo navegación CDP, capturas ni asserts HTTP válidos de la tienda en esta intervención. No se presentan los GET planificados como realizados.

### Matriz actualizada tras MAIN
| Casos | Resultado | Limitación |
|---|---|---|
| Home, catálogo, búsqueda, filtros, paginación | NO EJECUTADO | runner privado ya ocupado por procesos existentes; no tomar instancia ajena |
| Producto publicado, oculto, sin stock, variantes | NO EJECUTADO | no se creó ni alteró fixture |
| Ficha, carrito, cuenta, Checkout sin pago | NO EJECUTADO | requiere sesión privada inequívocamente propia |
| 404, vacíos, recursos HTML/CSS/JS/imágenes y errores consola/red | NO EJECUTADO | no hubo navegador sobre servidor propio |
| Responsive 363/390/768/1024/1600 | NO EJECUTADO | no hay capturas comprobadas |
| Teclado, foco, labels | NO EJECUTADO | navegador de prueba no autorizado sobre instancia ocupada |

### Seguimiento exacto / seguridad
MAIN debe liberar y confirmar finalización de la instancia QA en puertos 18897/18896/18997/18998 y directorio temporal, o reservar un rango **diferente** y una copia/datadir propios para 08. Luego podrá ejecutarse solo el bloque de pruebas públicas, sin carreras ni checkout confirmado. No reutilizar el datadir ajeno, no matar esos PID sin identificar titular, no copiar .env comercial.

**Cambios:** se actualiza únicamente `RESULTADO_08.md`; se generó un script desechable en TEMP fuera del repo, sin ejecutar suites de Checkout. **Sin commits, sin modificaciones de backend, sin pagos ni llamadas externas.** No eliminar los procesos ni carpeta temporal encontrados.

**Dictamen segunda intervención: PARCIAL LOCAL — bloqueo de aislamiento concurrente comprobado.** Mantener 197 contratos históricos en su alcance; no existe evidencia E2E pública para aprobarla.


## Segunda intervención obligatoria — navegador privado real (25/10/2026)

**Dictamen actualizado: PARCIAL LOCAL.** Esta adenda prevalece sobre la afirmación anterior de que no hubo pruebas HTTP/navegador. HEAD inicial anterior `6063ae7`; contratos 197 **no reejecutados**.

### Bootstrap aislado verificado y resultado
- Se inspeccionó `scripts/dev/run_checkout_http_private.ps1`, `checkout_http_real_fixture.php` y `checkout_04_visual_private.mjs`. Se generó una adaptación **temporal fuera del repo** para omitir suites de checkout/concurrencia y utilizar sólo clon, semilla sintética, PHP privado y Edge/CDP.
- Preflight inicial: libres los puertos `18897`, `18896`, `18997`, `18998`, y ausente directorio QA previo. Runner comprobó clon sin `.env` comercial, base privada `mi_checkout_http_private_qa` y datadir físico bajo `%TEMP%\\mi_checkout_http_private_qa\\data`. Se cargó `PRIVATE_QA_SEED_OK`, y el GET real `http://127.0.0.1:18897/login` respondió HTTP 200. No se usó Apache global ni base XAMPP habitual.
- Se ejecutó Edge headless real mediante CDP, con **25 capturas** sobre páginas renderizadas y anchos 363, 390, 768, 1024, 1600; salida `BROWSER08_DONE pages=25 overflow=0 errors=0`. El runner finalizó con **exit 0**, publicó `Private test folders and ports cleaned`. Evidencia: `docs/evidencias/qa_public_08_20261025/summary.json` y capturas `*.png`. Adaptaciones temporales: `%TEMP%\\mi_public_08_runner.ps1`, `%TEMP%\\mi_public_08_browser.mjs`; no forman parte de la aplicación.
- El navegador documentó estilos presentes (`document.styleSheets.length=38`), elementos enfocables presentes y ausencia de desbordamiento **en los 25 renders ejecutados**. `errors=0` refiere a excepciones JS y eventos `Network.loadingFailed` interceptados, **no** acredita todos los HTTP status individuales ni accesibilidad manual.

### Resultados por escenario HTTP/browser
| Caso | Resultado | Evidencia / advertencia |
|---|---|---|
| Home real | **PASS LOCAL parcial** | `/`, h1 comercial visible, CSS cargado, 5 tamaños, capturas; sin overflow |
| Catálogo, búsqueda, vacíos | **NO ACREDITADO** | El script inicial construyó `//productos`, por lo que volvió a renderizar Home; capturas no corresponden a catálogo. No atribuirlo a la aplicación |
| Ficha sintética | **NO ACREDITADO** | Se usó equivocadamente `/producto/producto-qa-e2e`; aplicación define `GET /producto?slug=producto-qa-e2e`. La pantalla 404 observada fue debida al URL equivocado |
| Carrito sin sesión | **PASS LOCAL parcial** | `/carrito` mostró `Tu carrito está vacío` en 5 tamaños; faltan agregar producto, cambiar cantidades y flujo con sesión |
| Login y acceso a Checkout | **PASS LOCAL parcial** | `/login` mostró formulario; `/checkout` redirigió a login para invitado. Captura de destino inicial afectada por doble barra; falta flujo autenticado hasta checkout |
| Error 404 | **PASS LOCAL** | `/qa-ruta-inexistente-08` mostró página 404 propia |
| Filtros, paginación, variantes, ocultos, sin stock | **NO EJECUTADO** | Faltan fixture apropiado, URL correcto y acciones reales |
| Accesibilidad | **PARCIAL / NO ACREDITADA** | Conteo de controles enfocables únicamente; faltan Tab, foco visible, labels por formulario y uso teclado |
| CSS, JS, imágenes | **PARCIAL** | CSS cargado y sin errores CDP detectados; faltan inspección completa de status de CSS/JS/imágenes y recursos rotos |

**Advertencia importante:** 25 capturas no significan 25 pruebas funcionales PASS. La falta de overflow sí es evidencia verificable para las URLs efectivamente servidas, no certifica páginas distintas de las capturadas.

### Segunda pasada corregida e impedimento de concurrencia de herramientas
Tras consultar `config/routes.php` y `ProductController::show()`, se preparó corregir las rutas a `/productos` y `/producto?slug=producto-qa-e2e`, pero **no llegó a ejecutarse la segunda pasada**: el intento de nuevo bootstrap respondió `REFUSED: private test directory already exists; review manually`. Comprobación posterior mostró listeners activos en `18897`, `18896`, `18997`, `18998`, con `mysqld.exe`, `php.exe` y Edge/CDP del directorio privado. Dado el acceso concurrente, **no se mataron procesos ni se borró ese datadir**. El bloqueo es de propiedad/aislamiento del runner compartido, no un P0/P1 confirmado de tienda. No corresponde asignar a esta especialidad el control de dichos procesos ocupantes.

### Próxima acción concreta MAIN
Reservar una ventana exclusiva del runner privado Checkout y el puerto CDP; ejecutar sólo el script de navegación corregido tras verificar que el datadir privado fue creado por la propia ejecución y sin tocar la instancia ajena. Completar: búsqueda/filtros/página 2, ficha correcta, variantes, inactivos/sin stock, carrito autenticado sin compra, controles Tab/foco y recursos HTTP. Si no puede garantizarse exclusividad, conservarlos como **PENDIENTE ENTORNO LOCAL**, sin convertirlos en PASS.

**Sin commits, sin cambios de backend, rutas, Checkout ni infraestructura comercial. Estado final: PARCIAL LOCAL.**

## Anexo segunda intervención — verificación independiente de entorno privado (QA08)
**Fecha:** 25/10/2026. **HEAD al inicio de esta intervención:** `c39261b`. Este anexo se agrega sin sustituir la evidencia de otra sesión que también trabajó sobre QA08.

**Proceso:** inspección de `scripts/dev/run_checkout_http_private.ps1`, router y fixture existentes; adaptación temporal *fuera del repositorio* en `%TEMP%/qa08_private.ps1` y `%TEMP%/qa08_browser.mjs` para ejecutar SOLO navegación pública, sin suites ni concurrencia Checkout. El runner mantuvo clon aislado excluyendo `.env` comercial, directorio MariaDB propio, contraseñas sintéticas, verificación de datadir y `127.0.0.1` en puertos 18897/18896 (PHP), 18997 (MariaDB), 18998 (CDP Edge). La primera ejecución no arrancó por directorio ocupado y se respetó el rechazo; no se borraron archivos de otros procesos. En la siguiente ventana libre se verificó `Private MariaDB and isolated clone verified`, `PRIVATE_QA_SEED_OK`, GET /login 200, cero órdenes sintéticas, y finalizó `exit 0` con `Private test folders and ports cleaned`.

**HTTP REAL, base `http://127.0.0.1:18897`:** `/` 200, `/productos` 200, `/productos?q=QA` 200, `/productos?q=zzzqa08noexist` 200, `/carrito` 200, `/login` 200, `/checkout` 401 sin sesión y `/ruta-qa08-no-existe` 404. La URL `/producto/producto-qa-e2e` devolvió 404 porque no existe esa ruta; `config/routes.php` y el controlador confirman `GET /producto?slug=producto-qa-e2e`. **No imputar como error de producto**. Los status HTTP acreditan respuestas, pero no por sí solos el funcionamiento de filtros o carrito.

**Edge/CDP headless:** 35/35 capturas métricas sin desbordamiento ni CSS vacío sobre home, catálogo, búsqueda, búsqueda sin coincidencias, URL incorrecta de producto (página error), carrito y login, a 363/390/768/1024/1600 px. La evaluación del DOM reportó **2235 reglas CSS** cargadas y stylesheet `/assets/css/public/app.css`. **No se midió disponibilidad de cada JS/imagen ni se confirmó teclado funcional**. Capturas y resumen en `docs/evidencias/preprod_08_public_2026-10-25T18-40-37-075Z/` (verificar sello si otro escritor altera el árbol). Edge finalizó con `QA08_BROWSER_TEARDOWN completed`, y el runner limpió instancias y carpeta temporal de esta ejecución.

**Límites aún vigentes:** falta evidencia independiente de clics reales para paginar, seleccionar variante y actualizar cantidades; falta ejecución con sesión autenticada y teclado/contraste; el único fixture preparado por este runner fue el simple 197. La evidencia de otra sesión debe revisarla MAIN y puede acreditar escenarios adicionales, pero no se contabiliza automáticamente en este anexo. **Estado: PARCIAL LOCAL**, sin P0/P1 nuevos confirmados, sin commits ni modificaciones de código.

---

## Segunda intervención obligatoria — HTTP privado + Edge real (25/10/2026)

**HEAD observado al iniciar:** `c39261b`. No se repitieron los 197 contratos de la primera intervención. Inspeccionados el diagnóstico MAIN, plan local, coordinación, `run_checkout_http_private.ps1`, fixture y router privado existentes. Se reutilizó su clon/guardia MariaDB/router PHP y el mecanismo CDP Edge; la adaptación temporal y descartable se construyó fuera del proyecto. El runner completo de Checkout **NO** se ejecutó: sin concurrencia ni pedidos/pagos. Tampoco se modificaron backend, rutas, servicio Apache ni base XAMPP.

**Seguridad demostrada:** los puertos 18897/18896/18997/18998 estaban ocupados por otras sesiones; se respetaron. Se reservaron 18867 (PHP), 18967 (MariaDB), 18866 y 18968 (CDP), previamente libres. Clon privado `%TEMP%\\mi_public_08_private_qa` sin copiar `.env` comercial ni `storage` ni `uploads`; se generó un `.env` exclusivamente sintético, password temporal y BD `mi_checkout_http_private_qa` renombrada a `mi_public_08_private_qa`. Antes de escribir se verificó `@@port=18967`, `@@datadir` igual a `%TEMP%\\mi_public_08_private_qa\\data`, y BD inexistente antes de crearla. PHP escuchó solo en `http://127.0.0.1:18867`. Sin proveedores externos; en la segunda pasada se interceptaron y bloquearon solicitudes no locales, incluido `https://sdk.mercadopago.com/js/v2`. **Ningún pago ni llamada externa autorizada.**

**Primera pasada real — 55 capturas Edge:** home `/`, catálogo `/productos`, búsqueda `/productos?q=QA`, página `/productos?page=2`, detalle publicado `/producto?slug=producto-qa-e2e`, oculto `/producto?slug=producto-qa-oculto`, sin stock `/producto?slug=producto-qa-sin-stock`, carrito `/carrito`, login `/login`, checkout anónimo `/checkout` y ruta inexistente `/ruta-inexistente-qa08`; todas probadas a **363, 390, 768, 1024 y 1600 px**. Evidencia `docs/evidencias/preprod_08_public_2026-10-25T18-40-40-633Z/` (55 PNG + `summary.json`), `RESULTADO_08_BROWSER_LOG.txt`. Las 55 vistas presentaron CSS con reglas cargadas (2235), sin overflow del documento medido por CDP; excepciones JS de navegador registradas: 0. Ruta oculta y URL inexistente mostraron página 404; producto sin stock mostró `Stock: 0`. Búsqueda mostró filtro activo. **No equivale a probar interacciones de paginación/filtros ni contraste WCAG.** Las imágenes sin origen real de fixtures sintéticos se registraron como `brokenImages` 1–2 en ficha/inicio; verificar si son placeholders de la instalación excluida antes de abrir defecto de imágenes.

**Segunda pasada interactiva real:** semilla adicional `producto-qa-variantes` con opciones A stock 4 y B stock 0 en base privada; login HTTP sintético devolvió **429**, por lo que se usó una **sesión sintética creada por CLI en el clon** (no declarar login aprobado). Edge autenticado cargó `/carrito` con producto y total $3.000, ficha de variantes (opción B etiquetada sin stock), cambió selección a variante A (ID 204, precio $1.800 y stock 4), y pulsó `+` de cantidad 1→2. Se probó pulsación de Tab y el foco avanzó a botón móvil con `aria-label=\"Abrir menu\"`. Se ejecutó botón `Agregar al carrito` en ficha simple, pero **no quedó acreditado cambio persistido de cantidad** (la lectura posterior siguió en 2; requiere prueba focal de actualización POST y no presupone defecto). La sesión sintética entró a `/checkout` y mostró el paso **1/4 Elige tu dirección**, dirección privada sintética y botón Continuar, **sin confirmar pago ni pedido**. Evidencia `docs/evidencias/preprod_08_interactions_2026-10-25T18-46-01-232Z/interactions.json` y `RESULTADO_08_INTERACTIONS_LOG.txt`. El SDK externo de Mercado Pago intentó cargarse en Checkout; la intercepción lo bloqueó **antes de salir**.

| Caso | Resultado segunda intervención | Evidencia y límite |
|---|---|---|
| Home/catálogo/búsqueda/página 2 | PASS LOCAL render/navegación | 20 capturas, CSS activo; filtros y paginación no ejercitados por clic |
| Fichas simple, oculta, sin stock | PASS LOCAL render/visibilidad | 15 capturas; oculto muestra 404 y sin stock 0 |
| Carrito vacío / login / 404 / checkout anónimo | PASS LOCAL render, con observaciones | 20 capturas; checkout anónimo redirigió a `//login`, que terminó en home: **investigar URL doble barra**, no declarar experiencia anónima aprobada |
| Responsive y carga CSS | PASS LOCAL medido | 55 capturas, cinco anchuras, reglas CSS cargadas, sin overflow documento |
| Variantes, selector de cantidad, foco básico | PASS LOCAL focal interactivo | Selección ID 204, cantidad 1→2; foco por Tab; sin auditoría de contraste ni todas las etiquetas |
| Carrito con sesión / acceso Checkout | PASS LOCAL navegación | Sesión **inyectada sintéticamente**; carrito con producto y checkout paso 1/4, sin pedido |
| Alta carrito/actualización persistente | **NO ACREDITADO** | Se accionó el botón pero la lectura posterior no registró cambio. Requiere POST controlado y seguimiento de respuesta, sin repetir checkout |
| Login auténtico | **NO EJECUTADO CON ÉXITO** | En clon respondió HTTP 429; investigar throttle/configuración, no desactivarlo sin autorización |
| Recursos de imágenes | **PARCIAL LOCAL** | 1–2 `brokenImages` por páginas sin uploads reales; no confundir con falla definitiva de producción |
| Red externa | PASS AISLAMIENTO, NO PASA integración | Mercado Pago JS bloqueado en navegador; no se contactó API |

**Hallazgos para MAIN (sin edición reservada):**
1. `QA08-URL-01` (candidato P2): múltiples `form.action` se resuelven como `http://127.0.0.1:18867//carrito/agregar`, `//carrito/actualizar`, `//checkout/pagar`, `//logout`, y el Checkout anónimo terminó en `//login`/home. Revisar generación de `base_url()`/URL en contexto privado y reproducir en entorno equivalente antes de clasificar defecto de producto.
2. `QA08-AUTH-01` (entorno/investigación): login HTTP aislado devolvió 429; con sesión sintética la navegación funcionó, pero eso **no valida** login real.
3. `QA08-CART-01` (no acreditado, sin severidad confirmada): verificar mediante respuesta POST y SQL privados el efecto de Agregar y Actualizar cantidades; el clic por sí solo no prueba persistencia.
4. `QA08-ASSET-01` (observación): distinguir placeholders/archivos de uploads excluidos del clon de errores verdaderos de imagen.

**Teardown:** la primera ejecución tuvo un error inicial SQL sin seleccionar BD, fue corregido **solo en script temporal**, se limpió y repitió; segunda ronda requirió ajuste de creación de sesión sintética CLI (sin desactivar rate-limit). Las ejecuciones definitivas terminaron con salida 0 y mensaje `Private test folders and ports cleaned`. Verificados `%TEMP%\\mi_public_08_private_qa` inexistente y puertos exclusivos sin listeners. Los archivos temporales de scripts no forman parte del proyecto. No se ejecutaron commits, pagos, emails ni procesos externos.

**Estado segundo cierre: PARCIAL LOCAL.** Hay pruebas reales de navegación y responsive, pero faltan alta/actualización persistente de carrito, clic completo filtros/paginación y autenticación positiva (429); los P2 indicados requieren confirmación por MAIN antes de corregir código compartido. No es GO de producción.

## Tercera intervención — Reintento con puertos liberados (25/10/2026)
**Se confirma viabilidad real del entorno privado; la colisión anterior era temporal.** Tras repetir `Get-NetTCPConnection` para 18897/18896/18997/18998, no existían listeners y `%TEMP%/mi_checkout_http_private_qa` ya no existía. No se puede atribuir con certeza la ocupación anterior a otro chat.

### Arquitectura de la prueba realizada
Se reutilizó fuera del repositorio (en `%TEMP%/qa_public_08_runner.ps1`) el bootstrap seguro del runner Checkout: copia descartable sin `.env` comercial, MariaDB temporal `18997`, verificación `@@datadir`, base `mi_checkout_http_private_qa`, producto/usuario QA sintéticos y router PHP `127.0.0.1:18897`. Se sustituyeron las suites Checkout por GET públicos; **no hubo órdenes, pagos, ni ejecución de concurrencia**. Navegador Edge headless/CDP privado con perfil propio `edge_public08`, puerto debug `18998` y script TEMP `qa_public_08_browser.mjs`. Ninguna instancia compartida de Apache/MySQL fue alterada.

### HTTP REAL confirmado (PowerShell Invoke-WebRequest)
| Ruta privada | HTTP | Interpretación |
|---|---:|---|
| `/` | 200 | Inicio |
| `/productos` | 200 | Catálogo |
| `/productos?q=QA-E2E` | 200 | Búsqueda con 1 producto |
| `/productos?q=nonexistent-qa-998` | 200 | Estado sin resultados |
| `/producto?slug=producto-qa-e2e` | 200 | Ficha publicada sintética |
| `/carrito` | 200 | Carrito visitante vacío |
| `/login` | 200 | Acceso a cuenta |
| `/qa-no-existe-998` | 404 | Página de error válida |
Evidencia: `docs/QA_PUBLIC_08_HTTP_PRIVATE_20261025.log`. Las primeras URLs supuestas `/catalogo` y `/producto/<slug>` dieron 404 porque **NO** son rutas registradas; no se clasifican como defectos.

### Navegador real y responsive
Se efectuaron **40 capturas** Edge headless: ocho páginas por **363, 390, 768, 1024 y 1600 px**. En los 40 casos, `documentElement.scrollWidth <= viewport`, 38 hojas de estilo detectadas; telemetría CDP sin excepciones de JS ni `Network.loadingFailed` significativos. Ver `docs/evidencias/preprod_08_public_private_20261025_v2/results.json` y PNGs `home_*.png`, `catalog_*.png`, `search_*.png`, `empty_*.png`, `product_*.png`, `cart_*.png`, `login_*.png`, `notfound_*.png`. **PASS LOCAL**: carga/render inicial, ancho sin desbordamiento, búsqueda positiva/vacía y 404 visibles. Esta captura no comprueba interacción/teclado.

En HTML visto desde navegador: listado contiene `Producto sintetico E2E` ($1500); búsqueda QA-E2E devuelve un resultado; búsqueda inexistente muestra `No encontramos resultados`; ficha muestra Stock: 5, precio $1500 y controles de compra; login ofrece email/clave y checkbox `Recordarme` con labels. El carrito no autenticado muestra vacío, coherente con una sesión de visitante (la semilla de carrito pertenece al usuario sintético autenticado). Marca/branding y placeholder SVG se observaron descargados en catálogo; algunas imágenes con `naturalWidth=0` (por ejemplo placeholder JPG en ficha), **requieren revisión puntual** por posible carga diferida o imagen no disponible; el monitor de red no informó fallos globales.

### Alcance pendiente honesto
- **NO EJECUTADO:** clicks reales de filtros/paginación; no había segunda página de fixture. Variante seleccionable, producto oculto e inventario cero no fueron sembrados para esta corrida.
- **NO EJECUTADO:** agregar/actualizar carrito en sesión autenticada, navegación desde cuenta hasta checkout sin finalizar; la inspección solamente cargó páginas como visitante.
- **NO EJECUTADO:** Tab/Enter/Shift+Tab y verificación manual de foco visible; se inspeccionaron atributos de inputs: email/password/remember del login tienen etiquetas, pero varios campos de búsqueda `q` no aparecen asociados a `label`/aria-label en la inspección simple. Revisar contexto accesible de esos inputs antes de abrir defecto.
- Las 40 capturas no son validación visual manual completa ni prueba de éxito de todos los assets (imágenes lazy pueden estar sin decodificar).

### Incidencia del arnés (no del producto)
El ejecutor TEMP terminó **exit 1** aun cuando imprimió `PRIVATE_QA_SEED_OK`, HTTP esperados, `PUBLIC08_BROWSER_DONE 40 exceptions 0` y `Private test folders and ports cleaned`. Causa en el arnés temporal: una secuencia literal `\\r\\n` quedó junto a `$success=$true` después del bloque Node; el flag de éxito no quedó asignado limpiamente. **No se declara PASS del script completo**, solo de las evidencias verificables anteriores. El clon fue eliminado correctamente al final. No se modificó un runner versionado.

### Estado
**PARCIAL LOCAL con navegación HTTP y navegador REAL acreditados**, mejora sustancial respecto de la segunda intervención. Sin P0/P1 de aplicación confirmado; quedan interacciones positivas y variantes sin cobertura para un cierre completo. Ningún commit; no se tocó backend, rutas, Checkout, `CONTROL_VIVO.md` ni servicios comerciales.
