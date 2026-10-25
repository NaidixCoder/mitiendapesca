# Especialista 06 — Facturación / ARCA — cierre QA LOCAL
**Objetivo cerrado:** validar lógica fiscal interna, estados, numeración y autorizaciones con ARCA completamente simulada.
**Evidencia reutilizable:** suite backend sintética 40/40, responsive Facturación 95/95, documentación de facturación y actas anteriores; no repetir visuales ya válidos.
### Trabajo asignado
1. Revisar rutas/controladores de Facturación, comprobantes, notas de crédito, IVA, numeración, cierres e integración pedido→factura con datos sintéticos; aislar responsabilidades.
2. Ejecutar contratos/suites existentes y pruebas locales razonables de éxito/error ARCA simulado, duplicados, invalidaciones, carreras de numeración, reversión, roles, CSRF y permisos; verificar SQL solo si ya hay clon privado autorizado.
3. Comprobar que ninguna ejecución emita comprobantes reales ni contacte ARCA productiva. No usar certificados/credenciales comerciales; servicios simulados exclusivamente.
4. Registrar cambios sin commit ajenos en vistas fiscal/Ajustes; **SOLO LECTURA** sobre esos archivos salvo reserva MAIN.
5. Corregir únicamente defectos P0/P1 o funcionales confirmados dentro de archivos reservados. Homologación fiscal, CAE reales, punto de venta productivo y entorno PHP de hosting quedan **PENDIENTE EXTERNO**.
**Entrega:** `docs/preproduccion/especialistas/RESULTADO_06.md`, con evidencia y límites exactos.

## Reglas obligatorias de esta tanda LOCAL (25/10/2026)
- Este mandato prevalece sobre la formulación antigua "E2E exhaustivo" únicamente para esta ronda. Leer antes: `docs/preproduccion/PLAN_QA_LOCAL_05_10.md`, `docs/preproduccion/CONTROL_VIVO.md`, `docs/COORDINACION.md`, matriz E2E y documentación específica. `PLAN_DOS_ETAPAS_LOCAL_STAGING.md` queda como antecedente histórico, no orden activa de desplegar.
- Sólo Windows/XAMPP y pruebas sin servicios externos. Reutilizar suites acreditadas, no repetir auditorías completas ni fabricar ejecutores complejos. Test simple de código/lint/contratos o HTTP sobre clon local permitido si es seguro, está autorizado y no choca con herramientas ni procesos de otros chats. No eludir bloqueos de seguridad. Un caso inaccesible pasa a **PENDIENTE EXTERNO/ENTORNO**, con pasos y riesgo, jamás PASS.
- Leer `git status --short` y reservas. No editar `config/routes.php`, `helpers.php`, esquemas o código compartido, ni archivos con cambios ajenos, sin reserva explícita de MAIN. Especialistas no hacen commits, push, reset, clean ni borrado de evidencia.
- Usar datos sintéticos y MariaDB aislada/descartable cuando corresponda. Jamás `.env` comercial, BD real, email real, MP productivo, ARCA productiva ni **ninguna llamada a API productiva de Correo Argentino**. Simular proveedores y bloquear salidas cuando haya riesgo; ante duda, abstenerse solo de ese ensayo.
- Actualizar **únicamente** tu archivo `RESULTADO_XX.md` como entrega y los archivos de código expresamente reservados. No modificar `CONTROL_VIVO.md` (lo mantiene MAIN). Registro: HEAD, fecha, prueba, alcance, esperado/observado, PASS/FAIL/NO EJECUTADO, evidencia verificable, defectos con prioridad, teardown, cambios y lista corta de pendientes.
- Cierre: **APTO PARA REVISIÓN MAIN (LOCAL)** si los tests locales elegibles pasan y no quedan P0/P1 confirmados abiertos dentro del ámbito; **BLOQUEADO DEFECTO** si hay P0/P1; **PARCIAL LOCAL** si faltan pruebas locales practicables. No proclamar GO de producción. Detener al cumplir, no ampliar alcance.
