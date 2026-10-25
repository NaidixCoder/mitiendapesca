# MiTiendaPesca — Control vivo de preproducción
Versión 2.0 · 25/10/2026 · MAIN CHAT · **GO producción: NO**

## Protocolo de mantenimiento
Cada entrega/corrección: MAIN verifica resultado real y actualiza este tablero en la misma intervención, con fecha, commit, entorno, casos ejecutados, evidencias, defectos y responsable. No sustituir PASS de staging por PASS local. Nunca se consideran aprobadas pruebas diferidas. Los informes individuales mantienen sus antecedentes; no reescribir para borrar fallos.

| ID | Responsable | Estado actual | Próxima acción / condición de cierre |
|---|---|---|---|
| 01 Auth | 01 + MAIN | PARCIAL; 42/42 + 21/21 PASS reportados | Integrar servicio y decidir UX. Multi-sesión/remember-me y alta/perfil positivo -> ST-01/ST-02 |
| 02 Catálogo | 02 | PARCIAL; contratos reutilizables | Fijar solo defectos locales evidenciados; CRUD HTTP/UI -> ST-03 |
| 03 Inventario | 03 + MAIN | BLOQUEADO DEFECTO P1 INV03-EXP-01 | Corregir export CSV en código fuente + probar roundtrip; ST-04 para límites hosting |
| 04 Checkout | MAIN + 04 | BLOQUEADO DEFECTO P0 04-P0-01 | Integrar token idempotente en fuente; correr suite privada sin parche de clon; ST-05 posterior |
| 05–10 | Sin activar | SIN INFORMES | No asignar trabajo en esta fase; planificar más adelante |
| MP / ARCA / Correo | MAIN | DIFERIDO STAGING | Sandbox autorizado o deshabilitación verificada ST-06/ST-07; jamás API Correo productiva para QA |
| PHP / Hosting | MAIN | DIFERIDO STAGING | Hosting contratado, PHP parcheado, backup/restore y seguridad ST-08 |
| UI navegador | MAIN / especialistas | DIFERIDO STAGING parcial | Ejecución real y capturas representativas ST-09 |

## Registro de hitos
- 25/10/2026: cambio aprobado de estrategia por propietario: dos etapas, solo especialistas 01–04. No se han ejecutado pruebas nuevas por la actualización documental. Se conservan estados de informes; pendientes de staging NO son PASS.
- 25/10/2026: persistentes P0 Checkout y P1 Exportaciones; ninguno puede diferirse al hosting.

## Formato obligatorio de actualización
Fecha/Hora | HEAD/commit | Especialista | ID caso | Entorno local/staging | Comando/acción | PASS/FAIL/BLOQUEADO/DIFERIDO | Evidencia | Defecto/severidad | Fix/commit | Próximo responsable | Gate. Añadir nueva entrada por cada auditoría; conservar fallos originales.