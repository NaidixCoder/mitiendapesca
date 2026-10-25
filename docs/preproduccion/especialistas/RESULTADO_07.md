# QA LOCAL 07 — Ajustes y operaciones

Fecha: 25/10/2026. HEAD observado: `6063ae7`. Estado: **PARCIAL LOCAL**.

## Seguridad y límites
- Inspeccionados plan QA 05–10, mandato 07, CONTROL_VIVO, COORDINACION, documentación Ajustes, Auditoría, Marketing y checklist SEO. No existen `docs/modulos/envios.md` ni `docs/modulos/seo.md` en las rutas solicitadas; Envíos se documenta dentro de Ajustes.
- `git status --short` inspeccionado: cambios ajenos sin commit en Ajustes, Auditoría, JS Envíos y vistas fiscales. Todos conservados en SOLO LECTURA. No se modificó código ni `CONTROL_VIVO.md`.
- **No se llamó a Correo Argentino ni a otros proveedores**. No se iniciaron pruebas de red ni escrituras SQL. Sin credenciales comerciales.

## Matriz configuración → consumidor → prueba → evidencia → resultado
| Configuración | Consumidor | Prueba/expectativa | Evidencia disponible | Resultado |
|---|---|---|---|---|
| General, catálogo, contacto y redes | Home y catálogo | POST autenticado, CSRF y persistencia, rollback; lectura posterior sin 500 | `docs/modulos/ajustes.md`: suite histórica 155/155 en MariaDB privada | PASS LOCAL histórico; no reejecutado |
| Cuotas, descuentos, reservas y mayoristas | Carrito/checkout/precios | Guardado atómico y lecturas de consumidores | 155/155: guardados y GET de carrito/checkout; no transacciones comerciales reales | PASS LOCAL histórico, alcance limitado |
| Fiscal/ARCA | Facturación | Guardar sin sobrescribir `shipping_*`, lectura y control de acceso | Ajustes 155/155; controlador fiscal con cambios ajenos sin commit | PASS histórico; integración actual no revalidada |
| Correo, shipping, remitente, retiro local | Checkout/envíos | CSRF, guardado, secretos vacíos conservados y consumidores; tarifas simuladas sin salida de red | Ajustes 155/155 sobre configuración; sin evidencia focal de cálculo de tarifas mock con red bloqueada | PARCIAL LOCAL: simulación NO EJECUTADA |
| Hero y secciones | Inicio público | Persistencia JSON, imágenes, CSRF, rechazo de traversal y borrado seguro | Ajustes 155/155, imágenes físicas sobre clon; navegación 132/132 | PASS LOCAL histórico |
| Menú Ajustes | Ocho pantallas y subnav | Acceso, área activa y responsive | 132/132 y 45 vistas Edge históricas, `SettingsNavigation.php` sin commit | PASS LOCAL histórico, no reejecutado |
| Marketing/email | Campañas y automatizaciones | Configuración, token no expuesto, simulación sin SMTP | `docs/modulos/ajustes.md` y documentación `docs/pruebas/MARKETING_EMAIL_E2E_CIERRE_20261021.md`; no se disparó email | PASS histórico limitado / NO EJECUTADO envío simulado nuevo |
| Auditoría y retención | Feed, detalle, logins, cleanup | Roles, CSRF, conservación, limpieza sintética y trazabilidad | `docs/modulos/auditoria.md`: 105/105 HTTP/SQL/Edge sobre clon privado | PASS LOCAL histórico; cambios posteriores del presenter no revalidados en HTTP |
| SEO | Head, producto, categoría, marca | Canonical, meta, robots, OG, JSON-LD, integración de configuración | `docs/checklists/seo_prelaunch_checklist.md`: implementación documentada, pendientes de PDP; sin suite focal ejecutada en esta intervención | NO EJECUTADO focal |
| Errores y rollback | SettingsAtomicWriter y vistas | Fallo SQL forzado sin persistencia parcial, validación de límites | Ajustes 155/155 histórico | PASS LOCAL histórico |

## Brechas concretas y próxima revisión MAIN
1. Verificar vigencia de evidencias históricas frente a los archivos actuales sin commit, sin intervenirlos sin reserva.
2. Tarifas y retiro: realizar únicamente ensayo focal con mock y **bloqueo verificable de toda salida de red**, en clon descartable. Sin aislamiento garantizado, conservar NO EJECUTADO; jamás API productiva de Correo Argentino, ni consultas.
3. SEO: falta prueba focal local de canonical/meta/robots/schema en consumidores públicos; coordinar con especialista 08 para evitar duplicar.
4. Marketing: verificar contratos de consumidores sin SMTP ni envíos reales; conservar externos como PENDIENTE EXTERNO/ENTORNO.
5. Integración real con hosting, colas, SMTP, etiquetas, seguimiento, tarifas de proveedor y credenciales reales: PENDIENTE EXTERNO/ENTORNO, no PASS.

No se confirmó ningún P0/P1 nuevo; esto no acredita ausencia de defectos en casos no ejecutados. Sin nuevos procesos ni temporales propios; teardown no aplicable. Archivos modificados: únicamente este informe. Sin commits.

**PARCIAL LOCAL**
