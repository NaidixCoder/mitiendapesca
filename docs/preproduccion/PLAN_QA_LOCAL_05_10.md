# Plan operativo vigente — QA LOCAL de especialistas 05–10
**Versión 3.0 | 25/10/2026 | MAIN CHAT | Producción NO GO**

## Decisión del propietario
Trabajar **únicamente en entorno local XAMPP**, sin despliegue, staging ni contratación como dependencias de esta tanda. Meta: cierre de pruebas locales en las diez especialidades. **01–04 cerrados localmente** según `ACTA_CIERRE_LOCAL_20261025.md`, gate PHP 909/JS 120 PASS; su cierre tiene límites de cobertura y no equivale a producción apta. **05–08 ACTIVOS ahora**; **09–10 pendientes** para la siguiente tanda, después de integrar 05–08.

Los documentos previos `PLAN_DOS_ETAPAS_LOCAL_STAGING.md` y las secciones históricas de `PLAN_MAESTRO_PREPRODUCCION.md` se conservan como antecedentes. Este plan prevalece sobre ellos **para el orden y entorno de ejecución actual**. Los requisitos de seguridad y criterios de producción continúan vigentes. Los diferidos previos se conservan; no convertirlos en PASS.

## Criterios estrictos de ejecución
1. Reutilizar evidencia histórica vigente de los módulos y no repetir pruebas por rutina. Una suite sintética no acredita HTTP real ni interacción de proveedor. Se prueba lo faltante en local que sea razonable, seguro y autorizado.
2. Si un caso requiere proveedor externo, HTTPS público, dominio, plataforma definitiva, credenciales reales o runner local desproporcionado/bloqueado por herramienta: registrar **PENDIENTE EXTERNO/ENTORNO** con escenario, precondición, esperado y riesgo. **No crear staging en esta tanda**.
3. Si aparece fallo P0/P1 reproducible localmente: clasificar **BLOQUEADO DEFECTO**, documentar reproducción y corregir con reserva de MAIN, sin delegarlo como diferido para eludirlo.
4. Aislar toda operación de escritura en MariaDB/archivos con clon descartable verificado sin `.env` comercial ni datos reales; conservar logs, cancelar procesos propios; no modificar servicios instalados de otros especialistas.
5. Prohibido tocar APIs productivas de MP, ARCA, Correo Argentino y servidores SMTP; Correo Argentino **ni siquiera consultas**. Simulación de proveedor con bloqueo de salida de red cuando corresponda.
6. Roles: especialistas 05–08 ejecutan y documentan en `RESULTADO_05.md`...`RESULTADO_08.md`, no hacen commits ni editan `CONTROL_VIVO.md`; MAIN revisa, reserva, integra, ejecuta regresión focal y actualiza control/acta. No tocar rutas, helpers, esquema ni archivos de terceros sin reserva.
7. Resultado permitido por caso: PASS LOCAL (evidencia concreta) / FAIL / NO EJECUTADO (motivo) / PENDIENTE EXTERNO/ENTORNO / NO APLICA (justificación). Resultado por área: APTO PARA REVISIÓN MAIN LOCAL / PARCIAL LOCAL / BLOQUEADO DEFECTO.
8. Solo si todas las áreas 01–10 cierran su alcance local, ejecutar gate global una vez sobre cambios integrados y emitir **ACTA FINAL DE QA LOCAL 01–10**, con riesgos pendientes separados de la aprobación para producción.

## Tanda actual — 05 a 08
| Chat | Alcance local | Evidencia previa reutilizable | Propietario de edición |
|---|---|---|---|
| 05 Pagos/MP | Intentos, transiciones approved/pending/rejected **sintéticos**, firma, replay, idempotencia, conciliación, SQL simulado | Contratos HMAC 15/15 y cierre 80/80 MP; checkout 04 sin repetir | Sus servicios tras reserva MAIN; no Checkout compartido |
| 06 Facturación/ARCA | Comprobantes internos, NC, IVA, numeración, estados, permisos y simulación fiscal sin emitir | 40/40 sintético; 95/95 visual Facturación | Fiscal privado con reserva; archivos de Ajustes/fiscal no tocar |
| 07 Ajustes/Ops | Persistencia y consumidores; shipping/retiro, SEO, auditoría, marketing/email simulado, seguridad configuración | 155/155 Ajustes, 132/132 navegación, 105/105 Auditoría | SOLO LECTURA donde haya cambios sin commit; reserva MAIN |
| 08 Público | Inicio, ficha, búsqueda, filtros, carrito, UX/SEO, accesibilidad/responsive local | B1, SEO/categorías, checkout 04, responsive previo | vistas y CSS/JS público tras reserva; no backend 04 |

## Tanda posterior
09 Seguridad transversal PHP: revisión focal de auth, rutas, CSRF/IDOR, secretos, subidas, webhooks y riesgos críticos localmente verificables. No repetir todas las suites funcionales.
10 Infraestructura: PHP local, dependencias, migraciones, logs, backup/restore **solo sobre BD descartable**, runbook de deploy y límites de infraestructura no verificables localmente. No alterar XAMPP instalado ni BD comercial.

## Cadencia y evidencias
Especialista entrega `RESULTADO_XX.md` con fecha/HEAD, prueba y comando, caso→expectativa→resultado/evidencia, severidad, archivos y teardown. MAIN revisa al concluir la tanda (o ante P0/P1), integra selectivamente, actualiza `CONTROL_VIVO.md` con fecha/commit y conserva los informes. Nunca transformar bloqueo de herramienta en test PASS. No se abre otra ronda general tras cierre; solo regresión afectada.

## Puertas
**L05–08:** sin P0/P1 confirmados abiertos dentro del ámbito local, suites disponibles relevantes aprobadas, pendientes externos especificados y correcciones integradas. **L09–10:** mismo criterio. **L01–10:** gate global y acta final. **Producción permanece NO GO** por los requisitos externos y de plataforma pendientes.
