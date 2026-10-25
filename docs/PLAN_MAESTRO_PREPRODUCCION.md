# MiTiendaPesca — Plan Maestro de Preproducción

**Versión:** 1.0 · **Fecha de corte:** 24/10/2026 · **Responsable de integración:** MAIN CHAT · **Estado general:** NO GO, condicionado a puertas de salida.
**Objetivo:** publicar una primera versión segura, íntegra, funcional y recuperable, sin retrasar el lanzamiento por una refactorización estética/estructural masiva.
**Alcance:** tienda pública, panel Admin V3, integraciones, base de datos y despliegue. El pulido visual del Admin y la eliminación exhaustiva de legacy quedan para posteriores versiones; los defectos que comprometan seguridad/operación no se postergan.

## 0. Reconciliación histórica obligatoria — no repetir pruebas ya ejecutadas por rutina

Este plan parte de la revisión de `git log`, `docs/modulos/*.md`, `docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md`, `docs/QA_TRANSVERSAL_TECNICO_20261022.md`, `docs/CIERRE_MERCADOPAGO_20261023.md`, `docs/AUDITORIA_CSS_2026.md`, `docs/COORDINACION.md` y los informes técnicos. **No se reejecutaron íntegramente todas las pruebas al redactar este plan**. Se distinguen: evidencia documentada, pruebas reejecutadas personalmente por MAIN y faltantes. Los resultados históricos no acreditan automáticamente la compilación/despliegue final.

### 0.1 Integraciones y pruebas ya acreditadas

| Área | Evidencia previa aprovechable | Estado de reutilización / límite |
|---|---|---|
| Categorías, B1, catálogo y checkout | Contratos 27/27 MAIN (SQLite); regresiones específicas B1/SEO; informe de Categorías | Reutilizar como contratos de regresión; no sustituyen compra E2E HTTP completa. |
| Importaciones I05 y variantes | QA SQL/HTTP privado y regresiones 76 HTTP/SQL reportadas; contratos de atomicidad y rollback | Reutilizar; cubrir conexión con pedidos, stock y procesos públicos en E2E final. |
| Atributos y plantillas | 25 verificaciones HTTP MariaDB privadas reportadas; 18/18 SQLite repetidas por MAIN; integridad y asociación probadas | Reutilizar; mantener anotada cuestión UNIQUE física ante escrituras externas y NULL. |
| Marcas | Suite HTTP/SQL privada reportada, contratos focales MAIN, 8/8 portadas públicas | Reutilizar; comprobar rutas públicas y edición en recorridos generales. |
| Proveedores | 36/36 HTTP/MariaDB privados, 10/10 visuales, regresiones SQLite 19/19 (integración `9967096`) | Dominio cerrado técnicamente; no repetir suite íntegra sin cambio relevante. |
| Tags | 32/32 HTTP/MariaDB, 17/17 integridad/concurrencia, 13/13 consumidores, 15/15 SQLite (integración `8ad8e50`) | Dominio cerrado técnicamente; comprobar integración de producto en flujos completos. |
| Auditoría | 105/105 HTTP/SQL MariaDB y 15/15 visuales (integración `1d23409`); revisión posterior de caracteres | Dominio probado. Revisar correcciones posteriores pendientes de integrar y **origen** compartido de etiquetas, no solo vista. |
| Usuarios | 42/42 HTTP/MariaDB repetidas por MAIN, revocación inmediata 401 después de modificar `auth_required`, concurrencia último admin 4/4 (`6c852bd`) | Seguridad de sesiones corregida y acreditada; incluir regresión focal en gate de seguridad final. |
| Ajustes | 155/155 HTTP/MariaDB y 40/40 responsive informados por especialista; MAIN reejecutó 75 casos antes de bloqueo por imágenes preexistentes; navegación de 5 áreas/8 pantallas: **132/132 repetidas por MAIN**, con teardown correcto. Base `0fbc00d`. | **Reorganización final aún no integrada en commit** al corte; realizar commit selectivo y regresión focal. No rehacer 155 sin cambio de persistencia. |
| Facturación | Backend sintético 40/40 (integración `4fade2f`); rediseño visual **95/95 repetido por MAIN**, 19 vistas × 5 tamaños (`bd0a531`) | QA visual terminado; **NO** equivale a HTTP/MariaDB autenticado fiscal y homologación de concurrencia/ARCA. |
| Cruce Importaciones ↔ Atributos ↔ B1 ↔ Checkout/Stock | **40/40 E2E SQL privado**, instancia descartable, registrado en cierre transversal (22/10) | Reutilizar; expresamente no creó pedido ni probó HTTP/CSRF de checkout. |
| Checkout/Mercado Pago | Contratos firma 15/15, flujo de pago/stock/conciliación e idempotencia sintéticos; informe de homologación con focal 80/80 | **NO GO** para cobros hasta prueba externa autorizada con credenciales de prueba, webhook HTTPS firmado y conciliación documentada. |
| Marketing/Email | Evidencia HTTP/Edge/SQL del especialista e informe de alcance; integraciones externas no acreditadas | Inventariar flujos y responsabilidades pendientes antes de campaña/envío real. |
| Gate técnico global | `scripts/build/gate.php` con pasadas OK documentadas (cantidades dependen del corte, ~890–907 PHP, 120 JS) | Ejecutar **una vez** sobre el árbol final congelado; no duplicar gates pesados entre especialistas. |
| CSS | Auditorías estáticas existentes `docs/AUDITORIA_CSS_2026.*`, mapas de legacy/utilidades; Admin V3 implementado; no se detectó carga Tailwind directa en barrido focal MAIN | **No certificar “sin legacy”**. Evitar limpieza masiva preproducción; retirar solo código/dependencias inseguros o que bloqueen funcionamiento. |

**Precaución histórica:** documentos antiguos contienen estados PARCIAL/BLOQUEADO que después fueron superados. La adenda cronológicamente más reciente y el commit prevalecen; no convertir un bloqueo histórico en pendiente vigente. También existen cambios sin commit pertenecientes a otras sesiones; NO hacer `git add .`.

## 1. Reglas de ejecución y puertas de aprobación

- Congelamiento funcional: sin nuevas funciones. Sí correcciones P0/P1 y defectos funcionales P2 relevantes.
- MAIN CHAT es coordinador, único integrador y responsable de commits selectivos. Especialistas trabajan con archivos reservados conforme `docs/COORDINACION.md`.
- Cada resultado debe registrar: commit, entorno, fecha, comando/script, casos, PASS/FAIL, evidencia, cobertura y limitaciones; “HTTP 200” no equivale a operación comercial completa.
- Pruebas con escritura solo en copia/instancia aislada **sin .env ni datos comerciales**; verificar puerto y datadir antes de operar. Servicios externos mediante sandbox/homologación aprobados y sin envíos/cobros reales.
- Fallo conocido P0/P1 en seguridad, dinero, stock, datos o recuperación bloquea producción y se investiga de inmediato, sin esperar a fase 4.
- Severidad: P0 crítico, P1 alto (ambos bloqueantes); P2 medio con decisión y mitigación explícita; P3 menor puede diferirse.
- Criterio GO: **todos** los gates obligatorios en PASS, responsables de negocio definidos y autorización explícita del propietario. Ante evidencia insuficiente: NO GO, no “PASS estimado”.

## 2. Fases acordadas y relación con lo ya realizado

| Fase | Qué hacer ahora | Qué NO repetir de forma indiscriminada | Entregable / gate |
|---|---|---|---|
| **F1. E2E Admin completo — primero** | Inventariar rutas/pantallas reales, mapear operaciones CRUD, permisos, CSRF, archivos, estados, errores, búsquedas, paginación y conexiones inter-módulo. Priorizar Dashboard, Catálogo/Productos/Inventario, Categorías, Marcas, Atributos, Tags, Importaciones, Compras/Proveedores, Pedidos, Clientes/Usuarios, Marketing/Email, SEO, Auditoría, Ajustes y Facturación. | Suites locales cerradas del dominio sin cambios relevantes; utilizar sus scripts como regresión selectiva. | Matriz ruta→escenario→evidencia; operaciones críticas reales HTTP+SQL; pendientes por severidad. |
| **F1b. E2E público y conversión** | Inicio→búsqueda/listado→ficha/variantes→carrito→login/registro/direcciones→checkout→pago sintético→orden/stock→consulta cliente; errores, abandono, doble click, reintentos, restricciones B1, visibilidad, precios/envíos/promos. | El 40/40 SQL transversal NO se repite como sustituto de compra HTTP real; conservarlo como contrato técnico. | Flujos completos autenticados y anónimos en navegador, SQL y webhook simulado/sandbox. |
| **F2. Pulido final CSS público** | Auditoría visual mobile-first, catálogo, detalle, búsqueda, cuenta, carrito, checkout; contrastar CSS/JS dinámico, accesibilidad, rendimiento y consistencia; corregir defectos UX reales. | No rediseñar Admin ni emprender purga masiva de todos los selectores legacy. | Matriz pública 363/390/768/1024/1600, capturas, defectos corregidos y E2E de regresión posterior. |
| **F3. Seguridad exhaustiva — bloqueante transversal** | Revisar **todos** los puntos de entrada públicos/admin/API/webhooks/CLI/cron: sesión, revocación, autorización por objeto/rol, CSRF, XSS, SQL, subida/borrado de archivos, traversals, SSRF, secretos, logs, webhook HMAC/replay, rate limiting, pagos, stock, fraude, email, fiscal. Probar explotación segura y corrección. | No dar por nulo el avance de Usuarios/Ajustes/Auditoría; hacer regresiones focales y ampliación donde falte cobertura. | Matriz amenazas/rutas, P0/P1=0 abiertos, evidencias de casos negativos y fixes. |
| **F4. Integridad y rendimiento** | Concurrencia pedido/pago/stock, idempotencia, transacciones, índices/EXPLAIN, cardinalidad, carga moderada, imágenes, cache, paginación, backups de consultas pesadas. | No deducir rendimiento productivo desde fixtures pequeños ni repetir suites aisladas sin cambio. | Límites medidos, fallos corregidos y riesgos aceptados por propietario. |
| **F5. Infraestructura y recuperación** | Configurar dominio/HTTPS, variables separadas, permisos, cierre de rutas dev, migraciones versionadas, cron/colas, correo, logs/alertas, backup automático y **restore realmente probado**. | No usar datos/credenciales reales en scripts de QA. | Runbook reproducible de despliegue, restauración y rollback, pruebas PASS. |
| **F6. Staging + simulacro + gate final** | Desplegar commit candidato congelado en staging equiparable a producción; migración de ensayo, smoke crítico y E2E final afectado, homologaciones externas obligatorias, checklist GO/NO GO, despliegue/monitoreo y reversión. | No publicar por suma de PASS locales históricos ni por aprobación exclusivamente visual. | Acta GO/NO GO con autor, fecha y evidencia; propietario decide habilitar comercialmente. |

**Seguridad es continua:** la F3 es la auditoría sistemática final, pero P0/P1 detectados durante F1/F2 se bloquean/corrigen de inmediato. Después de F2 y de cualquier hotfix, reejecutar solo regresiones impactadas más smoke transversal; E2E amplio final sobre commit congelado.

## 3. Coberturas prioritarias aún NO acreditadas globalmente

1. **HTTP autenticado transaccional de compra completa** con pedido persistido, stock descontado exactamente una vez, pago/intento y respuesta de cliente; concurrencia, reintentos y fallo después del cobro. Los contratos actuales no sustituyen ese recorrido.
2. **Mercado Pago externo:** credenciales de prueba propias, HTTPS webhook firmado, approved/rejected/pending, duplicados/fuera de orden, conciliación de cobro sin pedido, responsable financiero designado. Referencias: `docs/CIERRE_MERCADOPAGO_20261023.md` y `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md`. **Bloqueante GO si se ofrecen cobros MP**.
3. **Facturación:** HTTP autenticado y MariaDB privada con escenarios fiscales/estados/competencia, y posterior homologación ARCA únicamente en entorno autorizado. No emitir comprobantes productivos desde QA.
4. **Navegación/compatibilidad total entre módulos** bajo una misma sesión y el mismo checkout; privilegiar enlaces entre órdenes, pagos, clientes, stock, auditoría, fiscal y reportes.
5. **Security gate integral:** controles de autorización por ID, carga de imágenes/documentos, webhooks, scripts dev y superficie pública; revisar endpoints dinámicos y configuración de despliegue.
6. **Recuperación real:** respaldo/restauración de base y archivos persistidos, reversión de código/migraciones, observabilidad y alertas.
7. **Marketing e integraciones externas** (correo, Correo Argentino, automatizaciones) solo si van a estar habilitadas desde el día de lanzamiento; caso contrario, deshabilitación verificable y sin rutas activas inseguras.
8. **Riesgo registrado** `variant_value_sets`: unicidad lógica validada; ausencia de UNIQUE físico con `brand_id NULL`, no alterar esquema sin diagnóstico y migración compatible.
9. **Ajustes y Auditoría posteriores al commit:** integrar selectivamente la reorganización de cinco áreas y las correcciones de caracteres/etiquetas, previa verificación de que pertenecen al responsable y no chocan con trabajo paralelo.

## 4. Matriz mínima obligatoria por circuito

Cada caso de `docs/preproduccion/MATRIZ_E2E.md` (crear durante F1) registrará: ID, área/ruta, acción, precondiciones, rol, datos sintéticos, expected HTTP/UI/SQL, prueba existente reutilizada, prueba nueva necesaria, resultado, evidencia, severidad y commit. Estado permitido: NO INVENTARIADO / CUBIERTO POR CONTRATO / PROBADO HTTP+SQL / PROBADO E2E / BLOQUEADO / DIFERIDO JUSTIFICADO. **Nunca marcar PROBADO E2E cuando solo exista lint, contrato o HTTP GET 200.**

La ejecución debe inventariar las rutas reales de `config/routes.php`, controladores y vistas antes de elaborar la matriz, para no omitir módulos ni inventar pantallas. Conservar un resumen de rutas públicas y administrativas, APIs, callbacks, webhooks, CLI y cron.

## 5. Coordinación y conservación de evidencia

- Mantener `docs/COORDINACION.md` y protocolos V2/V3 como reglas vigentes de aislamiento, seguridad y trabajo multichat.
- El especialista no realiza commits ni edita archivos compartidos reservados; MAIN integra con `git add -- <rutas explícitas>`.
- Ante falla de herramienta o falta de entorno: registrar PASO NO EJECUTADO; nunca sustituirlo por inferencia.
- Los documentos históricos se citan como antecedentes, no se reescriben para borrar sus limitaciones. Capturas grandes y bases temporales no se agregan al repositorio por defecto.
- Mantener inventario de artefactos dev, logs, backups y archivos privados para excluirlos del despliegue; no borrar archivos comerciales por “limpieza”.
- Fuentes de referencia: `docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md`, `docs/QA_TRANSVERSAL_TECNICO_20261022.md`, `docs/EVIDENCIA_E2E_SQL_TRANSVERSAL_20261022.md`, `docs/modulos/*.md`, `docs/CIERRE_MERCADOPAGO_20261023.md`, `docs/COORDINACION.md`, `docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md`.

## 6. Salida final y decisión

**NO GO hasta demostrar:** (a) operaciones críticas E2E públicas/admin sobre entorno aislado; (b) P0/P1 cerrados y regresiones; (c) facturación/pagos/integraciones habilitadas homologadas; (d) backup+restore ensayados; (e) staging, migraciones, rollback y observabilidad verificados; (f) aprobación expresa del propietario.

**Primer paso operativo:** crear matriz exhaustiva de rutas y cobertura del Admin, clasificando automáticamente cuáles cuentan ya con suites reproducibles. Comenzar por hallazgos reales y vacíos de cobertura, no reiniciar auditorías locales ya cerradas.


## 7. Adenda de seguridad — OWASP ASVS y plataforma PHP (24/10/2026)

**Decisión del propietario:** expandir la fase F3 a una matriz sistemática de seguridad; los 30 puntos iniciales son una lista de comprobación orientativa, **no** acreditan seguridad por mera presencia.

### 7.1 Matriz de seguridad trazable

- Base principal: **OWASP ASVS 5.0.0**, aspiración a verificación de requisitos aplicables de nivel 2 con selección y justificación según riesgos; complementos OWASP WSTG y API Security Top 10 y contratos específicos de negocio.
- Auditar todas las rutas identificadas: tienda pública, Admin V3, API/AJAX, callbacks/webhooks, CLI/cron y superficie de despliegue; registrar por requisito ASVS: identificador/versión, activo y punto de entrada, amenaza, condición aplicable/no aplicable justificada, prueba/script, resultado, evidencia, defecto/severidad, corrección y regresión. Sin presuponer que 100 % de rutas equivale a 100 % de amenazas.
- Cobertura agrupada: identidad/sesiones, autorización horizontal/vertical por ID, autenticación y contraseñas, CSRF, validaciones/XSS/SQL/SSRF, cargas/descargas, cookies/CSP/headers/HTTPS, secreto y API keys, dependencias, logs/datos personales, rate limiting/bots, webhooks HMAC/idempotencia, pagos/pedidos/stock/concurrencia, integraciones ARCA/Correo/Email, DB privilegios mínimos, backups y recuperación.
- Estados: SIN INVENTARIAR / PENDIENTE / PASS CON EVIDENCIA / VULNERABLE / NO APLICABLE JUSTIFICADO / BLOQUEADO. Reutilizar evidencia válida Usuarios 42/42, Ajustes 155/155 y otros contratos solo para el requisito exacto demostrado; volver a probar si cambian código/plataforma/configuración.
- No se permite GO con P0/P1 abiertos o requisitos aplicables críticos sin evidencia; pruebas controladas solo contra ambientes privados/sandbox.

### 7.2 Avisos oficiales de PHP — riesgo urgente previo al lanzamiento

**Fuente primaria consultada 24/10/2026:** https://www.php.net/archive/2026.php y https://www.php.net/supported-versions.php .

El **21/10/2026** PHP publicó correcciones para dos vulnerabilidades de RCE sin autenticación: **CVE-2026-109618** (corrupción de estado ante OOM) y **CVE-2026-109619** (estado global obsoleto/use-after-free). Versiones mínimas parcheadas: **8.2.35 / 8.3.36 / 8.4.27 / 8.5.12**; Apache mod_php, FPM, CGI y otros están comprendidos. Actualizar es obligatorio; mitigaciones parciales **no** reemplazan el parche. PHP 8.6 todavía es candidato RC, no instalar RC en producción.

**Hallazgo verificado MAIN:** `C:\\xampp\\php\\php.exe -v` devuelve **PHP 8.2.12 (CLI, compilado 24/10/2023)**. El mismo CLI devuelve `session.upload_progress.enabled=1`. Esto **NO confirma aún** la versión/configuración que utiliza Apache; se exige verificar el runtime web y el servidor de hosting destino por separado, sin exponer phpinfo públicamente.

El **23/10/2026** PHP comunicó advertencia adicional relativa a `session.upload_progress.enabled` en combinación poco habitual de serialize handlers, con **CVE-2026-115560** previsto para una próxima corrección. La recomendación temporal oficial es `session.upload_progress.enabled=0` hasta nueva versión; verificar que esté desactivado en el runtime web y reiniciar procesos propios bajo cambio controlado. No tocar configuración del XAMPP activo sin plan de rollback ni interrumpir otras tareas.

**Criterio de versión para producción:** preferir la última revisión estable y parcheada de PHP 8.5 (al 24/10: mínimo **8.5.12**) tras E2E de compatibilidad; PHP 8.4.27+ es alternativa soportada con menor riesgo de migración, previa validación. PHP 8.2.35 corrige las dos RCE pero la rama termina soporte 31/12/2026, por lo que **no** se recomienda como base nueva a largo plazo. No usar PHP 8.1 o anteriores sin soporte del proveedor y verificación explícita. Confirmar con cada proveedor de hosting sus backports, binarios, configuración y extensiones reales.

**Tareas bloqueantes nuevas:** inventario de CLI/Apache/staging/hosting y versiones extensiones; matriz PHP/Composer/dependencias; elegir runtime parcheado; staging PHP actualizado con lint, gate global, E2E y smoke público/admin y workers; prueba de sesiones, imágenes/uploads, mail, SQL/driver PDO, cron, Mercado Pago/ARCA; logs sin warnings/deprecations críticos, configuración de memoria y límites de petición; evaluar despliegue/rollback y ventanas de actualización futura. Guardar evidencia de versión web mediante prueba privada, no endpoint público persistente.

**GATE P0:** nunca publicar en PHP afectado por las dos CVE de octubre; no iniciar cobros si plataforma/producto carece de parche y verificaciones de seguridad. La detección local de 8.2.12 es real para CLI; estado Apache/prod permanece **PENDIENTE DE MEDIR**, no asumir afectación confirmada del proveedor externo.


## 8. ACTUALIZACIÓN VIGENTE — Plan local + staging (25/10/2026)

**Decisión del propietario:** en esta fase solo trabajan especialistas 01–04. Todas las pruebas cuya ejecución local sea desproporcionadamente compleja, dependa de HTTPS/hosting/proveedor sandbox o esté impedida por controles de seguridad de la herramienta pasan a una cola detallada de staging. **No se consideran aprobadas ni se omiten de las puertas finales de producción.**

**Documento operativo prioritario:** `docs/preproduccion/PLAN_DOS_ETAPAS_LOCAL_STAGING.md`. **Tablero de estados obligatorio:** `docs/preproduccion/CONTROL_VIVO.md`. Ambos prevalecen sobre las indicaciones de fases F1–F6 del presente documento únicamente en el **momento y entorno de ejecución**, no en los requisitos de seguridad, integridad ni lanzamiento.

**Prioridad local inaplazable:** corregir en código fuente el P0 de idempotencia Checkout (dos pedidos/stock 5→1; candidato comprobado solo en clon), y el P1 de exportación CSV en Importaciones (columnas de variante inexistentes). Mantener Auth corregido y asegurar regresiones permitidas. MAIN integra selectivamente y documenta cada commit. No iniciar producción por tener pruebas locales PASS.

**Etapa staging:** al contratar hosting, configurar entorno cerrado con PHP parcheado y DB sintética, pruebas completas HTTP/UI/roles/cookies/archivos/concurrencia, backup+restore, HTTPS, sandbox o proveedores deshabilitados. Correo Argentino en producción no se usa para pruebas. Staging verificado tampoco autoriza producción sin acto GO y aprobación expresa.

**Mantenimiento:** cualquier nueva auditoría actualizará tablero y reporte del especialista con estado, evidencia y pendientes, manteniendo histórico. P0/P1 reales nunca se rebautizan como diferidos para avanzar una etapa.
