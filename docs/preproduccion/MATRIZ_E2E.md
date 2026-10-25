# MiTiendaPesca — Matriz E2E Admin (inventario inicial)
**Fecha:** 24/10/2026. **Estado:** cobertura preliminar, NO aprobación E2E. **Responsable:** MAIN CHAT.

## Fuente y alcance
Revisión inicial de `config/routes.php` y evidencias en `docs/PLAN_MAESTRO_PREPRODUCCION.md`, `docs/modulos/*.md`, `docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md`. Se identificaron **287 rutas** declaradas en formato método+ruta (**149 GET y 138 POST**), de ellas **192 con /admin**. Faltan clasificar en detalle rutas API/AJAX, cron y webhooks sin prefijo. No se usó base comercial ni se ejecutaron escrituras.

## Cobertura administrativa y vacíos

| Módulo/circuito | Evidencia previa útil | Qué falta acreditar en E2E integrado | Prioridad | Estado |
|---|---|---|---|---|
| Login, sesiones y Usuarios | 42/42 HTTP+MariaDB MAIN; revocación inmediata y carrera último admin | Sesión transversal, permisos por registro y flujos de recuperación | P1 | Parcial |
| Dashboard | Componentes y contratos Admin V3 | KPIs con SQL y enlaces bajo sesión real | P2 | Pendiente |
| Productos, variantes e inventario | Contratos B1/visibilidad, SQL transversal 40/40 | CRUD navegador/HTTP, imágenes, stock concurrente y compra | P1 | Parcial |
| Categorías | Contratos B1, SEO y checkout | Alta/edición/desactivación→catálogo/checkout real | P1 | Parcial |
| Marcas | HTTP/SQL, portadas 8/8 | CRUD+SEO+relación pública e imágenes | P2 | Parcial |
| Atributos | 25 HTTP privados; 18/18 SQLite MAIN | Cruce importación/variante/pedido | P1 | Parcial |
| Importaciones | 76 HTTP/SQL; rollback I05 y QA transversal | CSV→producto público→pedido, errores de archivos | P1 | Parcial |
| Tags | 32/32 HTTP, 17/17 SQL, 13/13 consumidores | Asociación→filtro público→pedido | P2 | Parcial |
| Proveedores y compras | Proveedores 36/36 HTTP+SQL | Compra→recepción→stock/costo y errores | P1 | Parcial |
| Pedidos | Checkout/intentos/stock contratos sintéticos | Orden real HTTP+SQL, estados, devolución, idempotencia, concurrencia | P0 | Pendiente transversal |
| Clientes | Usuarios 42/42 y vistas Admin | Direcciones, pedido, aislamiento por ID | P1 | Parcial |
| Mercado Pago y pagos | Firma 15/15; focal 80/80; contratos sintéticos | Sandbox, webhook HTTPS firmado, approved/pending/rejected, conciliación | P0 | Bloqueado proveedor |
| Facturación/ARCA | Backend 40/40 y visual 95/95 MAIN | HTTP+MariaDB fiscal autenticado, concurrencia, homologación ARCA | P0 si habilitado | Parcial |
| Ajustes | 155/155 SQL/HTTP reportados; navegación 132/132 MAIN | Cambios→consumidores reales y regresión tras integración pendiente | P1 | Parcial |
| Auditoría | 105/105 HTTP/SQL y 15/15 visual | Trazabilidad cruzada de eventos y permisos | P1 | Parcial |
| SEO | Contratos anteriores y Admin V3 | Guardado→metadatos públicos y redirecciones | P2 | Parcial |
| Marketing/Email | E2E HTTP/Edge/SQL especialista | Campaña→consentimiento→cola/automatización segura | P1 si habilitada | Parcial |
| Reportes/finanzas | Vistas y contratos focales | Integridad numérica, permisos y exportaciones | P1 | Parcial |

## Orden de lotes
1. **Base y seguridad Admin:** login, usuarios/roles, sesión, permisos, CSRF, aislamiento por ID.
2. **Catálogo y existencias:** productos, variantes, categorías, marcas, atributos, etiquetas, importaciones, compras/proveedores.
3. **Dinero y pedidos:** clientes, órdenes, intentos, stock, pago/webhooks, doble confirmación, conciliación.
4. **Fiscal y finanzas:** comprobantes, estados, libros IVA, cierres, informes.
5. **Configuración y operaciones:** Ajustes, Auditoría, SEO, Marketing/Email.
6. **Cruce Admin ↔ Público:** publicar producto→carrito→checkout→pedido→stock→seguimiento.

## Reglas y próximos entregables
- No tratar `lint`, `HTTP 200`, capturas ni contratos aislados como E2E completo.
- Cada escenario final requerirá ruta/acción, rol, datos sintéticos, precondiciones, resultado HTTP+UI+SQL, evidencia, script, commit, fecha y resultado.
- Estados: NO INVENTARIADO / CONTRATO / HTTP+SQL / E2E / BLOQUEADO / DIFERIDO JUSTIFICADO.
- **Paso siguiente obligatorio:** expandir individualmente las **192 rutas Admin** con controlador/acción/permiso/CSRF/persistencia y relacionar suites de pruebas existentes; incluir endpoints administrativos externos a `/admin`.
- En entorno privado comprobar puerto/datadir antes de escribir; bloquear producción con P0/P1 sin resolver.
- El documento es una **primera matriz funcional**, no certifica E2E Admin ni sustituye los gates de producción.
