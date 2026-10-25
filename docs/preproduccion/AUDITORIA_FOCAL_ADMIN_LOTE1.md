# Preproducción — Auditoría focal de rutas críticas Admin (24/10/2026)

**Alcance:** 49 rutas agrupadas bajo Usuarios (5), Pedidos (9 + 2 acciones Correo Argentino), Productos y variantes (32), Inventario (1). Revisión estática inicial y comparación con suites ya acreditadas. **No se ejecutaron nuevas solicitudes HTTP, SQL ni mutaciones.**

## Observaciones verificadas en código

- `config/routes.php` identifica **49 operaciones** en los grupos seleccionados: cinco Users, nueve Orders directos, dos actions Correo bajo Orders, 32 Products y una Inventory. Esta agrupación contiene tanto GET como POST.
- `UsersController.php` hereda de `BaseController`; no se debe inferir ausencia de autenticación por no ver `auth_required()` dentro del método. En su listado utiliza sentencias PDO preparadas, límites numéricos para paginación y longitud de búsqueda. **Prueba previa reejecutada MAIN:** `usuarios_http_private_qa.py` 42/42 HTTP/MariaDB, revocación de privilegios inmediata HTTP 401 y cuatro carreras sobre último administrador.
- `OrdersController.php` contiene `admin_required()` en su listado y delega operaciones a `AdminOrderService`; el controlador no contiene transacciones explícitas en esa ruta. **La protección real de cada operación debe verificarse dentro de su método/servicio**, no mediante conteo de palabras en el controlador.
- `ProductVariantsController.php` delega sus acciones a tres traits (`VariantReadActions`, `VariantAttributeActions`, `VariantLifecycleActions`). Un análisis estático que solo inspeccione el controlador de 20 líneas produciría un falso negativo. Se deben revisar esos traits y servicios en la próxima pasada.
- Los POST de Products incluyen mutaciones sensibles: guardar producto, IVA, estado, uploads, asociar/ordenar/eliminar imágenes, atributos, variantes y limpieza. Deben probarse con CSRF, permisos, validaciones de archivos y SQL/rollback por endpoint.
- Se identifican POST de alto riesgo entre Pedidos: `mark-paid`, `status`, emisión ARCA/NC y cambios de envío; se exige trazabilidad de rol/objeto, transacción, idempotencia y consumidores.
- La evidencia previa de 40/40 SQL transversal Catálogo/Checkout **no** prueba todos estos endpoints Admin y no debe elevar sus estados a E2E.

## Dictamen y siguiente ejecución

**NO CERRADO.** La revisión inicial encontró rutas críticas y puntos donde profundizar; **no demuestra vulnerabilidades explotables ni aprobación total**. La ausencia de una cadena de texto en un controlador no acredita una falla porque existen BaseController, traits, middleware y servicios.

Siguiente lote técnico: (1) mapear `BaseController`, router/guard y traits; (2) inspeccionar cada POST de Pedidos/Productos y su servicio de escritura; (3) correr tests HTTP+MariaDB privados por escenarios faltantes, sin repetir indiscriminadamente Usuarios 42/42; (4) verificar acceso por ID, CSRF, transacciones y eventos auditables.
