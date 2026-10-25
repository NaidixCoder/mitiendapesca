# MAIN CHAT — Revisión independiente de especialistas 01–04

Fecha 25/10/2026. Fuentes: `docs/preproduccion/especialistas/RESULTADO_01.md` a `RESULTADO_04.md`, rutas, inspección de `PasswordResetService.php`, antecedentes del Plan Maestro. Esta revisión **no reejecutó** suites; diferencia informes del especialista de hallazgos verificados por MAIN.

| Chat | Resultados que reporta | Dictamen MAIN | Acción pendiente |
|---|---|---|---|
| 01 Usuarios | 42/42 HTTP/MariaDB; Edge 10/10, pruebas de revocación rol y último administrador. | AVANCE ACEPTADO, NO CERRADO. | Corregir/reprobar concurrencia de token de reset y revocación de sesiones tras reset/cambio de clave; luego ampliar recuperación y permisos por objeto. |
| 02 Catálogo | Contratos de categorías/atributos/marcas/tags PASS y PHP lint. No SQL/HTTP autenticado nuevo. | PARCIAL; QA nuevo insuficiente para E2E integral. | Rutas mutantes Products/Variants/Images con HTTP+MariaDB privada, validaciones, CSRF, permisos, concurrencia y navegación. |
| 03 Inventario | 36/36 Proveedores auténtico; 284 checks de importaciones; contratos stock 16/16. Sin carrera HTTP pedido-stock integral. | PARCIAL; preservar pruebas reutilizables. | Coordinar stock/pedido con chat 04; verificar recepción comercial concreta (no presumir que compra fiscal mueve stock); medir paginación de inventario. |
| 04 Pedidos | 19/19 compra transferencia HTTP+SQL, carrera 3/3 en ejecución aislada; corrida encadenada falla `B1_UNAVAILABLE`. | PARCIAL / NO APROBADA LA SUITE GLOBAL. | Investigar fixture y estado B1 entre casos, preservar logs separados, pasar suite integral secuencial y repetir con escenarios concurrentes. |

## Comprobación propia de seguridad (01)

`app/Services/Auth/PasswordResetService.php` fue leído directamente: `resetPassword()` valida token antes de `beginTransaction()`, cambia hash y ejecuta `UPDATE password_resets SET used_at=NOW() WHERE id=?` sin condicionar `used_at IS NULL` ni bloquear la fila. Esto sustenta riesgo potencial de uso simultáneo del mismo token; explotación/concurrencia no comprobada por MAIN. Tanto `resetPassword()` como `changePassword()` actualizan `password_changed_at` y eliminan `user_tokens` pero no incrementan `session_version`. Conforme a la inspección del guard documentada por especialista 01, riesgo de sesión nativa no invalidada; defecto de diseño confirmado por lectura, **impacto HTTP pendiente de reproducción**. Prioridad P1 propuesta; MAIN debe asignar reserva de servicios Auth antes de modificar. Corregir y probar en entorno privado, incluyendo reset, cambio, cookies anteriores y remember-me.

## Reglas de aceptación

No marcar PASS global a partir de contratos estáticos, lint o pruebas aisladas. Sin P0/P1 abierto en producción. Los `RESULTADO_XX.md` continúan **sin integrar**; no añadir archivos ajenos por accidente. Cualquier corrección de auth/checkout/inventario compartida requiere reserva y regresiones específicas. No usar producción de Correo Argentino, MP, ARCA, correo ni DB comercial.

## Estado de la auditoría MAIN

**Dictamen: cuatro avances recibidos; ninguno cerrado integralmente.** Evidencias contrastadas principalmente a nivel documental y lectura focal de servicio Auth; no se realizó nueva prueba de explotación, concurrencia o navegador durante esta revisión. Primero atender P1 de revocación/token de recuperación y fallo secuencial de checkout. Luego completar E2E auténtico de catálogo y stock.
