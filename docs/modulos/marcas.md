# Marcas — informe vivo del módulo

> **Actualización:** 2026-10-21 — segunda pasada según `docs/GUIA_VISUAL_ADMIN_V3.md` **v2.0**.
> **Estado actual (tercera pasada 21/10):** **rediseño implementado y pruebas técnicas/visuales sintéticas aprobadas; PENDIENTE de aprobación visual del propietario y comprobación autenticada del nuevo layout.** Se sustituyó la composición que el propietario rechazó. La integración final queda supeditada a esa revisión.
> **Responsable:** chat de Marcas; única carpeta `C:\xampp\htdocs\mi-pesca`, rama `main`. No hizo commits, push, migraciones ni cambios a archivos de otro módulo.

## 1. Alcance y contratos preservados

Rutas reales en `config/routes.php` (solo inspección):

| Ruta | Finalidad |
|---|---|
| GET `/admin/brands` | Búsqueda y paginación |
| GET `/admin/brands/new` | Alta |
| GET `/admin/brands/edit?id=` | Edición |
| POST `/admin/brands/save` | Persistencia de marca y SEO |
| POST `/admin/brands/quick` | Alta rápida vía formulario de Productos; respuesta `{ok,id,name}` |
| POST `/admin/brands/toggle` | Stub existente HTTP 204, sin funcionalidad real |

Mantiene `Admin\BaseController`/`require_admin()`, CSRF y rate limit en los POST operativos. Conservaron sus contratos `name`, `id`, selectores `data-seo-*`, módulos JS de SEO y endpoint usado por `public_html/assets/js/admin/product_form/index.js`. Sin cambios en `/public`, rutas, layout, shell, sidebar, PHP/JS/CSS de otros módulos ni servicios compartidos.

## 2. Hallazgos, prioridades y resolución

| Prioridad | Problema | Estado comprobado |
|---|---|---|
| Alta | Controlador concentraba consultas, presentación, validaciones, SQL de escritura, subida, auditoría y SEO. | **Parcial:** lectura/listado/slug en repositorio y datos de paginación en ViewModel; el POST sigue en controlador para no alterar servicios compartidos. |
| Alta | Edición de ID inexistente daba falsa alta. | **Corregido:** consulta por ID y redirección informativa en GET y POST. |
| Alta | Falta validación de nombre/slug/orden/metadata/portada. | **Corregido en campos básicos:** longitud de nombre 120, slug 140 con formato y unicidad, orden entero INT, meta title 70, description 160, ruta de portada 255. Quedan validaciones de URLs/SEO avanzado. |
| Alta | Imagen sin límites explícitos. | **Corregido y probado por HTTP:** JPEG/PNG/WEBP, peso hasta 5 MB, dimensiones hasta 6000 px, validación de archivo temporal y limpieza del nuevo archivo si falla la escritura SQL. |
| Alta | Guardado SQL y SEO pueden quedar parcialmente aplicados. | **Pendiente por diseño:** advertencia visible si falla SEO; propuesta transaccional documentada en `6`, sin implementarla. |
| Media | Todas las marcas se obtenían sin paginación. | **Corregido:** COUNT/limit/offset, 25/50, orden estable por sort/nombre/ID. |
| Media | No se mostraban relaciones a productos. | **Corregido:** totales y activos con consultas de solo lectura; sin modificar relaciones. |
| Media | Lista móvil, vacíos y controles duplicados respecto al DS. | **Corregido:** filas móviles, tabla desktop y uso de `av3-ui-*` reales. |
| Media | Buscador `LIKE` trataba `%` y `_` como comodines. | **Corregido:** `LIKE ... ESCAPE '='`, escapando `=`, `%` y `_`; verificado en consultas con las tres cadenas. |
| Media | Pérdida de valores ante error del formulario. | **Corregido:** `BrandFormDraft` conserva lista blanca de campos y SEO en sesión, consume una sola vez en el GET adecuado, rehace preview SEO y avisa que la imagen debe volver a seleccionarse. Verificado en POST duplicado real. |
| Media | Alta rápida podía devolver nombre incorrecto ante slug existente. | **Corregido:** devuelve ID/nombre almacenados, JSON de error y manejo de colisión. Probado desde el **modal real de Productos**. |
| Media | Errores de DB podían aparecer poco claros. | **Mejorado:** captura, registro y mensaje + redirección; falta prueba de falla forzada de DB y concurrencia. |
| Media | No hay estado activo ni eliminación/archivo de marcas. | **Sin cambios, deliberado:** tabla `brands` no tiene `is_active` y `toggle` es stub. Requiere decisión comercial por marcas relacionadas con productos. |
| Media | CSS de módulo intentaba definir controles similares al DS. | **Corregido:** `brands.css` quedó para composición/imagen/filas propias y estados; botones, tarjetas, campos, tablas y vacíos usan `av3-ui-*`. |
| Media | Contrato visual de 20 px para Page Header debe venir del shell. | **Respetado:** Page Header único, sin duplicado ni desplazamiento de sidebar. |
| Media | Nombre de marca no es único, solo su slug. | **Pendiente de definición comercial**, no forzar unicidad de nombre. |

## 3. Cambios concretos (todos exclusivos de Marcas)

| Archivo | Cambio |
|---|---|
| `app/Controllers/Admin/BrandsController.php` | Validaciones y manejo de error, repositorio/ViewModel, formulario con draft de un solo uso, quick y flujo SEO original preservado. |
| `app/Repositories/Admin/BrandAdminRepository.php` | Consultas preparadas, paginación 25/50, conteos de productos; búsqueda literal `%`/`_`/`=`. |
| `app/Services/Admin/Brands/ViewModels/BrandsIndexViewModel.php` | Rangos, tipos, enlaces y páginas calculados fuera de vista. |
| `app/Services/Admin/Brands/BrandFormDraft.php` | **Nuevo en segunda pasada:** captura/carga de campos permitidos y configuración SEO sin almacenar CSRF, archivo ni campos arbitrarios. |
| `app/Views/pages/admin/brands/index.php` | DS reutilizado, tabla desktop, tarjetas/filas móviles, búsqueda, vacíos y paginador. |
| `app/Views/pages/admin/brands/form.php` | DS reutilizado, secciones identidad/portada/SEO, draft y advertencia sobre volver a adjuntar archivo. |
| `public_html/assets/css/admin-v3/pages/brands.css` | CSS único de composición, enfoque mobile-first, container query para lista, máximo 860 px en formulario. |
| `scripts/build/brands_admin_contract.php` | Contrato focal extendido: DS, literal SQL, draft y CSS. No agregado al `gate.php` compartido. |
| `scripts/dev/brands_readonly_smoke.php` | Casos de `%`, `_`, `=`, borrador de sesión/SEO de un solo uso, render sintético, MySQL readonly y rechazo de archivos demasiado grandes. |

No se tocaron `MANIFEST.md`, `docs/ESTADO_DESARROLLO.md`, `head.php`, `scripts/build/gate.php`, `config/routes.php` ni otros documentos de módulos. La hoja de Marcas se carga desde sus dos vistas sin modificar `head.php`.

## 4. Contrato visual v2.0, duplicaciones y mobile-first

- Identidad oficial: `#006B6B`, `#F6F8FA`, `#FFFFFF`, `#182B39`, `#647786` y `#DFE5E9`, mediante tokens globales ya existentes; tipografía heredada del shell.
- **Se reutilizaron** `av3-ui-card`, `av3-ui-card-header`, `av3-ui-card-body`, `av3-ui-field`, `av3-ui-field-label`, `av3-ui-field-hint`, `av3-ui-control`, `av3-ui-table`, `av3-ui-table-scroll`, `av3-ui-empty`, `av3-ui-actions`, `av3-ui-badge` y `av3-ui-btn`.
- **CSS propio justificado:** toolbar grid, tabla compacta con proporciones de columna y celdas únicas, fila móvil y portada, composición del editor, estados flash, máximo de 860 px. No recrea base de botones, inputs ni tarjetas. Solo hay una excepción tipográfica local para corregir tamaños heredados DS de 11/12 px (label 13, hint 12), candidata a armonización global por chat principal. No se añadió CSS legacy, Tailwind ni superposiciones de hojas.
- **Anchos HTTP autenticados:** **363, 390, 768, 1024 y 1600 px**, index y form (10 capturas finales). 363/390: filas móviles y campos a una columna; 768/1024/1600: tabla y editor adaptado. Formulario centrado a 860 px en escritorio. **Sin overflow lateral** ni defectos JS detectados.
- **Defecto detectado y corregido visualmente:** la sección SEO había quedado sin `av3-ui-card` tras migrar el markup, creando paneles flotantes; se restauró tarjeta estándar y se verificó su clase/aplicación en 10 capturas finales. Se evitó el salto de línea del badge del ID en móvil.
- **Partes compartidas no modificadas:** el panel `admin/seo/entity_panel.php` conserva sus propios controles `av3-seo-*` y scripts JS porque lo comparten Categorías y Marcas; su homogenización interna exige coordinación explícita.

## 5. Pruebas y evidencia real/sintética

### 5.1. Contratos, smoke y build

- `C:\xampp\php\php.exe scripts/build/brands_admin_contract.php`: **OK**.
- `C:\xampp\php\php.exe scripts/dev/brands_readonly_smoke.php`: **OK**. Consultas reales **solo SELECT** sobre DB local: 7 marcas; `%`, `_`, `=` retornaron 0 porque no existían esos literales en nombres/slugs, sin coincidencias indebidas. HTML sintético, CSRF ausente en draft, consume una vez, overlay SEO y límite de imagen.
- `C:\xampp\php\php.exe scripts/build/gate.php`: **OK — 826 PHP, 119 JS**, ejecutado **después de la última modificación tipográfica** (21/10; exit code 0). También pasaron el contrato focal, smoke, PHP lint y analizador CSS (0 errores), además de `git diff --check` focal (solo avisos informativos LF/CRLF).
- **No se hicieron mutaciones reales sobre la base habitual** desde las pruebas focales.

### 5.2. HTTP autenticado **sobre base aislada**

- Se creó una **copia de la aplicación fuera del proyecto** bajo `C:\xampp\mi-pesca-backups\brands_http_isolated_20261021` y la base **nueva** `mt_brands_isolated_20261021`. Se copiaron 65 **estructuras de tablas** mediante MySQL `CREATE TABLE ... LIKE`, **sin datos del origen**, más un usuario admin fixture; la app copiada tenía `.env` propio.
- Servidor efímero PHP en `127.0.0.1:18677`, Edge headless con sesión admin fixture **inyectada para pruebas** y verificada por `require_admin()` contra tabla `users` aislada. **No se probó el login con credenciales real**; sí la autorización, GET y POST de rutas reales.
- **OK HTTP:** crear marca y metadatos/robots SEO; editar nombre/slug/SEO; duplicado de slug con redirección y **recuperación real** de nombre, slug, meta title y selección noindex; subida de PNG mediante multipart y archivo físico; endpoint quick con creación y reutilización; **modal real “Agregar marca” dentro de la pantalla Productos**, selección automática del nuevo ID/nombre sin guardar un producto; CSRF inválido rechazado 403; usuario anónimo redirigido a login.
- **DB aislada comprobada:** 3 marcas fixtures, portada persistida, SEO settings, **2 históricos de slug** y **1 redirect** por cambio de slug. No se usó contenido real de usuarios/productos.
- **Responsive autenticado HTTP:** cinco resoluciones × dos pantallas, 10 capturas y comprobaciones automatizadas de carga CSS, tipo de listado, ancho del formulario y overflow=0; revisión visual del móvil 390 y desktop 1600.
- Una URL con doble slash en la raíz del **servidor de prueba** requirió normalización **solo en su router temporal**, nunca en código compartido del proyecto.
- **Higiene:** terminó el servidor temporal, puerto 18677 dejó de escuchar, se eliminó endpoint de sesión fixture, se descartó la base `mt_brands_isolated_20261021` y se eliminó la carpeta clonada con credenciales temporales. Se preservaron únicamente capturas y scripts auxiliares de evidencia fuera del repositorio.
- **Evidencias:** `C:\xampp\mi-pesca-backups\brands_admin_20261020_2306\http_final_brands_{index,form}_{363,390,768,1024,1600}.png`, más scripts de ejecución externa. Estos scripts requieren reconstruir base/servidor nuevos para repetirse; no se instalan en el proyecto ni son parte del build.

## 6. Propuesta de atomicidad Marca + SEO (**no implementada**)

**Problema:** hoy UPDATE/INSERT de `brands` se confirma antes de registrar historial de slug, redirects y guardar `seo_settings`/`brand_seo_blocks`. Si uno de esos pasos falla, el nombre/slug ya cambió aunque el SEO quede anterior. La advertencia al administrador comunica el problema, pero no lo revierte.

**Propuesta para aprobación del chat principal:**
1. Introducir un servicio de aplicación propio (`BrandWriteService`) que normalice/valide entrada y prepare el archivo antes de comenzar. Mantener contratos POST actuales.
2. Iniciar una **transacción única InnoDB sobre el mismo PDO** para UPDATE/INSERT de `brands`, registro de slug, retarget/alta del redirect, `seo_settings` y `brand_seo_blocks`. Los servicios SEO actuales reciben ese mismo PDO; verificar que no hacen commits internos ni tragan errores.
3. Si cualquier operación SEO falla: `rollBack()`, mensaje de error consistente, conservar campos con `BrandFormDraft`, limpiar subida temporal y no mostrar flash de éxito.
4. Si todo concluye: `commit()`, luego auditoría best-effort y aviso de éxito. Conservar constraint UNIQUE de slug y cubrir colisiones concurrentes.
5. **Archivos no son transaccionales:** usar staging temporal privado para subidas, mover a destino definitivo de forma controlada y compensar/limpiar ante rollback. Nunca borrar portada anterior hasta completar commit; evaluar limpieza diferida de huérfanos.
6. Pruebas de fallo forzado en cada paso SEO, cambio de slug con historial/redirect, rollback de marca, reintento de colisión y subida interrumpida, todo con DB/archivos descartables.
7. Coordinar diseño e impacto en `SeoManagerService`, repositorios SEO, `Audit` y otros consumidores antes de modificar cualquier clase compartida. **Esta segunda pasada no modifica servicios compartidos ni aplica la transacción.**

## 7. Riesgos, pendientes y decisiones

- **Revisión funcional real en entorno operativo:** probar con sesión real e imágenes habituales, contrastes, teclado/lector de pantalla y errores de conexión; las pruebas actuales usan sesión de fixture, aunque invocaron controladores y guardados HTTP reales en DB aislada.
- **Pendientes de robustez:** input viejo en rutas no clasificadas, validación avanzada URL/SEO, índice de rendimiento para listado masivo, prueba de conflictos simultáneos de slug y política de limpieza de portadas reemplazadas.
- **Decisión comercial:** no añadir borrado, desactivación, estados ni archivado hasta definir implicancias sobre productos y SEO. `toggle` sigue no-op.
- **Pendiente arquitectónico:** atomicidad descrita en `6`; no aprobada para implementar.
- **Coordinación:** modificación de `head.php`, `scripts/build/gate.php`, panel SEO compartido o JS de Productos necesita ventana de edición del chat principal.
- **Entrega:** estos nueve archivos de Marcas y este documento están listos para revisión e integración selectiva del chat principal. No incorporar por accidente archivos de otros módulos.

## 8. Política de actualización

Este archivo se actualizará tras cada nueva fase, corrección, prueba o bloqueo. Solo Marcas escribe `docs/modulos/marcas.md`; el chat principal mantiene `docs/ESTADO_DESARROLLO.md` y `MANIFEST.md`, y realiza commits/push.


## ENTREGA PARA INTEGRACIÓN — auditoría final de solo lectura (2026-10-21)

> **Dictamen:** **LISTO PARA INTEGRACIÓN SELECTIVA POR EL CHAT PRINCIPAL.** No se detectaron bloqueos críticos confirmados. Esta entrega no constituye aprobación de producción. La propuesta de atomicidad Marca + SEO de la sección 6 queda diferida y **no se implementó**.

### 1. Guía visual v2.0 y coherencia interna

Se releyó `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0 y se contrastaron las vistas/CSS vigentes de Dashboard (`app/Views/pages/admin/dashboard/index.php`, `dashboard.css`) y Pedidos (`app/Views/pages/admin/orders/index.php`, `orders.css`) con Marcas.

- Coinciden la jerarquía (Page Header centralizado → filtros → contenido), colores/token, densidad de paneles y filas, acciones discretas y patrón tabla escritorio / filas móviles. Marcas no imita pestañas o estados comerciales inexistentes; reutiliza componentes `av3-ui-card`, `av3-ui-field`, `av3-ui-control`, `av3-ui-table`, `av3-ui-empty`, `av3-ui-actions`, `av3-ui-badge` y `av3-ui-btn`.
- `brands.css` está acotado a composición del módulo: toolbar, filas móviles, ancho/formulario, imagen y avisos. Hay una excepción tipográfica documentada de label 13 px y hint 12 px por tamaños antiguos del DS compartido; no se altera ese DS. No hay `!important`, overrides globales, CSS `-v2` ni Tailwind nuevo.
- Una sola hoja `brands.css` se carga desde cada vista `index.php` o `form.php`, **mutuamente excluyentes**: no figura añadida a `head.php`. Una definición de componente y ninguna carga doble dentro de la misma pantalla. Una sola `@container (min-width:650px)`; mobile-first conforme a guía.
- Evidencia HTTP disponible: listado y formulario a **363, 390, 768, 1024 y 1600 px** (10 capturas previas), sin overflow horizontal ni errores JS según el E2E. En escritorio el formulario usa máximo 860 px.
- No se detectó problema visual crítico a partir de la revisión de código y las capturas anteriores. En esta fase **no se abrió navegador ni se hizo nuevo render**.

### 2. Dependencias, limpieza y seguridad del árbol

- Rutas `/admin/brands`, `/new`, `/edit`, `/save` y `/quick` conservadas; el stub `/toggle` continúa sin estados ni mutación. Controlador, repositorio, ViewModel, `BrandFormDraft`, vistas, hoja CSS y scripts de prueba están presentes.
- Dependencias **compartidas y ya versionadas**, que NO hay que incluir en el commit de Marcas: `config/routes.php`, `app/Views/layouts/admin.php`, `app/Views/partials/admin/page_header.php`, `app/Views/partials/admin/seo/entity_panel.php`, `public_html/assets/css/admin-v3/tokens.css`, `public_html/assets/css/admin-v3/components/design-system.css` y `public_html/assets/js/admin/product_form/index.js`. Ninguna requiere modificación nueva para este cierre.
- No se encontraron HTML temporales `_brands_*`, fixtures o archivos de prueba en la raíz de `public_html` ni en las vistas del módulo. La carpeta clonada de HTTP aislado no existe y el puerto 18677 está apagado. Las capturas se conservan solo fuera del repositorio en `C:\xampp\mi-pesca-backups\brands_admin_20261020_2306`.
- Existe `scripts/dev/migrate_brands_form_av3.py`, pero **ya estaba versionado, sin cambios y no forma parte de este commit**. Tampoco debe incluirse el material de backup.
- `git status` muestra cambios simultáneos de otros chats (Categorías, Atributos, Marketing); **no usar staging global**.

### 3. Pruebas contrastadas con el código actual

| Prueba / evidencia | Resultado y precisión |
|---|---|
| Auditoría actual, sin escrituras de negocio | `php -l` sobre los **8 PHP** de Marcas/contratos: **OK**. `scripts/build/brands_admin_contract.php`: **OK** con fuentes actuales. Parser `tinycss2`: **0 errores**; CSS **0 !important** y **1 container query**. `git diff --check`: **sin errores**, con avisos informativos de normalización LF/CRLF. |
| Build gate de la implementación | `scripts/build/gate.php`: **OK, 826 PHP y 119 JS**. Se ejecutó en la pasada anterior **después del último cambio CSS**; no se reiteró ahora porque otros módulos se modifican simultáneamente. |
| Smoke de DB de la implementación | `scripts/dev/brands_readonly_smoke.php`: **OK**, 7 marcas locales, paginación, búsqueda literal `%`, `_`, `=`, render sintético y recuperación SEO. El script leído coincide con las afirmaciones. No se reejecutó aquí (altera sesión del proceso aunque las consultas DB sean de solo lectura). |
| HTTP E2E de la implementación | **OK** en base aislada descartable con administrador fixture: alta, edición, slug duplicado, retención POST y SEO, portada multipart, SEO/historial/redirect, creación rápida AJAX desde modal real de Productos, CSRF inválido y rechazo anónimo. El login real con credenciales **no** fue probado. La DB aislada y el servidor temporal se eliminaron. |
| Evidencia visual | **10 capturas** de ambos estados y cinco anchos, fechadas **21/10 00:47:52–00:47:56**. **Salvedad:** `brands.css` fue ajustado después, **00:51:03**, únicamente en tamaños de labels/ayudas. Por ello las capturas no verifican el CSS definitivo *píxel por píxel*. Sí pasaron después gate y parser CSS. **No bloqueante**, pero el chat principal puede revisar brevemente esa microdiferencia tras integrar. |

### 4. Lista cerrada y exacta del commit exclusivo de Marcas: **10 archivos**

**Tres modificados y ya versionados:**

1. `app/Controllers/Admin/BrandsController.php`
2. `app/Views/pages/admin/brands/index.php`
3. `app/Views/pages/admin/brands/form.php`

**Seis nuevos de Marcas:**

4. `app/Repositories/Admin/BrandAdminRepository.php`
5. `app/Services/Admin/Brands/BrandFormDraft.php`
6. `app/Services/Admin/Brands/ViewModels/BrandsIndexViewModel.php`
7. `public_html/assets/css/admin-v3/pages/brands.css`
8. `scripts/build/brands_admin_contract.php`
9. `scripts/dev/brands_readonly_smoke.php`

**Un documento exclusivo:**

10. `docs/modulos/marcas.md`

**El chat principal debe agregar exactamente esos diez paths y comprobar** `git diff --cached --name-only`, `git diff --cached --check` y el contrato/gate de integración antes del commit. **No** agregar con `git add .` ni `git add -A`: hay cambios ajenos. Excluir otros módulos, archivos globales, migrador previo sin cambios, `.env`, `storage`, `uploads`, backups, fixtures y evidencia externa.

### 5. Bloqueantes versus pendientes diferidos

**Bloqueantes confirmados hoy:** **ninguno**. Serían bloqueantes al integrar: encontrar uno de los diez archivos ausente, dependencia no versionada, diferencias staged de otro módulo o fallo del gate/contrato del árbol final. No se detectó ninguna de estas condiciones en esta auditoría.

**No bloqueantes, bajo seguimiento:**
- Falta de atomicidad entre Marca, historial de slugs y SEO; la advertencia al operador existe y la propuesta transaccional compartida está en sección 6. **Sin implementar por instrucción del propietario.**
- `toggle` sin lógica, y ninguna eliminación, archivado o estado nuevo: requieren aprobación comercial.
- Pruebas pendientes de login normal, concurrencia en slug, error de infraestructura, limpieza de portadas sustituidas, tablas muy grandes y accesibilidad exhaustiva.
- Microdiferencia visual de tipografía posterior a las capturas (`3`); no es error crítico confirmado.
- Posible integración futura del contrato al `gate.php` global y de CSS a `head.php` solo con coordinación. **No necesarios para este commit.**

**Estado final:** **LISTO PARA COMMIT SELECTIVO POR EL CHAT PRINCIPAL.** Esta auditoría fue de lectura salvo la presente sección documental. **Detener modificaciones y esperar revisión del chat principal.**


## REVISIÓN VISUAL NO APROBADA — propuesta de tercera pasada (2026-10-21, sin implementar)

**Origen:** cuatro capturas reales aportadas por el propietario de `/admin/brands/edit?id=6` (desktop y emulación móvil 414 × 846). La revisión de código se hizo contra `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0, contrato formal, `layout.css`, `brands.css`, `app/Views/pages/admin/brands/form.php`, `app/Views/partials/admin/seo/entity_panel.php`, `public_html/assets/css/admin-v3/pages/seo.css` y `public_html/assets/js/admin/seo/product_blocks.js`. No hubo modificaciones de PHP/CSS/JS ni pruebas de navegador adicionales.

### Diagnóstico concreto

1. **Ancho desktop excesivamente limitado:** tanto `public_html/assets/css/admin-v3/layout.css` (`.av3-layout--form`) como una regla local de `brands.css` fijan `max-width:860px`; las capturas confirman un formulario central estrecho con grandes márgenes desaprovechados.
2. **Flujo largo y sin composición:** Información de marca, Portada y SEO están en tres tarjetas verticales incluso cuando el contenido del shell admite columnas. El mismo bloque SEO incluye una tarjeta exterior y otras tres tarjetas interiores, con bordes anidados.
3. **Diagnóstico SEO desproporcionado:** score, preview y checklist anteceden a los campos editables y generan una lectura lenta. El rojo del score tiene protagonismo alto para un indicador informativo.
4. **Contenido móvil poco operativo:** `product_blocks.js` agrega botones Subir/Bajar a todas las filas en pantallas táctiles, incluso cuando solo hay una fila; el parcial SEO compartido crea por defecto una fila vacía de cada tipo. Los controles terminan apilados, con espacios excesivos y falta de jerarquía entre input, quitar y reordenar.
5. **Confusión entre secciones:** el `<details>` de «Configuración avanzada» controla solo robots/canonical/Open Graph/JSON-LD; «Contenido complementario» es un hermano visible dentro de la misma tarjeta. Visualmente parece pertenecer al acordeón, aunque no es así.
6. **Acción de guardado lejana:** los botones se presentan solo después de una página muy larga; evaluar una barra sticky contenida dentro del área del módulo.

### Propuesta de layout (a aprobar)

- **Desktop ancho útil suficiente (~>=1080px dentro del contenedor):** quitar `av3-layout--form` de la vista de Marcas (sin tocar la definición global) y reemplazar su max 860 local por composición fluid/max razonable bajo los gutters existentes del shell; cabecera maestra intacta.
- **Primera fila 65/35:** tarjeta «Información de la marca» y tarjeta «Portada», columnas proporcionales, alineadas arriba y sin forzar iguales alturas ni blancos artificiales. Nombre + slug comparten fila; orden queda en la zona inferior de identidad. Previsualización de imagen en columna lateral y controles claros.
- **SEO completo de ancho fluid:** quitar anidamiento de tarjeta externa y distribuir el editor en diagnóstico/preview (aprox. 40%) y campos SEO (aprox. 60%). Checklist debajo del preview dentro de su columna; campos título/descripcion/texto agrupados y con alturas útiles. Reutilizar markup y `data-*` existentes sin reimplementar score.
- **SEO avanzado separado:** acordeón propio visible, independiente del contenido complementario.
- **Contenido complementario:** sección propia al final, tarjetas de Puntos clave, Especificaciones y FAQ. En pantalla ancha pueden disponerse en columnas sin duplicación de headers y bordes. Controles `av3-ui-*`, sin nuevos sistemas visuales.
- **Tablet/mediano:** las tarjetas principales colapsan a una sola columna cuando el ancho útil no admite composición; se mantienen diseños internos compactos cuando hay espacio.
- **Mobile 363/390/414:** una columna, tarjetas compactas; sección «Contenido complementario» visualmente separada de «Configuración avanzada» y grupos legibles. Cada fila ofrece campos completos y botón de quitar próximo al contexto; solo mostrar Subir/Bajar cuando haya al menos dos filas ordenables, y agruparlos en un espacio pequeño. Evitar render permanente de tres formularios vacíos enormes: evaluar estados vacíos compactos/expansión en el editor compartido con la coordinación necesaria. Botón Guardar accesible sin interceptar teclado, safe area ni scroll.
- **Breakpoints por ancho disponible** mediante container queries donde corresponda; validar 363, 390, 768, 1024, 1600 y 414 de la captura aportada, con sidebar abierto/reducido, errores, datos largos y varias filas.
- **CSS:** estilos exclusivos en `brands.css`, reemplazando la regla local anterior, sin añadir overrides progresivos. Mantener Page Header, tokens, DS, rutas, input names y hooks JS. No cambiar reglas comerciales ni SEO.
- **Límite compartido:** `app/Views/partials/admin/seo/entity_panel.php`, `seo.css` y `product_blocks.js` también afectan Categorías/Productos. Para un cambio de estructura/acciones globales (filas vacías, reorder, accordions) solicitar reserva del chat principal. Preferir solución local acotada que no copie el editor ni fragmente el contrato compartido.

**Estado:** diagnóstico y propuesta únicamente. **Implementación visual pendiente de aprobación del propietario**. Por este motivo **NO considerar vigente el dictamen anterior de «listo para commit» respecto a la UI** hasta completar y aprobar la tercera pasada. Funcionalidad y E2E anteriores conservan su evidencia, sin repetir.


### Implementación autorizada — tercera pasada visual (21/10/2026, EN CURSO)

**Referencias públicas revisadas antes de escribir código:** [Shopify — resource details](https://polaris-site-prod-kit.shopify.prod.shopifyapps.com/patterns/resource-details-layout), [Shopify — forms/save bar](https://shopify.dev/docs/apps/design/user-experience/forms), [Nimbus — layouts asimétricos](https://tiendanube.dev/es-AR/documentation/patterns/layout), [Tiendanube — form organization](https://dev.tiendanube.com/en/docs/design-guidelines/concept-usage), [Tabler — cards/forms](https://docs.tabler.io/ui/components) y [falcolee/e-commerce-dashboard](https://github.com/falcolee/e-commerce-dashboard). Criterios adoptados: principal/auxiliar, formularios por agrupación, mobile apilado y guardado contextual, **sin incorporar librerías**.

**Cambios de presentación en progreso (acotados a Marcas):**
- `app/Views/pages/admin/brands/form.php` elimina `av3-layout--form` y organiza Información + Portada dentro de `av3-brands__lead-grid`. SEO deja de estar envuelto en una tarjeta general adicional y se presenta como sección independiente; añade módulo local JS. Conserva POST, CSRF, `name` y editor SEO compartido.
- `public_html/assets/css/admin-v3/pages/brands.css` reescrito como una hoja consolidada: layout fluido del shell, composición 1.75fr/1fr para identidad/portada a partir de ancho útil 960px, diagnóstico y campos SEO 2fr/3fr, contenido complementario con columnas cuando se dispone de ≥1220px y composición móvil ≤649px. Sin offsets propios de sidebar.
- `public_html/assets/js/admin/brands/form.js` **nuevo:** contrae secciones complementarias vacías en móvil sin quitar la fila del DOM ni del POST; evita crear otra fila vacía en el primer clic «Agregar» y expone guardado contextual al modificar el formulario. No altera scripts compartidos.
- Respaldo privado anterior en `C:\xampp\mi-pesca-backups\brands_visual_20261021_implement`.

**Aún pendiente:** lint, build gate, contrato, revisión visual responsive y prueba de interacciones. **No declarar aprobado antes de verificar.**


### Tercera pasada — cierre de implementación y QA (2026-10-21)

**Estado definitivo de esta pasada:** **Listo para revisión visual del propietario**. Reemplaza las conclusiones de la sección «REVISIÓN VISUAL NO APROBADA» respecto del código anterior; la aprobación humana del nuevo diseño sigue pendiente. No se hizo commit/push ni se alteraron reglas comerciales.

**Investigación previa:** Tabler UI (cards/forms), Shopify (resource details, forms y contextual save bar), Tiendanube/Nimbus (layout asimétrico y agrupación de campos) y falcolee/e-commerce-dashboard (flujo de producto). Se adoptaron sus patrones de UX, no CSS/librerías externas; links y decisiones documentados en la entrada de implementación anterior.

**Archivos efectivamente editados y nuevos en esta pasada:**
- `app/Views/pages/admin/brands/form.php` (**modificado**): modo `av3-layout--operational`, `av3-brands__lead-grid` para identidad + portada, sección SEO sin tarjeta exterior redundante, conserva parcial SEO y contratos de formulario; agrega script local.
- `public_html/assets/css/admin-v3/pages/brands.css` (**modificado, archivo propio**): reemplaza la composición de 860px y los estilos anteriores por ancho fluido, 2 columnas 1.75/1 en escritorio amplio, diagnóstico+metadatos SEO en 2 columnas balanceadas, tres bloques complementarios en escritorio amplio, móvil en 1 columna y tarjetas compactas de contenido vacío. No introduce CSS global, Tailwind ni `!important`. Se retiró footer sticky que tapaba campos; acciones normales al final + barra contextual al editar.
- `public_html/assets/js/admin/brands/form.js` (**nuevo**): JS exclusivo. En móvil, oculta visualmente la fila de entrada vacía en cada bloque SEO, la conserva en DOM/POST y la revela con el primer clic en «Agregar» sin duplicarla; preserva data-seo-* compartidos. Barra contextual de guardado al editar, oculta si el botón original final ya es visible (IntersectionObserver).
- `docs/modulos/marcas.md` (**modificado**): investigación, diagnóstico, avances y validación.

**Pruebas actuales realizadas:**
- `scripts/build/gate.php`: **OK, 826 PHP / 120 JS, exit code 0 (77,1s)**, tras la última modificación funcional y CSS.
- `scripts/build/brands_admin_contract.php`: **OK**. `scripts/dev/brands_readonly_smoke.php`: **OK** contra base local, **7 marcas**, búsqueda literal `%`, `_`, `=`, paginación, HTML y validaciones, sin POST.
- `php -l` del formulario, `node --check` del nuevo JS: **OK**. `tinycss2`: 0 errores de parseo; ninguna declaración `!important` en `brands.css`.
- **Validación visual Edge headless, HTML/SEO sintéticos, sin autenticación ni POST**: anchos **363, 390, 414, 768, 1024 y 1600 px**; 6 screenshots iniciales + uno de interacción. Resultado: **sin overflow lateral ni page errors**, CSS y script cargados. En 1600 ancho útil medido **1302px**; columnas identidad/portada aprox. **818/468 px**, diagnóstico/metadatos SEO aprox. **612/674 px**. En 363/390/414, tres bloques SEO inicialmente sin datos presentan estado vacío compacto. A 390 se verificó «Agregar»: se conserva una sola fila, no se duplica; el guardado contextual aparece tras input y se oculta al llegar al botón final.
- **Teardown:** navegador creado por script de respaldo con `headless:true`, cerrado en `finally`, vistas temporales servidas bajo `public_html` eliminadas tras ejecución. Evidencias y respaldo **fuera del proyecto** en `C:\xampp\mi-pesca-backups\brands_visual_20261021_implement\form_{363,390,414,768,1024,1600}.png` y `form_390_editing.png`. No se generó ZIP.
- Las pruebas E2E HTTP autenticadas anteriores cubren lógica, no el diseño actualizado. **No afirmar que esta tercera pasada tuvo nueva autenticación HTTP.**

**Pendientes concretos:**
1. **Aprobación visual del propietario** con recarga forzada de `/admin/brands/edit?id=6` en desktop y móvil; los screenshots sintéticos no sustituyen el comportamiento exacto con datos reales.
2. Comprobación manual accesibilidad/foco, campos SEO previamente completados, varias filas de bloques, reordenamiento táctil y combinaciones de sidebar abierto/reducido. Se probaron 6 viewports de base sin overflow, pero no todas esas variantes.
3. Si se desea consolidar el editor SEO en todas las entidades, coordinar `entity_panel.php`, `seo.css` y `product_blocks.js` con SEO/Categorías/Productos. **No se modificaron compartidos** en esta pasada.
4. Atomicidad Marca + SEO, estados y eliminación continúan fuera de alcance y sin autorización para implementar.
5. El listado `/admin/brands` no se rediseñó en esta pasada, solo se conserva su CSS existente; archivos asignados a otros módulos intactos.

**Entrega para integración:** sumar el JS nuevo `public_html/assets/js/admin/brands/form.js` a la lista cerrada previa; todos los archivos antiguos de Marcas siguen siendo parte del módulo. El chat principal decide staging selectivo tras aprobación visual. No usar `git add .`.


### Verificación final posterior a ajustes (21/10/2026)

- El último ajuste visual reemplazó **en la regla existente de `brands.css`** el margen heredado de `.av3-flow-6 > ...`, que desplazaba el segundo panel SEO 24px hacia abajo. Los paneles de diagnóstico y metadatos quedaron alineados; se reabrió la captura `form_1600.png` y se confirmó visualmente.
- `scripts/build/brands_admin_contract.php` (**archivo propio modificado en esta etapa**) ahora registra también el nuevo JS de Marcas y exige que `form.php` cargue el script y conserve sus hooks, `IntersectionObserver` y `requestSubmit`.
- **Gate global final ejecutado tras ajuste:** **OK, 827 PHP / 120 JS, exit 0, 74,77 s**. Contrato focal actualizado: **OK**. CSS y JS revisados, sin error de parseo, `!important` ni errores de JavaScript detectados en navegador.
- **Nueva repetición visual:** Edge headless, 363/390/414/768/1024/1600, HTML sintético servido temporalmente; **OK, sin overflow, sin POST**. A 390, interacción «Agregar» mantuvo 1 fila y barra contextual apareció al editar pero se ocultó ante el pie de guardado. `finally` cerró Edge, no quedaron HTML `_brands_v3_*` en `public_html`; 7 PNG de evidencia fuera del repositorio en el respaldo privado.
- **Archivos de esta tercera pasada:** `app/Views/pages/admin/brands/form.php`, `public_html/assets/css/admin-v3/pages/brands.css`, **nuevo** `public_html/assets/js/admin/brands/form.js`, `scripts/build/brands_admin_contract.php` y `docs/modulos/marcas.md`. No se modificaron servicios SEO, parciales globales, scripts de Categorías/Productos ni reglas de negocio.
- **Para el staging del chat principal:** la lista histórica de diez paths en la sección ENTREGA PARA INTEGRACIÓN debe **ampliarse con `public_html/assets/js/admin/brands/form.js`**, total **11 paths del módulo**, y comprobar el diff actual antes de cometer. La nueva composición queda **pendiente de aprobación visual humana con sesión real**. No commits ni push.

### Cuarta pasada — grilla compacta y simétrica (2026-10-21)

**Decisión del propietario:** descartar la imagen BigCommerce; no limitar ancho de formularios; respetar el shell Admin V3 y aprovechar su espacio con grilla fluida, portada pequeña, campos alineados, padding compacto y sin espacios muertos artificiales. El diseño anterior de tarjetas enormes queda sustituido.

**Archivos modificados (exclusivos de Marcas):**
- `app/Views/pages/admin/brands/form.php`: unifica Datos e Imagen en **una sola tarjeta**, conserva nombres de inputs, CSRF, rutas y partial SEO. Edición de portada/URL mediante details nativo, sin ocultar el valor al POST.
- `public_html/assets/css/admin-v3/pages/brands.css`: **sustituida la composición anterior**, grilla 12 columnas fluidas (5/5/2 para inputs, 9/3 entre información y portada), miniatura 140×72 px, inputs 36 px, espaciados 10–14 px, SEO 50/50 a partir de 850 px útiles; avanzado en tres columnas balanceadas y contenido complementario compacto, sin filas vacías visibles. Sin CSS compartido nuevo ni `!important`.
- `scripts/build/brands_admin_contract.php`: ampliado con checks de la grilla y las clases nuevas.
- `docs/modulos/marcas.md`: registro de esta etapa.

**Evidencias:** PHP lint, smoke focal readonly con 7 marcas, contrato del módulo, parser CSS (0 errores, 0 `!important`) y sintaxis JS local aprobados. QA visual Edge headless **sin POST**: 363, 390, 414, 768, 1024 y 1600 px; no overflow horizontal ni errores JS. A 1600, formulario a ancho del shell, portada 140×72, panel superior ~225 px y SEO dos columnas exactamente iguales; a 1024 SEO también 50/50. En móvil, campos apilados, edición de imagen desplegable, Agregar no duplica la primera fila SEO vacía, barra contextual no tapa guardado. Configuración avanzada abierta a 1600 sin overflow. Backups y capturas en `C:\xampp\mi-pesca-backups\brands_compact_20261021`, fuera del repositorio; HTML de prueba eliminado y Edge cerrado en finally.

**Pendientes:** inspección visual autenticada de `/admin/brands/edit?id=6` y aprobación del propietario. Sin cambios en SEO compartido, rutas, otros módulos ni base de datos; atomicidad y estados fuera de alcance. Sin commits ni push.

**Cierre de validación técnica:** `scripts/build/gate.php` ejecutado después del rediseño: **OK, 828 PHP / 120 JavaScript, exit 0**. Contrato de Marcas ampliado y QA sintética aprobados. **Estado: listo para inspección visual y aprobación del propietario, no aprobado aún en sesión real.**

### Quinta pasada — reestructuración HTML mobile-first (21/10/2026)

**Estado: EN CURSO.** Pedido del propietario: reemplazar tanto HTML como CSS para resolver inputs que crecen en desktop y recuperar densidad, diseñando primero a 363/390 px. A diferencia de las pasadas anteriores, se planifica un formulario SEO local de Marcas con las mismas entradas/hook JS del componente compartido; los servicios y el editor compartidos quedan sin edición. El eventual costo de mantener el marcado local se documentará al cerrar. Respaldo externo: `C:\xampp\mi-pesca-backups\brands_mobile_restructure_20261021`. Pendientes: implementación, lint, build gate, QA móvil/desktop y E2E seguro.

### Quinta pasada — implementación mobile-first de HTML + CSS (2026-10-21)

**Estado: LISTO PARA REVISIÓN VISUAL DEL PROPIETARIO; no aprobado todavía en navegador autenticado.**

**Motivo:** corregir el uso de CSS sobre un HTML SEO pensado para otra composición; evitar inputs gigantes de Nombre, Slug, Orden y Título SEO, sin limitar el ancho del layout maestro.

**Cambios concretos:**
- `app/Views/pages/admin/brands/form.php` (**reescrito**): una sección de identidad con campos y portada en grid semántico, labels accesibles e inputs existentes; mantiene `name`, `slug`, `sort`, `cover_image_file`, `cover_image`, `id`, CSRF, acción POST y retorno. En móvil: nombre y slug fluidos en filas propias, orden compacto, portada pequeña con configuración desplegable. En escritorio: campos en tracks explícitos, sin crecer indefinidamente.
- `app/Views/pages/admin/brands/seo_editor.php` (**nuevo, vista exclusiva de Marcas**): la sección SEO se estructuró **en HTML propio** (metadatos / diagnóstico / avanzado / bloques) partiendo de los campos y plantillas funcionales existentes. Mismos nombres POST y atributos `data-seo-*` usados por los JS compartidos. SEO básico de dos columnas proporcionadas en desktop y una en móvil; diagnóstico y checklist conservados. Contenido complementario con plantillas de filas, vista previa, drag-and-drop y acciones táctiles idénticas. **Compromiso técnico:** replica el marcado del parcial SEO compartido, pero no duplica servicios ni JS; si en el futuro cambia ese contrato, coordinar cambios en ambos o parametrizar la vista compartida desde el chat principal.
- `public_html/assets/css/admin-v3/pages/brands.css` (**consolidado**): reemplaza reglas obsoletas del editor; mobile-first desde 363px, composición fluida por @container del shell. Controles de 38px, portada 88×58px (82×56 en desktop); ancho fijo de 100px para Orden en desktop y max 128 en móvil; Nombre y Slug aprox. 390px a 1600/1920, no crecen arbitrariamente en monitores grandes. Metadatos SEO con campos proporcionados, Texto SEO a ancho útil. Estados, bordes y componentes Admin V3; sin Tailwind/hojas extra ni `!important`. La tabla del listado mantiene sus reglas responsive.
- `scripts/build/brands_admin_contract.php` (**modificado**): incorpora la nueva vista, vigila todos los campos SEO básicos/avanzados, nombres de los arrays de contenido y templates; no cambia el gate global.
- `docs/modulos/marcas.md` (**actualizado**): este informe. `public_html/assets/js/admin/brands/form.js` (existente) sin cambios; conserva cierre de bloques vacíos y barra contextual; servicios de Marcas y SEO, parcial SEO compartido, Categorías, Productos, rutas y layout sin cambios.

**Pruebas realizadas:**
- PHP lint de ambas vistas: **OK**. `scripts/build/brands_admin_contract.php`: **OK** con campos de SEO completos. `scripts/dev/brands_readonly_smoke.php`: **OK**, 7 marcas locales; solo SELECT, render HTML y validación de draft/portada (sin POST). Parser CSS: 0 errores / 0 `!important`. Node check del JS existente y `git diff --check`: OK (aviso informativo LF→CRLF).
- Edge **headless, HTML sintético y sin POST**, resoluciones **363, 390, 414, 768, 1024, 1600 y 1920px**: **sin scroll lateral ni errores JavaScript**. En 390: portada desplegable, cambios de título reflejados en preview, primer Agregar sin duplicar fila vacía, agregar 2.º punto clave, reordenar Subir, quitar, rellenar Especificaciones/FAQ y verificar ambas vistas previas, barra contextual visible/oculta según pie. Configuración avanzada desplegada en 1600 sin overflow y con todos sus campos. A 1600 Nombre/Slug 390px, Orden 100px, Título SEO 303px; a 1920 Nombre/Slug siguen en 390px. SEO 50/50 en escritorio. Capturas `v5_{363,390,414,768,1024,1600,1920}.png`, `v5_390_interaction.png` y `v5_1600_advanced.png` en `C:\xampp\mi-pesca-backups\brands_mobile_restructure_20261021` (fuera del repositorio).
- **Cierre de prueba:** script con `finally` cierra páginas/navegador y elimina HTML temporal en `public_html`. No lanzó servidor, no copió BD, no realizó POST ni generó ZIP. La prueba HTTP aislada autenticada de una fase anterior cubre backend, **no sustituye una revisión autenticada de este HTML**.
- **Gate global**: ejecutándose al redactar este apartado; registrar resultado final antes de entrega.

**Pendientes:** aprobación visual sobre `/admin/brands/edit?id=6` en sesión real tras recarga, accesibilidad con teclado/lectores de pantalla, pruebas de datos extensos y subida HTTP real con nuevo HTML (endpoint ya verificado en E2E anterior). Atomicidad Marca+SEO se mantiene propuesta y no implementada; no agregar estados/eliminación.

**Corrección de ubicación durante gate (21/10):** El primer build gate de esta pasada falló porque el nuevo SEO se había creado en `app/Views/pages/admin/brands/seo_editor.php` y el contrato de Page Header exige encabezado a cada página. **Corregido sin editar archivos compartidos:** movido a `app/Views/partials/admin/brands/seo_editor.php`; se actualizaron exclusivamente `form.php` y `brands_admin_contract.php`. El archivo ya no se considera una página independiente; falta registrar el gate repetido.

**Cierre técnico (quinta pasada):** la plantilla exclusiva SEO quedó definitivamente en `app/Views/partials/admin/brands/seo_editor.php`, no en `pages/`, para respetar el contrato de Page Header. **Gate general reejecutado y APROBADO: 829 PHP, 120 JS, exit 0 (75,74 s)**. Contrato Marcas actualizado, PHP lint, CSS parser, JS check y smoke readonly: OK.

**QA ampliada:** la grilla del listado también fue comprobada con Edge headless a 363/768/1600 px: filas móviles en 363, tabla en 768/1600 y **sin overflow ni errores JS**. En el formulario se verificaron siete anchos, más interacciones CRUD de bloques SEO y portadas, sin POST. Teardown: HTML temporal de ambas pruebas retirado; navegadores headless cerrados en `finally`. **Archivos propios de esta pasada para integración:** `app/Views/pages/admin/brands/form.php`, `app/Views/partials/admin/brands/seo_editor.php` (**nuevo**), `public_html/assets/css/admin-v3/pages/brands.css`, `scripts/build/brands_admin_contract.php` y este `docs/modulos/marcas.md`. El JS ya existente `public_html/assets/js/admin/brands/form.js` no se modificó. **Estado: LISTO PARA REVISIÓN VISUAL AUTENTICADA DEL PROPIETARIO; no declarar aprobado en producción.**

### Sexta pasada — densidad y alineaciones (21/10/2026, EN CURSO)

Pedido del propietario: corregir de forma integral uso de espacio, paddings/margins y **alineaciones en móvil y escritorio**; mantener ancho del shell sin paneles limitados ni inputs enormes. Reserva exclusiva de Marcas verificada; respaldo de sus tres archivos de UI anterior en `C:\xampp\mi-pesca-backups\brands_alignment_v6_20261021`. Se reestructura HTML de las filas compactas, grupos SEO avanzado y vista complementaria; CSS único local, preservando los campos POST y hooks del SEO compartido. No tocar archivos globales, rutas, base ni otros módulos. Pendientes: implementar, QA funcional y responsive, build gate y revisión visual autenticada.

### Sexta pasada — alineaciones, densidad y grillas (2026-10-21)

**Estado:** implementado; pendiente de validación visual autenticada y aprobación del propietario. No se tocó negocio, DB, rutas, shell ni servicios/JS SEO compartidos.

**Archivos modificados:**
- `app/Views/pages/admin/brands/form.php`: agrupación estructural de Orden e Imagen con un eje común; editor de portada toma fila completa al abrirse en móvil.
- `app/Views/partials/admin/brands/seo_editor.php`: reorganizados Indexación, Open Graph y JSON-LD plegable; labels/IDs propios, nombres POST y hooks SEO preservados.
- `public_html/assets/css/admin-v3/pages/brands.css`: CSS v6 consolidado, mobile-first; controles 38 px, labels 13 px, ayuda 12 px; padding 14–16 px, gaps 12 px; columnas proporcionales; sin anchos limitados del shell, sin overrides acumulados ni `!important`. Diseño de listado preservado.
- `scripts/build/brands_admin_contract.php`: contrato propio ampliado a nuevas secciones y hooks.
- `docs/modulos/marcas.md`: documentación actualizada. El JS local `public_html/assets/js/admin/brands/form.js` no fue modificado en esta pasada.

**Resultados:** contrato focal y smoke readonly OK (7 marcas, búsqueda literal `%`, `_`, `=`, sin POST). Lint PHP OK, parser CSS 0 errores, `git diff --check` sin errores. QA visual headless sin POST a 363/390/414/768/1024/1600/1920 px: sin overflow ni errores JS; ejes de Orden/Portada y de campos alineados; encabezados de los tres bloques complementarios simétricos en desktop. Configuración avanzada abierta: grupos de Indexación y Open Graph alineados y JSON-LD preservado. Portada desplegada en 390 px sin input de URL comprimido. Listado a 363/768/1600 conservó filas móviles y tabla desktop. Capturas y scripts en respaldo externo `C:\xampp\mi-pesca-backups\brands_alignment_v6_20261021`. Navegadores headless cerrados y HTML temporal eliminado.

**Límites:** no se hizo nuevo POST ni validación HTTP autenticada de este HTML; deben verificarse con sesión real. Atomicidad Marca+SEO y reglas comerciales quedan como pendientes ajenos a esta pasada.

**Build gate general final (tras la sexta pasada): OK — 829 PHP / 120 JavaScript, exit code 0.** Implementación detenida a la espera de la revisión del chat principal/propietario, sin commits, push ni modificaciones de otros módulos.


### Séptima pasada — portada inline y SEO avanzado siempre visible (2026-10-21)

**Motivo:** las capturas auténticas `/admin/brands/edit?id=6` mostraron un gran espacio vacío entre Orden e Imagen, y una grilla desordenada al abrir «Modificar imagen». El propietario prohibió la expansión de imagen en escritorio y pidió edición directa en la misma fila. También solicitó eliminar la expansión de Configuración avanzada y rediseñar ese sector.

**Cambios implementados, solo Marcas:**
- `app/Views/pages/admin/brands/form.php`: retirado por completo el `<details>` de portada y su botón «Modificar imagen». Los controles `cover_image_file` (file) y `cover_image` (URL) están siempre visibles y operativos junto a la miniatura, con etiquetas accesibles y el mismo `name`/POST. No se alteró la prioridad comercial entre archivo/URL ni el endpoint.
- `app/Views/partials/admin/brands/seo_editor.php`: configuración avanzada convertida de `<details>` a `<section>` **siempre visible**; JSON-LD también quedó permanentemente visible, sin controles plegables. Se mantuvieron todos los campos SEO y sus nombres, selectores `data-*`, scores, listas, plantillas y preview.
- `public_html/assets/css/admin-v3/pages/brands.css`: sustituidos los estilos originales de los componentes modificados y reorganizados los breakpoints mediante `@container`. A 1600/1920 px, Nombre/Slug/Orden/Imagen en una fila sin justificación `space-between`: distancia entre Orden e Imagen de **12 px**. Selector de archivo y campo URL horizontales, miniatura 66×38. En móvil se apilan sin comprimir, con archivo y URL siempre accesibles; Canonical también gana ancho completo. Configuración avanzada en dos columnas a ancho útil >=860 px; izquierda Indexación + JSON-LD, derecha Open Graph, títulos alineados y alturas balanceadas; móvil una columna. Sin CSS compartido ni `!important`.
- `scripts/build/brands_admin_contract.php`: corregido el contrato específico para exigir portada inline y encabezado fijo de SEO avanzado, en vez del antiguo desplegable.
- `docs/modulos/marcas.md`: este seguimiento. Ningún cambio en controladores, servicios, base, JS compartido, sidebar o rutas. Sin Git commit/push.

**Validación focal:** contrato Marcas OK; smoke readonly sobre base local OK (7 marcas, consultas `%`, `_`, `=`); lint PHP de las dos vistas OK; CSS sin errores de parseo ni `!important`; JS existente sin errores de sintaxis. Pruebas en Edge headless con HTML sintético y solo GET: **363, 390, 414, 768, 1024, 1600, 1920 px**, sin desbordamiento lateral ni errores de JS. Contrato POST incluye los dos controles de portada y todos los campos SEO. JSON-LD y todos los campos avanzados visibles sin clic. Confirmado ancho compacto del panel superior (192 px a 1600), alineación de fila y separación Orden/Imagen de 12 px. Se probó selección de archivo de imagen descartable y edición URL sin POST; no se guardaron datos reales. Evidencias `v7_*.png` y scripts bajo `C:\xampp\mi-pesca-backups\brands_inline_cover_v7_20261021` fuera del repositorio. Navegador headless cerrado y HTML temporal retirado por teardown.

**Pendientes:** revisión visual y funcional auténtica del propietario sobre `id=6`; prueba de guardado HTTP autenticado sobre base aislada con esta nueva vista (las E2E anteriores validaron backend pero no sustituyen esa prueba). Atomicidad Marca+SEO sigue propuesta compartida sin implementar. La funcionalidad comercial permanece intacta.

**Cierre de la séptima pasada:** build gate global final **OK (829 PHP, 120 JS, exit 0)**. Repetida QA visual tras último ajuste: 7 resoluciones OK; selector de imagen eligió correctamente `fixture_cover.png` de 68 bytes sin POST; controles URL/SEO accesibles; CSS parse 0 errores y 0 `!important`. **Estado: listo para revisión visual humana; no afirmar aprobación sin inspección del propietario.**


## CIERRE TÉCNICO INTEGRAL V2 — 22/10/2026

**Alcance:** Admin Marcas completo; revisión estética y producción expresamente diferidas. Se conserva la UI actual con portada inline, SEO avanzado siempre visible y Admin V3 responsive. **QA SQL/HTTP: PASS**, ejecutado en un entorno aislado descartable.

### P0/P1 corregidos

1. **Atomicidad:** previamente el Controller confirmaba la marca antes de SEO, historial, redirects y bloques, generando guardados parciales. Nuevo `BrandSaveService.php` con una única transacción PDO para las cinco tablas (brands, seo_settings, seo_slug_history, seo_redirects, brand_seo_blocks), COMMIT solo al final, rollback ante cualquier excepción. Flash y auditoría de éxito solo después del COMMIT. Servicios SEO compartidos intactos.
2. **Historial de slugs:** validación de propiedad del slug histórico con bloqueo SQL antes de persistir; impide que el upsert compartido lo reasigne a otra marca. Duplicados y carrera por slug reciben rollback completo.
3. **Archivos:** subir archivo nuevo antes de SQL; si falla operación, eliminar solo el nuevo archivo, nunca el anterior. Después del COMMIT, se limpia el archivo previo únicamente si su ruta coincide con /uploads/brands/{32hex}.{png,jpg,webp}, no es enlace simbólico y ninguna marca lo referencia. URLs externas y archivos heredados nunca se borran. Errores FS best-effort registrados.
4. **Datos SEO:** `BrandSeoInput.php` valida JSON-LD, límites reales de campos OG/canonical, robots y arrays. Rechaza elementos incompletos, largos o en exceso en lugar de truncar silenciosamente. `BrandFormDraft.php` conserva hasta 100000 caracteres de SEO/JSON-LD al recuperarse de errores.
5. **Lectura segura:** si `BrandSeoBlocksService::getForBrand` falla, el Controller no muestra un editor vacío que pudiera borrar datos existentes al guardar; redirige con error y sin mutación.
6. **URL de imagen absoluta en Admin:** `BrandCoverUrl.php` resuelve HTTP(S) sin prefijo local. Miniaturas de listado y edición ya funcionan para rutas locales y URL externa; HTML y CSS existentes conservados.

### Pruebas SQL/HTTP y evidencia

Se usó un **clon privado fuera del repositorio** en C:\xampp\mi-pesca-backups\brands_integral_v2_20261022, HTTP PHP 127.0.0.1:18688 y BD exclusiva mt_brands_integral_20261022. Nueve tablas creadas con CREATE TABLE LIKE (solo estructura, **ningún dato comercial copiado**), usuarios admin/cliente fixture, categoría, producto y marcas sintéticos. Vendor montado temporalmente por junction. El router del clon se ajustó exclusivamente para la particularidad de dirname('/') en Windows; no se modificó infraestructura global de producción.

**Suite http_qa.js (PASS exit 0, SQL verificado en seis tablas y HTTP real):**
- Rechazo GET anónimo, POST con CSRF erróneo y GET/POST con cliente autenticado sin rol Admin. Ninguna mutación SQL.
- Crear marca con nombre, slug, sort, metadatos básicos, robots, canonical, Open Graph, Twitter, JSON-LD, bullets, specs y FAQ; verificar contenido persistido de columnas y arrays JSON, e historial.
- Editar marca, cambiar slug y generar redirect 301; intentar reutilizar slug histórico de otra marca: rechazo, historial intacto y draft recuperado.
- Subir PNG real por multipart, confirmar ruta aleatoria y bytes en disco; guardar sin cambiar imagen conserva referencia y archivo.
- **Trigger SQL forzado en seo_settings** tras cambiar marca y subir otro archivo: rollback completo (snapshot idéntico de brands, products, seo_settings, seo_slug_history, seo_redirects y brand_seo_blocks); portada anterior intacta; archivo nuevo eliminado; valores del formulario recuperados. Triggers eliminados.
- Reemplazar portada por URL HTTPS, verificar render HTML de Admin, eliminar archivo local anterior solo tras éxito SQL; vaciar URL externa correctamente.
- JSON-LD inválido, especificaciones incompletas y slug duplicado: rechazo sin cambios SQL, recuperación de campos.
- Asociación de producto por brand_id intacta tras edición; endpoint quick Ajax usado por Productos compatible.
- Responsive HTTP autenticado 363/390/768/1024/1600 sin overflow.
- `shared_image_qa.js` PASS: dos marcas referencian una misma imagen administrada; cambiar una no borra el archivo compartido; última referencia liberada permite eliminación, producto asociado permanece unido.
- `seo_read_failure_qa.js` PASS: se renombró temporalmente la tabla brand_seo_blocks SOLO en DB fixture; GET edición falló cerrado y redirigió al listado; tabla restaurada.
- `brands_readonly_smoke.php` ejecutado desde el **clon aislado**, no en DB comercial: búsqueda literal %, _, =, render, paginación, draft y rechazo de subida grande, PASS.

**Límite residual FS:** una caída catastrófica del proceso entre mover un archivo y compensarlo podría dejar un huérfano. SQL es transaccional; el filesystem no comparte COMMIT. El cleanup posterior al éxito es best-effort con log. **Autenticación del ensayo:** sesión Admin fixture, no login real por contraseña; sí se validaron permisos de HTTP y CSRF.

### Dependencia cruzada pública (sin tocar otros módulos)

La vista de Catálogo público `app/Views/pages/catalogo/marcas.php` sigue construyendo las imágenes con public_url(cover_image), que puede prefijar incorrectamente una URL HTTPS absoluta. No se editó Catálogo ni helpers compartidos. **El principal debe asignar esa corrección y verificar catálogo público si se usarán imágenes remotas**. Esto no bloquea staging del Admin Marcas, pero sí la afirmación de cobertura completa de imágenes externas en el catálogo público para producción.

### Lista exacta de 15 archivos propios para integración

1. app/Controllers/Admin/BrandsController.php
2. app/Repositories/Admin/BrandAdminRepository.php
3. app/Services/Admin/Brands/BrandFormDraft.php
4. app/Services/Admin/Brands/BrandSaveService.php
5. app/Services/Admin/Brands/BrandSeoInput.php
6. app/Services/Admin/Brands/BrandCoverUrl.php
7. app/Services/Admin/Brands/ViewModels/BrandsIndexViewModel.php
8. app/Views/pages/admin/brands/index.php
9. app/Views/pages/admin/brands/form.php
10. app/Views/partials/admin/brands/seo_editor.php
11. public_html/assets/css/admin-v3/pages/brands.css
12. public_html/assets/js/admin/brands/form.js
13. scripts/build/brands_admin_contract.php
14. scripts/dev/brands_readonly_smoke.php
15. docs/modulos/marcas.md

Excluir del staging otros módulos, repositorios SEO compartidos, migraciones, Clon privado, fotos/fixtures, credenciales temporales y Catálogo público. No usar git add .; no se hicieron commits, push ni cambios de rama.

**Estado al redactar:** contrato focal y HTTP SQL PASS, gate global final y limpieza de recursos privados pendientes de registrar en adenda inmediata. La revisión estética y el despliegue siguen sujetos a autorización del propietario.


### CIERRE V2 — adenda final de validación y limpieza (2026-10-22)

- **Suite E2E real:** `http_qa.js` completó `BRANDS_INTEGRAL_HTTP_SQL_PASS` (exit code 0). Incluyó 26 señales de progreso y controles de SQL, CSRF, permisos, alta/edición, SEO, imágenes, historial, quick, errores deliberados y responsive. Fallos iniciales del arnés (comparación number/string, base_url en el servidor PHP Windows, rate limit acumulado y fixture incompleto) se corrigieron **solo en el entorno privado**; la última ejecución pasó íntegramente. `shared_image_qa.js`: `SHARED_COVER_NOT_DELETED_OK` y `LAST_REFERENCE_REMOVAL_AND_PRODUCT_RELATION_OK`. `seo_read_failure_qa.js`: `SEO_READ_FAILURE_FAIL_CLOSED_OK` y tabla restaurada.
- **Regresión local segura:** `scripts/build/brands_admin_contract.php` PASS; PHP lint de Controller y tres servicios nuevos PASS; `git diff --check` focal sin errores (aviso informativo de LF/CRLF). El smoke de Marcas se ejecutó **dentro del clon contra fixtures**, sin acceder a filas comerciales. No se editó el gate global.
- **Gate general FINAL (solo una pasada de cierre):** `scripts/build/gate.php`: **PASS, 886 PHP / 120 JS, exit 0, 77,04 segundos**. Incluye contratos del árbol compartido de Categorías, Importaciones y Checkout; no constituye E2E transversal público ni aprobación de producción.
- **Higiene confirmada:** base privada `mt_brands_integral_20261022` eliminada (`TEST_DATABASE_DROPPED`); servidor PHP propio 127.0.0.1:18688 detenido, puerto libre; junction temporal del vendor retirada sin afectar la carpeta original; directorio `stage` con .env de prueba, endpoint de autenticación fixture, uploads y logs eliminado (`STAGE_REMOVED=True`, `PORT_CLOSED=True`, `SOURCE_VENDOR_OK=True`). Los scripts de QA y `final_synthetic_sql.json` —**solo datos artificiales**— permanecen fuera del repositorio bajo `C:\xampp\mi-pesca-backups\brands_integral_v2_20261022`. No hay fixtures permanentes en /public ni datos comerciales modificados.
- **Estado final:** **Marcas Admin técnicamente cerrado y apto para auditoría / staging selectivo del principal**. **Dos límites de alcance:** pendiente la corrección asignable de imagen HTTPS en la vista de Catálogo público, sin tocarla desde Marcas; estética y producción diferidas por el propietario. Sin commits, push, cambios de rama ni ZIP.
