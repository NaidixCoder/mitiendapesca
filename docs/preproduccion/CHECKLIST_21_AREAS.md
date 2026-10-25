# MiTiendaPesca — Checklist maestro de 21 áreas de prueba

Fecha 25/10/2026 · Estado: por vincular con evidencias previas. **Una casilla vacía no significa que nunca se probó.** Cada familia de pruebas se desglosa en casos positivo, negativo, límite, permisos y concurrencia cuando aplique.

Leer Plan Maestro de Preproducción §7 OWASP ASVS/PHP; matriz E2E; protocolos V2/V3 y coordinación. Marcar PASS únicamente con script/comando, commit, fecha, entorno, HTTP/SQL/UI y evidencia reproducible. P0/P1 bloquean producción. Entornos privados; nunca DB comercial ni proveedores reales sin autorización.

## 01 — Autenticación, usuarios y permisos

- [ ] 01.01 login y logout
- [ ] 01.02 registro y validación email
- [ ] 01.03 recuperación de contraseña
- [ ] 01.04 revocación inmediata y expiración
- [ ] 01.05 roles admin cliente mayorista
- [ ] 01.06 IDOR y BOLA
- [ ] 01.07 protección CSRF
- [ ] 01.08 último administrador concurrente
- [ ] 01.09 cookies seguras
- [ ] 01.10 rate limiting

## 02 — Dashboard administrativo

- [ ] 02.01 KPIs SQL
- [ ] 02.02 filtros períodos
- [ ] 02.03 gráficos
- [ ] 02.04 enlaces internos
- [ ] 02.05 roles
- [ ] 02.06 paginación
- [ ] 02.07 estados vacíos
- [ ] 02.08 errores
- [ ] 02.09 responsive

## 03 — Productos y catálogo

- [ ] 03.01 CRUD y estados
- [ ] 03.02 SKU y slugs únicos
- [ ] 03.03 IVA precios costos
- [ ] 03.04 categorías marcas tags
- [ ] 03.05 archivos e imágenes seguras
- [ ] 03.06 variantes y stock
- [ ] 03.07 SEO y visibilidad B1
- [ ] 03.08 búsquedas y paginación
- [ ] 03.09 concurrencia
- [ ] 03.10 rollback

## 04 — Atributos y variantes

- [ ] 04.01 CRUD atributos y valores
- [ ] 04.02 plantillas
- [ ] 04.03 combinaciones únicas
- [ ] 04.04 generación de variantes
- [ ] 04.05 atributos de producto
- [ ] 04.06 stock y precio por variante
- [ ] 04.07 eliminar referenciadas
- [ ] 04.08 rollback
- [ ] 04.09 concurrencia
- [ ] 04.10 importación y checkout

## 05 — Categorías, marcas y etiquetas

- [ ] 05.01 CRUD
- [ ] 05.02 árbol y ciclos
- [ ] 05.03 B1
- [ ] 05.04 SEO redirecciones
- [ ] 05.05 marcas e imágenes
- [ ] 05.06 relaciones con productos
- [ ] 05.07 filtros tags
- [ ] 05.08 duplicados
- [ ] 05.09 integridad SQL
- [ ] 05.10 concurrencia

## 06 — Inventario, proveedores y compras

- [ ] 06.01 stock y movimientos
- [ ] 06.02 proveedor CRUD
- [ ] 06.03 compra
- [ ] 06.04 recepción y recepción parcial
- [ ] 06.05 costos
- [ ] 06.06 doble recepción
- [ ] 06.07 stock negativo
- [ ] 06.08 operaciones concurrentes
- [ ] 06.09 historial
- [ ] 06.10 rollback

## 07 — Importaciones y exportaciones

- [ ] 07.01 archivo válido e inválido
- [ ] 07.02 formatos y límites
- [ ] 07.03 previsualización
- [ ] 07.04 rollback I05
- [ ] 07.05 atributos y variantes
- [ ] 07.06 duplicados
- [ ] 07.07 reimportación
- [ ] 07.08 concurrencia
- [ ] 07.09 exportaciones
- [ ] 07.10 permisos y seguridad

## 08 — Clientes y cuentas

- [ ] 08.01 registro
- [ ] 08.02 perfil y direcciones
- [ ] 08.03 historial de pedidos
- [ ] 08.04 recuperación de cuenta
- [ ] 08.05 mayoristas
- [ ] 08.06 aislamiento IDOR
- [ ] 08.07 privacidad
- [ ] 08.08 validaciones
- [ ] 08.09 consistencia cliente-pedido

## 09 — Carrito y checkout

- [ ] 09.01 carrito y variantes
- [ ] 09.02 stock
- [ ] 09.03 persistencia sesión
- [ ] 09.04 descuentos
- [ ] 09.05 precios y totales
- [ ] 09.06 envíos
- [ ] 09.07 cambio de precio
- [ ] 09.08 CSRF
- [ ] 09.09 doble clic
- [ ] 09.10 pedido único
- [ ] 09.11 error y rollback

## 10 — Pedidos y administración comercial

- [ ] 10.01 listado filtros
- [ ] 10.02 detalle
- [ ] 10.03 comprobantes y permisos
- [ ] 10.04 transiciones de estado
- [ ] 10.05 marcar pagado
- [ ] 10.06 envío
- [ ] 10.07 cancelación
- [ ] 10.08 stock
- [ ] 10.09 concurrentes
- [ ] 10.10 idempotencia
- [ ] 10.11 auditoría

## 11 — Mercado Pago y pagos

- [ ] 11.01 approved pending rejected en sandbox
- [ ] 11.02 firma webhook
- [ ] 11.03 HTTPS y credenciales
- [ ] 11.04 notificaciones duplicadas
- [ ] 11.05 reordenamiento
- [ ] 11.06 importe y moneda
- [ ] 11.07 idempotencia
- [ ] 11.08 pago sin pedido
- [ ] 11.09 conciliación
- [ ] 11.10 fallos de red
- [ ] 11.11 no cobros reales

## 12 — Facturación y ARCA

- [ ] 12.01 datos fiscales
- [ ] 12.02 emisión homologada
- [ ] 12.03 notas de crédito
- [ ] 12.04 numeración única
- [ ] 12.05 idempotencia
- [ ] 12.06 errores inciertos
- [ ] 12.07 concurrencia
- [ ] 12.08 libros IVA
- [ ] 12.09 pedido pago comprobante
- [ ] 12.10 permisos y CSRF

## 13 — Envíos y Correo Argentino

- [ ] 13.01 tarifas y reglas
- [ ] 13.02 retiro
- [ ] 13.03 remitente
- [ ] 13.04 embalaje
- [ ] 13.05 conexión de prueba
- [ ] 13.06 importación/etiqueta
- [ ] 13.07 tracking
- [ ] 13.08 duplicados
- [ ] 13.09 errores
- [ ] 13.10 secretos
- [ ] 13.11 permisos

## 14 — Ajustes generales

- [ ] 14.01 5 áreas/8 pantallas
- [ ] 14.02 persistencia
- [ ] 14.03 diseño y hero
- [ ] 14.04 pagos y precios
- [ ] 14.05 mayoristas
- [ ] 14.06 envíos
- [ ] 14.07 fiscal
- [ ] 14.08 email
- [ ] 14.09 secretos
- [ ] 14.10 CSRF
- [ ] 14.11 rollback

## 15 — SEO y marketing

- [ ] 15.01 metadatos y canonical
- [ ] 15.02 sitemap robots
- [ ] 15.03 slugs y redirects
- [ ] 15.04 campañas
- [ ] 15.05 UTM
- [ ] 15.06 consentimiento
- [ ] 15.07 newsletters
- [ ] 15.08 colas
- [ ] 15.09 reintentos
- [ ] 15.10 no enviar correos reales

## 16 — Auditoría y reportes

- [ ] 16.01 evento registrado
- [ ] 16.02 acceso según rol
- [ ] 16.03 filtros
- [ ] 16.04 logins y fallos
- [ ] 16.05 limpieza segura
- [ ] 16.06 UTF-8/mojibake
- [ ] 16.07 informes y exportaciones
- [ ] 16.08 consistencia SQL
- [ ] 16.09 privacidad

## 17 — Seguridad OWASP y aplicación

- [ ] 17.01 OWASP ASVS 5 L2 aplicable
- [ ] 17.02 WSTG
- [ ] 17.03 API Top 10
- [ ] 17.04 autenticación y permisos
- [ ] 17.05 SQLi XSS CSRF
- [ ] 17.06 SSRF y path traversal
- [ ] 17.07 uploads
- [ ] 17.08 CORS y headers
- [ ] 17.09 cookies
- [ ] 17.10 secretos
- [ ] 17.11 rate limiting
- [ ] 17.12 webhooks
- [ ] 17.13 pruebas negativas

## 18 — PHP servidor e infraestructura

- [ ] 18.01 PHP CLI Apache hosting
- [ ] 18.02 parches y CVE
- [ ] 18.03 extensiones
- [ ] 18.04 session.upload_progress
- [ ] 18.05 configuración php.ini
- [ ] 18.06 rutas dev y .env
- [ ] 18.07 HTTPS
- [ ] 18.08 archivos y permisos
- [ ] 18.09 logs
- [ ] 18.10 cron/colas
- [ ] 18.11 dependencias

## 19 — Integridad concurrencia y rendimiento

- [ ] 19.01 atomicidad y rollback
- [ ] 19.02 stock
- [ ] 19.03 pagos y pedidos únicos
- [ ] 19.04 índices EXPLAIN
- [ ] 19.05 locks
- [ ] 19.06 errores de red
- [ ] 19.07 colas
- [ ] 19.08 grandes volúmenes
- [ ] 19.09 paginación
- [ ] 19.10 latencia
- [ ] 19.11 imágenes

## 20 — Diseño público accesibilidad dispositivos

- [ ] 20.01 inicio catálogo ficha carrito checkout cuenta
- [ ] 20.02 anchos 363 390 768 1024 1600
- [ ] 20.03 Firefox Chrome Edge
- [ ] 20.04 teclado y foco
- [ ] 20.05 contraste
- [ ] 20.06 mensajes
- [ ] 20.07 imágenes
- [ ] 20.08 overflow
- [ ] 20.09 SEO
- [ ] 20.10 compra completa móvil

## 21 — Backups despliegue y lanzamiento

- [ ] 21.01 backup DB y archivos
- [ ] 21.02 restore real
- [ ] 21.03 migraciones
- [ ] 21.04 rollback
- [ ] 21.05 staging
- [ ] 21.06 HTTPS DNS
- [ ] 21.07 secretos
- [ ] 21.08 logs alertas
- [ ] 21.09 smoke
- [ ] 21.10 E2E final
- [ ] 21.11 GO NO GO
