# MiTiendaPesca — QA transversal técnico (avance 2026-10-22)

**Responsable:** chat principal. **Visual:** expresamente diferido por el propietario. **Estado:** parcial, NO autorización de producción.

## Alcance y evidencia ejecutada

La rama `main` contiene las entregas selectivas de **Categorías** (`65c36cb`), **Checkout/Mercado Pago** (`d8db2ec`) e **Importaciones** (`973bc23`). Atributos y Marcas todavía presentan cambios sin integrar de sus respectivos responsables; no incluirlos en commits ajenos ni presentar su estado como cierre transversal.

Se reejecutaron **13 contratos focales** del árbol actual con éxito:

| Área | Contratos | Resultado |
|---|---|---|
| Categorías/B1 | `categories_integral`, `categories_checkout_b1`, `categories_product_visibility`, `categories_public_visibility`, `categories_seo_redirects_head_tracking` | 18 + 31 + 28 + 24 + 31 = 132 checks PASS |
| Importaciones | `imports_i05_atomic`, `imports_attribute_relations`, `imports_variant_parent_snapshot` | 21 + 30 + 25 = 76 checks PASS |
| Checkout/MP | `checkout_payment_integrity`, `checkout_payment_orphan`, `checkout_intent_reuse` | 26 + 12 + 12 = 50 checks PASS |
| Atributos/Marcas, aún sin integración | `attributes_admin_contract.php`, `brands_admin_contract.php` | PASS (contratos de superficie, no aprobación de integridad/producción) |

**258 comprobaciones cuantificadas** de los once primeros contratos; dos contratos adicionales sin contador numérico informaron PASS. Estos son contratos focales y NO acreditan por sí solos E2E SQL/HTTP entre módulos.

## Ensayo cruzado adicional (preparado pero NO ejecutado)

Se creó un MariaDB **descartable** en `127.0.0.1:18989`, base exclusiva `mi_transversal_qa_20261022`, con las **65 tablas de `database/schema.sql`**. Se preparó `scripts/dev/transversal_import_category_checkout_isolated.php` para ensayar el **motor ImportService real** con los siguientes casos:

1. Importar dos productos publicados, asignando uno a categoría activa y otro a categoría inactiva; comprobar categoría y stock persistidos, sin reactivar categoría.
2. Con B1 desactivada, admitir ambos en `CheckoutProductEligibilityGuard` (lectura común y bloqueo `FOR UPDATE`).
3. Con B1 activada, admitir solo producto de categoría activa; confirmar coincidencia con `ProductPublicVisibilityService`.
4. Importar CSV mixto válido + inválido; comprobar I05 `rolled_back`, sin producto ni inventario residual.
5. Confirmar que tras rollback siguen iguales las decisiones B1; desactivar categoría y revalidar Checkout; volver a B1 OFF.

**El ejecutor remoto bloqueó la llamada de ejecución del fixture. La prueba cruzada NO se considera pasada.** Se realizó únicamente `php -l` del script (PASS). El entorno SQL privado quedó **completamente eliminado** y el puerto 18989 quedó libre. No hubo conexiones a bases comerciales ni pagos, órdenes o reembolsos reales.

## Pendientes técnicos y operativos

- Reejecutar el fixture cruzado con método autorizado y/o E2E HTTP/SQL sintético después de integrar Atributos y Marcas, validando asociación producto-categoría, variantes, atributos, stock, rollback y preflight/checkout B1.
- Verificar efectos de actualización/visibilidad de productos importados en catálogo público y carrito, así como producto desactivado durante pedido y sus consecuencias de stock/pago.
- Probar integración transaccional real de Importaciones con plantillas/relaciones de Atributos una vez aprobado ese módulo; contratos estáticos actuales no equivalen a ese QA.
- Mercado Pago: homologación externa y adopción por responsable financiero del protocolo `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md`, sin devoluciones automáticas.
- Finalizar con gate global **cuando estén integrados los módulos restantes** y se disponga del E2E SQL/HTTP autorizado. No repetir gates pesados durante edición concurrente de especialistas.

**Conclusión del avance:** no se observó regresión en los contratos ejecutados. QA transversal completo y producción permanecen sin aprobar. Sin cambios en archivos de los especialistas.

## Actualización — Integración de módulos (2026-10-22)

Atributos cerró e integró sus protecciones P0 de asociaciones/plantillas (commit local `c893a12`) y Marcas cerró e integró persistencia atómica Marca+SEO/imágenes (commit local `00a176b`). Su QA SQL/HTTP completo sigue respaldado por los informes del especialista; el principal reejecutó contratos y 18 casos SQLite Atributos. Se resolvió también el caso de imágenes remotas de Marcas en el catálogo público, con **8/8** aserciones ejecutables sintéticas. El E2E transaccional real de `ImportService` junto a Atributos sigue pendiente (el fixture SQL del principal fue escrito pero su ejecución remota se bloqueó), junto a la política de UNIQUE físico, el posible evento histórico de login fallido del primer ensayo y homologación MP. No producción.

## 2026-10-22 — Segunda pasada ejecutada directamente por el chat principal

**Estado:** QA focal cruzado ampliado, **no equivale a QA E2E MariaDB completo**, revisión visual y producción siguen diferidas.

- **Reejecución independiente real**: nuevo contrato `scripts/build/transversal_catalog_checkout_contract.php`, **27/27 PASS**, con SQLite `:memory:` y servicios REALES `CategoryPublicVisibilityService`, `ProductPublicVisibilityService`, `CheckoutProductEligibilityGuard`, `RowNormalizer` y `RowValidator`. Verifica B1 OFF/ON, ancestros desactivados, ciclos, categorías huérfanas, producto inactivo, variante inactiva/de otro producto, carrito mixto, revalidación de carrito antiguo tras desactivación y normalización de `attr_1/valor_1`, fila incompleta inválida. No replica `FOR UPDATE` ni `ImportService` completo.
- `scripts/dev/attributes_integrity_isolated.php`: **18/18 PASS** nuevamente; Atributos protege selecciones/plantillas ante baja o pares inválidos con SQLite aislado. Integrados previamente Atributos y Marcas en Git; las portadas HTTPS ya tienen contrato público 8/8.
- **Intento MariaDB privado:** inició MariaDB propia `127.0.0.1:18989` bajo `%TEMP%/mi_transversal_qa_20261022`; una política de seguridad del ejecutor remoto **bloqueó la llamada de inicialización del esquema**. No se continuó por vías alternativas para eludir el bloqueo. Por tanto el fixture `scripts/dev/transversal_import_category_checkout_isolated.php` permanece sin ejecución E2E acreditada. `mysqld` privado detenido, directorio eliminado y puerto 18989 libre **comprobado**. No se consultó `.env`, DB comercial ni se hicieron pagos.
- **UNIQUE físico de plantillas:** `database/schema.sql` define `variant_value_sets(name,category_id,brand_id NULL)` con índice **no único** `idx_vvs_cat_brand`; `variant_value_set_items` sí tiene UNIQUE por triple. Un simple `UNIQUE(name,category_id,brand_id)` en MySQL/MariaDB **no impide duplicados cuando brand_id es NULL**. Los servicios de Atributos tienen validaciones/bloqueos lógicos, pero no protegen escrituras externas. **No se crea una migración destructiva sin diagnosticar duplicados históricos y definir semántica de NULL.**
- **Diagnóstico propuesto solo de lectura para futura migración en un entorno autorizado**:
```sql
SELECT category_id, brand_id, name, COUNT(*) AS cantidad
FROM variant_value_sets
GROUP BY category_id, brand_id, name
HAVING COUNT(*) > 1;
```
- Próximo bloqueo externo: habilitar/autorizar ejecución E2E MariaDB privada para `ImportService` ↔ atributos, stock, B1 y reversión I05; ejecutar HTTP sintético completo con login aislado antes de aprobación transversal. MP continúa pendiente de homologación real y operador financiero para conciliación, sin reembolsos automáticos.

- **Gate general tras integrar el nuevo contrato cruzado:** `scripts/build/gate.php` **PASS**: 888 archivos PHP, 120 JavaScript, contratos de Categorías, Importaciones, Checkout/MP, Atributos, Marcas y 27 casos cruzados; salida `[gate] OK`, exit 0. Este gate no convierte el E2E MariaDB bloqueado en prueba pasada.

## Dictamen consolidado de cierre (22/10/2026)

Documento principal de salida: `docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md`. En esta pasada se verificó documentación oficial de pruebas de Checkout Bricks y validación Webhooks; `scripts/build/transversal_mp_signature_contract.php` aportó **15/15 firmas HMAC verificadas con el controlador real y datos sintéticos**, incorporado al gate.

**GO a integración interna de cambios y regresiones; NO GO a producción** hasta completar prueba E2E SQL transversal con `ImportService` real en DB desechable, homologación externa Mercado Pago y adopción del procedimiento supervisado de conciliación. No modificar el esquema real por UNIQUE físico sin diagnóstico/compatibilidad de NULL. Diseño visual pendiente por decisión del propietario.

## 2026-10-22 — E2E ImportService ↔ Atributos ↔ B1: tercer intento de ejecución y preparación final

**Estado: NO EJECUTADO. No certificar como PASS.**

El principal intentó por tercera vez crear una MariaDB aislada y sintética en `127.0.0.1:18989` usando un datadir efímero `%TEMP%/mi_transversal_qa_20261022/data`. `mysql_install_db` y `mysqld` **funcionaron** (MariaDB 10.4.32 lista solo en 127.0.0.1:18989). La operación remota **CREATE DATABASE** fue explícitamente bloqueada por los controles de seguridad antes de cargar esquema o ejecutar el fixture. No se intentó ninguna ruta alternativa para eludir el control ni se accedió a DB comercial.

**Limpieza verificada:** servidor privado detenido, directorio efímero eliminado y puerto 18989 libre. No se tocó el `mysqld` habitual del equipo.

### Fixture reforzado y verificaciones previstas

`scripts/dev/transversal_import_category_checkout_isolated.php` ahora exige:

1. DSN literal de QA + puerto 18989 + nombre de base `mi_transversal_qa_20261022`.
2. Datadir real `@@datadir` igual al directorio físico temporal autorizado. Rechaza conexión a cualquier otro servidor, aunque coincidan puerto y nombre lógico.
3. Tablas de negocio sintéticas instaladas y **vacías** (`categories`, `products`, `product_variants`, `attributes`, `import_jobs`).
4. Almacenamiento de CSV/snapshots dentro de la misma carpeta efímera y `ImportSnapshotStore` inyectado con directorio permitido.

Al ser habilitada la ejecución privada, el ensayo validará:

- Alta real de productos publicados en categorías activa e inactiva, inventarios y snapshot reversible.
- Visibilidad B1 OFF/ON y validaciones de Checkout comunes y con `FOR UPDATE`.
- Alta de **variante real con stock y pares de atributo/valor**; filas persistidas en `product_variant_values`, `product_attributes`, `product_attribute_values`.
- Rechazo de variante perteneciente a otro padre.
- Archivo mixto con producto + variante + atributo nuevo válidos, seguido de fila inválida: rollback I05; conteos de productos, variantes, inventarios, maestros de atributos y relaciones **idénticos antes y después**, ningún snapshot publicado.
- Categoría desactivada después de importación: carrito previamente válido bloqueado con B1 ON y admisible al desactivar B1 de forma controlada.

**Resultados realmente ejecutados tras editar:** `php -l` del fixture PASS, `scripts/build/transversal_catalog_checkout_contract.php` **27/27 PASS**, `scripts/build/imports_i05_atomic_contract.php` **21/21 PASS**, `git diff --check` PASS. Esto no reemplaza el E2E MariaDB.

**La prueba completa de pedido/stock con el servicio real** (y cualquier HTTP autenticado, sesión, CSRF) debe acompañar al ensayo de importación cuando se disponga de ejecución privada autorizada. No conectar pasarela ni generar pagos reales. El servicio de pedido y su concurrencia ya tuvieron fixtures aislados previos pero **no** dentro de esta misma base transversal.

**Criterio de aprobación:** ejecución E2E verificable con log de cada aserción, código de salida 0 y limpieza confirmada del entorno privado; recién entonces actualizar este estado. Si no se habilita ejecución SQL aislada, mantener NO GO a certificación E2E y producción.

- **Regresión final tras ampliación del fixture:** `scripts/build/gate.php` PASS (889 PHP, 120 JS, exit 0); `php -l` del fixture PASS; contratos B1/CSV 27/27 e I05 21/21 PASS. El propio fixture MariaDB permanece **no ejecutado** por bloqueo remoto de CREATE DATABASE.

## 2026-10-22 — Ejecutor local privado preparado (NO ejecutado)

Se agregó `scripts/dev/run_transversal_e2e_private.ps1`, launcher Windows para ejecución local voluntaria. Genera contraseña sintética aleatoria, no lee `.env`, crea MariaDB efímera en `%TEMP%/mi_transversal_qa_20261022` con puerto 18989, valida **@@datadir y puerto antes de CREATE DATABASE**, importa el esquema `database/schema.sql`, ejecuta `scripts/dev/transversal_import_category_checkout_isolated.php` (asociaciones de atributos, variantes, stock y rollback I05) y registra evidencia en `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log`. Destruye instancia/carpeta solo cuando verifica la identidad y detención del servidor privado. Ante error conserva log y retorna exit 1.

**Verificaciones hechas por el principal:** parser PowerShell **0 errores**, PHP lint fixture PASS, 27 contratos B1/CSV PASS y 21 I05 PASS, `git diff --check` PASS. **No se ejecutó el launcher ni el fixture contra MariaDB** a través de la conexión remota. Documentación/único comando en `docs/EJECUCION_E2E_MARIADB_PRIVADA.md`.

**Pendiente al propietario:** iniciar el archivo PowerShell directamente desde Windows, revisar que `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log` contenga la salida de la ejecución y entregar su resultado para auditoría. No corresponde autorizar producción sin evidencia de prueba SQL y otras condiciones del dictamen.

## 2026-10-22 — Primera ejecución real del E2E por el propietario: fallo reproducible en aserción 6

La ejecución **local y aislada** del launcher `scripts/dev/run_transversal_e2e_private.ps1` creó correctamente MariaDB privada con **65 tablas** y ejecutó `ImportService` real. Reportó **PASS 1–5** (categorías sintéticas, COMMIT de los productos, snapshot publicado, SKUs persistidos y asignación de categorías), seguido de **FAIL (6)**: los valores de `products.is_active` importados no cumplieron simultáneamente `1` como requería `publicado=1`. El dato exacto de cada producto **no fue registrado en esta primera corrida**; no afirmar aún si falló uno, ambos o por qué. El runner reportó limpieza correcta de DB/puerto/carpeta.

El principal ejecutó fuera de MariaDB comercial dos diagnósticos independientes: lectura/normalización real de CSV **2/2** (`raw_publicado='1', normalized_active=1`) y `RowNormalizer → ProductImporter → ProductRepository::insertGetId` con SQLite de memoria **2/2**, incluyendo categoría inactiva. Ambos crean `products.is_active=1`; la diferencia exige evidencia específica MariaDB y no admite cambiar a ciegas la lógica de producto ni bajar el rigor de B1.

Se reforzaron únicamente herramientas de QA:
- `transversal_import_category_checkout_isolated.php` ahora emite `[qa-publication]` con SKU sintético, `raw_publicado`, `normalized_active`, `products.is_active` y `categories.is_active` **antes** de la aserción.
- `run_transversal_e2e_private.ps1` conserva `fixture.out` **además** de `fixture.err` en `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log` cuando una corrida falla.
- PHP lint del fixture, parser PowerShell, contrato cruzado B1/CSV **27/27**, contrato I05 **21/21** y `git diff --check`: PASS. Los scripts de diagnóstico descartables se eliminaron; ninguna modificación funcional ni dato comercial.

**Próximo paso único:** volver a ejecutar el mismo launcher de QA local; capturar el nuevo log con valores observados y recién entonces corregir la causa exacta. E2E MariaDB transversal **NO aprobado** hasta recuperar la aserción 6 y completar las restantes.

- **Diagnóstico adicional antes de la próxima corrida:** el fixture ejecuta `ProductImporter::upsert` + `ProductRepository` reales directamente en la misma MariaDB privada, imprime `[qa-repository-probe]` con publicación persistida, y hace `ROLLBACK` inmediato. El ensayo principal imprime `[qa-publication]` con CSV y SQL de los dos productos. El runner conserva ahora `fixture.out` y `fixture.err` incluso cuando el fixture falla. Así se separa el problema de persistencia SQL del flujo integral sin modificar código de producción.

- Tras la instrumentación de diagnóstico, gate global `scripts/build/gate.php` **PASS**, 889 archivos PHP, 120 JavaScript, `[gate] OK`, `PUBLICATION_DIAG_GATE_EXIT=0`. No acredita el E2E MariaDB, que continúa detenido en el hallazgo 6 hasta conocer los valores SQL.
