# QA LOCAL — Bloque A, ejecución MAIN 25/10/2026
**HEAD de partida:** cc9bf41. **Dictamen:** BLOQUE A NO COMPLETO; evidencia previa reutilizada y nuevas ejecuciones limitadas. No declarar E2E integral.

## Ejecución directa realizada
- `php scripts/dev/facturacion_domain_smoke_private.php`: 40 casos sintéticos correctos en SQLite memoria. Se vuelve a comprobar el comportamiento interno fiscal; no acredita concurrencia MariaDB, NC y ARCA integral.
- `php scripts/dev/proveedores_smoke_private.php`: **19/19 PASS**, SQLite memoria. Alta, edición, baja lógica, duplicados y validaciones de proveedores; no incluye compra/recepción/stock.
- `php scripts/dev/marketing_email_campaign_lifecycle_smoke.php`: **BLOQUEADO**, PDO `SQLSTATE[HY000] [2002]` al intentar utilizar la conexión DB configurada. No hubo operaciones DB; no ejecutar contra base comercial.
- `php scripts/dev/seo_readonly_smoke.php`: **BLOQUEADO**, conexión SQL denegada (2002). No declarar PASS SEO.
- Se preservan sin repetir los contratos de 05 (77/77), 08 (197 checks), el checkout privado aprobado 04 (19+3+75+20), suite usuarios (42+21), Ajustes (155), Auditoría (105), importación (35), y responsive fiscal previo, con sus respectivos límites.
- **No se levantaron nuevos servidores/instancias**, no se emitieron pagos, facturas, correos ni se modificaron datos reales. Ningún archivo compartido alterado.

## Matriz real de los ocho circuitos
| Caso | Qué se acreditó | Qué NO se acreditó | Dictamen |
|---|---|---|---|
| A1 Auth | 42/42 usuarios, 21/21 auth históricos, código de logout al cambiar clave integrado | HTTP integral recuperación/registro/cambio, sesiones simultáneas/remember-me | PARCIAL |
| A2 Productos | B1/visibilidad, contratos, fix redirect y paginación | CRUD + imágenes + variantes + publicación con SQL/HTTP público | PARCIAL |
| A3 Proveedores/importación | 19/19 proveedor SQLite nuevo, 35/35 importaciones HTTP previas | Compra→recepción→stock/costos y reimportación CSV completa | PARCIAL |
| A4 Carrito | Checkout 19+3+75 anterior, navegación Edge 08 parcial | POST carrito autenticado de agregar/cambiar/eliminar persistido | PARCIAL |
| A5 Pagos | 77/77 contratos HMAC/integridad, previos de Checkout | Transiciones persisted, duplicados, conciliación, rollback SQL | PARCIAL |
| A6 Facturación | 40/40 smoke fiscal SQLite memoria PASS, 95/95 visual anterior | MariaDB fiscal, NC, carreras numeración, roles/CSRF HTTP | PARCIAL |
| A7 Ajustes | 155/155 histórico en clon, 132/132 navegación | Config actual → consumidor real revalidado tras cambios ajenos | PARCIAL |
| A8 SEO/marketing | Contratos SEO previos y E2E histórico marketing, ninguno extendido en esta corrida | Smoke SEO y lifecycle marketing bloquearon PDO 2002 contra DB configurada | BLOQUEADO ENTORNO |

## Causa técnica y ejecución siguiente
El DB configurado de XAMPP no está accesible. **No levantar ni iniciar esa base por conveniencia**: los ensayos requieren clon MariaDB autónomo, cuya identidad de puerto y datadir se compruebe ANTES de cualquier escritura, con `.env` sintético y bloqueo de proveedores. Esta necesidad afecta A2–A8. Los resultados históricos acreditan partes de esos flujos, no el E2E completo.
El cierre no se puede emitir simplemente por un gate estático. MAIN conserva responsabilidad; no enviar al usuario más prompts ni atribuir éxito a suites no ejecutadas.

## Seguridad
No se modificaron configuraciones, servicios comerciales o código del negocio. Cambios ajenos de Ajustes/Auditoría/Fiscal protegidos. Próxima ejecución debe ser un runner privado *existente* que cree y valide su propia DB; nunca redirigir el smoke actual hacia la DB comercial.

## Intento de ejecución con runner privado de Ajustes
`python scripts/dev/settings_http_mariadb_private.py` rechazó iniciar: `REFUSED existing QA dir: inspect manually`. No abrió procesos ni DB y conservó el directorio existente `%TEMP%/mi_settings_http_qa_20261023` porque su propiedad/estado no está confirmada. **No se borra ni se fuerza el runner**. A7 continúa bloqueado para esta ejecución, sin imputar fallo a Ajustes.
