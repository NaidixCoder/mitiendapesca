# MiTiendaPesca — Bloque A MAIN, ejecución local MySQL/MariaDB
Fecha: 25/10/2026. **Origen:** 21 áreas / matriz E2E original. **Dictamen:** AVANCE FUNCIONAL REAL; **BLOQUE A NO CERTIFICADO COMPLETO**.
Servidor: XAMPP local MariaDB 10.4.32 (compatibilidad MySQL) en 127.0.0.1:3306. Base comercial no modificada deliberadamente.

## Aislamiento y entorno
- Creada `mi_pesca_qa_local` luego de verificar que no existía y estaba vacía. Importado `database/schema.sql` correctamente: **65 tablas**, entre ellas `users/products/orders/payments`.
- Fixture sintético persistente: tres usuarios (admin, cliente, mayorista), categoría QA, tres productos (publicado/oculto/sin stock), inventario ficticio, carrito cliente; emails `example.invalid`; sin usuarios/credenciales comerciales.
- Test SQL `qa_local_mysql_fixture_main.php`: INSERT/SELECT de usuarios, productos, categorías, carritos, restricción UNIQUE de email y ROLLBACK de los cuatro registros, **9 comprobaciones PASS**. Todo revertido.
- Bootstrap QA `qa_mysql_env_guard_main.php` define `env()` antes de aplicación, impone DB y conexión local, y exige flag `MI_PESCA_QA_MAIN=1`; no escribe `.env` comercial.
- **Incidente de aislamiento detectado y contenido:** el primer PHP CLI-server no aplicaba `auto_prepend_file` al router HTTP; `/__qa_dbcheck_20261025` detectó DB distinta y ese servidor se detuvo. Se corrigió el router para exigir el guard y verificar DB **antes de cualquier request dinámico**. La segunda ejecución confirmó `QA_DATABASE_OK mi_pesca_qa_local`. La primera pasada fue GET solamente, sin escrituras. No considerar acreditado ningún HTTP anterior a ese guard.
- El servidor de pruebas escucha solo `127.0.0.1:18855`; API productivas de MP, ARCA, Correo Argentino y SMTP no utilizadas.

## Resultados nuevos, acreditados sobre la base QA real
| ID | Ejecución | Resultado con evidencia | Límites |
|---|---|---|---|
| A1 | `qa_auth_http_main.py`: GET login, POST con CSRF/anti-bot real, sesión de cliente, GET /cuenta | **PASS HTTP**: login 302 a /cuenta y /cuenta 200 | Registro, recuperación de clave, cierre/revocación completos no recorridos en esta ejecución (reutilizar suites 01) |
| A1/admin | `qa_admin_read_main.py`: login admin, Dashboard, productos, edición, ajustes, pedidos | **PASS HTTP**: todas 200 | No acredita POST mutantes admin ni permisos IDOR |
| A2 | `qa_http_main_smoke.py`: home, catálogo, ficha publicada/oculta, carrito, login, 404 y checkout anónimo | **PASS HTTP focal**: 200/200/200/404/200/200/404; Checkout invitado 401 | Imágenes upload, variantes y CRUD completo aún no ejecutados |
| A3 | `proveedores_smoke_private.php` | **19/19 PASS** SQLite memoria proveedores | Falta circuito compra→recepción→stock y CSV integrado en MariaDB |
| A4 | `qa_cart_http_main.py` + SQL | **PASS HTTP+SQL**: cliente real, agregar producto qty 1/subtotal $2300, actualizar qty 2/$4600, acceder Checkout 200, quitar y verificar `cart_items` cero | No finalizar pedido/cobro ni cubrir alternativas de envío |
| A5 | `qa_payment_sql_main.php`, repo real | **6/6 PASS SQL**: pending, duplicado mismo ID, approved, huérfano sin pedido, importe/moneda, rollback. Sin proveedor externo | Falta webhook HTTP firmado/idempotencia stock/pedido y conciliación real |
| A6 | `facturacion_domain_smoke_private.php` | **40/40 PASS** SQLite memoria fiscal | MariaDB comprobantes/NC/numeración concurrente y ARCA integral NO ejecutados |
| A7 | `qa_settings_consumer_main.php`: AppSettingsRepository y ProductPublicVisibilityService HTTP | **3/3 PASS SQL+HTTP**: persiste setting, categoría inactiva + B1 ON da ficha 404; OFF devuelve 200; se restauran estado y setting anteriores | Otros consumidores de Ajustes no revalidados |
| A8 | `marketing_email_campaign_lifecycle_smoke.php` bajo guard QA | **PASS MariaDB**: alta/edición/cola sintética, transición, rollback, cero envíos | SMTP/automatización externa NO ejecutados |
| A8/SEO | `seo_readonly_smoke.php` bajo guard QA y `marketing_readonly_smoke.php` | **PASS MariaDB+render** con tres productos sintéticos, filtros y HTML de auditoría SEO, sin POST; marketing readonly PASS | SEO write/redirect y pipeline total no recorridos |
| Extras | `categories_readonly_smoke.php`, `brands_readonly_smoke.php`, `orders_readonly_smoke.php` | **PASS** luego de fixture con datos, consultas/render | No equivale a transacciones CRUD completas |

## Defecto de código detectado y corregido
En Windows, `dirname('/index.php')` puede devolver una barra invertida. `base_url()` y `sanitize_next_url()` en `app/Support/helpers.php` no normalizaban dicho resultado, causando Location inválido `http://127.0.0.1:18855\\/cuenta` después de POST login. MAIN normalizó el directorio con `str_replace('\\','/',dirname(...))`. Regresión `php -l` PASS y POST login HTTP 302 a **`http://127.0.0.1:18855/cuenta` correcto**, con sesión funcional.

## Casos faltantes concretos para considerar Bloque A completo
1. A1: registro/recuperación/cambio de clave/sesiones transversal, además de las suites históricas que ya lo acrediten.
2. A2: CRUD HTTP admin de producto/imagen/variante y publicación verificada en consumidor público; una modificación de admin fue bloqueada por restricción del conector y no se forzó.
3. A3: compra→recepción parcial→stock/costo, importación/reimportación CSV completa y rollback.
4. A4: escenarios negativos de descuentos, cambios de precio, direcciones/envíos y pedido final sin cobro real (reutilizar 04 si vigente).
5. A5: webhooks duplicados/replay/out-of-order, conciliación y transacciones negocio en integración real HTTP/SQL simulada.
6. A6: fiscal MariaDB autenticado, NC, numeración concurrente, roles/CSRF y errores ARCA simulados.
7. A7: consumidores restantes: pagos/mayoristas/envíos/email; cambios compartidos de Ajustes siguen de otros autores.
8. A8: SEO guardado→página pública, campañas/consentimiento/colas/reintentos completos sin SMTP real.

**No declarar esos casos PASS** por la existencia de los runners. Integraciones productivas y HTTPS público continúan FUERA de QA local, como establece el plan. No repetir suites estáticas sin cambios.

## Cambios y límites de seguridad
Solo QA database `mi_pesca_qa_local` con fixtures ficticios; código comercial sin cambios salvo corrección focal de paths en `app/Support/helpers.php`. No se modificaron datos de negocio, Apache XAMPP, backups ni servicios terceros. El archivo `.env` comercial no se copió. El servidor PHP privado se detendrá al finalizar; XAMPP MySQL levantado por el usuario permanece intacto.

## Reanudación y regresión después de interrupción
Se inspeccionó el servidor privado propio en 127.0.0.1:18855 (PID 6268 identificado por línea de comandos). Se repitió `python scripts/dev/qa_cart_http_main.py`: login 302 a `/cuenta`, agregar 200 ($2300), actualizar 200 ($4600), checkout autenticado 200, quitar 200 y carrito vacío; SQL de ítems cero acreditado en corrida previa. `qa_payment_sql_main.php` **6/6 PASS** (identidad, cambios, duplicado, rollback) y `qa_settings_consumer_main.php` **3/3 PASS** (404 y 200 según setting B1, restauración). PHP privado propio finalizó mediante PID verificado; XAMPP MySQL del usuario intacto.
El gate global ejecutado antes de la interrupción sobre los cambios presentes: **913 archivos PHP, 120 JavaScript, `[gate] OK`, exit 0**; tiempo 77,04 s. El número 913 incluye los scripts temporales de QA todavía en el árbol. Sigue sin existir acreditación E2E completa de A1–A8. Ninguna API de proveedor externo fue utilizada.


## Nueva intervención MAIN — continuación E2E local 25/10/2026
Se reutiliza exclusivamente la DB `mi_pesca_qa_local` con esquema de 65 tablas y fixtures sintéticos. Servidor PHP privado iniciado en `127.0.0.1:18855` con router `qa_http_router_main.php`, guard de DB antes de dispatch y sin servicios externos.

### QA de seguridad HTTP nuevo
`python scripts/dev/qa_security_http_main.py` **10/10 PASS**: tres diagnósticos retirados `/phpinfo.php`, `/tmp_session_dump.php`, `/tmp_session_info.php` (404); ruta inexistente 404; invitado a `/cuenta` y `/admin/products` (401); POST `/carrito/agregar` sin CSRF (419); login cliente sintético (302), cliente accediendo a admin (403), acceso a su cuenta (200). Confirma HTTP focal del P1 corregido, no certifica OWASP/IDOR global.

### Regresión E2E con DB real
- `qa_mysql_seed_main.php`: seed idempotente usuarios/productos/categoría/inventario/carrito QA OK.
- `qa_admin_read_main.py`: admin login, dashboard, listado/edición productos, ajustes y pedidos **HTTP 200**.
- `qa_cart_http_main.py`: login 302; agregar qty1=$2300, actualizar qty2=$4600, checkout autenticado 200, quitar ítem 200 y dejar carrito vacío, **PASS**. Sin pedido ni cobro externo.
- `qa_payment_sql_main.php`: **6/6 PASS** SQL repo real, pendiente→aprobado, duplicado idempotente, importe/moneda, huérfano, rollback, sin proveedor.
- `qa_settings_consumer_main.php`: **3/3 PASS** persistencia SQL y consumidor real B1 HTTP 404/200, estado restaurado.
- `marketing_email_campaign_lifecycle_smoke.php` con guard: **PASS** transiciones/cola sintética/rollback, **0 envíos**.
- `seo_readonly_smoke.php` con guard: **PASS** consultas sobre 3 productos y render.
- `facturacion_domain_smoke_private.php`: **40 comprobaciones sintéticas OK**, NO son MariaDB fiscal E2E.

### Alcance abierto
Continúan NO ACREDITADOS como E2E full: registro y recuperación de clave completos, CRUD de imagen y variantes, compras/recepción/importaciones con cruce SQL, confirmación idempotente de pedido y stock por HTTP sobre MariaDB, procesamiento webhook MP integrado y conciliación, facturación/NC concurrencia MariaDB y consumidor de SEO tras edición. No hay GO de producción ni declaración de cierre del Bloque A. No repetir estas suites aprobadas salvo cambios afectados.
