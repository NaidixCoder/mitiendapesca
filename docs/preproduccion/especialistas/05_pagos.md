# Especialista 05 — Pagos / Mercado Pago — cierre QA LOCAL
**Objetivo cerrado:** comprobar consistencia del ciclo de pago y sus integraciones simuladas sin transacciones externas.
**Evidencia reutilizable:** `docs/CIERRE_MERCADOPAGO_20261023.md`, HMAC 15/15 y focal 80/80, además de suites aisladas de pagos/checkout ya registradas. No repetirlas si son válidas sobre código no afectado.
### Trabajo asignado
1. Inventariar solo rutas/controladores de intentos de pago, webhooks, reconciliación y estados, confirmando contratos reales del código.
2. Ejecutar **solamente** suites locales existentes y regresiones focales para: approved/pending/rejected sintéticos, firma/HMAC inválida, timestamp/replay, webhooks duplicados/fuera de orden, orden sin pago, pago sin pedido, idempotencia de stock y conciliación; distinguir qué no cubren.
3. Verificar que ninguna prueba contacte MP real. No hacer llamadas a endpoints de sandbox externos sin autorización explícita; simular webhook con payloads sintéticos.
4. Corregir solo defecto demostrado y con reserva previa; si afecta Checkout/stock ya cerrados en 04, documentar y coordinar con MAIN, sin reabrir toda su suite.
5. Dejar fuera: cobro real, callback HTTPS público y conciliación con proveedor real. Registrar como **PENDIENTE EXTERNO**.
**Entrega:** `docs/preproduccion/especialistas/RESULTADO_05.md`, con matriz sucinta casos/evidencias y veredicto local.

## Reglas obligatorias de esta tanda LOCAL (25/10/2026)
- Este mandato prevalece sobre la formulación antigua "E2E exhaustivo" únicamente para esta ronda. Leer antes: `docs/preproduccion/PLAN_QA_LOCAL_05_10.md`, `docs/preproduccion/CONTROL_VIVO.md`, `docs/COORDINACION.md`, matriz E2E y documentación específica. `PLAN_DOS_ETAPAS_LOCAL_STAGING.md` queda como antecedente histórico, no orden activa de desplegar.
- Sólo Windows/XAMPP y pruebas sin servicios externos. Reutilizar suites acreditadas, no repetir auditorías completas ni fabricar ejecutores complejos. Test simple de código/lint/contratos o HTTP sobre clon local permitido si es seguro, está autorizado y no choca con herramientas ni procesos de otros chats. No eludir bloqueos de seguridad. Un caso inaccesible pasa a **PENDIENTE EXTERNO/ENTORNO**, con pasos y riesgo, jamás PASS.
- Leer `git status --short` y reservas. No editar `config/routes.php`, `helpers.php`, esquemas o código compartido, ni archivos con cambios ajenos, sin reserva explícita de MAIN. Especialistas no hacen commits, push, reset, clean ni borrado de evidencia.
- Usar datos sintéticos y MariaDB aislada/descartable cuando corresponda. Jamás `.env` comercial, BD real, email real, MP productivo, ARCA productiva ni **ninguna llamada a API productiva de Correo Argentino**. Simular proveedores y bloquear salidas cuando haya riesgo; ante duda, abstenerse solo de ese ensayo.
- Actualizar **únicamente** tu archivo `RESULTADO_XX.md` como entrega y los archivos de código expresamente reservados. No modificar `CONTROL_VIVO.md` (lo mantiene MAIN). Registro: HEAD, fecha, prueba, alcance, esperado/observado, PASS/FAIL/NO EJECUTADO, evidencia verificable, defectos con prioridad, teardown, cambios y lista corta de pendientes.
- Cierre: **APTO PARA REVISIÓN MAIN (LOCAL)** si los tests locales elegibles pasan y no quedan P0/P1 confirmados abiertos dentro del ámbito; **BLOQUEADO DEFECTO** si hay P0/P1; **PARCIAL LOCAL** si faltan pruebas locales practicables. No proclamar GO de producción. Detener al cumplir, no ampliar alcance.
