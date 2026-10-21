# Incremental — Recepción global reanudable de pedidos (2026-10-04)

## Base auditada
- ZIP completo: `misistema(20261004-221703).zip`.
- Decisión funcional aplicada: los comprobantes de un pedido se registran primero y la recepción física se realiza después de forma global, consolidada por producto y únicamente desde Seguimiento del pedido.

## Comportamiento implementado
- Los comprobantes vinculados a pedidos ya no pueden recibir mercadería individualmente.
- `Confirmar compra` de un comprobante originado en pedido registra solo el documento; no mueve stock.
- La recepción global se habilita únicamente después de `Finalizar carga y conciliar`.
- Productos repetidos entre distintos comprobantes se consolidan en una única fila: Facturado / Recibido / Pendiente / Recibir ahora.
- `Guardar avance` persiste un control físico interrumpido sin crear recepciones ni modificar stock.
- Al reabrir la recepción se restauran fecha, observaciones y cantidades controladas.
- `Finalizar recepción` confirma solo las cantidades controladas. Puede ser parcial y repetirse más adelante por el pendiente restante.
- La distribución interna entre comprobantes se mantiene determinística y transparente para el usuario.
- Si una recepción global afecta varios comprobantes, todos los `purchase_receipt` generados quedan agrupados bajo el mismo `order_receipt_batch_id`.
- El detalle del comprobante queda documental: sin estado/columna/acciones de recepción física.
- Seguimiento muestra estados globales `Por recibir`, `Recepción en proceso`, `Recepción parcial` y `Recepción completa`; los comprobantes muestran `Borrador` / `Registrado`.
- Compras independientes conservan su recepción individual normal.

## Persistencia
- Nueva migración `0127_purchase_order_receipt_batches`.
- Nueva tabla `purchase_order_receipt_batch` para sesiones globales `DRAFT` / `FINALIZED`.
- Nueva tabla `purchase_order_receipt_batch_item` para cantidades controladas por producto.
- Nuevo `purchase_receipt.order_receipt_batch_id` nullable para conservar trazabilidad entre una recepción física global y los recibos internos por comprobante.
- Índice único parcial: un solo control `DRAFT` activo por pedido.

## Archivos modificados
- `desktop/app/application/purchases/dto.py`
- `desktop/app/application/purchases/purchases.py`
- `desktop/app/application/purchases/__init__.py`
- `desktop/app/bootstrap/container.py`
- `desktop/app/domain/entities/purchase_receipt.py`
- `desktop/app/infrastructure/database/mappers/purchase_receipt_mapper.py`
- `desktop/app/infrastructure/database/models/__init__.py`
- `desktop/app/infrastructure/database/models/purchase_receipt.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/app/presentation/pages/suppliers/purchases_page.py`
- `desktop/app/presentation/viewmodels/purchases_view_model.py`
- `desktop/tests/unit/application/purchases/test_confirm_and_receive_purchase.py`
- `desktop/tests/unit/application/purchases/test_purchase_traceability.py`
- `desktop/tests/unit/application/purchases/test_purchase_order_receipt_guards.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/01-functional/modelo-comercial-definitivo.md`
- `docs/04-development/pedidos-compras-recepcion-conciliacion-separadas-2026-09-29.md`

## Archivos nuevos
- `desktop/app/infrastructure/database/models/purchase_order_receipt_batch.py`
- `desktop/app/infrastructure/database/models/purchase_order_receipt_batch_item.py`
- `desktop/app/infrastructure/migrations/alembic/versions/0127_purchase_order_receipt_batches.py`
- `desktop/tests/integration/migrations/test_0127_purchase_order_receipt_batches.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`

## Validaciones ejecutadas
- `python -m compileall` sobre módulos y tests modificados: OK.
- Contratos estáticos del flujo Pedido/Comprobantes/Recepción: `15 passed`.
- Tests focales de Application de compras/recepción global, excluyendo el único test que importa PySide6: `11 passed, 1 deselected`.
- Test focal de migración `0127`: `1 passed` con SQLite y `foreign_keys=ON`, preservando `purchase_receipt` y `purchase_receipt_item` existentes.
- El test general de Alembic no pudo recolectarse en este contenedor porque `AppPaths` importa `PySide6`, dependencia no instalada en el entorno. El test focal de la migración sí fue ejecutado correctamente.
- `ruff` no está instalado en el contenedor; se validó compilación y se revisaron imports modificados.

## Prueba funcional recomendada en Windows
1. Crear/usar un pedido con al menos dos comprobantes que compartan un mismo producto.
2. Confirmar ambos comprobantes y verificar que ninguno permita recepción individual.
3. Ejecutar `Finalizar carga y conciliar`.
4. Abrir `Recibir`: el producto repetido debe aparecer una sola vez con la cantidad facturada acumulada.
5. Cargar solo una parte y pulsar `Guardar avance`; cerrar y volver a abrir. Deben restaurarse las cantidades y el stock no debe haber cambiado.
6. Completar parcialmente y pulsar `Finalizar recepción`; verificar incremento de stock solo por lo finalizado y estado `Recepción parcial` si aún queda pendiente.
7. Abrir nuevamente `Recibir` y finalizar el remanente. Verificar `Recepción completa`.
8. Revisar los comprobantes: deben mostrar estado documental `Registrado` y su detalle no debe exponer acciones ni columna de recepción.

# Incremental — Seguimiento: `Ver` informativo + recepción separada (2026-10-04)

## Ajuste aplicado
- Se restaura la acción `Ver` del KPI `Pendientes` como consulta informativa de la comparación pedido vs. comprobantes.
- `Ver` ya no abre ni continúa una recepción física.
- Mientras la carga documental está abierta, la acción primaria del footer continúa siendo `Finalizar carga y conciliar`.
- Una vez cerrada la carga, la acción primaria del footer pasa a `Recibir mercadería` o `Continuar recepción` cuando existe un avance guardado.
- Después del cierre documental se conserva `Ver conciliación` como acción secundaria independiente.
- El KPI `Pendientes` vuelve a representar diferencias de facturación/conciliación; el estado de recepción se mantiene separado en el KPI `Estado`.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`

## Validaciones
- `python -m py_compile` sobre los módulos modificados: OK.
- 15 contratos estáticos del flujo de pedido ejecutados directamente: OK.
- `pytest` no pudo recolectar en este contenedor porque `desktop/tests/conftest.py` requiere `PySide6`, dependencia no instalada en el entorno.

# Incremental — Seguimiento: CTA global de recepción (2026-10-04)

## Ajuste aplicado
- La recepción física se inicia exclusivamente desde el footer de `Seguimiento del pedido`.
- Mientras la carga documental está abierta, el CTA principal es `Finalizar carga y conciliar`.
- Una vez conciliado y con mercadería pendiente, el CTA principal es `Iniciar recepción`.
- Si existe un control físico guardado, el CTA pasa a `Continuar recepción`.
- Cuando no queda mercadería facturada pendiente, el CTA muestra `Recepción completada` y queda deshabilitado.
- El link `Ver` del KPI `Pendientes` continúa siendo exclusivamente informativo y no inicia recepción.
- No se reintroducen acciones de recepción dentro del detalle de comprobantes.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`

## Validaciones
- `python -m py_compile` sobre el diálogo y el contrato estático: OK.
- Contratos estáticos focales de seguimiento/recepción: OK.

# Incremental — Acciones contextuales por comprobante (2026-10-07)

## Base auditada
- ZIP completo: `misistema(20261007-171509).zip`.
- Alcance: `Proveedores → Pedidos → Seguimiento del pedido`.

## Ajuste aplicado
- Se agrega una columna final compacta de acciones con menú de tres puntos por comprobante.
- `Borrador`: `Editar borrador` y `Eliminar borrador`.
- `Registrado`: `Ver detalle` y, mientras la carga documental siga abierta y el comprobante permanezca confirmado, `Anular comprobante`.
- Un comprobante confirmado no se reabre para edición directa. La regla vigente sigue siendo: anular y volver a registrar el documento correcto.
- Con la carga documental cerrada, los comprobantes quedan en modo consulta. Para volver a modificar la composición documental se usa `Reabrir carga` a nivel pedido.
- `Eliminar borrador` reutiliza la anulación auditada existente; al tratarse de un DRAFT no genera deuda ni movimiento contable.
- `Anular comprobante` exige motivo y confirmación y reutiliza los guards existentes de Application.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`

## Validaciones
- `python -m py_compile` sobre diálogo y contrato estático: OK.
- 15/15 contratos estáticos del flujo Pedido/Comprobantes/Recepción: OK.
- PySide6 no está instalado en el contenedor; la prueba visual/funcional debe realizarse en Windows.

# Incremental — Pedido: refinamiento visual del editor (2026-10-16)

## Base auditada
- ZIP completo: `misistema(20261007-171509).zip`.
- Se aplicó encima el incremental vigente `misistema_incremental_acciones_comprobantes_seguimiento_20261007.zip`.
- Alcance: `Proveedores → Pedidos → Nuevo/Editar pedido`.

## Ajustes aplicados
- Badge `Borrador` del header desplazado al lateral derecho, junto al botón de cierre y con separación de 14 px.
- Encabezados de tabla: `Costo estimado` → `Sub-total` y `Estimado` → `Total`.
- `Quitar línea` y `Editar línea` pasan a 140 px de ancho.
- Se reacomoda el bloque inferior para conservar un gap limpio antes de `F2 SELECCIONAR PRODUCTO`.
- `Completar por stock mínimo` pasa a mostrarse como `Stock mínimo`, sin cambios funcionales.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_form_dialog.py`
- `desktop/tests/ui/test_purchase_form_dialog.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre diálogo y test UI modificados: OK.
- Contrato estático focal del layout: 6/6 verificaciones OK.
- `pytest desktop/tests/ui/test_purchase_form_dialog.py` no pudo recolectar en este contenedor porque falta `PySide6`; la validación visual/funcional queda para Windows.

# Incremental — Seguimiento: header operativo y doble clic (2026-10-16)

## Ajustes aplicados
- `+ Registrar comprobante` se mueve desde el bloque documental al header de `Seguimiento del pedido`, antes del botón `X`.
- Icono, título/descripción, acción y cierre quedan alineados verticalmente al centro.
- Se elimina el título/icono redundante `Documentos registrados`.
- La tabla documental aprovecha el alto liberado y sube su altura mínima a 340 px.
- El doble clic en un pedido enviado/completado abre directamente `Seguimiento del pedido`.
- El modal informativo `Editar pedido` deja de intervenir en ese recorrido; los borradores editables conservan su editor normal.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/app/presentation/pages/suppliers/purchase_orders_page.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`
- `MANIFEST.md`

# Incremental — Seguimiento: ajuste tipográfico de `Registrar comprobante` (2026-10-16)

## Ajuste aplicado
- Se incrementa en `2 px` la tipografía del link `+ Registrar comprobante` en el header de `Seguimiento del pedido` para mejorar jerarquía y legibilidad.
- No se modifican comportamiento, alineación ni espaciados del header.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`: OK.

# Incremental — Comprobante: editor unificado + optimización XLSX (2026-10-16)

## Base auditada
- ZIP completo: `misistema(20261016-164345).zip`.
- Alcance: `Proveedores → Pedidos → Registrar comprobante → Cargar comprobante`.

## Ajustes aplicados
- Se elimina el editor legacy exclusivo de comprobantes XLSX.
- Carga manual e importación XLSX usan una única UI basada en el diseño aprobado de Figma.
- La tabla queda liviana y de solo lectura; la edición de líneas se realiza desde el bloque superior `Agregar producto`.
- Estados de comparación actualizados a `Falta/Faltan N por facturar`, `Excede pedido: +N`, `Fuera del pedido` y estado defensivo de vínculo inválido; coincidencia exacta queda con icono verde y tooltip `Completa lo pedido`.
- La inspección y el preview del XLSX se ejecutan fuera del hilo de UI con `AsyncReadRunner` y `BusyProgressDialog`.
- La carga de candidatos para F2 pasa a ser lazy/asíncrona y no retrasa la apertura inicial del comprobante.
- `_validate_catalog_traceability` pasa de una consulta por línea a `list_by_ids` masivo.
- Se elimina la creación de 4 inputs Qt por fila del editor XLSX y los recálculos de columnas por cada línea.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/app/application/purchases/purchases.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `desktop/tests/unit/application/supplier_catalog_item/test_not_reported_items_remain_usable.py`
- `MANIFEST.md`

## Archivo nuevo
- `docs/04-development/pedidos-comprobante-editor-unificado-2026-10-16.md`

## Validaciones
- `python -m compileall` sobre módulos y tests afectados: OK.
- Contratos estáticos de Pedidos/Comprobantes/Recepción ejecutados directamente: `17/17 OK`.
- Tests focales directos de trazabilidad de catálogo: `4/4 OK`.
- `pytest` no puede recolectar en este contenedor porque `desktop/tests/conftest.py` requiere `PySide6`, dependencia no instalada.
- `ruff` no está instalado en este contenedor.

## Prueba funcional recomendada en Windows
1. Importar un XLSX grande (100+ líneas) y verificar que aparezca el progreso sin congelar la ventana.
2. Confirmar que el editor abierto coincide con la UI de carga manual/Figma y no muestra inputs embebidos por fila.
3. Revisar los cuatro estados semánticos con productos exactos, faltantes, excedidos y fuera del pedido.
4. Editar una línea mediante clic/doble clic, guardar y reabrir el borrador.
5. Usar F2 desde un comprobante XLSX y confirmar que los productos del pedido se cargan al primer uso.
6. Continuar y confirmar el comprobante verificando que no se mueva stock.

# Incremental — Comprobante: diferencias neutrales + interacción rápida (2026-10-16)

## Ajustes funcionales/UX
- `Falta/Faltan N por facturar` se reemplaza por `N menos que pedido`.
- El estado deja de asumir que el proveedor completará la diferencia en otro comprobante; durante la carga solo informa la diferencia contra el pedido original y el acumulado facturado.
- Tooltip de diferencia: `Pedido: X · Facturado acumulado: Y`.
- Después de modificar una línea existente, el foco vuelve a la misma fila de la tabla.

## Rendimiento
- Se elimina la escritura completa del borrador en SQLite después de cada modificación/eliminación de línea.
- El borrador queda marcado como modificado y se persiste una sola vez al `Continuar` o al cerrar/volver del editor.
- El flujo XLSX deja de persistir las líneas importadas antes de abrir el editor; para comprobantes grandes el modal se muestra sin esa reescritura previa.
- Modificar una línea ya no reconstruye toda la tabla: solo se actualiza la fila editada y, si corresponde, otras filas vinculadas al mismo ítem del pedido.
- La selección de tabla actualiza solamente los widgets de las filas cuyo estado de selección cambió, evitando repulir todas las filas.
- El recálculo de anchos permanece reservado a la carga completa inicial, no a cada modificación.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-comprobante-editor-unificado-2026-10-16.md`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre workflow y contrato estático: OK.
- Contratos estáticos del flujo Pedido/Comprobantes/Recepción ejecutados directamente: 18/18 OK.
- La validación temporal final debe hacerse en Windows/PySide6 con el comprobante real de 135 artículos, comparando apertura y edición de cantidades.

# Incremental — Comprobante: tabla virtualizada con delegates (2026-10-16)

## Objetivo
- Eliminar la demora restante al abrir `Cargar comprobante` con 135+ productos.

## Ajustes aplicados
- Se reemplazan `SemanticStateCell` y `SemanticDetailCell` instalados con `setIndexWidget()` por delegates reutilizables del Design System.
- `SemanticStateDelegate` pinta el icono/tono directamente sobre la celda.
- `SemanticDetailDelegate` pinta descripción + detalle semántico sin widgets por fila.
- El modelo mantiene tooltips y roles semánticos; la selección continúa usando el contrato visual de `AppTableView`.
- La carga inicial usa `QSignalBlocker` mientras se completan las celdas para evitar señales/repaints por cada `QStandardItem`.
- `AppTableView.set_proportional_columns()` incorpora `include_cell_widget_minimums`; el editor lo desactiva porque esta tabla ya no contiene cell-widgets.
- Se elimina el refresco proporcional extra al terminar de poblar la tabla; el layout responsive se resuelve normalmente al mostrar/redimensionar el modal.

## Archivos modificados
- `desktop/app/presentation/widgets/tables.py`
- `desktop/app/presentation/widgets/__init__.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre los módulos y contratos modificados: OK.
- Contratos estáticos de Pedido/Comprobantes: 18/18 OK.
- No se pudo ejecutar UI real en el contenedor porque PySide6 no está instalado; la validación de tiempo de apertura corresponde realizarla en Windows con el comprobante de 135 artículos.

# Incremental — Comprobante: corrección visual del detalle semántico (2026-10-16)

## Ajuste aplicado
- Se corrige el solapamiento visual de textos como `2 menos que pedido` dentro de la columna `Descripción`.
- La causa era el uso de la misma instancia mutable de `QRect` para pintar descripción y detalle semántico dentro de `SemanticDetailDelegate`.
- Descripción y detalle ahora usan rectángulos independientes, conservando la virtualización y la apertura instantánea lograda con delegates.
- No se reintroducen widgets por fila ni `setIndexWidget()`.

## Archivos modificados
- `desktop/app/presentation/widgets/tables.py`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile desktop/app/presentation/widgets/tables.py`: OK.

# Incremental — Comprobante: estados sin fondo de fila (2026-10-16)

## Ajuste aplicado
- Se elimina el color de fondo de las filas con estados semánticos `N menos que pedido`, `Excede pedido: +N` y `Fuera del pedido`.
- Se conserva únicamente el indicador vertical izquierdo en el color correspondiente al estado.
- Se mantienen texto, icono, tooltip, selección de fila y virtualización por delegates sin cambios funcionales.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`: OK.

# Incremental — Pedidos: stock mínimo por bulto + roundtrip XLSX (2026-10-16)

## Problema corregido
- Los pedidos almacenan cantidades en unidades físicas, pero el catálogo del proveedor puede usar `Cantidad por bulto > 1`.
- La reposición por stock mínimo sugería la necesidad exacta sin respetar bultos completos.
- La exportación XLSX escribía cantidad física y costo físico unitario. Al reutilizar esa hoja como factura, el importador interpretaba ambos valores como presentación del proveedor y podía transformar, por ejemplo, `32` en `320` unidades para un `bulto x10`.

## Reglas implementadas
- Stock mínimo continúa calculándose con unidades físicas: `mínimo - disponible`.
- Si el artículo proveedor tiene `bulk_quantity > 1`, la necesidad se redondea hacia arriba al siguiente bulto completo.
- Ejemplo real: disponible `-12`, mínimo `20`, necesidad `32`, bulto `x10` → pedido `40 unidades = 4 bultos`.
- Cada línea de pedido guarda un snapshot de `bulk_quantity`; la cantidad solicitada y el costo estimado continúan persistidos como unidad física/costo unitario.
- `Stock mínimo` en el editor de pedido conserva el bulto sugerido y muestra la presentación comercial existente (`4x10`, etc.).
- La creación masiva de pedidos por stock mínimo usa la misma regla de redondeo.

## Exportación/importación XLSX
- El XLSX del pedido pasa a exponer `Codigo`, `Producto`, `Cantidad`, `Precio`, `Presentación`.
- `Cantidad` se exporta como cantidad de bultos/presentaciones.
- `Precio` se exporta como costo de la presentación completa.
- `Presentación` muestra `Unidad` o `Bulto xN`.
- Roundtrip verificado: `40 un.`, `bulto x10`, costo unitario `462,281` → XLSX `4`, `$4.622,81`, `Bulto x10` → importación de factura vuelve a `40 un.` y `$462,281` por unidad.

## Persistencia
- Nueva migración `0128_purchase_order_item_bulk_quantity`.
- Agrega `purchase_order_item.bulk_quantity >= 1` con default `1`.
- Para líneas históricas recupera el bulto desde `supplier_catalog_item` cuando existe el vínculo, sin modificar la cantidad física solicitada originalmente.
- Los pedidos nuevos dejan de depender de cambios futuros del catálogo para recordar la presentación con la que fueron creados.

## Archivos modificados
- `desktop/app/application/purchase_orders/dto.py`
- `desktop/app/application/purchase_orders/purchase_orders.py`
- `desktop/app/application/purchase_orders/xlsx_exporter.py`
- `desktop/app/application/purchases/dto.py`
- `desktop/app/domain/entities/purchase_order_item.py`
- `desktop/app/infrastructure/database/mappers/purchase_order_mapper.py`
- `desktop/app/infrastructure/database/models/purchase_order_item.py`
- `desktop/app/infrastructure/repositories/sqlalchemy_purchase_order_repository.py`
- `desktop/app/infrastructure/repositories/sqlalchemy_purchase_repository.py`
- `desktop/app/presentation/pages/suppliers/purchase_form_dialog.py`
- `desktop/tests/unit/application/purchase_orders/test_purchase_order_edit_export.py`
- `desktop/tests/unit/application/purchases/test_purchase_xlsx_import.py`
- `docs/01-functional/modelo-comercial-definitivo.md`
- `docs/04-development/etapa-11-proveedores-compras.md`
- `docs/04-development/implementacion-creacion-masiva-pedidos-stock-minimo-2026-08-03.md`
- `docs/04-development/implementacion-edicion-exportacion-pedidos-2026-08-03.md`
- `MANIFEST.md`

## Archivos nuevos
- `desktop/app/domain/services/purchase_packaging.py`
- `desktop/app/infrastructure/migrations/alembic/versions/0128_purchase_order_item_bulk_quantity.py`
- `desktop/tests/integration/migrations/test_0128_purchase_order_item_bulk_quantity.py`
- `desktop/tests/unit/domain/test_purchase_packaging.py`
- `docs/04-development/pedidos-stock-minimo-bultos-y-roundtrip-xlsx-2026-10-16.md`

## Validaciones ejecutadas
- `python -m compileall` sobre módulos y tests afectados: OK.
- Dominio de presentación por bulto: `6 passed` (incluye tests previos de cantidades físicas en compras).
- Pedidos/exportación: `13 passed` junto con progreso de pedidos.
- Importación XLSX y roundtrip pedido → factura: `7 passed`.
- Migración `0128`: `1 passed` sobre SQLite, preservando cantidad histórica y recuperando `bulk_quantity`.
- `pytest` general sigue requiriendo PySide6 por `desktop/tests/conftest.py`; los tests focales se ejecutaron con `--confcutdir` para aislar la dependencia UI.

## Prueba funcional recomendada en Windows
1. Usar un producto con disponible `-12`, stock mínimo `20` y proveedor `bulto x10`.
2. Crear/completar pedido por `Stock mínimo`: debe sugerir `40 unidades` y mostrar `4x10`, no `32` ni `320`.
3. Guardar/enviar y exportar XLSX: debe escribir `Cantidad = 4`, `Precio = costo del bulto` y `Presentación = Bulto x10`.
4. Importar ese XLSX como factura del mismo proveedor: debe reconstruir `40 unidades` y el costo unitario original.
5. Confirmar que un pedido histórico ya enviado conserva su cantidad física original; la migración no la redondea retroactivamente.

# Incremental — Comprobantes: presentación proveedor visible + bulto informativo (2026-10-17)

## Criterio definitivo
- Pedido y Factura se operan visualmente en la presentación comercial del proveedor.
- Para `bulto x10`, `Cantidad = 1` representa un bulto y `Costo` representa el precio del bulto completo.
- Stock y costo interno del producto continúan normalizados en unidades físicas/costo físico unitario para no alterar inventario, recepción ni cálculo de precio de venta.
- `bulk_quantity` conserva la equivalencia reversible entre ambos mundos.

## Ajustes aplicados
- `Cargar comprobante` deja de exponer la normalización interna: una línea `1 bulto x10` vuelve a mostrarse como `Cantidad = 1` y costo por bulto, no como `10` unidades y costo dividido.
- La edición de una línea usa la misma presentación comercial y normaliza nuevamente solo al guardar la línea.
- Las filas con `bulk_quantity > 1` muestran un estado informativo azul independiente, por ejemplo `1 bulto x10 · 10 unidades` o `4 bultos x10 · 40 unidades`.
- Los estados de comparación (`N menos que pedido`, `Excede pedido`, `Fuera del pedido`) permanecen separados y conservan únicamente el borde izquierdo semántico.
- Las diferencias contra el pedido se expresan en cantidad comercial cuando existe bulto; la comparación interna continúa usando unidades físicas.
- Una factura vinculada a un pedido toma el `bulk_quantity` congelado en la línea original del pedido, no el valor actual del catálogo del proveedor.
- El selector F2 desde el pedido deja de consultar la presentación viva del catálogo para construir líneas ya pedidas.
- Los productos agregados desde catálogo se normalizan internamente desde su precio/cantidad de presentación sin alterar lo que el operador ve.
- Se agrega `SEMANTIC_CONTEXT_ROLE` al delegate virtualizado para mostrar la presentación sin reintroducir widgets por fila ni penalizar el rendimiento conseguido.

## Archivos modificados
- `desktop/app/domain/services/purchase_packaging.py`
- `desktop/app/application/purchases/dto.py`
- `desktop/app/application/purchases/purchases.py`
- `desktop/app/presentation/widgets/tables.py`
- `desktop/app/presentation/widgets/__init__.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/tests/unit/domain/test_purchase_packaging.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/01-functional/modelo-comercial-definitivo.md`
- `docs/04-development/pedidos-stock-minimo-bultos-y-roundtrip-xlsx-2026-10-16.md`
- `MANIFEST.md`

## Validaciones
- `python -m compileall` sobre los módulos y tests modificados: OK.
- `test_purchase_packaging.py`: 4 passed.
- `test_purchase_xlsx_import.py`: 7 passed.
- `test_purchase_order_control_workflow_contract.py`: 20 passed.
- `test_purchase_order_edit_export.py`: 5 passed.
- `ruff` no está instalado en el contenedor.
- La validación visual real de PySide6 queda para Windows.

## Prueba funcional recomendada
1. Usar un artículo `bulto x10` pedido como `1` presentación.
2. Exportar pedido: XLSX debe mostrar `Cantidad = 1`, `Precio = precio del bulto`, `Presentación = Bulto x10`.
3. Importar ese mismo archivo como factura: `Cargar comprobante` debe seguir mostrando `Cantidad = 1` y precio completo del bulto.
4. La fila debe mostrar `1 bulto x10 · 10 unidades` en azul.
5. Editar la cantidad a `2`: debe mostrar `2 bultos x10 · 20 unidades`, mantener el precio por bulto y recalcular el total correctamente.
6. Confirmar/recibir y verificar que stock aumenta por las unidades físicas equivalentes, no por cantidad de bultos.

# Incremental — Pedidos: botones y cambio reversible de estado (2026-10-17)

## Ajustes aplicados
- `Exportar XLSX` y el CTA principal (`Seguimiento` / `Recibir pedido`) pasan a 140 px de ancho, conservando mismo tamaño y evitando compresión del icono.
- Debajo de `Trazabilidad` se agrega un control compacto `Estado del pedido` con badge del estado actual.
- Sin comprobantes vigentes vinculados:
  - `Enviado` permite `Volver a borrador`.
  - `Borrador` permite `Marcar como enviado`.
- Si existe un comprobante en borrador o activo, el cambio queda bloqueado y se informa `Bloqueado por comprobantes`.
- Los comprobantes cancelados no bloquean el cambio de estado.
- `ChangePurchaseOrderStatus` refuerza la regla en Application: verifica tanto borradores como comprobantes activos antes de cambiar el estado.
- Toda transición continúa auditándose mediante `PURCHASE_ORDER_STATUS_CHANGED`.

## Archivos modificados
- `desktop/app/application/purchase_orders/purchase_orders.py`
- `desktop/app/presentation/pages/suppliers/purchase_orders_page.py`
- `desktop/app/presentation/themes/light.qss`
- `desktop/tests/unit/presentation/test_purchase_orders_json_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre los módulos modificados: OK.
- Contratos estáticos de `PurchaseOrdersPage`: OK.
- Tests focales de dominio/progreso de pedidos: OK.
- La validación visual final queda para Windows/PySide6.

# Incremental — Pedidos: estado integrado en Trazabilidad (2026-10-17)

## Ajuste aplicado
- Se replica en PySide6 la revisión aprobada del JSON `SCR / Proveedores / Pedidos / Referencia actual · Estado del pedido`.
- El control `Estado del pedido` deja de ocupar una tarjeta independiente y pasa a formar parte del bloque `Trazabilidad`.
- `Trazabilidad` queda en tres niveles visuales: título + `Ver historial`, estado + badge + acción administrativa, y resumen de vínculos.
- Para un pedido `Enviado` sin comprobantes vigentes, la acción pasa a llamarse `Reabrir como borrador`.
- Si existen comprobantes vigentes, la acción se reemplaza por `Bloqueado por comprobantes`.
- En estados no reversibles (`Completado` / `Cancelado`) se conserva visible el estado dentro de Trazabilidad, sin exponer acción de cambio.
- El bloque integrado usa 108 px de alto, badge de 88×24 y mantiene `Exportar XLSX` / `Seguimiento` en 140 px.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_orders_page.py`
- `desktop/app/presentation/themes/light.qss`
- `desktop/tests/unit/presentation/test_purchase_orders_json_contract.py`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre página y contrato estático: OK.
- `test_purchase_orders_json_contract.py`: 6 passed.

# Incremental — Confirmar compra: decisión Registrar / Registrar y recibir (2026-10-17)

## Implementación
- Se restaura el diseño aprobado de `Confirmar compra` con las dos decisiones visuales:
  - `Registrar compra`.
  - `Registrar y recibir`.
- Se aplica el copy acordado para dejar explícito que la segunda opción corresponde al caso de un único comprobante ya controlado físicamente.
- Se corrige el ritmo vertical entre `¿Cómo querés continuar?`, el helper y las dos cards, manteniendo el modal en 774 px de ancho y ajustando su alto a 577 px.
- `Registrar y recibir` queda deshabilitado visualmente cuando ya existe otro comprobante vigente o borrador en el pedido.

## Regla transaccional
- Para compras independientes, `ConfirmAndReceivePurchase` conserva el comportamiento previo.
- Para compras vinculadas a pedido, `Registrar y recibir` solo se admite si el comprobante confirmado queda como único documento activo del pedido.
- En una sola transacción se:
  1. confirma la compra;
  2. cierra la carga documental del pedido;
  3. crea un `purchase_order_receipt_batch`;
  4. recibe todas las líneas del comprobante;
  5. finaliza el lote global y actualiza stock/estado.
- El atajo usa `order_receipt_batch_id`; no reintroduce la recepción individual por comprobante.
- Ante otros comprobantes activos/borradores o un avance de recepción existente, la operación se rechaza sin commit.

## Archivos modificados
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/app/presentation/themes/light.qss`
- `desktop/app/application/purchases/purchases.py`
- `desktop/tests/unit/application/purchases/test_confirm_and_receive_purchase.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-recepcion-global-reanudable-2026-10-04.md`
- `docs/04-development/pedidos-compras-recepcion-conciliacion-separadas-2026-09-29.md`
- `MANIFEST.md`

# Incremental — Conciliación con filtros y resolución de faltantes (2026-10-17)

## Implementación
- La conciliación ahora abre por defecto en `Con diferencias` cuando existe al menos una diferencia.
- Se agregan filtros: `Todos`, `Con diferencias`, `Faltantes`, `Excesos` y `Coinciden`.
- Los productos facturados fuera del pedido se muestran como `Fuera del pedido` y forman parte de las diferencias.
- Antes de finalizar una conciliación con diferencias se muestra un resumen explícito de faltantes, excesos y productos fuera del pedido.
- Finalizar conciliación no modifica el pedido original: solamente cierra la carga documental.
- Se agrega `Generar pedido por faltantes`.
- El flujo de faltantes permite elegir el proveedor por producto entre vínculos activos del catálogo.
- Las líneas se agrupan por proveedor y se crea un pedido borrador por proveedor elegido.
- Las cantidades del nuevo pedido se redondean a la presentación/bulto del proveedor seleccionado.
- El proveedor original permanece disponible como alternativa y el pedido fuente queda intacto.
- Se incluye `light.qss` con el hotfix del token válido `$COLOR_SURFACE_SOFT_BG` para evitar el `KeyError: COLOR_SURFACE_SUBTLE` del incremental anterior.

## Archivos modificados
- `desktop/app/application/purchase_orders/dto.py`
- `desktop/app/application/purchase_orders/purchase_orders.py`
- `desktop/app/application/purchase_orders/__init__.py`
- `desktop/app/bootstrap/container.py`
- `desktop/app/presentation/viewmodels/purchase_orders_view_model.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_document_workflow.py`
- `desktop/app/presentation/pages/suppliers/purchase_order_tracking_dialog.py`
- `desktop/tests/unit/presentation/test_purchase_order_control_workflow_contract.py`
- `docs/04-development/pedidos-compras-recepcion-conciliacion-separadas-2026-09-29.md`
- `MANIFEST.md`

## Validaciones
- `python -m py_compile` sobre todos los archivos Python modificados: OK.
- Contrato estático focal de conciliación: OK.
- `pytest` no puede recolectar en este entorno porque PySide6 no está instalado.

## Bloque 1 v2 — fundamentos transversales admin (2026-10-17)
- Nuevo `public_html/assets/css/v2/components/admin-foundations.css`: contrato visual transversal administrado por `body[data-page^="admin"]` para ritmo del layout, encabezados, botones, acciones, campos, filtros, tarjetas, tablas, badges, contenedores de diálogos y responsive. Respeta clases y colores existentes; no interviene apertura/cierre de modales.
- `app/Views/partials/head.php`: stylesheet añadido únicamente a `$v2AdminStyles`, después del puente de compatibilidad. `$v2PublicStyles` intacto.
- Nuevo `scripts/build/admin_css_scope.php`: prueba que el CSS exista sólo en lista admin y no aparezca en lista pública; vinculado a `scripts/build/gate.php`.
- El puente admin-beta-bridge y Tailwind CDN siguen vigentes, porque retirarlos sin migrar sus consumidores implicaría regresiones. Esta pasada consolida fundamentos; no se declara terminada la migración de todos los módulos ni aprobada visualmente.
- Verificación visual pendiente: Dashboard, Productos, Pedidos, edición de producto y Ajustes; especialmente header, tarjetas, filtros, tabla, acciones, modales, estados de foco y ancho en móvil.

## Limpieza preventiva y layout común admin — 2026-10-17
- Se detectaron 9 archivos del proyecto de escritorio MiSistema bajo `desktop/` y 1 documento ajeno `docs/04-development/pedidos-compras-recepcion-conciliacion-separadas-2026-09-29.md`. Se respaldaron íntegramente y se comparó el contenido del respaldo antes de retirarlos. Respaldo fuera de htdocs: `C:/xampp/mi-pesca-backups/misistema-extraccion-recuperada-20261017-161421.zip`.
- Los 10 archivos eliminados no existen en `mi-pesca.zip`. Al no estar disponible el ZIP exacto de MiSistema, no puede probarse que ninguna otra ruta se haya sobreescrito; no se restauró ningún PHP/CSS/JS de MiTiendaPesca para preservar trabajo reciente.
- `public_html/assets/css/v2/layouts/admin-shell.css`: nuevas reglas exclusivas de `body[data-page^="admin"]` para ancho fluido, márgenes y padding homogéneos de los contenedores administrativos y breadcrumb anteriores a `.admin-surface`, incluidos layouts legacy y subrutas.
- No se editaron los CSS del frontend público, ni lógica de pedidos, pagos o base de datos.
- Validación visual recomendada: /admin, /admin/products, /admin/users, /admin/audit, /admin/settings, /admin/proveedores, /admin/facturacion/control; comprobar alineación lateral, breadcrumb, tarjetas, formularios, tablas y menú contraído.

## Ajuste layout de Facturación — 2026-10-17
- `public_html/assets/css/v2/pages/admin/facturacion/shared.css`: `.fisc-wrap` como contenedor fluido de administración, con offset del sidebar y gutter compartido; grilla fiscal pasa a una columna en pantalla angosta.
- `public_html/assets/css/v2/pages/admin/facturacion/nav.css`: `.fisc-nav-shell` utiliza el mismo offset y padding lateral; sin ancho máximo 1240px, alcanza subrutas que comparten la navegación fiscal.
- Cambios acotados a `body[data-page^="admin"]`; no se modifica CSS público ni se alteran operaciones fiscales.

## Piloto nuevo frontend administrativo de Productos — 2026-10-17
- Nuevo `public_html/assets/css/v2/pages/admin/products-v3.css`: estilos de presentación aislados a `.admin-v3-products` y `.admin-v3-product-form` (títulos, tarjetas, filtros, tabla, pestañas, responsive). Es primer corte visual, no reconstrucción total de parciales.
- `app/Views/pages/admin/products/index.php` y `form.php`: añadidos identificadores de alcance para CSS, sin alterar contratos de formulario o lógica.
- `app/Views/partials/head.php`: nuevo CSS cargado únicamente en `$v2AdminStyles`; lista pública intacta. Sidebar no se tocó.
- Verificar listado, Nuevo producto y Editar: alineación, formulario, pestañas, Guardar/Cancelar, navegación y sidebar; sin confirmar cambios de datos reales.

## Rediseño visual sustancial de Productos — 2026-10-17
- Listado reconstruido: nuevo encabezado editorial, resumen del catálogo, buscador compacto y nueva tabla que agrupa nombre+SKU, mantiene precio/actualización/variantes/estado/editar. Vistas: `app/Views/partials/admin/products/index/{header,filters,table}.php`, `app/Views/pages/admin/products/index.php`.
- Editor de alta/edición: encabezado nuevo, breadcrumb, progreso y acciones rediseñados en `app/Views/partials/admin/products/form/header.php`. La estructura y lógica de tabs, quick-create de marcas/categorías, campos, POST/CSRF y flujo JS se conservan.
- Nuevos estilos exclusivos `/admin`: `public_html/assets/css/v2/pages/admin/catalog-redesign.css` y `product-editor-redesign.css`, registrados solo en `$v2AdminStyles` en `app/Views/partials/head.php`.
- El sidebar y los CSS del frontend público no fueron modificados. Los componentes que son modales internos o tabs especializados conservan lógica y su diseño puede necesitar una segunda pasada tras revisión real en navegador.
- Comprobar manualmente `/admin/products`, `/admin/products/new`, `/admin/products/{id}/edit`, pestañas, validación y creación rápida de marca/categoría sin guardar datos de prueba innecesarios.

## Dashboard rediseño estructural — 2026-10-17
- `app/Views/pages/admin/dashboard/index.php`: composición íntegramente reorganizada: encabezado, KPIs generales, alertas, pedidos y pendientes, rendimiento y rankings, stock y actividad. Preserva todos los parciales y datos del presenter existentes, sin consultas añadidas.
- Nuevo `app/Views/partials/admin/components/page_header.php`: componente reutilizable de encabezado con acciones vía propiedades escapadas.
- Nuevo `public_html/assets/css/v2/pages/admin/dashboard-v3.css`: estilo del Dashboard exclusivo del selector admin y del opt-in `.admin-v3-dashboard`, tarjetas, alertas, rejillas y responsive. Se registra solo en `$v2AdminStyles` de head.php.
- Sidebar, CSS y comportamiento de /public, controladores, repositorios, base de datos y JS sin modificar.
- Limitación: composición implementada con parciales funcionales ya existentes; paneles internos requieren inspección visual real para validar acabado y conflictos con CSS legacy. No se han realizado clicks autenticados ni pruebas visuales automatizadas.

## Dashboard 2026 — Contrato visual v1.0, densidad equilibrada (2026-10-17)
- `app/Views/pages/admin/dashboard/index.php`: nueva composición compacta de cuatro métricas principales, pedidos, atención y tareas, métricas secundarias, tendencia, destacados/clientes, stock/actividad. No se cambian fuentes de datos del presenter, rutas ni eventos.
- `public_html/assets/css/v2/pages/admin/dashboard-v3.css`: reemplazada la propuesta anterior por una versión sobria de densidad equilibrada: encabezado 24px/600, títulos 15px/600, métricas con superficie única y divisores internos, alertas en lista compacta, sin subtítulos decorativos. Se conservan el sidebar y CSS público intactos.
- `app/Views/partials/admin/components/metric_card.php`: nuevo componente PHP reutilizable para KPI, con variante enlace y subtítulo opcional; `app/Views/partials/admin/dashboard/kpis.php` pasa a componerlo. Preserva props, etiquetas, enlaces y datos ya preparados.
- Visual pendiente: revisar en Chrome/Firefox escritorio y ventana angosta, actividad reciente y tendencia sin datos; build gate no demuestra aprobación estética.

## Dashboard — simplificación final de jerarquía 2026-10-17
- `app/Views/pages/admin/dashboard/index.php`: encabezado «Resumen general» sin accesos redundantes; cuatro KPI clave reutilizando los datos existentes; pedidos + alertas; rendimiento, tablas de stock y actividad. Se retiraron de la vista las tarjetas repetidas «Pendientes de hoy» y KPI secundarios; no se modificaron sus cálculos ni sus pantallas de destino.
- `public_html/assets/css/v2/pages/admin/dashboard-v3.css`: reescrito el CSS del Dashboard sin añadir una nueva capa. Tipografía más ligera, métricas agrupadas, alertas compactas, tablas y paneles de baja ornamentación, grillas responsive.
- Rankings de ventas y clientes se muestran sólo con datos. Se conservan los IDs y selectores del gráfico para no romper la hidratación existente; su estado vacío visual necesita validación en navegador.
- Mantiene datos, enlaces, sidebar y /public sin modificaciones. Sin pruebas autenticadas de clics y de diseño todavía.

## Limpieza visual de clases legacy del Dashboard — 2026-10-17
- Parciales de `app/Views/partials/admin/dashboard`: `pending_orders.php`, `low_stock.php`, `activity.php`, `featured_products.php`, `top_clients_30.php` y `trend.php` migrados de clases visuales globales `admin-card`/`admin-data-table`/`admin-panel-*`/Tailwind a clases `dv-*` exclusivas del Dashboard. Atributos de datos, IDs de integración JS, URLs y datos preparados no alterados.
- `public_html/assets/css/v2/pages/admin/dashboard-v3.css`: estilos autosuficientes para paneles, tablas, badges y actividad; la tabla de pedidos ya no tiene scroll vertical interno. Se muestran cinco filas de pedidos en el resumen.
- La hoja legacy `dashboard-trend.css` aún carga porque el gráfico SVG/controles usan selectores de integración existentes. Las otras hojas globales del administrador se conservan por sus consumidores; no se eliminaron clases compartidas de otras rutas.
- No se modificó sidebar, /public, lógica comercial ni DB. Inspección visual de navegador pendiente.

## Reinicio visual de /admin — 2026-10-17
- Solicitud corregida: NO eliminar estructura HTML, solo atributos de clase CSS y hojas exclusivas del Dashboard.
- Respaldo completo anterior a cambios: `C:\\xampp\\mi-pesca-backups\\dashboard_reset_20261017_183133` (vistas de Dashboard, `metric_card.php`, `head.php` y tres CSS).
- Se retiraron referencias en `app/Views/partials/head.php` a `dashboard.css`, `dashboard-trend.css`, `dashboard-v3.css`, y se eliminaron esos tres archivos del directorio de estilos. Las hojas compartidas permanecen por otras rutas.
- Se quitaron atributos `class` estáticos de la vista y parciales del Dashboard, conservando DOM, PHP, IDs, `data-*`, eventos, rutas y contenido. Clases dinámicas PHP y potenciales hooks `admin-row-link` / `hidden` preservados por seguridad; esto no representa limpieza absoluta de cada clase funcional.
- Antes de rediseñar, revisar renderización y selector JS del gráfico (estilos eliminados deliberadamente) y accesibilidad de la página sin diseño.

## Dashboard 2026 — Reconstrucción visual desde cero (2026-10-17)
- Contrato formal creado en `CONTRATO_VISUAL_ADMIN_2026.md` (v1.0, densidad equilibrada).
- Vista `app/Views/pages/admin/dashboard/index.php` reconstruida con DOM propio `mt-*` y usando los datos de `$dashboard_view`: indicadores, 5 pedidos, alertas, tendencia, stock y hasta 6 eventos; sin cambiar presenter, controlador ni DB.
- CSS nuevo y aislado `public_html/assets/css/v2/pages/admin/dashboard-2026.css`, cargado solo en admin desde `app/Views/partials/head.php`.
- Reparado hook de SVG `.tframe` en `app/Views/partials/admin/dashboard/trend.php`; `public_html/assets/js/admin/dashboard/trend_draw.js` presenta estado vacío cuando todos los valores son cero, preservando las actualizaciones del endpoint.
- Se conserva sidebar. Build gate completo correcto. Validación visual autenticada NO completada: captura desde aplicación remota seleccionó una ventana diferente del navegador; por lo tanto no afirmar que se validó render real.
- Pendiente: comprobar screenshot de `/admin` en Firefox autenticado y corregir cualquier defecto visual, también responsive. No publicar a producción antes de esa verificación.

## Dashboard 2026 — implementación directa de composición acordada (2026-10-17)
- `app/Views/pages/admin/dashboard/index.php`: encabezado contextual, 4 KPI principales, pedidos y alertas, resumen compacto de tareas del día en el mismo panel de atención, métricas secundarias discretas, tendencia con vacío, stock y actividad. Mantiene presenter, data hooks y rutas.
- `public_html/assets/css/v2/pages/admin/dashboard-2026.css`: REEMPLAZADA hoja del Dashboard completa (sin nueva capa), tokens visuales v1.0, densidad equilibrada, tablas y estados semánticos, secciones compactas y media queries. Aislamiento por `body[data-page^=admin] .mt-dashboard`.
- Regla de anchura: la vista usa width/max-width 100%, `min-width:0`, grid `minmax(0,...)`, recorte de desbordamiento solo en `.mt-dashboard` y desplazamiento horizontal interno de tabla únicamente en móvil; no se modifica geometría del shell ni sidebar.
- No cambia lógica ni frontend público. Falta inspección visual autenticada independiente y responsive (no dar por aprobado sin captura de la vista real).

## Corrección del desbordamiento horizontal de Dashboard — 2026-10-17
- Causa: `app/Views/pages/admin/dashboard/index.php` colocaba `<main class="mt-dashboard">` directamente bajo `body`, sin el contenedor `.admin-surface` requerido por `admin-shell.css` para aplicar compensación por sidebar y gutters.
- Corrección: se envolvió el `<main>` completo con `<div class="admin-surface">`; mantiene el layout compartido original. Sin tocar sidebar, hoja del shell, otros módulos, lógica o /public.
- No se agregó ningún overflow o desplazamiento compensatorio extra.

## Unificación CSS — etapas 1 y 2 (documentación)
- PLAN_CSS_UNIFICADO.md: plan maestro vivo.
- docs/AUDITORIA_CSS_2026.md: inventario estático, alcance y riesgos.
- docs/AUDITORIA_CSS_2026.json: resultados cuantificados y archivos con posibles utilidades Tailwind.
- docs/ARQUITECTURA_ADMIN_V3.md: arquitectura, normas y checklist por módulo.
- scripts/dev/audit_css_unification.py: auditoría reproducible.
- Sin cambios en archivos CSS cargados, vistas, lógica, sidebar o público. Esta etapa es documental y diagnóstica.

## Migración CSS unificado — avance 2026-10-17
- app/Views/partials/head.php: carga admin-v3/tokens.css, admin-v3/components/common.css y admin-v3/pages/dashboard.css en admin; retira referencia a v2/pages/admin/dashboard-2026.css.
- app/Views/pages/admin/dashboard/index.php: migración clases mt-* a av3-*, preservando DOM/IDs y hooks JS.
- public_html/assets/css/admin-v3/{tokens.css,components/common.css,pages/dashboard.css}: nuevo sistema administrativo.
- docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json, scripts/dev/audit_css_dependencies.py: clasificador de clases y coincidencias en JS.
- public_html/assets/css/v2/pages/admin/dashboard-2026.css: archivo viejo desconectado, pendiente de eliminación verificada.
- Sin cambios de apariencia pública previstos; no hay validación visual autenticada.

## Dashboard V3 — limpieza adicional 2026-10-17
- Eliminado public_html/assets/css/v2/pages/admin/dashboard-2026.css después de verificar que head.php carga exclusivamente admin-v3/pages/dashboard.css.
- Agregado scripts/dev/verify_admin_v3_dashboard.py con aserciones de CSS único, contenedor admin-surface, prefijo av3 y hooks preservados.
- El build gate general había aprobado la migración V3 antes de esta eliminación; verificación focal posterior: ADMIN_V3_DASHBOARD_CONTRACT_OK.

## Unificación CSS administrativa — consolidación 2026-10-17
- Nuevas hojas admin-v3/pages/: orders.css, products.css, catalog.css, product-editor.css, billing.css.
- Se eliminaron ocho hojas CSS v2 administrativas tras incorporar sus reglas en los cinco archivos nuevos. Cargas corregidas en app/Views/partials/head.php.
- Se migraron a clases av3 algunos elementos de los rankings condicionales del Dashboard.
- Eliminar clases Tailwind de las plantillas y retirar bridges sigue pendiente. No se cambió el frontend público ni el sidebar.

## Continuación 2026-10-17
- Confirmadas 18 hojas administrativas cargadas y sin referencias ausentes.
- Diez hojas admin-v3 preexistentes verificadas; se trasladaron además tres CSS exclusivos de admin a admin-v3/components sin alterar ni una regla (ui-admin-compat, legacy-bridge, foundations). Se desconectaron y retiraron sus antiguos archivos v2.
- Quedan cinco hojas v2 compartidas por público/admin; NO se movieron para evitar regresiones públicas.
- Tailwind CDN y clases utilitarias siguen activos; falta migración efectiva de plantillas y dependencias JS.

- Se actualizó scripts/build/admin_css_scope.php para comprobar foundations.css en admin-v3 tras su traslado (la primera ejecución del gate detectó ruta antigua).

## Continuación 2026-10-17 — limpieza Tailwind en plantillas y módulos JS
- Migradas las últimas tres plantillas PHP identificadas con utilidades Tailwind: partials/flash_inline.php, partials/catalogo/active-filter.php y pages/auth/reset_password.php.
- Añadido css/v2/components/no-tailwind-public.css a las cargas pública y administrativa para estilos semánticos equivalentes; CSS público visualmente pendiente de validar al final.
- Migrados los elementos dinámicos de recent_searches.js y ui/loading_overlay.js a clases site-*, incluidos estados de visibilidad. No se eliminó aún el CDN global: siguen consumidores JS administrativos.
- audit_css_dependencies.py ahora reporta 0 candidatos en declaraciones class de vistas PHP (alcance de ese escáner). JS conserva clases y selectores utilitarios no renombrados; audit_js_tailwind_classes.py listó 66 parejas archivo/clase candidatas.
- No declarar ausencia completa de Tailwind mientras exista carga global y JS sin migrar.

## Continuación 2026-10-18 — depuración de CSS de compatibilidad
- Auditoría de los tres estilos de compatibilidad: legacy-bridge.css (70 reglas, 72 clases), ui-admin-compat.css (54 reglas, 26 clases), foundations.css (28 reglas, 46 clases).
- Auditadas clases consumidas en plantillas PHP y módulos JavaScript; identificadas 17 reglas simples sin referencias en ui-admin-compat.css y retiradas con respaldo en C:\xampp\mi-pesca-backups.
- No se eliminaron legacy-bridge.css ni foundations.css: conservan consumidores activos o selectores compuestos que requieren validación.
- Scripts reproducibles: scripts/dev/audit_compat_classes_extended.py, audit_compat_removal_candidates.py, prune_unused_admin_css_rules.py y verify_css_unification_integrity.py.
- La herramienta de integridad confirmó 20 CSS administrativos y 37 CSS públicos, sin duplicados ni referencias rotas; CDN Tailwind ausente. Barrido amplio de utilidades identificadas: 0 coincidencias. Una búsqueda estática no equivale a validación visual/funcional.
- La eliminación de compatibilidad legacy completa sigue pendiente de migración de sus consumidores; no declarar terminado el proyecto.

## Control de regresión CSS — 2026-10-18
- Se agregó scripts/build/css_unification_contract.php al gate general en scripts/build/gate.php.
- El contrato verifica: ausencia de CDN Tailwind, existencia de todas las hojas registradas en admin y público, ausencia de rutas duplicadas y presencia del layout admin-v3.
- Prueba individual: [css-unificado] OK. La validación visual final sigue pendiente.

## Mapa de consumidores CSS — 2026-10-18
- docs/MAPA_CONSUMIDORES_LEGACY_CSS.json identifica coincidencias de clases en PHP/JS para las tres hojas antiguas. Precaución: análisis léxico conservador, puede incluir coincidencias no funcionales; no utilizar como criterio único para borrar selectores.
- Gate completo aprobado después de retirar 17 reglas simples y agregar contrato CSS: CSS_UNIFIED_GATE_OK.
- Riesgo pendiente: legacy-bridge.css y foundations.css tienen dependencias distribuidas; antes de retirarlas, migrar componentes reales de sus vistas y comprobar estados.

## CSS público separado por dominio — 2026-10-18
- Reubicadas 30 hojas exclusivas del público a public_html/assets/css/public/{pages,layouts,components}, sin cambiar las declaraciones ni el orden registrado en head.php.
- Se dejó copia de seguridad previa bajo C:\xampp\mi-pesca-backups. No se modificó su presentación intencionalmente.
- Hoja compartida en css/shared y hojas administrativas en css/admin-v3; subsisten CSS legacy y compatibilidad, aún no eliminados.
- Auditoría de rutas resueltas: 20 admin y 37 público, sin faltantes. Gate general en curso al registrar este avance.

## Estructura de carga limpia — 2026-10-18
- CSS exclusivos del público: 30 hojas ubicadas bajo css/public/, respaldadas y verificadas por build gate.
- Arrays de carga renombrados a $adminStyles/$publicStyles; nombre obsoleto home-hero-v2.css reemplazado por home-hero.css sin cambio de reglas.
- Verificados contrato CSS, integridad de referencias y aislamiento de foundations.
- Aún quedan estilos legacy dentro del CSS administrativo y app.css para compatibilidad pública. No eliminados sin comprobación funcional final.

## Limpieza de CSS públicos y agregadores V2 — 2026-10-18
- La hoja de edición de direcciones, antes cargada desde AddressesController.php en css/v2/pages/public, se migró a css/public/pages/account-address-edit.css y se actualizó su controlador.
- Se eliminaron los agregadores sin consumidores: css/v2/index.css, css/v2/admin.css, css/v2/public.css. Se dejó copia en C:\xampp\mi-pesca-backups.
- No se modificaron controladores salvo la ruta estática de CSS; sin cambios de negocio.
- Se mantiene pendiente revisar visualmente /public y admin, así como retirar compatibilidad administrativa cuando sus consumidores estén migrados.

## Consolidación de estructura (2026-10-18 00:37)
- Carpetas CSS operativas: css/shared, css/public y css/admin-v3.
- Módulos compartidos importados desde css/shared; 31 hojas públicas trasladadas a css/public, incluida edición de direcciones desde AddressesController.
- CSS público principal movido a css/public/app.css e importación de Mi Cuenta a css/public/components/account.css. Se verificaron cuatro imports relativos.
- Se removió la importación pública del viejo paquete admin y sus cuatro archivos, sin consumidores directos adicionales.
- Tres agregadores v2 obsoletos eliminados, con respaldos en C:\xampp\mi-pesca-backups; carpeta css/v2 sin archivos activos.
- Los estilos administrativos legacy-bridge y foundations no están eliminados: todavía tienen consumidores. Las 17 reglas sin referencias de ui-admin-compat se retiraron con respaldo.
- Eliminar nombres v2 y el CDN Tailwind no garantiza paridad visual: la revisión funcional y visual de rutas públicas y admin sigue siendo requisito de cierre.

## Consolidación final de compatibilidad CSS (2026-10-18)
- Se consolidaron ui-admin-compat.css, legacy-bridge.css y foundations.css en una sola hoja administrativa: public_html/assets/css/admin-v3/components/admin-components.css. Se preservaron orden y contenido del CSS para no alterar la cascada.
- Se eliminaron las tres hojas de carga anterior y se actualizó app/Views/partials/head.php.
- Se actualizó scripts/build/admin_css_scope.php para exigir carga exclusiva y única de la nueva hoja.
- Se auditó la repetición de selectores por archivo: docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json. NO se borraron reglas repetidas que pueden ser correcciones intencionales de cascada.
- Atención: consolidar físicamente estos archivos NO implica que las clases legacy internas hayan desaparecido. Siguen existiendo selectores admin-* y otros contratos en uso. No declarar completada la migración semántica ni la validación visual.
- No crear ZIP adicional: cambios hechos directamente con Desktop Commander. Respaldos previos en C:\xampp\mi-pesca-backups.

## Etapas 4 y 5 — limpieza de clases (2026-10-18 00:58)
- Migrados 69 nombres de clases de presentación admin-* hacia av3-* en 117 archivos CSS/PHP/JS. Mapa: docs/MIGRACION_CLASES_ADMIN_V3.json.
- Conservado admin-surface: contrato estructural del sidebar/layout, no clase legacy arbitraria.
- Mantener btn, badge, input y otras clases base compartidas como contratos existentes hasta validar componentes visualmente; renombrarlas sin transferir sus estilos rompería la interfaz.
- Comprobación exhaustiva de 69 nombres antiguos: cero referencias remanentes en código PHP/JS/CSS de la aplicación. Prueba scripts/build/admin_classnames_contract.php integrada en el gate.
- Auditoría de selectores sin consumidores y duplicados: scripts/dev/audit_admin_v3_dead_selectors.py, docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json y scripts/dev/audit_exact_css_duplicates.py. No se borraron reglas de cascada cuyo efecto no se pudo demostrar nulo.
- Se resguardó el estado previo a la migración bajo C:\xampp\mi-pesca-backups.
- Validación visual integral expresamente diferida; cualquier diferencia se tratará como regresión.

## Corrección de regresión visual del sidebar — 2026-10-18
- La migración automática de clases admin-* a av3-* creó una colisión entre av3-label/av3-link del sidebar y las clases de formularios/enlaces en admin-components.css.
- Se aislaron las clases del sidebar: av3-sidebar-label, av3-sidebar-link y av3-sidebar-icon en su vista y en admin-v3/layout.css. Sin rediseñar el sidebar.
- Se añadió scripts/build/sidebar_style_contract.php al build gate. Respaldo previo en C:\xampp\mi-pesca-backups. Validación visual pendiente.

## Rediseño completo /admin/analytics — 2026-10-19
- Se reconstruyó app/Views/pages/admin/analytics/index.php con encabezado y contenedor propios.
- Se creó app/Views/partials/admin/analytics/overview.php, sin reutilizar el antiguo parcial sin estilos de Dashboard.
- Se creó public_html/assets/css/admin-v3/pages/analytics.css y se registró en head.php.
- Presentación: indicadores KPI, lectura secuencial de conversión, seis informes en tablas con estados vacíos y responsive.
- Se conservaron las claves originales de $navigationAnalytics y enlaces existentes; no se modifica el backend ni se inventan datos.
- Inspiración de estructura: cuadros de métricas y paneles de Shopify Analytics y Stripe Dashboard; sin copiar logos/identidad.
- No se generó ZIP. Validación de navegador queda pendiente de revisar visualmente.

## Design System Tabler (2026-10-19)
- Creado css/admin-v3/components/design-system.css con componentes av3-ui-* exclusivos del admin.
- Integración inicial en Analíticas; no se migraron todavía Productos ni Pedidos.
- Referencias y contrato: docs/ADMIN_DESIGN_SYSTEM_TABLER.md.
- Sin ZIP ni framework nuevo. Validación visual pendiente.

## Layout fluido 2026-10-19
- Tres contenedores opcionales en admin-v3/layout.css (operativo/detalle/formulario).
- Analíticas usa modo operativo, KPI y tablas fluidos, panel de conversión y resumen lateral.
- Sin cambiar sidebar ni vistas restantes. Sin ZIP.

## Auditoría de encabezados /admin (2026-10-19)
- Ejecutado scripts/dev/audit_admin_headers.py sobre 69 vistas PHP administrativas en 19 módulos y 95 referencias literales de rutas /admin.
- Inventarios: docs/AUDITORIA_ADMIN_ENCABEZADOS.md y docs/AUDITORIA_ADMIN_ENCABEZADOS.json.
- Esta etapa es solo inventario estático: falta inspección de parciales y navegación real antes de implementar/migrar el componente header. No se modificó el diseño ni el sidebar.

## Encabezado unificado /admin — piloto (2026-10-19)
- Auditoría estática: 69 vistas en 19 módulos y 95 referencias de rutas. Inventario en docs/AUDITORIA_ADMIN_ENCABEZADOS.md/.json.
- Componente PHP compartido en app/Views/partials/admin/page_header.php y CSS exclusivo en admin-v3/components/page-header.css.
- Piloto migrado: Pedidos listado, Pedido detalle, Auditoría SEO y Analíticas. Breadcrumb jerárquico, título, descripción y acciones existentes preservadas.
- Pendiente: inspección de rutas reales y migración de Dashboard, Facturación, Productos, Usuarios y restantes parciales, sin introducir encabezados duplicados. Sidebar intacto. Sin ZIP.

## Encabezados y breadcrumbs unificados (continuación 2026-10-19)
- Cobertura: 67 vistas administrativas completas usan app/Views/partials/admin/page_header.php, según scripts/build/admin_page_header_contract.php. Dos vistas de Atributos son fragmentos internos sin encabezado propio.
- Se eliminaron los parciales antiguos app/Views/partials/admin/breadcrumbs.php y app/Views/partials/admin/components/page_header.php y se retiraron las 40 llamadas al breadcrumb anterior. Cero referencias en vistas.
- Facturación: subnavegación desplazada debajo del encabezado en 19 vistas; se conservaron enlaces y estados del componente.
- Eliminadas 8 reglas CSS obsoletas específicas de encabezados (Dashboard y editor de Productos).
- Validaciones: build gate completo OK tras traslado de navegación; contratos focales de CSS/encabezado nuevamente OK tras limpieza de reglas.
- Backups locales de archivos previos: C:\xampp\mi-pesca-backups (sin paquetes incrementales de entrega).
- Pendiente: inspección visual de las rutas en navegador, especialmente el editor de Productos, Auditoría y Facturación; el build gate no equivale a validación visual.

## Layout maestro del administrador (2026-10-20)
- Creado app/Views/layouts/admin.php: un único aside, encabezado blanco y área de contenido.
- render_page() en app/Support/helpers.php captura la salida de vistas /admin/* y la coloca dentro del layout, conservando el contenido y todas las variables actuales.
- El parcial page_header.php registra el encabezado durante el renderizado maestro en lugar de imprimirlo dentro del módulo; subnav.php evita duplicar el aside mientras se captura.
- CSS nuevo en public_html/assets/css/admin-v3/components/master-shell.css, cargado último en admin para mantener el shell como propietario del layout. Gutter global y tres modos de ancho preservados.
- Contrato scripts/build/admin_master_layout_contract.php integrado en build_gate.
- Sin ZIP. Validación visual real del navegador pendiente: comprobar Dashboard, editor de Productos, Control fiscal, Pedidos y Auditoría.

## Page Header opción B (2026-10-20)
- Establecido diseño profesional equilibrado para todo /admin: breadcrumb con separador chevron, título 24 px peso 600, descripción opcional 13 px y acciones funcionales a la derecha.
- Solo modificados el componente compartido page_header.php, su CSS, padding del master-shell y texto del encabezado de Dashboard.
- Dashboard: subtítulo de actividad y rendimiento; retirado texto estático de 'Estado de la tienda' por carecer de señal real.
- Aside, contenido y backend inalterados. Sin ZIP. Validación visual pendiente.

## Page Header compacto horizontal (2026-10-20)
- Implementado sobre el componente único `app/Views/partials/admin/page_header.php`: título de 20 px, breadcrumb debajo y acciones a la derecha en una misma barra; se elimina la descripción de la barra, sin borrar los datos de las vistas.
- CSS aislado en `admin-v3/components/page-header.css`. Padding vertical maestro reducido a 12 px arriba y abajo (`master-shell.css`), altura nominal cercana a 60–64 px; responsive conserva acciones y breadcrumbs.
- El aside, el backend y las 67 vistas con encabezado común no cambian. Sin ZIP. Requiere validación visual de navegador.

## Dashboard mobile-first — 2026-10-20
- Rediseñada la presentación de `/admin` bajo contrato visual, sin cambios de backend: cuatro KPIs independientes, pedidos y stock responsive, alertas, rendimiento y actividad reciente.
- Eliminada del Dashboard la franja de métricas secundarias de usuarios/inactivos y la segunda franja duplicada de pendientes. Rankings solo si hay datos; conservados IDs y hooks del gráfico.
- Reemplazado por completo `public_html/assets/css/admin-v3/pages/dashboard.css`: no se añadió CSS encima del legado. Nuevo CSS único del módulo, responsive desde móvil con cambios a tablas a partir de 700px y paneles de dos columnas a partir de 1050px.
- Añadido `scripts/build/dashboard_responsive_contract.php` al build gate. Respaldo local fuera del proyecto en `C:\xampp\mi-pesca-backups`.
- La limpieza de CSS legacy es específica del Dashboard; no se eliminaron componentes de otros módulos. Pendiente validación visual real a 390/768/1024/1600px con aside expandido/contraído.
- No se generó ZIP incremental.

## Navegación móvil overlay del administrador — 2026-10-20
- En <=767px se elimina el estado visual de aside comprimido: el contenido ocupa todo el viewport (offset 0).
- Botón hamburguesa en el Page Header compartido; abre el aside como drawer fijo por encima del layout con backdrop; no desplaza tarjetas, tablas ni header.
- Cierre mediante backdrop, Escape y navegación; botón con aria-controls/aria-expanded, foco inicial y ciclo de Tab en el drawer. El control de escritorio se conserva a partir del breakpoint.
- Cambios: layouts/admin.php, sidebar_v2.php, admin-v3/components/master-shell.css, js/admin/core/sidebar.js. Contrato scripts/build/admin_mobile_drawer_contract.php añadido al gate.
- Ningún ZIP incremental. Validación visual manual a 363px y escritorio pendiente.

## Dashboard definitivo — implementación 2026-10-20

- Alcance: únicamente `/admin` (presentación). No se modificaron controlador, SQL, queries, rutas, estados de negocio ni JS de tendencia.
- Presentación mobile-first y `container-type:inline-size` en `.av3-dashboard`: composición basada en espacio real disponible, no en el ancho del navegador.
- Jerarquía final: cuatro KPIs; pedidos y avisos (2/3 + 1/3 con espacio útil >=1050 px); stock y actividad (mitades según espacio); rendimiento comercial; rankings condicionales al final.
- Móvil: KPIs 2x2, avisos antes que pedidos, pedidos y stock como filas compactas enlazadas y accesibles, sin tabla horizontal. A partir de 680 px útiles se activan KPIs 4 columnas y tablas. A partir de 980 px se pueden mostrar stock/actividad lado a lado; a partir de 1050 px también pedidos/alertas 2/3 + 1/3.
- Paleta derivada de tokens del admin: primario #006B6B, fondo #F6F8FA, superficies #FFFFFF, texto #182B39, secundario #647786, borde #DFE5E9; semántica limitada para stock crítico, advertencias y fallas.
- Tipografía 14 px operativa, 13 px etiquetas, 12 px auxiliares y 28 px KPI. Paneles de 11 px de radio, 18 px padding en escritorio, filas con división sutil, alineación tabular numérica.
- Se eliminaron del markup y CSS la franja de pendientes repetidos y cálculos no usados de métricas extra. Se conservan todos los hooks DOM y los controles del gráfico.
- Rendimiento: se mantiene el JS existente, que oculta SVG sin actividad; el CSS compacta el estado vacío y suprime el título interno redundante sin esconder filtros ni controles.
- `public_html/assets/css/admin-v3/pages/dashboard.css` fue sustituido como hoja única del módulo, no se sumaron override CSS legacy nuevos. Se corrigió el padding heredado de `.av3-card`.
- Prueba permanente `scripts/build/dashboard_responsive_contract.php` actualizada con ausencia de bloques duplicados, orden de elementos y adaptación con container queries.
- Respaldo privado de archivos anteriores bajo `C:\xampp\mi-pesca-backups\dashboard_final_design_20261020_020843`. No se creó ZIP incremental de entrega.
- **Pendiente:** validación visual real en Firefox autenticado (363/768/1024/1600 px) y retoques focales si aparecen colisiones externas. La aprobación técnica no sustituye la revisión visual.

## Dashboard: columnas independientes y filas móviles (2026-10-20)
- Vista `app/Views/pages/admin/dashboard/index.php`: cuatro KPIs; columnas independientes reales: principal [Pedidos por gestionar, Stock bajo] y secundaria [Requiere atención, Actividad reciente]. Rendimiento comercial y rankings condicionales permanecen abajo.
- CSS `public_html/assets/css/admin-v3/pages/dashboard.css`: eliminado el grid de filas acopladas (`.av3-work`/`.av3-bottom`). En escritorio ancho (viewport >=1200px) dos columnas 7/5 con tarjetas apiladas independientemente; no se fijan alturas ni se dejan huecos intermedios.
- KPI: cuatro columnas desde 900px de viewport como regla robusta; dos columnas en móvil. La regla anterior basada solo en contenedor no se activaba de manera fiable en el navegador del usuario.
- Móvil: el orden es KPIs -> Atención -> Pedidos -> Stock -> Actividad -> Rendimiento. Las columnas usan `display:contents` y `order` sin duplicar datos ni elementos.
- Filas móviles de pedidos rehechas en tres niveles: [pedido + total], [cliente + fecha], [estados]; enlace único a detalle conservado.
- Tablas: `.av3-orders` y `.av3-stock` tienen `container-type:inline-size`; tabla si el panel supera 620px, filas compactas si es más angosto; fallbacks por viewport en escritorio. Sin `!important` añadidos.
- Actualizado `scripts/build/dashboard_responsive_contract.php` para evitar regresión al layout anterior. CSS analizado mediante tinycss2 sin errores de sintaxis.
- Se preservan JS, rutas, valores, Page Header, aside y componentes administrativos ajenos al Dashboard. Respaldo local bajo `C:\xampp\mi-pesca-backups\dashboard_columns_20261020_023120`; no se creó ZIP incremental.
- Validación visual pendiente: capturas en Firefox con 363px y 1650px tras recarga forzada.

## /admin/orders — listado operativo rediseñado (2026-10-20)
- UI terminada: tabs de trabajo `Todos`, `Por cobrar`, `Para preparar`, `En camino`, `Finalizados` con recuentos del backend; barra de búsqueda + orden; filtros avanzados desplegables para estado, método, fechas y tamaño de página; filtros activos removibles; estado vacío y paginación con anterior/siguiente.
- Columnas de tabla desktop: pedido y fecha/hora, cliente, estado del pedido, estado de pago con método debajo, etapa de entrega y total; cada registro permite navegar al detalle.
- Móvil: enlace accesible para cada pedido y tres filas de información (número/importe; cliente/fecha; estado/pago). Vista adaptada mediante container queries sobre el ancho útil. Anchos medios omiten columna Entrega antes de pasar a filas móviles.
- Backend: `AdminOrderService::listForAdmin` ahora cuenta resultados reales y pagina 25/50; admite vista, búsqueda, estado, método de pago, fechas, orden y página, con valores de entrada parametrizados o validados. El antiguo `LIMIT 200` fijo se eliminó.
- Datos: último estado de `payments` y de `order_shipments` se consultan independientemente del estado del pedido. Falta de registro se diferencia de pago confirmado. Query de envíos adaptada al esquema real (no hay `order_shipments.id`; se usa `updated_at`).
- ViewModel presenta etiquetas y tonos semánticos españoles, con estados de pago y entrega separados. Cambios de estado, cobros, ARCA, inventario, envíos y resto de la lógica transaccional siguen exclusivamente en el detalle sin cambios.
- Reescritos `app/Views/pages/admin/orders/index.php`, `admin-v3/pages/orders.css` (CSS previo sustituido, sin legacy nuevo), `js/admin/orders/index.js` (clic de fila más accesible, orden automático). Se eliminó el botón redundante "Volver al dashboard" del Page Header.
- Pruebas reales solo lectura: `scripts/dev/orders_readonly_smoke.php`, 14 pedidos en base local, vistas all/collect/prepare, búsqueda exacta y sin resultados; `scripts/dev/orders_render_smoke.php` verifica HTML completo y misma cantidad en móvil. Ninguna fila se modificó.
- Contrato permanente `scripts/build/orders_index_contract.php` integrado a `scripts/build/gate.php`; verifica filtros, paginación, datos independientes, ausencia de CSS viejo, JS y responsive.
- El resto del layout, aside, Page Header y demás vistas permanecen intactos. Respaldo privado de los archivos anteriores en `C:\xampp\mi-pesca-backups\admin_orders_redesign_20261020_025713`. Sin ZIP incremental.
- **Pendiente:** revisión visual en Firefox, con la sesión autenticada y anchos 363/768/1024/1600px. La validación de sintaxis, consulta y HTML no equivale a aprobación visual.

## Rediseño SEO mobile-first y editores SEO (2026-10-20)

- Auditoría: actualizado app/Views/pages/admin/seo/auditoria.php y sus parciales filters.php/results.php. Nuevo resumen de score para productos activos, filtros rápidos + avanzados, listado compacto móvil, tabla desktop y diagnóstico expandible.
- Hoja única específica de SEO: public_html/assets/css/admin-v3/pages/seo.css, registrada en app/Views/partials/head.php. Se quitaron las clases y controles heredados del listado; se reutilizan componentes administrativos av3-ui, tokens V3 y namespace av3-seo.
- Lógica: SeoAuditQuery reutiliza el cálculo SQL del score para indicadores globales, y corrige la búsqueda por nombre/SKU/slug usando tres parámetros PDO distintos (error HY093 previo). SeoAuditController entrega el resumen; el ViewModel construye URLs, filtros, paginación y separa incidencias evaluadas del contenido complementario.
- Editores: products/tab_seo.php y seo/entity_panel.php con paneles av3-seo-editor, avanzados desplegables y limpieza focal de presentación obsoleta. Se mantienen nombres de campos y data-seo-* de todos los flujos de guardado. product_blocks.js permite subir/bajar con botones táctiles y teclado; product_score.js, entity_score.js y ProductFormService armonizan tonos 65/85.
- El listado deja de autoenviar el formulario después de 450 ms: la búsqueda se aplica por Enter o botón.
- Verificaciones: contrato scripts/build/seo_admin_contract.php incorporado al gate; smoke de solo lectura scripts/dev/seo_readonly_smoke.php (25 productos reales, 19 activos, filtros y HTML correctos); lint PHP y Node OK; análisis tinycss2 sin errores; build gate completo OK.
- Inspección visual desde HTML renderizado y estilos reales de XAMPP: Firefox headless 363/390 px, Edge headless 768/1600 px. Revisión interactiva en Firefox autenticado pendiente para cierre visual.
- Respaldo privado fuera del proyecto: C:\xampp\mi-pesca-backups\seo_mobile_first_20261020_134241. Sin ZIP incremental.

## /admin/seo/auditoria — compactación final y vista previa en columna (2026-10-20)

- Simplificado Buscar y filtrar: título + filtros rápidos en una franja compacta con Limpiar; controles principales y avanzados con grillas adaptativas y botones alineados, sin bloque vacío para Aplicar. Mobile mantiene búsqueda/orden compactos.
- Desktop: eliminada la expansión de diagnóstico de las filas y agregada columna permanente "Vista previa en buscadores" con título público enlazado, URL y descripción real o aviso de ausencia. Todas las incidencias evaluadas aparecen en chips; retirado el resumen de Puntos/Especificaciones/FAQ que duplicaba contenido y no influye en score.
- Tablet/móvil: listado propio con incidencias principales, acción Editar SEO y diagnóstico breve desplegable para información adicional y vista previa; sin scroll horizontal de tabla.
- Vista compartida de SERP en app/Views/partials/admin/seo/auditoria/serp_preview.php; diagnostic.php queda solo para móvil. Actualizado results.php, ViewModel (bandera has_meta_description), CSS del módulo reestructurado sin overrides acumulados y contrato seo_admin_contract.php.
- Verificación: smoke de base y HTML de solo lectura OK (25 productos, 19 activos); contrato SEO, lint PHP y parseo CSS OK. Capturas de Firefox headless 363/390px y Edge 768/1600px inspeccionadas. Las pruebas visuales no sustituyen un recorrido interactivo final autenticado.
- Respaldo privado antes de los cambios de columna: C:\xampp\mi-pesca-backups\seo_preview_column_20261020_144516; respaldo previo de la compactación: C:\xampp\mi-pesca-backups\seo_compact_20261020_143836. Sin ZIP.

## 2026-10-20 — Marketing: correcciones funcionales y nuevo listado de campañas

- **Búsquedas**: corregidos placeholders repetidos en `MarketingCampaignRepository::listForAdmin` y `EmailQueueRepository::listQueue` (HY093 bajo PDO nativo). Consultas locales de solo lectura aprobadas.
- **Envíos**: `EmailBroadcastSchedulerService` excluye suscriptores previamente incorporados a cada campaña y bloquea finalización si quedan destinatarios elegibles sin encolar. `EmailQueueWorkerService` comprueba suscripción vigente y campaña no cancelada/finalizada antes del despacho. `EmailCampaignsController::cancel` cancela además la cola pendiente (no interrumpe un mensaje que ya fue entregado).
- **Seguridad**: vista previa `marketing_email/templates_show/preview.php` ahora usa iframe aislado (`sandbox` y `referrerpolicy`).
- **Diseño**: `/admin/marketing/campanas` rediseñado mobile-first con navegación de Marketing, filtros compactos, pestañas Todas/Activas/Inactivas, paginación SQL 25/50, tabla desktop, listado móvil, acciones directas y estado vacío. QR duplicados eliminados de la lista; la función QR sigue en el detalle.
- **Arquitectura**: `MarketingCampaignsIndexViewModel` concentra las URLs, totales, estados y navegación; nueva hoja `admin-v3/pages/marketing-campaigns.css` conectada en `head.php`; revisado `marketing_subnav.php`. Sin CSS legacy adicional ni cambios al público.
- **Pruebas nuevas**: `scripts/build/marketing_admin_contract.php` integrado al build gate y `scripts/dev/marketing_readonly_smoke.php`. Prueba de lotes transaccional externa en `C:\xampp\mi-pesca-backups\marketing_batch_readonly_transaction_test.php` (rollback completo). Capturas sintéticas de Edge 1600/768 y Firefox 390/363 en `C:\xampp\mi-pesca-backups\marketing_visual_20261020`; **no son pruebas del navegador autenticado**. 
- **Respaldos funcionales**: `C:\xampp\mi-pesca-backups\marketing_impl_20261020_151138`. Backend del listado: `C:\xampp\mi-pesca-backups\marketing_list_20261020_151647`. Sin ZIP.
- **Pendiente**: rediseño de formulario y detalle de Campañas y todas las subrutas Email (incluyendo editor); pruebas de envío reales solo en ambiente seguro de homologación, nunca en base/usuarios reales; validar visualmente autenticado.

- Limpieza adicional: eliminado parcial huérfano `app/Views/partials/admin/marketing_campaigns/index/help.php` (sin referencias activas; respaldo externo). Build gate **OK**: 792 PHP, 116 JS, todos los contratos. Capturas de estado vacío y filas sintéticas en Firefox 363/390 y desktop; no se enviaron correos.

## 2026-10-20 — Marketing: Nueva/Editar y Detalle de campaña (fase 2)

- Reescrita la presentación de `/admin/marketing/campanas/new` y `/edit`: información principal, ruta de destino, parámetros UTM completos, notas avanzadas colapsables y estado activo, con panel lateral de publicación en escritorio. Los diez nombres de controles existentes, CSRF, IDs, POST, validación y lógica de guardado quedaron intactos.
- El JS `public_html/assets/js/admin/marketing/campaigns.js` conserva Copiar/Toast y añade preview de enlace corto en vivo basado en el slug escrito; el slug generado automáticamente por backend no se simula antes de guardarlo. No se copian enlaces vacíos.
- Detalle `/admin/marketing/campanas/show`: eliminado breadcrumb manual duplicado; Page Header maestro con CSV/Editar, badge activo y ruta, link público/destino/UTM, QR original descargable bajo `details`, indicadores de visitas/usuarios/carrito/compras/ingresos, ratios/notas, serie diaria, rankings y actividad reciente.
- Rediseñados `show/{header,link_card,performance_card,diagnostics_card,recent_card}.php`; ViewModel referrer ahora expone tonos semánticos `neutral/warning/external` en vez de clases heredadas. Se preservan valores y enlaces preparados, no se cambian consultas ni modelos transaccionales.
- Hoja existente `public_html/assets/css/admin-v3/pages/marketing-campaigns.css` ampliada en secciones semánticas propias, sin archivos -v2 ni overrides legacy superpuestos; contenedor responsive habilitado en listado y formulario. Breakpoints según ancho útil, con grids de KPIs/diagnósticos y controles compactos.
- Pruebas focales nuevas: `scripts/dev/marketing_campaign_forms_smoke.php` renderiza nuevo, editar, detalle lleno y vacío con muestras (sin persistencia); `scripts/build/marketing_admin_contract.php` reforzado. Snapshots locales sintéticos Firefox 363/390/768 y Edge 1600, sin simular persistencia real.
- Respaldo previo: `C:\xampp\mi-pesca-backups\marketing_campaign_editor_20261020_153336`. Sin ZIP. **Pendiente:** interacción autenticada real y rediseño de `/admin/marketing/email/*`.

- Cierre técnico de la fase 2: **build gate completo aprobado** tras cambios finales (792 PHP, 116 JS, contratos Marketing/CSS/layout y regresiones). Smoke de Nuevo/Editar/Detalle completo-vacío, listado previo y sintaxis JS/CSS aprobados. Firefox headless 363/390/768 y Edge 1600px comprobados con datos simulados. La aprobación visual interactiva en Firefox autenticado continúa pendiente.

## 2026-10-20 — Email Marketing V3, fase 1: resumen, suscriptores y automatizaciones

- Migradas `/admin/marketing/email`, `/admin/marketing/email/suscriptores` y `/admin/marketing/email/automatizaciones` a presentación mobile-first `av3-marketing-email` con la hoja nueva `public_html/assets/css/admin-v3/pages/marketing-email.css`, registrada exclusivamente en estilos administrativos desde `app/Views/partials/head.php`.
- Agregado parcial de navegación secundaria `app/Views/partials/admin/marketing_email_nav.php`: Resumen, Suscriptores, Automatizaciones, Envíos, Cola y Plantillas. Se mantiene navegación superior Campañas / Email. No se tocaron las demás rutas (todavía heredadas).
- Resumen: retirados enlaces repetidos del Page Header, tarjetas anidadas, instrucciones permanentes y botón de procesar correos del resumen. Conserva estadísticas reales y rutas a herramientas; procesamiento manual accesible desde Cola. **No se ejecutó el worker**.
- Suscriptores: `EmailSubscriberRepository::countForAdmin` y `listForAdmin` con offset y orden estable; controlador pagina 25/50 y valida filtros; `MarketingEmailSubscribersViewModel` calcula rango/enlaces. Vista con filtros compactos, tabla escritorio, listado móvil y acción POST compartida (mantiene CSRF y nombres/id/status). Sin reescritura de consentimiento ni alteración de suscriptores.
- Automatizaciones: series y mensajes renderizados en filas claras, conservando estado, código, tiempo, asunto, plantilla y acciones independientes de activar/pausar; CSRF y rutas POST originales intactas. No se ejecutó `ensureDefaults()` durante pruebas para evitar modificaciones en DB.
- Contrato `scripts/build/marketing_email_v3_contract.php` integrado a `scripts/build/gate.php`, smoke `scripts/dev/marketing_email_v3_smoke.php`: consultas SQL solo lectura, resumen sintético, 25 contactos paginados sintéticos y automatizaciones sintéticas con pasos activos/pausados y serie vacía. CSS parseado con tinycss2 sin errores. Capturas Firefox (363/390/768) y Edge (1600) inspeccionadas, con URL correcta para navegación activa; son snapshots sintéticos y no sustituyen Firefox autenticado.
- Respaldo privado en `C:\xampp\mi-pesca-backups\marketing_email_phase1_20261020_160038`. No generar ZIP.
- **Pendientes:** Envíos masivos y detalle, Cola, Plantillas, editor de envío; UI y CSS heredados siguen presentes en esas rutas. Validación manual en Firefox autenticado de cada POST antes de aprobación operativa final.

- **Cierre técnico Email Marketing V3 fase 1:** build gate completo OK (796 archivos PHP, 116 JavaScript, contratos Marketing/Email V3/CSS/layout/checkout y regresión). Navegación activa comprobada en capturas a 363px; otras rutas de Email siguen pendientes de migración y de validación autenticada en Firefox.

## 2026-10-20 — Email Marketing V3, fase 2: Envíos, Cola, Biblioteca y Detalle de plantillas

- Migradas las vistas de `/admin/marketing/email/campanas`, `/cola`, `/templates` y `/templates/show` a `av3-marketing-email`, usando exclusivamente la hoja `admin-v3/pages/marketing-email.css` ya existente. Se eliminaron clases visuales heredadas, alturas fijas en tablas, encabezados duplicados y enlaces de navegación repetidos; se mantuvieron rutas, acciones, VM, CSS compartido y lógica de negocio.
- Envíos: tabla desktop con estado, enviados, aperturas, clics, compras e ingresos; filas móviles, detalle/editar y ayuda secundaria. `EmailCampaignRepository` permite `listWithStats(limit,offset)` y `countForAdmin()`. `EmailCampaignsController` pagina 25/50; `MarketingEmailCampaignsIndexViewModel` prepara tonos semánticos sin clases antiguas.
- Cola: indicadores por estado, filtro con búsqueda, estado, por página, listado y últimos 50 logs diferenciados. `EmailQueueRepository::countForAdmin` y `listQueue(...,offset)` permiten 25/50 resultados; `MarketingEmailQueueViewModel` expone tonos semánticos. La acción real `/cola/procesar` queda en un desplegable de advertencia, con POST y CSRF intactos; no se ejecutó.
- Plantillas: biblioteca 25/50 con acciones Ver/Usar/Eliminar (confirmación JS `data-confirm-delete-template` y CSRF), estado vacío y banner semántico. `EmailTemplateRepository::countForAdmin`, `listLite(limit,offset)`, `EmailTemplatesController::index` paginado y total verdadero. Detalle mobile-first, enlaces de navegación correctos (`/templates`), metadatos y vista previa `iframe srcdoc` aislada con `sandbox=""`, sin scripts.
- Componente de paginación único `MarketingEmailPagination` en ViewModels y parcial `marketing_email_pagination.php`. Acciones de plantillas compartidas en `marketing_email_template_actions.php`; no se creó CSS duplicado.
- Smoke `scripts/dev/marketing_email_v3_phase2_smoke.php`: DB local de solo lectura (actualmente sin registros en estas tablas), paginación SQL, render sintético lleno/vacío, logs con fallos, HTML escapado y acciones POST. Contrato `scripts/build/marketing_email_v3_phase2_contract.php` integrado al gate. Capturas sintéticas 390px Firefox y 1600px Edge de todas las vistas, incluida vista previa de plantilla, inspeccionadas.
- Respaldo: `C:\xampp\mi-pesca-backups\marketing_email_phase2_20261020_163032`; ningún ZIP.
- **Pendiente**: `/admin/marketing/email/campanas/new|edit|show` y editor avanzado (quedan legacy y requieren migración específica), prueba interactiva autenticada de acciones, optimización futura de agregados en `listWithStats` para catálogos con gran volumen.

- **Cierre técnico fase 2 Email V3:** build gate completo aprobado (801 archivos PHP, 116 JavaScript, contratos Marketing/Email fases 1–2/CSS/layout, checkout y regresiones). Smoke focal read-only y render lleno/vacío OK; CSS 156 reglas sin errores. Capturas Firefox 363/390 px y Edge 1600 px comprobadas. Sigue pendiente validación autenticada de controles y migración del editor avanzado de campañas de email.

## 2026-10-20 — Email Marketing V3: campaña masiva, editor y detalle (fase 3)

- Nuevo/Editar envíos: migrado Page Header + navegación Email, aviso compacto, formulario de configuración mobile-first y campos de audiencia condicionales (VIP/días de inactividad). Se conserva POST `/admin/marketing/email/campanas/save`, nombres e IDs para JS, CSRF y estados originales.
- Se reorganizó `campaign_form/{pre_form,form_block,post_form}.php`: Plantillas ahora se despliegan de forma nativa; se corrigieron atributos `form` duplicados en la aplicación de plantillas (antes podían apuntar al formulario equivocado); los formularios externos siguen fuera del formulario principal. Se trasladaron estilos `<style>` inline de placeholder/listas a `admin-v3/pages/marketing-email.css`. Los scripts y modales mantienen hooks históricos `id`, `data-*` y clases de estado usadas por el JS para prevenir regresiones; **la eliminación total de las utilidades internas del editor avanzado queda como limpieza posterior**, no confundir con una migración 100% libre de legacy.
- Detalle `campaign_show.php` reescrito: encabezado maestro, estado semántico, CTA Programar/Cancelar con advertencia de confirmación, KPIs, segmento/CTA, historial de compras y HTML del correo dentro de `iframe sandbox=""` con `srcdoc` escapado. El antiguo HTML crudo insertado directamente en el DOM administrativo fue retirado. ViewModel entrega `status_tone`/`notice.type` y traduce estados visibles de pedidos.
- JS: `campaign_editor/index.js` conserva módulos del editor y agrega filtro visual de audiencia sin cambiar backend; nuevo `admin/marketing/campaign_show.js` confirma acciones sensibles y se activa con `pageId=admin-email-campaign-show` en `app.js`.
- Test focal `scripts/dev/marketing_email_editor_smoke.php` sin persistencia ni envíos: nuevo/editar, todos los hooks, formularios externos, HTML escapado y estados con/sin compras. Contrato `scripts/build/marketing_email_editor_contract.php` incluido en el build gate.
- Navegador automatizado: `C:\xampp\mi-pesca-backups\marketing_email_editor_20261020_165541\browser_interaction.js` validó cambio de estado y horario, audiencia, Visual/HTML, herramientas y modales; `browser_actions.js` interceptó preview, abrió asistente de cards y selector de productos y rechazó ambas confirmaciones de programación/cancelación (**0 POST, 0 emails**).
- Capturas sintéticas Firefox móvil y Edge desktop almacenadas fuera del proyecto en `C:\xampp\mi-pesca-backups\marketing_email_editor_20261020_165541`. Respaldo de archivos originales en el mismo directorio; sin ZIP. Todavía falta validación final autenticada de guardado real/programación exclusivamente en ambiente seguro.

- **Cierre técnico fase 3 Editor Email:** build gate integral aprobado: 803 archivos PHP, 117 JavaScript, contratos administrativos/Marketing/Email editor, CSS/layout, UTF-8, checkout y regresiones; CSS 270 reglas sin errores. Render sintético, prueba de interacción de navegador y vistas responsive Firefox 363/390 y Edge 1600 OK. No se probaron guardado real, subida real ni envío efectivo; confirmaciones sensibles rechazadas sin POST. La limpieza de utilidades legacy internas que aún comparte el editor con su JavaScript queda registrada como trabajo posterior.

## 2026-10-20 — Email Marketing V3, limpieza focal del editor (fase 4)

- Respaldo previo a las modificaciones en `C:\xampp\mi-pesca-backups\email_editor_cleanup_20261020_183405`. Trabajo directamente en proyecto; sin ZIP y sin tocar `/public` del comercio.
- `campaign_form/form_block.php`: se extrajeron cuatro modales a `campaign_form/modals/{preview,send_test,card_builder,product_picker}.php`. El formulario principal pasó de 775 a aproximadamente 426 líneas. Se corrigió un `div` de la composición del editor sin cerrar, que mantenía involuntariamente elementos dentro del formulario y del contenedor de ancho limitado.
- `campaign_form/post_form.php`: aplica/copia de plantillas mantienen formularios POST externos con CSRF; los cuatro modales ahora están **fuera del formulario principal y fuera del contenedor `av3-wrap`**. No se modificaron los endpoints.
- Los estados JS/HTML del editor se migraron de `av3-hidden` y utilidades de activación/opacidad a `is-hidden`, `is-active`, `is-disabled`, `is-dimmed`, `is-complete` y tonos de notificación locales. Se actualizaron `index.js`, `card_modal.js`, `preview_modal.js`, `product_picker.js` y los parciales asociados.
- Preview, envío de prueba y picker de productos fueron rediseñados como modales con componentes semánticos propios y CSS compartido en `admin-v3/pages/marketing-email.css`. El renderizado de productos en JavaScript usa `av3-email-picker__*`; las IDs/eventos se conservan. El asistente avanzado de cards conserva temporalmente sus clases estructurales utilitarias.
- Corrección real de responsive: la capa exterior de modales NO debe tener `role="dialog"` porque `admin-components.css` limita los diálogos a `max-width:960px`. Ahora ese rol y `aria-modal="true"` están en el panel interior; el fondo cubre 100vw y el panel se limita y centra. Verificado a 363, 390, 768 y 1440px.
- Incidente durante desarrollo: una escritura remota truncó `marketing-email.css`. Se restauró desde el respaldo previo y se reaplicaron solo las reglas migradas. Verificado contenido HTTP 200, CSS completo (más de 36 kB), sin errores de parseo, y screenshots reales. Se endureció `marketing_email_editor_contract.php` para detectar CSS truncado, cierres mal posicionados, dialog role incorrecto y estados antiguos en JS.
- Pruebas sin envíos ni escrituras: smoke PHP del editor, contratos de estructura, interacciones Chromium/Edge (estado, audiencia, Visual/HTML, plantillas, preview interceptado, cards, selector y confirmaciones), geometría de 4 anchos y capturas de los cuatro modales en 390/1440. Confirmaciones canceladas, 0 POST.
- **Aún pendiente**: migrar todas las clases utilitarias estáticas de `form_block.php` y del asistente de cards; pruebas autenticadas de acciones persistentes en un entorno seguro. No retirar CSS global antes de revisar otros consumidores.

## 2026-10-20 — Email Marketing V3: consolidación final de componentes (fase 5)

- El formulario de campañas masivas se ensambló por parciales en `campaign_form/{form_block,templates,message,tools}.php`. `form_block.php` pasa de 426 a 69 líneas; `templates.php` mantiene los forms externos de Guardar/Aplicar plantilla, `message.php` conserva Visual/HTML y `tools.php` conserva bloques, imágenes y checklist.
- `modals/card_builder.php` migrado por completo a `av3-email-composer__card-*`, igual que los otros tres modales. Se preservaron los 34 IDs originales y los contratos de Card/Grilla 2/Picker/subida; revisión comparativa automatizada OK. CSS obsoleto de las estructuras antiguas retirado de `admin-v3/pages/marketing-email.css` y sustituido por estilos semánticos únicos, mobile-first.
- `dialog_scroll_lock.js` centraliza el estado de bloqueo del scroll de Preview, Enviar test, Asistente y Picker. El bloqueo persiste al cerrar un modal anidado y se libera al cerrar el último. Se quitaron el uso de `av3-overflow-hidden` en scripts del editor y los cambios directos de `style.overflow`.
- Validación de campos: `form_validation.js` usa `is-invalid` y `aria-invalid`; colores, borde y focus se resuelven en `marketing-email.css`, no con estilos inline desde JavaScript. Se eliminó el último toggle dinámico de borde legacy.
- Auditoría posterior: en los parciales propios y JavaScript del editor no quedan clases visuales utilitarias antiguas; `av3-wrap` y `av3-marketing-email` del layout se conservan por contrato. `marketing_email_editor_contract.php` reforzado para detectar regresiones en parciales, estado de scroll y validación CSS.
- Render sintético, navegación de tabs, audiencia, Visual/HTML, modales, Grilla 2 y catálogo con respuestas simuladas; también se verificaron overlay a 363/390/768/1440 px, layout sin scroll horizontal y campos de error CSS. **No se enviaron emails, no hubo POST reales y no se modificaron datos de campañas.**
- Backup y pruebas auxiliares en `C:\xampp\mi-pesca-backups\email_editor_cleanup_20261020_183405`; sin ZIP incremental. Se conserva como pendiente una prueba autenticada de persistencia y envío en ambiente de pruebas con destinatarios controlados.

- **Cierre de fase 5 (verificado):** build gate integral **OK** (810 PHP, 118 JavaScript, contratos y regresiones); parser CSS OK (~38 kB), contrato del editor OK, CSS y JS servidos por Apache HTTP 200. Firefox/Edge sintético mobile-first y escenarios interactivos comprobados (vista previa interceptada, Card/Grilla 2, catálogo simulado, validación, scroll bloqueado en modales anidados). Sin POST ni envío de correos. Pendiente validación manual autenticada de guardado y programación en entorno de pruebas; no se declara producción verificada.

## 2026-10-20 — Email Marketing: protección del ciclo de vida y validación funcional real

- Corregido el `pageId` del detalle: `EmailCampaignsController::show()` entrega `admin-email-campaign-show` para activar la inicialización de `campaign_show.js` y sus confirmaciones de Programar ahora/Cancelar.
- Endurecidas las transiciones de estado en `EmailCampaignRepository`: `canEdit()`, `update()` protegido en SQL (solo `draft/scheduled` sin ningún destinatario en cola), `scheduleNowIfDraft()` atómico y `cancelIfActive()` condicional. El UPDATE admite retornos de éxito en consultas sin modificación real, pero bloquea campañas ya enviadas, canceladas, en proceso o con cola.
- `EmailCampaignsController`: deniega edición de campañas no editables, evita reprogramación de campañas en curso/finalizadas, valida preparación (asunto, cuerpo, CTA e imágenes) antes de guardar en estado programado, y restringe la cancelación a programadas/en proceso. `EmailTemplatesController::applyToCampaign` respeta la misma política incluso si se accede directamente al POST.
- `MarketingEmailCampaignShowViewModel` y vista de detalle muestran acciones por estado: Editar solo cuando `can_edit`, Programar ahora solo en borrador, Cancelar solo programada/enviando. El formulario ya no incluye `cancelled` como estado editable: esa operación se realiza únicamente desde el detalle.
- Prueba `scripts/dev/marketing_email_campaign_lifecycle_smoke.php`: usa transacción MySQL, crea borrador y mensaje sintéticos, valida guardado, actualización, transición y cancelación, comprueba bloqueo de edición cuando hay cola, y hace `ROLLBACK`. No ejecuta scheduler/worker ni envíos, no persiste registros. `scripts/build/marketing_email_campaign_lifecycle_contract.php` integrado en el gate sin conexión a DB.
- Pruebas UI automáticas con páginas sintéticas de detalle para Borrador/Programada/Enviada: confirmaciones mostradas únicamente cuando corresponde, edición oculta para enviados, cero POST y cero errores JS. Guardado y envío efectivo a destinatarios reales quedan fuera del alcance de esta validación.
- Respaldo de los archivos originales en `C:\xampp\mi-pesca-backups\email_campaign_functional_20261020_203614`. Sin ZIP incremental.

- **Validación final del ciclo de vida (20/10/2026):** build gate **OK**: 812 PHP y 118 JavaScript, contratos de Email/Marketing/layout/CSS y regresiones. Smoke MySQL transaccional con `ROLLBACK` OK; render de formulario y detalle OK; navegador en borrador/programada/enviada OK con confirmaciones descartadas, sin POST. Base local al cerrar: 0 campañas, 0 cola, 0 registros de envío. Falta una prueba autenticada controlada por HTTP de guardado y programación en entorno de pruebas; no se efectuó un envío real.
