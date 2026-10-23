# MiTiendaPesca — Cierre transversal técnico: dictamen del chat principal

**Fecha:** 22/10/2026. **Ámbito:** integración de Categorías, Importaciones, Atributos, Marcas, Catálogo, Carrito/Checkout y Mercado Pago. **Decisión actualizada:** regresiones internas y **E2E SQL transversal MariaDB aprobados (40/40, exit=0)**. **NO GO a producción** por homologación externa de Mercado Pago, conciliación operativa y validaciones HTTP/de pedido real que no cubre el ensayo SQL. El registro histórico de bloqueos anterior permanece abajo identificado como superado. La revisión estética se postergó por decisión del propietario.

## 1. Evidencia que sí existe

| Área | Ejecución acreditada | Resultado |
| --- | --- | --- |
| Importaciones I05 | 10 contratos integrados en gate; también SQL/HTTP sintéticos del especialista documentados en `docs/modulos/importaciones.md` | PASS; todos-o-nada y rollback con diagnósticos |
| Categorías ↔ Catálogo ↔ Checkout | `scripts/build/transversal_catalog_checkout_contract.php`, reales CategoryPublicVisibilityService, ProductPublicVisibilityService, CheckoutProductEligibilityGuard sobre SQLite in-memory | 27/27 PASS; B1 ON/OFF, ancestros, variantes y carrito mixto |
| CSV ↔ validación de atributos | Mismo contrato, RowNormalizer y RowValidator reales | incluido en 27/27 |
| Atributos/plantillas | `scripts/dev/attributes_integrity_isolated.php` con servicios reales y SQLite sintético | 18/18 PASS; borrar referenciados, rollback y pares inválidos |
| Marcas ↔ catálogo público | `scripts/build/brands_public_cover_contract.php` | 8/8 PASS; portadas HTTP/HTTPS y locales |
| Checkout ↔ pagos ↔ stock | Anteriores fixtures aislados MariaDB 18959 con repos reales, simulación MP y dos procesos PHP concurrentes de OrderFromIntentService | 22+15+18+10+6 PASS en el alcance documentado en `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md` |
| Firma de webhook MP | `scripts/build/transversal_mp_signature_contract.php` contra método real isSignatureValid, sin proveedor/DB | 15/15 PASS; HMAC válido, ID alterado, firma falsificada, encabezados ausentes |
| E2E SQL Importaciones ↔ Atributos ↔ B1 ↔ Checkout ↔ Stock | `scripts/dev/run_transversal_e2e_private.ps1` → fixture `transversal_import_category_checkout_isolated.php`, MariaDB 65 tablas, evidencia privada del 22/10/2026 22:16 | **40/40 PASS, exit=0; instancia destruida, puerto libre** |
| Gate de proyecto | `scripts/build/gate.php` (incluye contratos de áreas integradas) | **Reejecutado después del E2E: 890 PHP, 120 JS, `[gate] OK`, exit=0** |

**Diferenciar:** los contratos de fuente estática no son pruebas SQL/HTTP integrales. Los fixtures previos de importaciones, atributos y pagos fueron ejecutados por los especialistas y/o el principal con ambientes descartables y cuentan con evidencia propia. No atribuir a la presente pasada la repetición de todos esos escenarios.

## 2. Histórico del bloqueo del ensayo E2E (SUPERADO el 22/10/2026)

El archivo `scripts/dev/transversal_import_category_checkout_isolated.php` está preparado para usar **exclusivamente** `127.0.0.1:18989` / `mi_transversal_qa_20261022`, con verificación de puerto y nombre real de base; contiene productos, categorías y stocks sintéticos, y usa el ImportService auténtico con snapshot limitado a almacenamiento temporal.

En dos pasadas del principal la herramienta de ejecución remota **bloqueó la carga del esquema SQL privado y la ejecución del fixture**. Se respetó el bloqueo, sin acceder a la base habitual ni trasladar datos comerciales. La última MariaDB descartable fue detenida, la carpeta borrada y el puerto 18989 comprobado libre.

**Actualización:** ese bloqueo fue superado mediante ejecución voluntaria y local del propietario sobre una MariaDB temporal estrictamente aislada. El log acredita **40/40 pruebas SQL exitosas**. Esta advertencia describe únicamente la situación histórica anterior a la ejecución de las 22:16 del 22/10/2026.

**Alcance pendiente, distinto del E2E SQL ya aprobado:** un escenario HTTP autenticado con preflight, autorización/CSRF y confirmación única del pedido mediante el servicio real, incluyendo los impactos de stock y errores; y homologación MP por separado. Las 40 aserciones SQL verifican importación de producto/variante, atributos, inventarios, B1, validación de Checkout y rollback I05, pero **no generan pedidos ni validan HTTP/CSRF**.

## 3. Integridad de plantillas y UNIQUE física

El esquema actual `variant_value_sets` tiene `name`, `category_id` y `brand_id` opcional. Se aplica unicidad **lógica** en servicios administrativos por categoría/ámbito mediante bloqueos; **no existe UNIQUE físico** para el nombre de plantilla por categoría+marca.

**No agregar a ciegas** `UNIQUE(category_id, brand_id, name)`: MySQL/MariaDB permite múltiples `NULL` bajo UNIQUE. Antes hay que definir tratamiento de `brand_id IS NULL` y evaluar duplicados históricos en instancia autorizada, solo lectura inicialmente:

```sql
SELECT category_id, brand_id, name, COUNT(*) AS cantidad
FROM variant_value_sets
GROUP BY category_id, brand_id, name
HAVING COUNT(*) > 1;
```

La migración y saneamiento exigirían un plan de compatibilidad, backup y aceptación explícita; **no se ejecutó ningún ALTER en la base comercial**. El riesgo residual de escritores SQL externos queda registrado.

## 4. Mercado Pago — homologación y operación real

El checkout integrado utiliza **Checkout Bricks** y un webhook con validación HMAC cuando existe `MP_WEBHOOK_SIGNATURE_SECRET`. Los ensayos sintéticos de firma y pagos son satisfactorios. Para homologación con el proveedor falta disponer de credenciales de **prueba**, una URL HTTPS de pruebas, evento `payment` firmado desde configuración oficial, y confirmar respuesta/registro de webhook sin credenciales ni cuentas productivas. No se debe habilitar tráfico comercial hasta que esa validación se complete. Fuentes oficiales:

- https://www.mercadopago.com.ar/developers/es/docs/checkout-bricks/integration-test/test-payment-flow
- https://www.mercadopago.com.ar/developers/es/docs/links-and-debts/additional-content/your-integrations/notifications

Asimismo se requiere un **responsable financiero nombrado** que adopte el procedimiento de `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md` para pagos huérfanos, marcadores `payment_in_flight`, pagos duplicados y estados desconocidos; con verificación en portal del proveedor, acta de decisión, trazabilidad del contacto con cliente y autorización por caso. **Sin devoluciones automáticas**, sin forzar pedidos ni borrar marcadores por SQL sin auditoría.

## 5. Incidente de aislamiento del primer ensayo de Atributos

El especialista reportó que un primer arranque HTTP podía resolver la base habitual de `.env` y registrar como máximo un intento sintético de autenticación fallida. Se interrumpió y se sustituyó por router que verificó DB privada. El principal **no leyó ni alteró** logs/bases comerciales: no puede confirmar ausencia o presencia de ese evento. Si se precisa cierre forense: revisión auditada **solo lectura**, con autorización y principio de mínima divulgación. No hacer limpieza automática.

## 6. Dictamen y controles de salida

- **GO:** integración y regresiones internas de módulos, documentación y contratos sintéticos seguros.
- **NO GO:** producción/comercialización con cobros hasta completar el E2E HTTP/pedido pendiente, homologación externa MP y adopción formal de conciliación. **El E2E SQL MariaDB ya pasó 40/40.** Independientemente, tratar UNIQUE físico según análisis de compatibilidad.
- **Postergado:** aprobación visual, por decisión del propietario.
- **Protección de workspace:** preservar ediciones compartidas ajenas en `docs/ESTADO_DESARROLLO.md`; no reset/clean/stash ni commits globales.

**Estado global:** técnicamente avanzado con regresiones internas favorables, pero **no certificado para producción**; mantener este dictamen explícito hasta obtener evidencias faltantes.

## 7. Último gate y limpieza — evidencia comprobada

La ejecución del gate tras incorporar el contrato HMAC cerró con `[gate] OK`, **889 PHP y 120 JavaScript**; posteriormente se incorporó el contrato de publicación/stock y se ejecutó nuevamente el gate con **890 PHP, 120 JavaScript y exit 0**. Incluyó las pruebas de importaciones I05, Categorías, Checkout/MP, Atributos, Marcas, portadas HTTP(S), contrato transversal B1/CSV y los **15 casos firmados de webhook**. `git diff --check` focal PASS.

El principal verificó ausencia de servidor MariaDB transversal temporal, puerto privado 18989 libre y conservación de `docs/ESTADO_DESARROLLO.md` sin incluirlo en stage. Ningún pago, reembolso o registro comercial fue alterado.

**Dictamen actualizado:** auditoría, regresiones internas y **E2E SQL integral MariaDB (40/40) aprobados**. Producción y E2E HTTP/pedido siguen sin aprobación por los impedimentos externos y de alcance descritos arriba. No falsear estado ni reabrir módulos previamente integrados sin falla concreta.

## 8. Ratificación final del cierre de fase SQL (22/10/2026 — continuación MAIN CHAT)

El chat principal retomó por Desktop Commander el dictamen existente y **no repitió** la prueba destructiva ya aprobada. Comprobaciones nuevas, de solo lectura:

- Archivo de evidencia `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log`: contiene las 40 aserciones PASS y termina en `[transversal-import-category-checkout] OK 40 MariaDB cross-module assertions`; se había documentado `exit=0` en esa misma evidencia.
- `git status --short`: solo `M docs/ESTADO_DESARROLLO.md` antes de esta adenda; edición ajena respetada. `git diff --check` sin errores (aviso de conversión LF/CRLF, no fallo del check).
- Directorio descartable `%TEMP%\mi_transversal_qa_20261022`: ausente. `netstat -ano -p tcp | findstr :18989`: sin entradas para el puerto 18989 al verificar.
- Los contratos previos, la suite Checkout privada y el gate 890 PHP/120 JS permanecen como evidencias de ejecuciones anteriores; **no se declara una nueva ejecución**.

**DICTAMEN DE FASE:** `CIERRE APROBADO — E2E SQL TRANSVERSAL` (40/40). Sin cambios en datos comerciales ni operaciones de pagos en esta continuación. El E2E HTTP autenticado con creación real y única de pedido/impacto de stock constituye la **fase siguiente y aún no aprobada**; no confundir ambos hitos. La homologación externa de Mercado Pago y la adopción de conciliación financiera continúan como bloqueos independientes de producción. **NO GO A PRODUCCIÓN**.

## 9. Apertura de fase siguiente — E2E HTTP autenticado (22/10/2026)

Se inició el reconocimiento de cobertura sin efectuar operaciones comerciales. El router real expone `POST /checkout/stock/preflight`, `POST /checkout/confirmar`, `POST /checkout/transferencia` y `POST /checkout/mp/payment`, implementados en `PaymentController` y `CheckoutController`. Los controladores invocan `auth_required` y `verify_csrf_or_abort` en rutas de pago; la cobertura exacta del resto exige ejecución HTTP aislada.

El fixture previo `scripts/dev/checkout_order_concurrency_isolated.php` verifica `OrderFromIntentService` real, pero utiliza stubs para elegibilidad de producto, publicación, lealtad y correo. **No sustituye** una prueba HTTP autenticada de punta a punta. El runner SQL transversal aprobado declara expresamente `Do not invoke via remote tooling`; se respetó y no se reejecutó.

**Matriz de aceptación para el nuevo E2E HTTP, todavía NO ejecutado:** (1) servidor PHP y MariaDB privados con guardias de DSN, `DATABASE()`, `@@port` y carpeta descartable, sin `.env` comercial; (2) GET de login/sesión sintética y POST sin autenticación rechazado; (3) POST sin token CSRF rechazado y con token correcto aceptado; (4) preflight de ítem activo/stock suficiente, producto/categoría B1 oculta, cantidad excesiva y variante incompatible; (5) confirmación de transferencia o camino simulado sin MP, con pedido e items persistidos y descuento de stock único; (6) replay mismo token, dos solicitudes concurrentes y errores forzados con idempotencia y rollback; (7) verificar que no se enviaron correos, eventos reales, cargos o reembolsos; (8) destruir solo recursos del ensayo y comprobar puerto/carpeta libres; (9) ejecutar gate solo al cierre.

**Dictamen:** fase E2E HTTP **ABIERTA / NO APROBADA**. El cierre SQL 40/40 sigue aprobado e independiente. No habilitar producción sobre la base del ensayo SQL ni de stubs de servicio.

### Regresión global de esta continuación

Se ejecutó realmente `C:\xampp\php\php.exe scripts\build\gate.php` desde la raíz del repositorio: **890 PHP, 120 JavaScript, `[gate] OK`, exit 0** (runtime 101.61 s). `git diff --check` sin errores; avisos LF/CRLF no bloqueantes. No se ejecutó el E2E HTTP ni se utilizaron servicios externos.

## 10. Corrección de alcance del E2E HTTP — identificación de rutas efectivas

Inspección directa de controladores (sin ejecutar efectos): `POST /checkout/confirmar` es una ruta legacy deshabilitada: únicamente exige auth y redirige, **no confirma pedido**. En cambio `POST /checkout/transferencia` verifica auth, CSRF, email validado, dirección y envío; llama a `CheckoutOrderService::confirm()` y puede persistir un pedido pendiente de transferencia. El circuito MP usa `POST /checkout/mp/payment`, `CheckoutIntentService` y creación de pedido posterior a aprobación; preflight se atiende en `POST /checkout/stock/preflight`.

**Corrección de la matriz anterior:** el E2E HTTP debe probar por separado transferencia con pedido realmente creado y MP con proveedor simulado/fixtures, sin confundir el `confirm` legacy con una confirmación válida. El método de transferencia no necesita proveedor financiero externo, pero sí DB privada, usuario/carrito/dirección/envío sintéticos y neutralización comprobable de envíos/analytics. No ejecutar contra `.env` habitual.

**Estado de validación:** esta inspección es evidencia de ruta/código, no test HTTP ni PASS de E2E. Mantener NO GO hasta ejecución efectiva y comprobada de casos de sesión/CSRF/preflight/stock/orden/idempotencia en entorno privado.

## 11. Intervención integral V3 — corrección Checkout y HTTP sintético

**Código funcional corregido:** app/Services/Checkout/CheckoutOrderService.php. Riesgo P1: después del commit, Audit::log o la limpieza del carrito podían lanzar excepción capturada por el manejador transaccional general y comunicar falsamente que el pedido había fallado, aunque ya existiera y su stock estuviera reservado. Ambas tareas post-commit quedan aisladas con captura/log; las transacciones de orden, ítems y stock siguen intactas.

**Prueba focal:** scripts/dev/checkout_postcommit_integrity_isolated.php, **3/3 PASS** con CheckoutOrderService real y SQLite en memoria. Inyecta error de auditoría y de limpieza, verifica una orden efectiva y reporte exitoso, diagnósticos registrados y replay sin nueva orden ni segunda reducción de stock. Quedó integrado al gate.

**Gate final posterior a todos los cambios de código:** **893 PHP, 120 JavaScript, [gate] OK, exit 0** (73.58 s). Incluye el runner PHP post-commit y el lint de los dos archivos PHP del harness HTTP. El script Python compiló sin errores y su prueba HTTP pasó 17/17. Una ejecución anterior de gate (891 PHP y 120 JS) también había pasado, antes de incorporar el harness.

**HTTP sintético realmente ejecutado:** scripts/dev/checkout_http_synthetic_isolated.py junto a checkout_http_private_router.php y checkout_http_private_stubs.php. **17/17 PASS, exit 0** en servidor PHP temporal 127.0.0.1:18898 y SQLite privado bajo %TEMP%/mi_checkout_http_qa_20261022. Ejecuta CheckoutController, PaymentController, CheckoutOrderService y CheckoutStockPreflightService auténticos, sustituyendo explícitamente login/CSRF y repositorios por adaptadores de QA. Cubrió anónimo rechazado, CSRF inválido/faltante, dirección inválida, preflight con stock, ocultamiento sintético y cantidad excesiva, transferencia con una sola orden y stock de 5 a 3, replay sin duplicado, ruta legacy /checkout/confirmar deshabilitada y cierre de sesión. La primera corrida falló únicamente por una aserción equivocada sobre la URL final (la base privada mostraba orden=1 y stock=3); se corrigió el test, no el servicio. La última corrida pasó íntegra y se verificó limpieza del servidor, puerto y SQLite temporales.

**Límites no cubiertos:** inicio de sesión y middleware CSRF reales, repositorios reales sobre MariaDB aislada, B1 aplicado por sus servicios reales durante HTTP, concurrencia de peticiones HTTP, flujo completo con pago MP simulado y homologación oficial del proveedor. No denominar al 17/17 E2E HTTP productivo ni extender su resultado a esas áreas.

**Dictamen:** mitigado el defecto P1, prueba focal y HTTP sintético **PASS**; **E2E HTTP real de la aplicación NO aprobado aún**. La certificación de producción sigue **NO GO**, independientemente de los resultados anteriores.

## 12. Preparación de arranque HTTP real aislado — sin ejecución (22/10/2026)

Se creó `scripts/dev/run_checkout_http_private.ps1` como **primera etapa de arranque**, diseñado para ejecución voluntaria local: clona el sitio excluyendo .env, storage, uploads y .git; genera credenciales sintéticas, configura .env en el clon, prepara MariaDB propia `127.0.0.1:18997` y HTTP `127.0.0.1:18897`, comprueba puerto/datadir/base y esquema, realiza GET /login con bootstrap original y verifica que POST anónimo de transferencia no genera un pedido. El finally detiene únicamente procesos identificados del ensayo y elimina el clon/DB temporal si los recursos pueden validarse. Verificación de **sintaxis PowerShell PASS** mediante `scripts/dev/lint_checkout_http_private.ps1`; **no ejecutado** el proceso integral de arranque, ni probado su resultado operativo en este turno. El script informa expresamente que aún **no** prueba login autenticado, CSRF con sesión legítima ni confirmación/pago real. No denominarlo cierre E2E HTTP.

### Actualización — Ejecutor autenticado listo para ejecución local; pendiente resultado

Se amplió scripts/dev/run_checkout_http_private.ps1 respecto del bootstrap previo. Crea clon privado sin .env comercial ni storage, MariaDB 127.0.0.1:18997 y servidor PHP 127.0.0.1:18897, sesiones QA privadas, SMTP local bloqueado. checkout_http_real_fixture.php comprueba DATABASE(), @@port y @@datadir y crea comprador, dirección, categoría, producto, inventario y carrito sintéticos. checkout_http_real_authenticated.py ejecuta login y CSRF reales, preflight, producto inactivo, falta de stock, restitución del carrito, transferencia, pedido, ítems y decremento de stock, y replay sin duplicación. Guarda diagnóstico en docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log y destruye únicamente recursos temporales controlados.

**Validado antes de ejecución local:** sintaxis PowerShell PASS, PHP lint PASS y py_compile Python PASS; el directorio temporal de PHP coincide con TEMP de Windows. **E2E HTTP transaccional aún NO ejecutado ni aprobado.** El comando requiere ejecución voluntaria local del propietario y revisión del resultado. Mercado Pago externo y conciliación son alcances separados.

## 13. E2E HTTP autenticado REAL con MariaDB privada — APROBADO (ejecución local del propietario)

Fuente de evidencia persistida: `docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log`, leído y comprobado por el chat principal mediante Desktop Commander. El propietario ejecutó `scripts/dev/run_checkout_http_private.ps1` en PowerShell; salida `CHECKOUT_AUTHENTIC_HTTP_MARIADB_PASS 19` y cierre `[QA] AUTHENTIC HTTP CHECKOUT PASS`, sin error. **19/19 casos PASS**: MariaDB privada inicialmente vacía, anónimo rechazado, GET login auténtico, token CSRF real, rechazo de CSRF de login y preflight, usuario sintético autenticado, sesión y envío privados, dirección ajena rechazada, preflight real con stock, producto inactivo rechazado, stock insuficiente rechazado, reposición de stock/carrito, confirmación de transferencia por controlador real, una orden y un ítem reales, stock 5→3, estado `pending_payment`, replay sin duplicar orden ni descuento. El runner informó eliminación de recursos temporales y liberación de puertos. La inspección adicional con `netstat` mostró únicamente sockets `TIME_WAIT` en 18897/18997, sin evidencia de `LISTENING`; `TIME_WAIT` no equivale a servidor vivo.

**Dictamen de alcance:** `APROBADO — E2E HTTP autenticado de Checkout por transferencia y persistencia MariaDB privada` (19/19). Ya no queda pendiente repetir esta prueba. **No verificado aquí:** carrera simultánea de múltiples peticiones HTTP, transacción externa Mercado Pago y webhook de proveedor real, ni adopción operativa de conciliación. Los contratos de concurrencia e integridad anteriores son evidencias distintas; no extender esta aprobación a escenarios no cubiertos. Producción/cobros externos: **NO GO**, por homologación MP y aceptación financiera pendientes.

## 14. Cierre interno focal MP y extensión de concurrencia HTTP (23/10/2026)

El chat principal reejecutó **80/80 comprobaciones focales PASS, exit 0** sin proveedor externo ni bases comerciales: `checkout_payment_integrity_contract.php` 26, `checkout_payment_orphan_contract.php` 12, `checkout_intent_reuse_contract.php` 12, `transversal_mp_signature_contract.php` 15, `checkout_payment_frontend_contract.php` 12, `checkout_postcommit_integrity_isolated.php` 3. Cubren contratos y verificaciones ejecutables sintéticas de HMAC webhook, prevención de duplicados, estados inciertos, conciliación de pagos huérfanos, respuesta y post-commit. **Estos 80 checks NO son pagos reales ni webhook HTTPS de MP.**

Para cerrar el escenario HTTP simultáneo pendiente se incorporó `scripts/dev/checkout_http_real_concurrent.py` y se extendió `scripts/dev/run_checkout_http_private.ps1` para levantar dos servidores PHP privados independientes (18897 y 18896) contra la misma MariaDB 18997, usando dos sesiones autenticadas independientes de comprador sintético. `checkout_http_real_fixture.php` agrega `race_reset` que opera exclusivamente tras verificar host, puerto, nombre y datadir privados. El nuevo test dispara dos POST concurrentes, valida exactamente una orden, un ítem y stock 5→3, y exige marcador `CHECKOUT_REAL_HTTP_CONCURRENCY_PASS 3`. Se integró apagado del segundo proceso y verificación de liberación del tercer puerto. PHP lint, Python compile y PowerShell parser: **PASS**.

**Estado:** preparado y estáticamente validado, pero **NO EJECUTADO** el nuevo ensayo local de concurrencia. Necesita que el propietario reejecute el mismo comando local de PowerShell. Solo una salida `CHECKOUT_REAL_HTTP_CONCURRENCY_PASS 3` con exit 0 permite cerrar esta cobertura. Hasta entonces, concurrencia HTTP conserva NO GO y producción sigue NO GO por homologación externa MP y adopción del protocolo de conciliación. No repetir auditorías globales por este ensayo.

## 15. Concurrencia HTTP: primer intento NO aprobado — instrumentación focal

Ejecución local del propietario: test autenticado secuencial 19/19 PASS; test de dos POST paralelos contestó 302 /checkout en ambas sesiones; SQL quedó orders=0, items=0, stock=5. **No hubo duplicación ni sobreventa, pero tampoco se ejercitó la creación concurrente de pedidos: escenario NO APROBADO.** Se requiere diagnosticar causa funcional, no asumir un problema del lock ni dar PASS.

Se instrumentó el fixture QA con modo `diagnose` protegido por verificación del datadir privado, para leer exclusivamente datos sintéticos de carritos, envío y flash de cada sesión tras la carrera. El test anexa estos diagnósticos al fallo y el runner los conserva en `docs/QA_CHECKOUT_HTTP_PRIVATE_RESULT.log` antes de eliminar el entorno. Se agregó `-RaceOnly` para ejecutar solo esta prueba focal, sin repetir los 19 casos previamente aprobados. Sintaxis PHP/PowerShell verificada. **Diagnóstico aún no ejecutado por el propietario.**
