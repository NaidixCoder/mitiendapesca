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
