# MiTiendaPesca — Plan Maestro V2: cierre local y staging
**Fecha:** 25/10/2026. **Responsable:** MAIN CHAT. **Estado:** NO GO para producción. **Especialistas activos:** solamente 01, 02, 03 y 04.

## Decisión de alcance
Durante los próximos días se mantiene XAMPP como entorno de desarrollo. Ejecutar localmente solo pruebas seguras, sencillas y ya soportadas por runners existentes; no desarrollar nuevos arneses complejos ni insistir cuando los controles de seguridad de una herramienta impidan la ejecución. Pruebas dependientes de HTTPS, hosting, multi-navegador persistente, dominios, correo sandbox, configuración real del servidor y proveedores externos se trasladan a staging una vez contratado el plan. Los resultados previos se conservan y no se repiten sin motivo.
**Crítico:** diferir PRUEBAS no implica diferir CORRECCIONES. Un P0/P1 confirmado que pueda corregirse localmente sigue bloqueando la salida local y producción. Un diferido no es PASS.

## Puertas de aprobación
**L — LISTO PARA STAGING:** los P0/P1 locales conocidos están corregidos en el código fuente, no solo en clon; regresiones privadas existentes PASS; cambios integrados por MAIN mediante commit selectivo; reporte y backlog staging actualizados. Este estado NO permite vender ni publicar en producción.
**S — STAGING VALIDADO:** staging privado HTTPS, PHP parcheado y compatible, MariaDB sintética separada, rutas y roles, CRUD, uploads, sesiones multi-dispositivo, checkout HTTP concurrente, seguridad, responsive, pruebas de integración simuladas o sandbox, backup/restore y rollback documentados.
**P — GO PRODUCCIÓN:** 0 P0/P1, todos los gates aplicables PASS con evidencia; MP/ARCA/Correo Argentino/comunicaciones reales homologados en entornos permitidos o estrictamente deshabilitados, responsable comercial y autorización expresa del propietario. Sin evidencia suficiente: NO GO.

## Regla de clasificación y diferimiento
Cada prueba tendrá ID, módulo, criticidad, precondiciones, entorno, pasos, esperado, resultado, evidencia, responsable, dependencia de hosting y gate. Estados: PASS LOCAL / PENDIENTE LOCAL / BLOQUEADO DEFECTO / DIFERIDO STAGING / PASS STAGING / NO APLICA JUSTIFICADO. No se usan APROBADO o PASS para casos no ejecutados. MAIN puede diferir solo si documenta motivo concreto, ventaja de staging y prueba de salida. Mantener el original de todos los logs y fallos.

## Especialistas 01–04: trabajo LOCAL ahora
| Chat | Evidencia reutilizable | Acción local acotada | Traslado a staging |
|---|---|---|---|
| 01 Usuarios | 42/42 usuarios y 21/21 Auth HTTP/MariaDB, reset concurrente y cookie revocada, según RESULTADO_01 | MAIN revisa integración del PasswordResetService y UX de cierre al cambiar contraseña; lint/regresión existente solo si afecta código | Cambio de clave desde dos navegadores, remember-me, registro/perfil/direcciones positivos, OAuth y correo sandbox |
| 02 Catálogo | contratos y regresiones sin E2E completo según RESULTADO_02 | Correcciones demostradas, chequeos de código/SQL y runner existente si es fácil y seguro; no construir nuevo harness complejo | CRUD/admin-publicación real de producto e imágenes, variantes, roles, CSRF, responsive navegador |
| 03 Inventario | Proveedores 36/36, contratos de importación y QA focal según RESULTADO_03 | **P1: exportación CSV HTTP 500** por campos de variantes no existentes: corregir con reserva MAIN, probar export y roundtrip con MariaDB privada; verificar stock con suites existentes | Navegación final de import/export, restricciones de upload del hosting y rendimiento en entorno definitivo |
| 04 Checkout | Reproducción P0: dos órdenes y stock 5 a 1; candidato en clon 19+3+55+20 PASS, según RESULTADO_04 | **MAIN integra P0 en CheckoutOrderService fuente**; reejecuta prueba privada sin CandidateTokenFix, una orden y un descuento; integrar corrección AddressRepository luego de revisión | Checkout HTTPS/browser real, comprobantes, sesiones, integraciones y regresiones representativas del hosting |
| 05–10 | Sin RESULTADO_05 a RESULTADO_10 al corte | No activar todavía | Se asignarán al abrir la etapa de staging según capacidad y riesgos |

## Lista de bloqueantes y dependencias
- **04-P0-01 BLOQUEADO DEFECTO:** checkout_token construido con estado mutable de stock permite doble confirmación. MAIN integra fix candidato, analiza protección adicional de unicidad, valida concurrencia local SIN parche inyectado. No posponer el arreglo al hosting.
- **INV03-EXP-01 BLOQUEADO DEFECTO P1:** ImportsController consulta columnas de variante no presentes en esquema oficial; fix compatible y regresión en clon MariaDB. No posponer.
- **AUTH-UX-01 PENDIENTE LOCAL:** definir comportamiento de la sesión del propio usuario tras cambiar contraseña; comprobar lógica, UX; E2E de múltiples sesiones difiere a staging si harness bloqueado.
- **PHP-GATE DIFERIDO STAGING:** comprobar runtime HTTPS del hosting PHP seguro y mantenido; PHP local CLI 8.2.12 NO es prueba del hosting. No publicar en un runtime sin parche.
- **INTEGRACIONES DIFERIDO STAGING:** Mercado Pago solo pruebas con credenciales sandbox, webhooks firmados HTTPS; ARCA solo homologación autorizada; Correo Argentino productivo PROHIBIDO en QA, incluso consultas no evaluadas. Integraciones que no pasen pruebas se deshabilitan antes de GO.

## Procedimiento de trabajo y actualización
1. Especialista utiliza informe RESULTADO_XX y trabaja solo pendientes locales asignados. No commits, no tocar archivos compartidos sin reserva.
2. MAIN revisa evidencia y diff, corrige/integra selectivamente, reproduce regresión afectada y asigna estado. Evitar ciclo de auditorías generales sin nuevos defectos.
3. Actualizar simultáneamente este plan si cambia la política, el tablero CONTROL_VIVO.md por cada nuevo resultado, y el informe RESULTADO_XX como histórico verificable.
4. Toda prueba diferida queda vinculada a un ticket de staging con pasos concretos; se exige PASS real antes del gate correspondiente. Registrar versión Git, fecha, DB/entorno, HTTP/UI/SQL y logs redactados.
5. Al contratar hosting, levantar staging privado protegido por autenticación o allowlist, noindex complementario, credenciales y DB de prueba, control de egress a APIs productivas. Probar migración, rollback, restore y versión PHP antes de E2E.
6. Ejecutar gates finales en staging sobre un commit congelado; el propietario autoriza paso a producción.

## Matriz obligatoria para staging
| ID | Escenario exacto a completar | Evidencia de salida | Gate |
|---|---|---|---|
| ST-01 | Dos sesiones activas; cambiar contraseña en A; B revocada; remember-me revocado; comportamiento A consistente | HTTP/cookies redactadas, SQL sintético, capturas | S |
| ST-02 | Registro nuevo, perfil, direcciones propias/ajenas, recuperar cuenta sin correo real | HTTP+SQL+UI y 403/419 | S |
| ST-03 | Producto admin, upload imagen, variantes, IVA y precio, publicación visible y despublicación | UI+HTTP+SQL | S |
| ST-04 | Importación/exportación roundtrip, archivos inválidos, límites de hosting y permisos | CSV sintético, 403/419 y SQL | S |
| ST-05 | Checkout dos sesiones simultáneas + reintento + cancelación; una orden y un decremento único | trazas HTTP, pedidos e inventario SQL | S |
| ST-06 | MP webhook test firmado, replay, conciliación sin cobro productivo | logs redaccionados y SQL | P si MP activo |
| ST-07 | ARCA homologación autorizada o deshabilitación verificada; Correo Argentino sin llamadas productivas | logs de sandbox o flags, sin envío | P si activos |
| ST-08 | PHP web actualizado, HTTPS, cookies, permisos, cron, uploads, logs, backup-restauración y rollback | inventario y acta de recuperación | P |
| ST-09 | Responsive y navegación pública/admin desde navegador sobre assets HTTPS reales | capturas 363/390/768/1024/1600, UX sin fallos críticos | S |

## Compras de hosting y seguridad
Contratar solo tras verificar PHP soportado y actualizado, MariaDB, SSL, subdominio staging, acceso a logs, tareas cron, permisos de archivos, backups y posibilidad de restauración. No subir base de clientes ni .env comercial al staging. No publicar la tienda antes de cerrar gates.

**Documento histórico de referencia:** se conserva PLAN_MAESTRO_PREPRODUCCION.md v1 (incluido OWASP/ASVS y advertencias PHP). Esta V2 prevalece en distribución local-versus-staging, sin relajar requisitos finales.