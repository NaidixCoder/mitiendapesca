# Atributos — informe vivo del chat especializado

> **Estado:** **LISTO PARA AUDITORÍA TÉCNICA V2 — integridad P0 implementada, pruebas privadas SQL/HTTP y concurrencia aprobadas; NO APROBADO PARA PRODUCCIÓN.** Aprobación estética postergada por el propietario. Integración selectiva y QA cruzado de ImportService pendientes del chat principal.
> **Última actualización:** 2026-10-22 (cierre técnico integral V2, documentación al final §13). **Responsable:** chat especializado de Atributos.
> **Proyecto / ubicación:** `C:\xampp\htdocs\mi-pesca`. **Git:** sin commits, push, cambios de rama ni worktrees por este chat.
> **Norma:** actualizar **solo este archivo** al completar cada etapa o descubrir/corregir problemas. El chat principal integra y actualiza los documentos generales.

## 1. Alcance y contratos inspeccionados

- Administrador: `/admin/attributes`, `/new`, `/edit?id=…`, `/catalog.json`, `/save`, `/quick`, `/value/save`, `/value/delete` y `/value/delete.json`.
- Plantillas: `/admin/attributes/templates/items-fragment`, `/edit-fragment` (GET y POST), `/save.json`, `/delete.json`. Las plantillas se **crean** desde el editor de Productos/Variantes; en Atributos se **listan, consultan, editan y eliminan**.
- Arquitectura existente: `AttributesController` + traits `AttributesCatalogActions`, `AttributesWriteActions`, `AttributesValueSetActions`; `AttributesSchema`; tres ViewModels de Atributos; servicio compartido `VariantValueSetsService`; servicios/repo de valores, variantes y selecciones de productos.
- Dependencias verificadas: `attributes`, `attribute_values`, `product_attributes`, `product_attribute_values`, `product_variants`, `product_variant_values`, `variant_value_sets`, `variant_value_set_items`, `categories`, `brands`.
- Documentación consultada: `docs/README.md`, `docs/COORDINACION.md`, `docs/ESTADO_DESARROLLO.md`, `docs/PROTOCOLO_GIT_MULTICHAT.md`, `CONTRATO_VISUAL_ADMIN_2026.md`, `docs/ARQUITECTURA_ADMIN_V3.md`, `PLAN_CSS_UNIFICADO.md`, `MANIFEST.md` y referencias históricas en `docs/referencia/catalogo/` y `docs/referencia/variantes/`.
- **Contrato actual del modelo (base local, verificación de solo lectura):** `attributes` contiene `id,name,code,slug,type,is_variant_axis,unit,created_at`; `attribute_values` contiene `id,attribute_id,value,sort,created_at`. Tipos aceptados por guardado: `select`, `text`, `number`. En el guardado administrativo actual `is_variant_axis` se establece a 1. **No existen** `attribute_values.is_active` ni `attributes.purpose` en el esquema observado. La referencia que propone `purpose=variant_option|filter_property` es una **decisión de diseño histórica/propuesta**, no una funcionalidad ya implantada; otras referencias de catálogo también quedaron desfasadas respecto de Tags y el editor real.

## 2. Diagnóstico de problemas, prioridad y estado

| Prioridad | Problema y evidencia concreta | Estado / decisión |
|---|---|---|
| **Alta** | Búsqueda de atributos con `LIKE :q` reutilizado para nombre/slug/código en PDO nativo: riesgo `HY093`. | **CORREGIDO** en consulta de `AttributesCatalogActions::index`: parámetros `:q_name`, `:q_slug`, `:q_code` separados. Smoke contra DB local **OK**. |
| **Alta** | `valueSetEditFragment` leía `$_REQUEST` y permitía crear valores durante una solicitud GET de fragmento. | **CORREGIDA la mutación desde GET**: GET de solo lectura; operaciones de incorporación requieren POST y verificación CSRF. El guardado previo a finalizar la plantilla **sigue siendo una característica del servicio compartido** (pendiente abajo). |
| **Alta** | Eliminación de `attribute_values` seleccionados en productos: el servicio compartido puede borrar filas de `product_attribute_values` cuando el valor no está en variantes/sets. | **MITIGACIÓN SOLO UI:** botón Eliminar inhabilitado si existe **cualquier** referencia a producto, variante o plantilla. **PENDIENTE** revisión de política y protección transaccional en backend compartido; no declarar corregida la integridad global. |
| **Alta** | `VariantValueSetsService::updateValueSet` recibe pares `(attribute_id,value_id)` sin verificar exhaustivamente que el valor pertenece a ese atributo, ni su condición de eje válido. | **PENDIENTE; requiere coordinación** por servicio usado desde Productos/Variantes. |
| **Media-alta** | Las tablas de plantillas no tienen índice UNIQUE que impida repetir `name + category_id + brand_id`; validación de creación no reemplaza restricción ante concurrencia. | **PENDIENTE** migración/criterio de unicidad coordinado. No se alteró la DB. |
| **Media-alta** | Fragmentos de plantilla usaban `render_page`, pudiendo introducir head/layout completo en una respuesta destinada a modal. | **CORREGIDO:** vistas del fragmento incluidas directamente, sin layout; render focal **OK**. |
| **Media** | El editor de valores ofrecía `Activo` y mostraba todos como inactivos, pero ese campo no existe en tabla ni se persistía. | **CORREGIDO en interfaz:** eliminado selector y columna; no se añadió falsa funcionalidad. El ViewModel mantiene compatibilidad de campo histórico, sin mostrarlo. |
| **Media** | Existía el POST de actualización de un valor, pero la vista no tenía controles para editar nombre/orden de valores ya creados. | **CORREGIDO en UI:** controles de edición con los nombres de campo y endpoints preexistentes, sin cambiar persistencia de negocio. **Guardado efectivo no probado con POST real.** |
| **Media** | Dos paneles simultáneos comprimían tablas, con scroll interior y clases heredadas como `max-h-[420px]`. Filtros y acciones se desalineaban en anchos intermedios. | **CORREGIDO visualmente:** paneles verticales de ancho útil, filtros fluidos, acciones claras, tablas/filas adaptativas y estados vacíos. |
| **Media** | Modal con clases utilitarias acumuladas, sin gestión íntegra de foco y con riesgo de contención de overlay. | **CORREGIDO en módulo:** CSS acotado, modal mediante portal a `body`, Escape, foco inicial/restaurado, ciclo de Tab y bloqueo/restauración del scroll. Interacción simulada en Edge **OK**. |
| **Media** | Listado de plantillas se trunca en `LIMIT 120`, sin total real ni páginas. | **PENDIENTE:** se añadió aviso visible al alcanzar límite; paginación SQL requiere intervención sobre `VariantValueSetsService` compartido. |
| **Media** | Slug, código y tipo pueden modificarse en entidades utilizadas por productos; el controlador valida algunas duplicaciones, pero no existe política completa de inmutabilidad/compatibilidad. | **PENDIENTE definición funcional** coordinada con Productos/Variantes; la UI advierte antes de editar. |
| **Media** | `quick()` reutiliza por slug o nombre y podría recuperar una definición existente de distinto tipo sin validar compatibilidad semántica. | **PENDIENTE revisión** del flujo de alta rápida compartido con Productos. |
| **Media** | Nuevos valores agregados en el editor de plantilla se crean al pulsar «Agregar valores», incluso si luego se cancela el diálogo. | **PENDIENTE rediseño transaccional** del servicio compartido; la nueva UI informa explícitamente y pide confirmación antes de esa operación. |
| **Baja** | Etiquetas de tipos del ViewModel sin tildes. | **CORREGIDO:** «Selección» y «Número». |
| **Arquitectura** | Separación histórica entre «Propiedades filtrables» y «Opciones de variante» con un `purpose` obligatorio no coincide con esquema/código actual. | **DECISIÓN PENDIENTE DEL CHAT PRINCIPAL.** No modificar modelos, migraciones, Productos, Tags ni Variantes sin coordinación. |

**Alcance de seguridad:** se preservaron las rutas registradas, CSRF existente de POST, nombres de formulario y hooks `data-role`. No se efectuó una auditoría pentest ni se verificaron todas las restricciones de negocio con escrituras reales.

## 3. Cambios reales implementados

### Lectura y endpoints exclusivos del módulo

- `app/Controllers/Admin/Concerns/AttributesCatalogActions.php`: parámetros PDO distintos en búsqueda, y CSS exclusivo del módulo incorporado por `extraHead` solo en páginas Atributos. **No se editó** el `head.php` compartido.
- `app/Controllers/Admin/Concerns/AttributesValueSetActions.php`: POST+CSRF para incorporación de atributos/valores mediante fragmento, separación de GET de lectura; render de `value_set_items.php` y `value_set_edit.php` como fragmentos sin layout.
- `app/Services/Admin/Attributes/ViewModels/AttributesFormViewModel.php`: traducción de etiquetas (sin alteración del contrato de guardado).

### Vistas, interacción y presentación

- `app/Views/pages/admin/attributes/index.php`: organización vertical, navegación interna a Atributos y Plantillas, shell y Page Header existentes.
- `app/Views/partials/admin/attributes/index/header.php`: encabezado coherente con Page Header Admin V3, resumen de atributos/valores y acción Nuevo atributo.
- `app/Views/partials/admin/attributes/index/attributes_card.php`: filtro y resultados más legibles, código si existe, tipo legible, estados vacíos, acciones y tabla responsive.
- `app/Views/partials/admin/attributes/index/sets_card.php`: filtros de nombre/categoría/marca, conservación del filtro de atributos, tabla responsive, acciones Ver/Editar/Eliminar con `data-role` conservados, aviso de límite de 120 y modal accesible.
- `app/Views/pages/admin/attributes/form.php`: formulario de atributo, vista de valores con indicadores de relaciones, creación y **edición de valor/orden**, eliminación visualmente bloqueada cuando hay uso, remoción de «Activo» no persistente.
- `app/Views/pages/admin/attributes/value_set_items.php`: detalle de plantilla simplificado para modal.
- `app/Views/pages/admin/attributes/value_set_edit.php`: formulario de edición y selección de valores, incorporación de atributos/valores, aviso explícito sobre persistencia anticipada.
- `public_html/assets/js/admin/attributes/index.js`: reescritura de interacción focal, estados y errores de carga, actualización de fila por `data-set-field`, confirmaciones y accesibilidad del diálogo; conserva endpoints, CSRF y contratos de evento.
- `public_html/assets/css/admin-v3/pages/attributes.css` (**NUEVO**): única hoja del módulo, sin Tailwind ni archivo paralelo de overrides.

### Cobertura automatizada añadida

- `scripts/build/attributes_admin_contract.php` (**NUEVO**): contrato focal de HTML/JS/CSS/endpoints y ausencia de campos/clases legacy determinados.
- `scripts/dev/attributes_readonly_smoke.php` (**NUEVO**): consultas contra DB local **solo lectura**, búsqueda SQL, render del listado/formulario/fragmento de edición y lectura cruzada con `VariantReadService`.

**No se modificaron:** `config/routes.php`, `app/Views/layouts/admin.php`, `app/Views/partials/head.php`, `public_html/assets/js/app.js`, `scripts/build/gate.php`, tablas/migraciones ni servicios/repositorios compartidos de Productos/Variantes. Los contratos focales se ejecutan por separado; **todavía no están integrados en el build gate global** por tratarse de un archivo compartido que debe coordinar el chat principal.

## 4. Admin V3, CSS y responsive

- Identidad aplicada por tokens/fallbacks oficiales: primario `#006B6B`, fondo `#F6F8FA`, superficie `#FFFFFF`, tinta `#182B39`, secundario `#647786`, borde `#DFE5E9`.
- Namespace visual `av3-attributes*` bajo `body[data-page="admin-attributes"]`, con botones reutilizados `av3-ui-btn`. Fuente Segoe UI/Inter, texto operativo 14 px, etiquetas 13 px, controles alrededor de 40 px, superficies de 11 px y estados de foco visibles.
- Se conserva sidebar, layout maestro, Page Header y espaciado global del shell. No se añadieron CSS legacy, Tailwind, overrides generales ni modificaciones a `/public`.
- Atributos y Plantillas son tarjetas **apiladas**, no grillas de dos columnas forzadas. La tabla se transforma en filas legibles cuando el contenedor no permite columnas; se eliminó scroll vertical interno. Los formularios se reorganizan según ancho.
- **Mobile-first estructural implantado 2026-10-21:** base a 363 px con tarjetas y formularios de una columna, tablas presentadas como filas y filtros apilados; se amplía mediante `@container (min-width: 511px)` y `@container (min-width: 761px)`, y `@media (min-width: 768px)` para el shell/espaciado. Se mantuvieron excepciones semánticas para `prefers-reduced-motion`. Se reutilizan `av3-ui-card`, `av3-ui-control`, `av3-ui-field`, `av3-ui-field-label`, `av3-ui-table`, `av3-ui-badge`, `av3-ui-actions` y botones DS; excepciones de color/tipografía explicadas en sección 8.
- Modal: overlay fijo fuera del contenedor con `container-type:inline-size`, `[hidden]`, scrolling interno del diálogo, focus trap y restauración de foco.
- **Punto visual pendiente:** el resultado es técnicamente verificable mediante navegador headless y capturas sintéticas; no hay todavía aprobación visual por recorrido autenticado con datos y acciones reales.

## 5. Pruebas y evidencia (sin modificación destructiva)

| Verificación | Resultado / alcance |
|---|---|
| `C:\xampp\php\php.exe scripts/build/attributes_admin_contract.php` | **OK**: hook AJAX, formulario, fragmentos, estilos scoped y responsive. Reejecutado 2026-10-20. |
| `C:\xampp\php\php.exe scripts/dev/attributes_readonly_smoke.php` | **OK**: lectura real local: **4 atributos, 7 valores, 1 plantilla**; búsqueda preparada PDO y render del módulo/fragmento. |
| `VariantReadService::getData` en smoke readonly | **OK**: producto local id 1 leído con **6 variantes y 1 eje**; no demuestra persistencia/regresión transaccional de guardados. |
| `C:\xampp\php\php.exe scripts/build/gate.php` | **OK**, tras implementación: **824 archivos PHP, 119 JavaScript**, contratos y regresiones globales. El nuevo contrato Atributos no está agregado al gate. |
| PHP `-l`, Node `--check`, analizador CSS `tinycss2` | **OK**: PHP/JS focal sin errores de sintaxis; CSS del módulo con **92 reglas, 0 errores de parseo** en la comprobación efectuada. |
| CSS servido desde XAMPP | **HTTP 200** para `attributes.css`. |
| Edge headless, HTML renderizado por PHP con datos locales | **363, 390, 768, 1024 y 1600 px comprobados** en listado y formulario; `documentElement.scrollWidth` igual a viewport (sin overflow horizontal); estilos cargados y comportamiento de tabla/fila según espacio. |
| Edge headless, modal con solicitudes interceptadas | **390 y 1600 px OK**: Ver, Escape, restauración del foco y scroll, Editar, visualización del formulario, Cancelar, sin errores JavaScript; **no se enviaron POST destructivos**. |
| Auditoría readonly de esquema/índices MySQL | Verificados columnas y ausencia de `purpose`/`is_active`, unicidad por `attributes.name`, `attributes.code`, `attributes.slug`, `attribute_values(attribute_id,value)` e ítems `(set_id,attribute_id,value_id)`; sin UNIQUE equivalente para combinación de plantilla. |

**Evidencia privada fuera del proyecto:** `C:\xampp\mi-pesca-backups\attributes_v3_20261020\visual` contiene HTML sintético, capturas `index_363/390/768/1024/1600.png`, `form_363/390/768/1024/1600.png`, `modal_390.png`, `modal_1600.png` y pruebas auxiliares de navegador. Respaldo de **11 archivos iniciales** en `C:\xampp\mi-pesca-backups\attributes_v3_20261020`. Las capturas **no son una sesión administrativa real autenticada**.

## 6. Riesgos y pendientes priorizados para coordinación

1. **P0 — Integridad de valores y selecciones:** acordar con el chat principal y el responsable de Productos/Variantes si la eliminación debe bloquearse en backend siempre que exista cualquier relación (y cómo resolver carreras concurrentes). Hoy la protección es **solo de UI**; el servicio compartido conserva lógica que puede quitar selecciones de productos.
2. **P0 — Validación de plantillas:** validar existencia y pertenencia de cada par `attribute_id/value_id`, compatibilidad del eje, categoría y marca antes de guardar/importar; evaluar validaciones transaccionales/DB. Requiere coordinación de `VariantValueSetsService`.
3. **P1 — Persistencia al agregar valores:** decidir si una plantilla cancelada debe revertir altas provisionales o emplear una acción explícita de guardado independiente. Hoy se informa y confirma al usuario.
4. **P1 — Unicidad y paginación:** esquema/índice UNIQUE por plantilla y listado con `COUNT` + offset/limit para superar 120 resultados sin truncar silenciosamente.
5. **P1 — Política de atributo:** definir `purpose` vs `is_variant_axis`/Tags; compatibilidad de `quick()`, cambios de slug, código y tipo sobre productos existentes.
6. **P1 — Pruebas integradas en entorno seguro:** usar DB de homologación/fixture descartable y sesión autenticada para probar altas, edición, baja segura, CSRF, duplicados, importación a Productos, relectura/generación de Variantes y regresión de combinaciones. **No realizar estas escrituras contra DB real**.
7. **P2 — CSS mobile-first estricto:** si el chat principal lo considera requisito de implementación y no solo resultado UX, convertir reglas colapsantes en base móvil con `min-width` y repetir las cinco capturas.
8. **P2 — Contratos CI:** solicitar al chat principal una ventana coordinada para incluir `scripts/build/attributes_admin_contract.php` en `scripts/build/gate.php` y actualizar `docs/ESTADO_DESARROLLO.md`/`MANIFEST.md`.

## 7. Entrega al chat principal

**Disponible para revisión e integración del rediseño**, con pruebas técnicas y de visualización headless **aprobadas**. No se afirma cierre funcional ni pruebas destructivas. Ninguna operación Git fue realizada por este chat; no hubo cambio de base de datos y no se tocaron archivos de coordinación generales ni informes de otros módulos. La coordinación necesaria se limita a **servicios compartidos, política de integridad y registro del contrato en el gate**.

**Próxima actualización obligatoria:** registrar aquí cualquier cambio autorizado, prueba adicional, revisión visual autenticada, nueva falla o resolución de un pendiente; actualizar el estado de cada hallazgo sin atribuir correcciones no realizadas.


## 8. Segunda auditoría: integridad de datos — 2026-10-21

**Guía aplicada:** `docs/GUIA_VISUAL_ADMIN_V3.md` **v2.0 (2026-10-21)**. Trabajo restringido a servicios compartidos **solo lectura y propuesta**, CSS/vistas propias de Atributos y pruebas aisladas. En esta etapa **no se tocó la DB comercial**, no se hicieron commits/push y **no se modificaron los servicios ni el repositorio compartido**.

### 8.1 Mapa exacto de borrados y pérdidas potenciales

| Ruta/método | Operación concreta | Efecto sobre producto y estado |
|---|---|---|
| `POST /admin/attributes/value/delete` y `/value/delete.json` → `AttributesWriteActions::deleteAttributeValueResult` → `VariantAttributesService::deleteAttributeValue` → `VariantAttributeValueDeleteService::deleteAttributeValue` | `countProductSelections`, `countVariantUsage`, `countSetUsage`. **Solo bloquea** si existen variantes o sets. Si solo existe referencia en producto, ejecuta `VariantAttributesRepository::deleteProductSelections(valueId)` = `DELETE FROM product_attribute_values WHERE value_id=?`; luego `deleteSetItems(valueId)` y `DELETE FROM attribute_values WHERE id=? AND attribute_id=?`. | **P0: pérdida efectiva de selección de uno o más productos** y eliminación del valor maestro. La protección visual del botón NO cubre POST directo u otro consumidor del servicio. |
| `POST /admin/products/variants/values/set` → `VariantAttributeActions::valuesSet` (modo `value_id`, `selected=0`, `prune_unused=1`) → `VariantAttributesService::pruneAttributeValueIfUnused` → `VariantAttributeValueDeleteService::pruneAttributeValueIfUnused` | Primero se deselecciona el valor del producto mediante `setAttributeValues`, que reemplaza las filas del atributo en `product_attribute_values`; después se comprueban referencias `product_attribute_values`, `product_variant_values`, `variant_value_set_items`. Si quedan a 0, `DELETE FROM attribute_values WHERE id=? LIMIT 1`. | La deselección del producto es **acción explícita del flujo de Productos**; el prune intenta borrar solo el valor global si quedó libre. **Riesgo:** captura `Throwable` y continúa cuando falla cualquiera de las consultas de referencias; no distingue tabla inexistente de error operativo (fail-open). Otra transacción podría asociar el valor entre check y delete si no hay locking/FK adecuado. |
| `POST /admin/products/variants/attributes/remove` → `VariantAttributesService::removeAttribute` | Transacción que ejecuta `DELETE FROM product_variant_values` de ese atributo, `DELETE FROM product_attribute_values WHERE product_id=? AND attribute_id=?` y `DELETE FROM product_attributes`. Si deja 0 ejes, puede limpiar otras selecciones del producto, variantes y deduplicar/pausar variantes según pedidos. | **Borrado intencional de asociación de un producto** al quitar un eje, distinto de borrar un valor maestro. **No se modificará en esta autorización**; debe conservar preview/semántica de Productos y verificarse en regresión. |
| `VariantAttributesService::setAttributeValues` desde edición de producto | `DELETE FROM product_attribute_values WHERE product_id=? AND attribute_id=?` y reinserción de IDs filtrados por atributo, con transacción o savepoint. | **Reemplazo intencional** al modificar la selección. Un `value_id` inválido puede omitirse al normalizar; revisar avisos UX en un ticket aparte. No confundir con eliminación administrativa inadvertida. |
| `VariantAttributesSelectionTrait::clearSelectionIfNoVariants` / `syncSelectionToExistingVariants` | Pueden borrar selecciones cuando no quedan variantes o al sincronizar contra las combinaciones actuales. | Dependencias compartidas: **inspección parcial de flujo**, fuera de alcance de edición. No están llamadas desde `VariantAttributeValueDeleteService`. |
| `VariantValueSetsService::updateValueSet` | `UPDATE variant_value_sets`, luego `DELETE FROM variant_value_set_items WHERE set_id=?`, luego insertar pares. | **No ejecuta DELETE directo de `product_attribute_values`**; puede introducir pares incorrectos que después se importen en Productos. |
| `VariantValueSetsService::deleteValueSet` | Borra ítems de plantilla y su fila maestra en una transacción. | No borra asociaciones de productos; eventual impacto sobre futura reutilización de plantilla. |

**Conclusión técnica del caso P0:** la ruta peligrosa es `deleteAttributeValue` ante una selección exclusiva de producto. Incluso sin variantes o sets, **producto referenciado ≠ valor libre**. `deleteProductSelections` no debe ejecutarse como efecto colateral de borrar un valor maestro. `countSetUsage` y `deleteSetItems` en el repo ocultan errores de consulta; requieren fallo cerrado o control explícito de compatibilidad de esquema.

### 8.2 Propuesta de protección del backend (NO implementada)

**Archivos compartidos sujetos a autorización exclusiva:**
- `app/Services/Admin/Variants/VariantAttributeValueDeleteService.php` — owner transaccional de borrado/prune.
- `app/Repositories/Catalog/VariantAttributesRepository.php` — consultas de usos y mutaciones. **Solicitar incluir en la misma reserva**, de ser necesario; no editar hasta aprobación.
- `app/Services/Admin/Variants/VariantValueSetsService.php` — owner del guardado y reutilización de sets.
- Eventualmente migración/FK sobre `attribute_values` y sus asociaciones, y `app/Controllers/Admin/Concerns/AttributesWriteActions.php` si hay códigos HTTP nuevos; **coordinar por separado**.

**Contrato de `deleteAttributeValue`:**

1. Validar `valueId` positivo; distinguir `NOT_FOUND` y `MISMATCH`; comprobar relación `value_id → attribute_id` **sin mutar**.
2. Iniciar una única transacción del owner; si el caller inició una, emplear **SAVEPOINT** con rollback local (no hacer commit de transacción ajena).
3. Bloquear fila `attribute_values` con `SELECT … FOR UPDATE` en MySQL/InnoDB; acordar orden de locks con writers de asociaciones para evitar deadlocks. **Lock del padre solo no es protección suficiente si inserciones concurrentes no coordinan/FK**.
4. Consultar con parámetros y control de errores las 3 relaciones: `product_attribute_values`, `product_variant_values` y `variant_value_set_items`. Si falta una tabla por versión de esquema, validar explícitamente versión de instalación/migración; ante fallo inesperado de consulta, **rollback y 500**, nunca tratarlo como «cero usos».
5. Si cualquiera tiene al menos una referencia, **NO ejecutar DELETE de ninguna tabla** y responder `409 VALUE_IN_USE` (con conteos `selected_in_products`, `used_in_variants`, `used_in_sets` para cliente). Conserva semántica de `valueDeleteJson` y de flashes.
6. Solo si **todas** son cero, ejecutar `DELETE FROM attribute_values WHERE id=? AND attribute_id=?`, comprobar `rowCount===1` y commit. Eliminar la rama `deleteProductSelections` y la limpieza redundante `deleteSetItems` del flujo de borrado maestro.
7. `pruneAttributeValueIfUnused` comparte **exactamente el mismo guard transaccional/fail-closed**; no mantener dos decisiones de seguridad distintas. No borrar del producto dentro del guard; la deselección explícita en Productos conserva su propio flujo.
8. Defensa DB: revisar `FOREIGN KEY ... ON DELETE RESTRICT` (con índices en `value_id` y compatibilidad de motores); migrar solo bajo autorización y con auditoría de datos preexistentes. Si el motor es MySQL, confirmar transaccionalidad/InnoDB. Antes de insertar nuevas asociaciones, escritores deben usar el mismo orden de locks; capturar error FK/concurrencia como conflicto recuperable, nunca como éxito.
9. Auditoría: registrar intentos bloqueados con `value_id`, `attribute_id` y usos; no exponer SQL en JSON.

**Contrato de `updateValueSet`:**

1. En la misma transacción, comprobar que la **plantilla existe** (`SELECT … FOR UPDATE`); `setId>0`; nombre no vacío y ≤120 caracteres; categoría y marca positivas, existentes y elegibles. Definir compatibilidad con `brand_id NULL` histórico; la edición actual exige marca, pero `importValueSet()` admite un set legacy sin marca.
2. Verificar unicidad `(category_id,brand_id,name)` contra otro `id`, e idealmente índice UNIQUE con plan de deduplicación previo a migración. Rechazar colisiones con `409`, no borrar primero sus ítems.
3. Exigir al menos un par y límite máximo razonable (para evitar POST voluminosos), IDs estrictamente enteros positivos, sin duplicados, sin pairs parciales; **no silenciar/omitir pares inválidos**.
4. **Validación SQL de pertenencia:** para **cada** par `(attribute_id,value_id)`, `JOIN attribute_values av ON av.id=:value_id AND av.attribute_id=:attribute_id` + `JOIN attributes a ON a.id=av.attribute_id AND COALESCE(a.is_variant_axis,0)=1`. Rechazar cualquier par cruzado, inexistente o sin eje, incluso si la FK individual de `value_id` es válida.
5. Verificar que categoría/brand elegidas sean válidas y la plantilla corresponda al nuevo contexto. **Regla de negocio propuesta:** permitir cambiar contexto al editar si ambos IDs son reales, con confirmación UX por impacto de reutilización; evaluar si deben restringirse cambios cuando hay productos que aún usan la plantilla (hoy la importación copia valores, no vínculo vivo).
6. Solo después de validar **todos** los datos, actualizar master, reemplazar ítems y commit. Ante error, rollback completo, preservar nombre, contexto e ítems anteriores. Documentar códigos `INVALID_PAIR`, `WRONG_ATTRIBUTE`, `NOT_VARIANT_AXIS`, `CATEGORY_NOT_FOUND`, `BRAND_NOT_FOUND`, `DUPLICATE_SET`, `NOT_FOUND`; transformar a mensajes públicos sin filtrar detalles SQL.
7. En `importValueSet`, revisar adicionalmente que los ítems corresponden al atributo y pertenecen al eje, que el producto tenga categoría/marca requeridas (hoy si `categoryId==0` o `brandId==0` se omiten comparaciones), y que la operación no inyecte asociaciones inconsistentes. Es **ticket compartido separado**, no implementado.
8. Rendimiento: prefetchear pares en una consulta con placeholders y validar que la cantidad de parejas retornada coincida, en vez de N consultas en el camino productivo; índices compuestos según consulta real.

**No autoriza cambios automáticos**: la implementación requiere un único responsable coordinado. Confirmar con el principal el criterio de `NULL` en marcas, política de cambios de contexto, FK existentes, race testing real de MySQL, validaciones del import y tickets de Productos.

### 8.3 Demostración en DB aislada — resultados verificados

Script de prueba propio: `scripts/dev/attributes_integrity_isolated.php` (**NUEVO**). Comando:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\mi-pesca\scripts\dev\attributes_integrity_isolated.php
```

- **Aislamiento obligatorio:** crea solamente `new PDO('sqlite::memory:')`. No carga `config/boot/*`, credenciales, `.env` ni llama a `db()`. Tablas sintéticas con FK y unicidades representativas. Permite ejecutar **los dos servicios reales sin modificarlos** mediante un adaptador en memoria para `SHOW TABLES LIKE` y `NOW()` de MySQL.
- Incluye prototipos `guardedDelete` y `guardedSet` **dentro del script de pruebas**, sin efectos en los endpoints. Cada caso usa su propia base y compara snapshots completos de `attribute_values`, `product_attribute_values`, `product_variant_values`, `variant_value_sets`, `variant_value_set_items`.
- **18/18 escenarios OK**: reproduce pérdida actual de selección product-only; confirma bloqueo protegido para producto, variante y plantilla; permite borrado libre; prune existente bloquea si hay producto; reproduce set cruzado y set de atributo no eje aceptados por servicio actual; comprueba rollback de set con duplicado UNIQUE; valida 8 rechazos transaccionales del guard (cruzado/no eje/categoría/marca inexistente/set inexistente/pares duplicados/plantilla duplicada/vacío) con snapshots inmutables; demuestra actualización válida sin alterar selecciones de productos.
- **Interpretación correcta:** los casos «BUG ACTUAL reproducido» pasan porque **demuestran explícitamente el comportamiento inseguro**; no significan que el servicio esté corregido. `guarded*` muestra un **contrato propuesto**, no su integración productiva.
- **Límites:** SQLite no reproduce `SELECT FOR UPDATE`, deadlocks, concurrencia InnoDB, triggers y DDL exacto de MySQL. Las FK sintéticas no demuestran equivalencia total con las FK de instalación comercial. **Pendientes tests en MySQL aislado con dos conexiones**, rollback, carrera consulta/eliminación y escrituras/autenticación tras autorización.

## 9. Refactor visual mobile-first v2.0 (2026-10-21)

**Archivos propios realmente modificados en esta segunda etapa:**

- `public_html/assets/css/admin-v3/pages/attributes.css` — **reescritura estructural**, sin segundo CSS ni overrides apilados. La base ahora es móvil: formularios/filtros 1 columna, filas para listados, botones apilables y diálogos fluidos.
- `app/Views/pages/admin/attributes/form.php`, `app/Views/pages/admin/attributes/value_set_edit.php`, `app/Views/partials/admin/attributes/index/attributes_card.php`, `app/Views/partials/admin/attributes/index/sets_card.php` — componentes **DS existentes** añadidos conservando selectores locales de composición y hooks AJAX.
- `scripts/build/attributes_admin_contract.php` — el contrato focal comprueba ahora breakpoints `min-width`, no el `max-width` histórico.
- `scripts/dev/attributes_integrity_isolated.php` — nuevo archivo de pruebas exclusivas del módulo (no incluido todavía en gate general).
- `docs/modulos/atributos.md` — esta bitácora, mantenida por su chat especializado.

**Reutilización real de DS:** `av3-ui-card` (tarjetas), `av3-ui-card-header` (cabeceras), `av3-ui-control` (inputs, selects), `av3-ui-field` + `av3-ui-field-label` (campos), `av3-ui-table` (tabla desktop), `av3-ui-badge` (conteo/tipo), `av3-ui-actions` (grupo acciones), más `av3-ui-btn` y variantes preexistentes.

**Duplicaciones locales eliminadas:** base completa de controles (border, fondo, radio, padding y foco), base de tarjetas/superficies, tablas generales, estilo cromático de badges y flex básico de grupos de acciones; también se eliminaron seis alias `--attr-*` que duplicaban los tokens oficiales. **Excepciones justificadas**: clase local para composición/queries, control y tipos a 14/12 px frente a tamaño heredado 13/11 px del DS, borde de tarjeta sincronizado con `--av3-color-border`, encabezado de tabla sin mayúsculas decorativas, patrón semántico de tabla→filas en móvil, y modal propio con altura/foco que no ofrece el DS actual. Las clases `av3-attributes__input`, `__panel`, etc. siguen en DOM como selectores de variantes de layout; **no replican el DS base**.

**Arquitectura CSS:** base móvil, `@container (min-width: 511px)` para 2 columnas parciales, `@container (min-width: 761px)` para tabla/filtros desktop según ancho real del panel, y `@media (min-width: 768px)` para espaciados y composición del viewport; sin `@container(max-width)` ni `@media(max-width)` correctivos. Se mantiene la semántica nativa de tabla por encima del breakpoint, con restauración explícita de `thead`, `tbody`, `tr` y `td`, sus bordes y pseudoetiquetas.

**Pruebas de esta etapa:** `attributes_admin_contract.php` y `attributes_readonly_smoke.php` aprobados. Edge headless con HTML renderizado desde datos locales y recursos HTTP propios en **363, 390, 768, 1024 y 1600 px**: sin scroll lateral accidental, tabla convertida en filas debajo de 761 px de ancho del panel; restituida tabla desktop. En modal: **390 y 1600 px**, carga de Ver/Editar mediante GET simulados, Escape, retorno del foco, Cancelar y scroll bloqueado/restaurado, sin errores JS ni llamadas POST comerciales. La prueba se ejecutó con interceptación HTTP y **no equivale a sesión autenticada real**. Capturas guardadas fuera del repo en `C:\xampp\mi-pesca-backups\attributes_v3_20261020\visual`.

**Revalidación completada 2026-10-21:** build gate integral **OK** (826 PHP, 119 JS), filtros desktop **alineados en una fila** a 1024/1600, y Edge headless con prueba de modal **en los cinco anchos**: foco inicial en nombre, ciclo Tab del último al primer control, Escape, restitución del foco y scroll; sin overflow ni errores JS. **Pendiente:** sesión autenticada, escrituras en DB descartable, textos extremos/errores reales y revisión visual con propietario. No declarar terminado solo por static-contract + headless.

## 10. Solicitud EXPLÍCITA de autorización exclusiva al chat principal

**BLOQUEO P0:** los servicios compartidos siguen sin cambios. Solicito que el chat principal confirme por escrito y reserve temporalmente, para **un único chat responsable**, los siguientes archivos antes de implementar la corrección:

1. `app/Services/Admin/Variants/VariantAttributeValueDeleteService.php`
2. `app/Services/Admin/Variants/VariantValueSetsService.php`
3. `app/Repositories/Catalog/VariantAttributesRepository.php` (si requiere mover las consultas al repositorio y eliminar borrado implícito).
4. Si procede: `VariantAttributesService.php`, `VariantAttributeActions.php`, `AttributesWriteActions.php`, migraciones/FK e importación de sets; requieren autorización **individualizada** por ser compartidos.

**Se pide decisión** sobre bloqueo absoluto cuando exista selección a nivel producto, modo fail-closed, transacción/locks InnoDB, política de marca `NULL` y cambios de categoría/marca. Con autorización, aplicar en un solo owner, correr prueba aislada + DB descartable MySQL, validar la integración con Productos/Variantes y recién entonces pasar a pruebas autenticadas. Mientras tanto, **no tocar esos servicios** ni efectuar migraciones comerciales.

**Estado de cierre de esta fase:** auditoría de integridad y prototipo aislado **listos para revisión** (18/18 casos), UI mobile-first **implementada** con pruebas focales, gate integral y visuales headless cinco anchos **OK**; **bloqueo de implementación backend** hasta autorización exclusiva. Mantener este informe vivo con cada resultado posterior.


## 11. RECHAZO VISUAL Y NUEVA PROPUESTA — 2026-10-21 (SIN IMPLEMENTAR)

### 11.1 Decisión vinculante

**El chat principal NO aprobó el rediseño visual de Atributos**. Sus pruebas técnicas, CSS responsive y tests de integridad no son criterios suficientes para aprobar la UX. **La interfaz actual queda pendiente de reemplazo**, sin autorización para modificar vistas, CSS ni JS hasta que el propietario apruebe la nueva propuesta. Este dictamen **supersede** referencias anteriores de esta bitácora a «visual aprobado», «listo para integrar» o «refactor completo», sin invalidar las pruebas de sintaxis/comportamiento ya registradas.

Los servicios compartidos `VariantAttributeValueDeleteService`, `VariantValueSetsService` y `VariantAttributesRepository` permanecen en **solo lectura** bajo el bloqueo P0 anterior.

### 11.2 Evidencia de comparación visual: Dashboard + Pedidos + Atributos

Se leyó `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0; se inspeccionaron vistas/CSS efectivos `app/Views/pages/admin/dashboard/index.php`, `app/Views/pages/admin/orders/index.php`, `app/Views/pages/admin/attributes/index.php`, parciales de Atributos, `public_html/assets/css/admin-v3/pages/dashboard.css`, `orders.css`, `attributes.css`, y `components/design-system.css`.

En Edge headless se intentó acceder por HTTP GET a `/admin`, `/admin/orders`, `/admin/attributes`; **las tres URLs redirigieron a login** en una sesión nueva. Por lo tanto se generaron **renderizados completos con las vistas y hojas de estilo reales**, en un directorio **fuera del proyecto**, para compararlos en Edge a 1600 y 390 px. Pedidos usó consultas de solo lectura y 14 registros existentes en la DB local; Dashboard usó datos de presentación de muestra (sin invocar acciones de escritura), y Atributos usó los 4 atributos y 1 plantilla de DB local. **No afirmar recorrido autenticado del administrador**. El mock de Dashboard puede mostrar el gráfico de tendencias sin datos reales por limitación del entorno de prueba.

Evidencia privada, no parte del repositorio: `C:\xampp\mi-pesca-backups\attributes_v3_20261020\visual\compare_{dashboard,orders,attributes}_{1600,390}.png`. Script visual auxiliar fuera del proyecto: `render_admin_refs.php`, `compare_admin_ref.cjs`.

**Patrones internos observados en Dashboard:**
- Page Header compacto y propio del shell; KPI en grilla corta, sin bloques redundantes.
- Tarjetas de borde suave con encabezados concisos, separador sutil y contenido de alta densidad útil.
- En móvil, listas operativas compactas; el identificador y el dato principal aparecen arriba, metadatos secundarios más abajo, acciones simples.

**Patrones internos observados en Pedidos (referencia primaria para Atributos):**
- **Un único panel operativo** (`.av3-orders-board`) desde debajo del Page Header.
- Pestañas horizontales **verdaderas por filtro de vista** (enlaces a `?view=…` que modifican el contenido), subrayado activo, sin pills independientes flotando por encima de tarjetas.
- Zona de búsqueda/filtros **blanca, contenida en el panel**, con controles de 40 px, sin una segunda tarjeta gris.
- Separador, tabla densa con `thead` suave, celdas 12–14 px de padding, acciones ligeras y footer contextual/paginación.
- Móvil: **lista específicamente renderizada**, sin repetir etiquetas «Pedido», «Fecha», «Estado» junto a cada campo; la información operativa es reconocible por jerarquía.

**Problemas observables específicos de Atributos y evidencia de código:**

| Prioridad visual | Hallazgo | Evidencia |
|---|---|---|
| P0 | **Dos paneles** visibles simultáneamente, sin contexto de tarea. | `index.php` incluye `attributes_card.php` y `sets_card.php` siempre; uso `av3-ui-card` en ambos. |
| P0 | **Pseudopestañas** sin selección persistente ni panel asociado. | `av3-attributes__jump` contiene enlaces `href="#atributos-listado"` / `#plantillas-listado`: solo desplazan el scroll. |
| P0 | **Pérdida de jerarquía**: título Atributos en Page Header y dentro de la primera tarjeta; resumen «4 atributos» y «7 valores» suelto; pills «4 resultados» y «1 mostradas». | `header.php`, `attributes_card.php`, `sets_card.php`. |
| P1 | **Exceso de contenedores** y aire no operativo: fondo de filtros gris + panel blanco + encabezado descriptivo + barra de resumen. | `av3-attributes__summary`, `__panel-head`, `__filters`. |
| P1 | **Tablas con jerarquía visual distinta** de Pedidos: badges verdes para «Tipo», encabezados y rellenos que no priorizan las operaciones. | `attributes.css` + `av3-ui-badge` para Selección/Texto. Tipo no es un estado semántico. |
| P1 | **Tres botones por fila** en Plantillas, pese a ser una acción principal Ver y dos secundarias. | `sets_card.php` botones Ver, Editar, Eliminar. |
| P1 | **Móvil demasiado alto**: cada atributo usa título, etiquetas redundantes «Atributo», «Slug», «Tipo», y una fila separada para «Editar»; ambas secciones completas requieren mucho desplazamiento. | Captura `compare_attributes_390.png` frente a `compare_orders_390.png`. |
| P1 | **Filtros móviles de Plantillas siempre abiertos** y muy altos; falta patrón de «Filtros avanzados»/resumen de filtros activos. | `sets_card.php` + CSS mobile. |
| P2 | Asimetría: el buscador de Atributos ocupa una fila completa y el de Plantillas usa 3 selectores + botón; la alineación interna y los márgenes no siguen exactamente el ritmo de Pedidos. | `av3-attributes__filters--attributes` y `--sets`. |
| P2 | Color/medidas del DS heredado (`av3-ui-*`) conviven con reglas compositivas locales; pasar por el DS no garantiza por sí solo igual apariencia a Pedidos. | `design-system.css` versus `orders.css` (Pedidos usa patrones propios). Reutilizar DS, **no copiar CSS de Orders**. |

### 11.3 Propuesta estructural aprobable: desktop (>= ~1024 px de viewport, según ancho útil)

**Sigue existiendo UN SOLO Page Header del shell:** título «Atributos», breadcrumb y una única acción contextual «Nuevo atributo» **solo cuando la pestaña Atributos está activa**. En Plantillas, no ofrecer «Nueva plantilla» inexistente: mostrar como acción secundaria contextual un enlace «Ir a Productos», pues las plantillas se originan allí. No duplicar el título «Atributos» en el cuerpo ni añadir KPIs globales decorativos.

**ÚNICO panel operativo `av3-ui-card`, sin tarjeta dentro de tarjeta:**

```text
[Page Header del shell: Atributos]                 [Nuevo atributo] (*)
┌────────────────────────────────────────────────────────────────┐
│ [Atributos __________] [Plantillas de variantes __________]     │ <- tabs reales, subrayado activo
├────────────────────────────────────────────────────────────────┤
│ [Buscar por nombre, código o slug                     ] [Buscar]│ <- sin contenedor gris
├────────────────────────────────────────────────────────────────┤
│ ATRIBUTO              TIPO           SLUG              ACCIÓN   │
│ Color                 Selección      color             Editar › │
│ Modalidad  · modalidad Selección     modalidad         Editar › │
│ Peje                  Selección      peje              Editar › │
├────────────────────────────────────────────────────────────────┤
│ 4 atributos mostrados (texto discreto; no KPI ni contador pill)│
└────────────────────────────────────────────────────────────────┘
(*) Acción «Nuevo atributo» visible solo en pestaña Atributos.
```

**Al activar Plantillas se reemplaza TODA la sección filtrable/listado, no se añade debajo:**

```text
[Page Header del shell: Atributos]             [Ir a Productos] (secundario)
┌────────────────────────────────────────────────────────────────┐
│ [Atributos __________] [Plantillas de variantes __________]     │
├────────────────────────────────────────────────────────────────┤
│ [Buscar plantilla...] [Categoría ▾] [Marca ▾] [Filtrar]       │
│ Filtros activos: [Categoría: ... ×] (solo si existen)           │
├────────────────────────────────────────────────────────────────┤
│ PLANTILLA           CATEGORÍA     MARCA     VALORES   ACCIONES │
│ COLORES GOZIO       Señuelos ...  Gozio       6      Ver  ··· │
├────────────────────────────────────────────────────────────────┤
│ Mostrar límite de 120 únicamente cuando corresponda.         │
└────────────────────────────────────────────────────────────────┘
```

- Pestañas integradas al borde superior de la tarjeta con `border-bottom` activo verde petróleo, textos 13–14 px, sin botones de píldora ni un segundo encabezado de sección.
- Contenido debajo del tablist: filtros compactos, tabla o vacío y pie discreto. **Una sola sección visible**. Se mantiene la búsqueda por atributos y, separadamente, `sets_q` + categoría + marca en plantillas.
- Filtros de Plantillas alineados en desktop: búsqueda flexible, dos selectores, botón final. Evitar background gris anidado y títulos de filtro demasiado altos. `Limpiar` solo al estar filtrado; filtros activos como chips livianos si aportan información.
- Tabla Atributos: columna nombre fuerte (código como ayuda cuando exista), `Tipo` neutro como texto (no verde de estado), `Slug` secundario, acción Editar discreta con enlace accesible. **No agregar columna de conteos** sin primero justificarla y revisar consulta; se evita inventar datos.
- Tabla Plantillas: nombre principal, categoría y marca legibles, cantidad de valores numérica alineada; «Ver» como apertura primaria y menú contextual «···» para Editar/Eliminar. Mantener hooks `data-role="set-view|set-edit|set-delete"`, confirmación de baja, permisos, CSRF y modal existentes. El menú debe poder manejarse por teclado/touch (no solo hover).
- Densidad de tabla comparable a Pedidos: fondo `thead` suave, 12–14 px de celda, línea divisoria sutil, altura de fila compacta (objetivo visual ~48–56 px cuando texto sea corto), hover tenue y vacíos integrados. Sin igualar alturas artificialmente.
- Las opciones de tablist y tabla deben emplear tokens y componentes `av3-ui-*` existentes; el CSS exclusivo de Atributos solo define composición, cambio de panel y casos móviles propios.

### 11.4 Propuesta mobile-first: 363–390 px

```text
[☰] Atributos
     Inicio › Catálogo › Atributos
[Nuevo atributo]   (contextual; mismo Page Header)
┌──────────────────────────────────┐
│ Atributos  │ Plantillas variantes│ <- tabs (sin anclas)
├──────────────────────────────────┤
│ Buscar atributo                  │
│ [Nombre, código o slug  🔎][Buscar]│ <- composición fluida
├──────────────────────────────────┤
│ Color                   Editar › │
│ Selección         slug: color     │
├──────────────────────────────────┤
│ Modalidad               Editar › │
│ Selección · modalidad             │
├──────────────────────────────────┤
│ Peje                    Editar › │
│ Selección             slug: peje  │
└──────────────────────────────────┘
```

En **Plantillas** (y solamente allí):

```text
┌──────────────────────────────────┐
│ Atributos │ Plantillas variantes │
├──────────────────────────────────┤
│ [Buscar plantillas...   🔎]      │
│ [Filtros ▾]  (chips solo activos) │ <- Categoría / Marca en desplegable
├──────────────────────────────────┤
│ COLORES GOZIO            Ver  ›  │
│ Señuelos duros · Gozio            │
│ 6 valores                     ⋯ │ <- menú editar / eliminar
└──────────────────────────────────┘
```

- Listas/tarjetas de fila como `/admin/orders`: identidad del registro en primera línea, metadatos en una segunda línea, una acción principal. **Sin repetir** «Atributo», «Slug» o «Tipo» en cada fila; labels accesibles para lectores de pantalla.
- Plantillas: búsqueda completa; `<details>` o control equivalente de **Filtros avanzados** con categoría y marca, Aplicar/Limpiar solo cuando se abre, badge/chips de filtro activo si aplica. Conserva POST/GET actuales; no introduce un backend diferente.
- Dos pestañas visibles dentro del panel, sin afectar el drawer/sidebar global del shell; se acomodan o permiten scroll horizontal de tablist cuando sea necesario, sin romper 363 px.
- **Modal existente** sin refactor de backend, ancho seguro `100%-gutter`, cabecera fija, body scrollable y foco/restauración/Tab/Escape intactos; acciones de menú por teclado y toque.
- Escalamiento progresivo `@container(min-width)`: móvil hasta ancho de panel suficiente; intermedio (tablet, 768 con aside compacto) lista adaptable o tabla si realmente cabe; desktop tabla al conseguir anchura útil comparable a Orders (`~780px`, validar en los 5 anchos). No usar el viewport como sustituto del ancho del panel.
- Se prioriza **una sola búsqueda y una sola sección** en pantalla: evita que el usuario atraviese Plantillas cuando solo necesita editar Color. Objetivo reducir la altura de las filas y los filtros, no forzar alturas fijas.

### 11.5 Contratos funcionales y plan condicionado a aprobación

- Propuesta de **tabs reales**: botones `role="tab"` dentro de `role="tablist"`, `aria-selected`, `aria-controls`, paneles `role="tabpanel"`, un solo panel no oculto; `ArrowLeft/Right`, Home/End y Tab, estados de foco. La pestaña activa debe persistir en `?tab=attributes|templates` **sin cambiar ruta** `/admin/attributes`. Con JS deshabilitado definir fallback server-side o enlaces legítimos a `?tab=…` con contenido correcto, **nunca enlaces de ancla**. Respetar historial y parámetros.
- Formularios de filtro de cada tab deben conservar `q`, `sets_q`, `sets_category_id`, `sets_brand_id` sin que un GET al buscar en una pestaña muestre la otra. Conservar valores al alternar pestañas sin mezclar los criterios. El parámetro de selección de tab sería nuevo pero **no rompe endpoints**.
- Mantener rutas `new`, `edit`, `save`, `value/save`, `value/delete`; endpoints de plantillas, `data-role` existentes de modal y `_csrf`. No tocar servicios compartidos de Variantes, Productos, consultas compartidas ni DB.
- Archivos del módulo que requerirían implementación **solo después de autorización visual**: `app/Views/pages/admin/attributes/index.php`, `app/Views/partials/admin/attributes/index/{header,attributes_card,sets_card}.php`, `public_html/assets/js/admin/attributes/index.js`, `public_html/assets/css/admin-v3/pages/attributes.css` y, únicamente si es necesario para persistir tab/filter state, `app/Controllers/Admin/Concerns/AttributesCatalogActions.php`. No autoriza archivos globales ni shared.
- Las subrutas `/new`, `/edit` y modales quedan funcionalmente intactos; tras aprobar el listado, una **segunda revisión visual** debería comparar los formularios con Admin V3 antes de retocarlos.
- Validación posterior a implementación: 363/390/768/1024/1600, desktop/sidebar abierto y reducido, active tab al recargar/Back, filtro en tab no activa, resultados vacíos, textos largos, modales y menús con teclado, sin scroll accidental; build gate + contrato Atributos + readonly smoke + prueba autenticada cuando haya entorno autorizado.

**Estado de esta sección: PROPUESTA SIN IMPLEMENTAR — esperando aprobación del propietario. Ninguna vista/CSS/JS de Atributos fue modificada durante este diagnóstico.** Todo trabajo de integridad P0 en servicios compartidos permanece fuera de la autorización visual.


## 12. Implementación autorizada del rediseño de listado — 2026-10-21

**Estado de la etapa: LISTO PARA REVISIÓN VISUAL DEL PROPIETARIO (no declarado aprobado).** La instrucción expresa «Hazlo» autorizó implementar la propuesta estructural de la sección 11. La cláusula histórica «sin implementar» de dicha sección describe el estado *previo* y queda superada por la presente entrega. **Alcance estricto:** listado de /admin/attributes, CSS del mismo módulo, JS del módulo y pruebas focales; sin tocar Variantes, Productos, DB, shell, rutas ni archivos globales.

### Cambios implementados

- **Panel único:** `app/Views/pages/admin/attributes/index.php` ahora ensambla Page Header y un único `av3-ui-card av3-attributes__board` con `role="tablist"` y dos paneles `role="tabpanel"`. No hay resumen/KPI decorativo, segunda tarjeta ni títulos de sección duplicados.
- **Pestañas reales:** Atributos y Plantillas de variantes con `aria-selected`, `aria-controls`, estado URL `?tab=attributes|templates`, teclado con flechas/Home/End y navegación Back/Forward. `hidden` aísla el contenido no seleccionado. SSR funciona sin JS y conserva URLs antiguas que tenían filtros de plantillas. **No cambió la ruta** `/admin/attributes` ni sus POST/JSON.
- **Page Header contextual:** `app/Views/partials/admin/attributes/index/header.php` usa Page Header existente y muestra «Nuevo atributo» solo en Atributos; «Ir a Productos» en Plantillas. No se añadió otro Page Header.
- **Atributos:** `app/Views/partials/admin/attributes/index/attributes_card.php` contiene búsqueda integrada sin contenedor gris, tabla compacta en desktop con nombre/tipo/slug/acción y **filas móviles reales** (nombre y Editar arriba, tipo · slug debajo), estados vacíos y conteo discreto en footer. Conserva URL de edición y parámetros actuales de búsqueda.
- **Plantillas:** `app/Views/partials/admin/attributes/index/sets_card.php` contiene búsqueda, categoría, marca y Filtrar **alineados en una fila desktop**; en móvil, filtros desplegables y acción conservada. Tabla desktop más densa y filas móviles específicas. «Ver» directo + menú `···` accesible por touch y teclado para Editar/Eliminar, con hooks `data-role=set-view|set-edit|set-delete` originales y datos CSRF intactos. El modal y sus endpoints no se modificaron. Estados vacíos, chips de filtros activos y límite visible de 120 cuando corresponda.
- **JavaScript:** `public_html/assets/js/admin/attributes/index.js` suma navegación de tabs con `history.pushState`/`popstate`, teclas direccionales, sincronización responsive de `details` avanzado (`ResizeObserver`), menús contextuales, Escape y restauración del foco del modal. Al editar por AJAX actualiza ambas representaciones desktop/móvil; al eliminar por AJAX quita ambas y actualiza footer/estado vacío. Se preserva bloqueo de eliminación visual por uso del valor en el formulario anterior.
- **CSS único:** `public_html/assets/css/admin-v3/pages/attributes.css` fue consolidado/rehecho dentro del archivo original (sin hojas adicionales). Estilos del listado ligados a `body[data-page="admin-attributes"]`, reutilización `av3-ui-card`, `av3-ui-control`, `av3-ui-field`, `av3-ui-table`, `av3-ui-btn` y tokens oficiales. Base móvil + `@container(min-width:780px)` para tabla/filtros desktop y el formulario anterior mantenido funcional. Evita chips verdes para un mero «tipo», contadores en pills, columnas apiladas como falsa tabla y CSS legacy. Menú contextual/composición son las excepciones locales justificadas.
- **Pruebas mantenidas:** `scripts/build/attributes_admin_contract.php` actualizado para estructura de tabs/filas/hooks/breakpoints; `scripts/dev/attributes_readonly_smoke.php` prueba SSR en ambas pestañas y ambas representaciones, además del formulario/fragmento y lectura de Variantes. **El nuevo contrato NO fue agregado al gate global**: archivo de coordinación compartido reservado.

**Los servicios P0 siguen sin cambios:** `VariantAttributeValueDeleteService`, `VariantValueSetsService`, `VariantAttributesRepository`; el guard propuesto de integridad NO está implementado. No se hicieron cambios de DB, transacciones destructivas, migraciones, Git, commits, push ni ramas.

### Pruebas de cierre y evidencia

| Prueba | Resultado real |
|---|---|
| PHP `-l` de vistas de index/header/listados y `node --check` de index.js | **OK** |
| CSS `tinycss2` (parseo sintáctico) | **OK, 0 errores** |
| Contrato focal `scripts/build/attributes_admin_contract.php` | **OK** |
| Smoke `scripts/dev/attributes_readonly_smoke.php` | **OK**: base local lectura 4 atributos, 7 valores, 1 plantilla; SSR Atributos y Plantillas, formulario/fragmento; 1 producto con 6 variantes y 1 eje. |
| Integrity `scripts/dev/attributes_integrity_isolated.php` | **18/18 OK**, SQLite en memoria; demuestra riesgos reales del backend y guard prototipo, **no corrige servicios compartidos**. |
| Build gate global `scripts/build/gate.php` | **OK**, 826 archivos PHP, 119 JS, contratos globales/regresiones. |
| Edge headless 363, 390, 768, 1024, 1600 px | **5/5 OK**, listado/plantillas y formulario; sin scroll horizontal, un panel visible, desktop tabla y mobile filas. |
| Edge headless teclado/modal en los cinco anchos | **5/5 OK**: Ver, Editar, menú, Escape, foco inicial/restaurado, URL/tab activo, contexto de Page Header; sin errores JS. |
| Alineación de filtros Plantillas 1024 y 1600 px | **OK tras ajuste:** buscador, categoría, marca y botón comparten la misma línea base. Hubo fallos intermedios de alineación que se **corrigieron y revalidaron**; no se ocultan. |
| Edge mock AJAX 390 y 1600 px (save/delete) | **2/2 OK**, respuestas POST interceptadas, actualiza dos representaciones y estado vacío/contador, sin escribir DB comercial. |
| SSR sin JS / Plantillas | **OK**, `?tab=templates` renderiza solo esa sección visible sin script. |
| Navegadores automáticos | **Edge headless**, scripts auxiliares fuera del proyecto con `finally` cerrando páginas y `browser.close()`; no abrió ventanas visibles. |

**Evidencia fuera del proyecto:** `C:\xampp\mi-pesca-backups\attributes_tabs_20261021\visual\approved_attributes_{363,390,768,1024,1600}.png`, `approved_templates_{363,390,768,1024,1600}.png`, screenshots `form_*.png`, HTML sintético y scripts `verify_v3.cjs`, `mock_actions.cjs`. Respaldo anterior al rediseño de los 9 archivos intervenidos en `C:\xampp\mi-pesca-backups\attributes_tabs_20261021`.

### Riesgos/puntos pendientes para el chat principal

1. **Aprobación estética todavía pendiente:** revisar la captura final del listado frente a `/admin/orders` y `/admin` en **sesión autenticada** (la simulación usa vistas reales y datos locales, pero no cookies de admin); comprobar sidebar expandido/contraído, textos largos, filtros vacíos y múltiples opciones en menú.
2. **Backend P0** sigue bloqueado para evitar eliminar selecciones de productos y validar pares en plantillas. Se mantiene la solicitud de owner exclusivo documentada en sección 10.
3. **Persistencia real no reprobada con DB destructiva:** Save/Delete de plantillas probaron la interfaz con respuestas simuladas. Pendiente DB descartable autenticada y verificación real de CSRF/guardado, no usar datos comerciales.
4. **Paginación 120** sigue limitada por servicio compartido. Solo se avisa al alcanzar el límite; no se modifica `VariantValueSetsService` sin coordinación.
5. **Editor individual/fragmentos:** se conservaron formularios y modal, pero una etapa visual específica para `/new`, `/edit` y editor de plantillas puede solicitarse después de aceptar el listado.

**Próximo paso:** chat principal/propietario inspecciona las nuevas capturas o abre la sesión Admin; indicar ajustes visuales concretos si hace falta. Sin nueva aprobación, no intervenir servicios compartidos ni reescribir globales.


## 13. Cierre técnico integral V2 — 2026-10-22

**Estado de la etapa:** IMPLEMENTADO Y VERIFICADO EN ENTORNO DESCARTABLE; **listo para auditoría técnica e integración selectiva del chat principal, NO aprobado para producción**. El propietario postergó la aprobación estética; no se realizaron nuevos rediseños de listado, formularios, modal ni CSS. Se siguió `docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md`.

### 13.1 Correcciones P0 realmente incorporadas

- `app/Services/Admin/Variants/VariantAttributeValueDeleteService.php`: `deleteAttributeValue` bloquea la fila de `attribute_values` con `SELECT … FOR UPDATE` en InnoDB, y consulta DENTRO de la transacción las referencias en `product_attribute_values`, `product_variant_values` y `variant_value_set_items`. Retorna `VALUE_IN_USE` y uso detallado si hay cualquier dependencia. **Nunca ejecuta DELETE de selecciones/productos/ítems para habilitar la baja del maestro**. Una comprobación SQL fallida bloquea la operación; ya no hay `catch/continue` que traduzca errores en «valor libre». Soporta transacción propia y `SAVEPOINT` si la operación está dentro de una transacción existente. `pruneAttributeValueIfUnused` usa el mismo guard, sin rutas de decisión diferentes.
- `app/Repositories/Catalog/VariantAttributesRepository.php`: `findAttributeValue(...,true)` permite bloqueo exclusivo. `countSetUsage()` deja de interpretar errores como cero referencias. Se conservan funciones del repositorio usadas por otros flujos; el guard ya no invoca `deleteProductSelections` ni `deleteSetItems`.
- `app/Services/Admin/Variants/VariantValueSetsService.php`: `updateValueSet` valida nombre/IDs, existencia de categoría y marca, existencia de plantilla, duplicados por nombre+categoría+marca, pares íntegros sin duplicados, correspondencia `attribute_values.attribute_id = attributes.id` y `is_variant_axis=1`. Se comprueba **antes** de `DELETE FROM variant_value_set_items`, con rollback completo y `SAVEPOINT` para transacciones externas. Acceso a `lastError()/lastErrorCode()` para errores HTTP comprensibles. `createValueSetFromProduct` coteja los pares con sus atributos y evita guardar una copia incompleta; bloquea categoría para serializar creación concurrente e implementa savepoint. `importValueSet` valida categoría/marca (respetando la marca histórica NULL), pertenencia y ejes antes de aplicar, vuelve a verificar después de adquirir locks transaccionales y usa savepoint al ser invocado por un owner externo. `deleteValueSet` exige existencia, bloquea fila y elimina maestro+ítems atómicamente, con savepoint.
- `app/Controllers/Admin/Concerns/AttributesValueSetActions.php`: validación estricta del POST de `values` (solo placeholder cero permitido), rechazo de valores malformados, códigos `404 NOT_FOUND`, `409 DUPLICATE_SET`, `422` validaciones y `500` error SQL; mensajes del servicio en la respuesta JSON. La baja de plantilla exige POST, CSRF y devuelve 404 si no existe.
- No se alteraron funciones compartidas de Importaciones, Checkout, Categorías, Marcas, ProductRepository, layouts, rutas ni componentes UI. Ningún cambio de DB comercial/migración/Git/commit/push.

### 13.2 Pruebas verificadas (evidencia reproducible)

**MariaDB física y descartable:** Windows MariaDB 10.4, puerto privado `127.0.0.1:18991`, esquema exclusivo `mi_attrs_v2_20261022` creado con las **65 tablas reales** de `database/schema.sql`. Usuario/clave sintéticos; fixtures 701–706 solo en dicha base. Invariantes de conexión del test verificaron base y puerto, sin utilizar `db()` del proyecto.

| Prueba | Resultado |
|---|---|
| `scripts/dev/attributes_integral_mysql.php` | **24 escenarios PASS**. Baja permitida/bloqueada por cada dependencia, no existente/mismatch, prune seguro, update válido/inválido, pares cruzados/no eje, categoría/marca inexistentes, duplicados, rollback/snapshot, savepoint ambient, alta desde producto, importación válida/inválida, eliminación de plantilla y asociaciones conservadas. |
| `scripts/dev/attributes_concurrency_mysql.php` | **2 carreras InnoDB PASS** con **dos procesos/conexiones**. La eliminación espera el lock y se rechaza al aparecer una selección concurrente; la creación concurrente con mismo nombre/categoría/marca no duplica plantillas. |
| `scripts/dev/attributes_integrity_isolated.php` | **18 escenarios PASS** en SQLite :memory:, actualizado para esperar la protección REAL, no la antigua vulnerabilidad. El prototipo de guard en memoria queda como comparación histórica, no sustituye prueba MariaDB. |
| HTTP real local, router temporal EXTERNO al proyecto | **25 comprobaciones PASS**: login admin/cliente con cuentas sintéticas, no autenticado 401, rol cliente 403, CSRF incorrecto 419, bajas usadas 409, mismatch 400, plantilla par inválido 422, guardado válido 200, fragmento GET 200, búsqueda GET 200, eliminación libre 200, baja de plantilla 200, segunda baja 404, creación de valor y relectura persistida. Respuestas obtenidas de controladores reales conectados SOLO a DB aislada. |
| Imports I05 + relaciones + snapshot padre | `scripts/build/imports_i05_atomic_contract.php` **21 PASS**, `imports_attribute_relations_contract.php` **30 PASS**, `imports_variant_parent_snapshot_contract.php` **25 PASS**. Solo contratos transversales: no se ejecutó una importación de catálogo completa por HTTP sobre el fixture. |
| `scripts/build/attributes_admin_contract.php` | **OK**: interfaz y hooks conservados. |
| PHP lint de los archivos intervenidos | **OK**. |
| `scripts/build/gate.php` | **PASS**: 886 archivos PHP, 120 JavaScript, contratos de Admin/Checkout/Importaciones, UTF-8 y regresiones, salida final `[gate] OK`. |

**Garantía de aislamiento para HTTP:** el bootstrap `config/boot/08-db.php` lee `env()` desde `.env`, NO el `getenv()` del proceso. Se detectó al iniciar el primer ensayo HTTP, se **detuvo inmediatamente** esa instancia y se descartó la ejecución. Luego se usó `isolated_router.php` externo al proyecto para reemplazar `env()` **solo en QA**, negar inicios con variables no idénticas a `127.0.0.1:18991 / mi_attrs_v2_20261022` y exponer `/__qa_db_check`, que confirmó `{"db":"mi_attrs_v2_20261022","port":18991}`. Todos los **25 casos HTTP aprobados** se ejecutaron SOLO después de esta verificación. **Incidente a informar al chat principal:** el primer intento de login sintético ocurrió antes de corregir la configuración HTTP; podría haber alcanzado la base indicada en `.env` y generado un evento de autenticación fallida. No se realizó ninguna limpieza ni verificación contra esa base para no intervenir datos comerciales. No afirmar ausencia absoluta de efectos de ese primer intento.

**Límite:** pruebas HTTP locales con sesiones reales sintéticas, no ambiente productivo; redirecciones de la instancia PHP CLI en Windows pueden contener separadores de URL anómalos, por lo que el QA siguió la URL local correcta sin autoseguir ese redirect. No se han realizado pagos ni envíos; visual quedó deliberadamente fuera de alcance por decisión del propietario. No se introdujeron migraciones: la unicidad `(name,category_id,brand_id)` se serializa mediante lock de categoría en los escritores del servicio, pero **no existe UNIQUE físico**, por lo que escrituras SQL externas que eviten el servicio podrían crear duplicados. La adición de valores desde el diálogo sigue siendo un paso explícito previo (con confirmación) y no parte de una única transacción de «guardar plantilla».

### 13.3 Archivos intervenidos en esta etapa

- `app/Services/Admin/Variants/VariantAttributeValueDeleteService.php`.
- `app/Services/Admin/Variants/VariantValueSetsService.php`.
- `app/Repositories/Catalog/VariantAttributesRepository.php`.
- `app/Controllers/Admin/Concerns/AttributesValueSetActions.php`.
- `scripts/dev/attributes_integrity_isolated.php` (regresión actualizada).
- `scripts/dev/attributes_integral_mysql.php` (**nuevo**, solo DB privada).
- `scripts/dev/attributes_concurrency_mysql.php` (**nuevo**, solo DB privada).
- Este archivo `docs/modulos/atributos.md`.

**Privado fuera del proyecto:** `C:\xampp\mi-pesca-backups\attributes_integral_v2_20261022` contiene respaldo y runners auxiliares de HTTP/instalación del SQL. Se debe destruir la instancia y su directorio `mariadb` tras el gate, y consignar los puertos 18991/18992 libres.

### 13.4 Estado / riesgos residuales para integración

**LISTO PARA AUDITORÍA TÉCNICA, NO PRODUCCIÓN.** El chat principal debe revisar la autorización estética diferida, el incidente inicial de aislamiento HTTP indicado arriba y la integración selectiva del módulo. No se han modificado contratos de Importaciones: las 76 verificaciones transversales pasan, pero el E2E integral del `ImportService` sobre MariaDB descartable sigue como ensayo cruzado pendiente de coordinación para poder afirmar conciliación de punta a punta. La ausencia de índice UNIQUE físico y las altas anticipadas de valores son decisiones de compatibilidad a evaluar sin modificar política comercial silenciosamente.

### 13.5 Limpieza final del entorno privado (completada)

- Servidor PHP HTTP aislado PID 5144 y primera instancia descartada PID 13416: detenidos individualmente.
- MariaDB privada PID 3908: detenida. Directorio de datos exclusivamente sintéticos `C:\xampp\mi-pesca-backups\attributes_integral_v2_20261022\mariadb` **eliminado** después de verificar los puertos 18991/18992 sin listeners.
- Se conservó fuera del proyecto la evidencia de QA y respaldo en `C:\xampp\mi-pesca-backups\attributes_integral_v2_20261022`, sin base de datos ni servicios iniciados.
- **Gate global al cierre:** `[gate] OK`, 886 archivos PHP y 120 archivos JavaScript. Contrato focal y 76 verificaciones de importaciones PASS.
- **Incidencia previa:** revisar con el principal el intento HTTP temprano que no respetó el `.env` (documentado en §13.2); el router aislado posterior sí confirmó mediante endpoint la DB y el puerto privado. No se intentó modificar, borrar ni inspeccionar registros de auditoría comerciales.
- **No se declara producción aprobada ni cierre del QA transversal completo**, solo cierre técnico del dominio de Atributos listo para auditoría.
