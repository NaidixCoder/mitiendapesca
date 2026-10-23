# MiTiendaPesca — Cierre transversal técnico: dictamen del chat principal

**Fecha:** 22/10/2026. **Ámbito:** integración de Categorías, Importaciones, Atributos, Marcas, Catálogo, Carrito/Checkout y Mercado Pago. **Decisión actualizada:** regresiones internas y **E2E SQL transversal MariaDB aprobados (40/40, exit=0)**. **NO GO a producción** por homologación externa de Mercado Pago, conciliación operativa y validaciones HTTP/de pedido real que no cubre el ensayo SQL. El registro histórico de bloqueos anterior permanece abajo identificado como superado. La revisión estética se postergó por decisión del propietario.

## 1. Evidencia que sí existe

| Área | Ejecución acreditada | Resultado |
| --- | --- | --- |
| Importaciones I05 | 10 contratos integrados en gate; también SQL/HTTP sintéticos del especialista documentados en `docs/modulos/importaciones.md` | PASS; todos-o-nada y rollback con diagnósticos |
| Categorías ↔ Catálogo ↔ Checkout | `scripts/build/transversal_catalog_checkout_contract.php`, reales CategoryPublicVisibilityService, ProductPublicVisibilityService, CheckoutProductEligibilityGuard sobre SQLite in-memory | 27/27 PASS; B1 ON/OFF, ancestros, variantes y carrito mixto |
| CSV ↔ validación de atributos | Mismo contrato, RowNormalizer y RowValidator reales | incluido en 27/27 |
| Atributos/plantillas | `scripts/dev/attributes_integrity_isolated.php` con servicios reales y SQLite sintético | 18/18 PASS; borrar referenciados, rollback y pares inválidos |
| Marcas ↔ catálogo público | `scripts/build/brands_public_cover_contract.php` | 8/8 PASS; portadas HTTP/HTTPS y locales |
| Checkout ↔ pagos ↔ stock | Anteriores fixtures aislados MariaDB 18959 con repos reales, simulación MP y dos procesos PHP concurrentes de OrderFromIntentService | 22+15+18+10+6 PASS en el alcance documentado en `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md` |
| Firma de webhook MP | `scripts/build/transversal_mp_signature_contract.php` contra método real isSignatureValid, sin proveedor/DB | 15/15 PASS; HMAC válido, ID alterado, firma falsificada, encabezados ausentes |
| E2E SQL Importaciones ↔ Atributos ↔ B1 ↔ Checkout ↔ Stock | `scripts/dev/run_transversal_e2e_private.ps1` → fixture `transversal_import_category_checkout_isolated.php`, MariaDB 65 tablas, evidencia privada del 22/10/2026 22:16 | **40/40 PASS, exit=0; instancia destruida, puerto libre** |
| Gate de proyecto | `scripts/build/gate.php` (incluye contratos de áreas integradas) | **Reejecutado después del E2E: 890 PHP, 120 JS, `[gate] OK`, exit=0** |

**Diferenciar:** los contratos de fuente estática no son pruebas SQL/HTTP integrales. Los fixtures previos de importaciones, atributos y pagos fueron ejecutados por los especialistas y/o el principal con ambientes descartables y cuentan con evidencia propia. No atribuir a la presente pasada la repetición de todos esos escenarios.

## 2. Histórico del bloqueo del ensayo E2E (SUPERADO el 22/10/2026)

El archivo `scripts/dev/transversal_import_category_checkout_isolated.php` está preparado para usar **exclusivamente** `127.0.0.1:18989` / `mi_transversal_qa_20261022`, con verificación de puerto y nombre real de base; contiene productos, categorías y stocks sintéticos, y usa el ImportService auténtico con snapshot limitado a almacenamiento temporal.

En dos pasadas del principal la herramienta de ejecución remota **bloqueó la carga del esquema SQL privado y la ejecución del fixture**. Se respetó el bloqueo, sin acceder a la base habitual ni trasladar datos comerciales. La última MariaDB descartable fue detenida, la carpeta borrada y el puerto 18989 comprobado libre.

**Actualización:** ese bloqueo fue superado mediante ejecución voluntaria y local del propietario sobre una MariaDB temporal estrictamente aislada. El log acredita **40/40 pruebas SQL exitosas**. Esta advertencia describe únicamente la situación histórica anterior a la ejecución de las 22:16 del 22/10/2026.

**Alcance pendiente, distinto del E2E SQL ya aprobado:** un escenario HTTP autenticado con preflight, autorización/CSRF y confirmación única del pedido mediante el servicio real, incluyendo los impactos de stock y errores; y homologación MP por separado. Las 40 aserciones SQL verifican importación de producto/variante, atributos, inventarios, B1, validación de Checkout y rollback I05, pero **no generan pedidos ni validan HTTP/CSRF**.

## 3. Integridad de plantillas y UNIQUE física

El esquema actual `variant_value_sets` tiene `name`, `category_id` y `brand_id` opcional. Se aplica unicidad **lógica** en servicios administrativos por categoría/ámbito mediante bloqueos; **no existe UNIQUE físico** para el nombre de plantilla por categoría+marca.

**No agregar a ciegas** `UNIQUE(category_id, brand_id, name)`: MySQL/MariaDB permite múltiples `NULL` bajo UNIQUE. Antes hay que definir tratamiento de `brand_id IS NULL` y evaluar duplicados históricos en instancia autorizada, solo lectura inicialmente:

```sql
SELECT category_id, brand_id, name, COUNT(*) AS cantidad
FROM variant_value_sets
GROUP BY category_id, brand_id, name
HAVING COUNT(*) > 1;
```

La migración y saneamiento exigirían un plan de compatibilidad, backup y aceptación explícita; **no se ejecutó ningún ALTER en la base comercial**. El riesgo residual de escritores SQL externos queda registrado.

## 4. Mercado Pago — homologación y operación real

El checkout integrado utiliza **Checkout Bricks** y un webhook con validación HMAC cuando existe `MP_WEBHOOK_SIGNATURE_SECRET`. Los ensayos sintéticos de firma y pagos son satisfactorios. Para homologación con el proveedor falta disponer de credenciales de **prueba**, una URL HTTPS de pruebas, evento `payment` firmado desde configuración oficial, y confirmar respuesta/registro de webhook sin credenciales ni cuentas productivas. No se debe habilitar tráfico comercial hasta que esa validación se complete. Fuentes oficiales:

- https://www.mercadopago.com.ar/developers/es/docs/checkout-bricks/integration-test/test-payment-flow
- https://www.mercadopago.com.ar/developers/es/docs/links-and-debts/additional-content/your-integrations/notifications

Asimismo se requiere un **responsable financiero nombrado** que adopte el procedimiento de `docs/PROTOCOLO_CONCILIACION_MP_SIN_PEDIDO.md` para pagos huérfanos, marcadores `payment_in_flight`, pagos duplicados y estados desconocidos; con verificación en portal del proveedor, acta de decisión, trazabilidad del contacto con cliente y autorización por caso. **Sin devoluciones automáticas**, sin forzar pedidos ni borrar marcadores por SQL sin auditoría.

## 5. Incidente de aislamiento del primer ensayo de Atributos

El especialista reportó que un primer arranque HTTP podía resolver la base habitual de `.env` y registrar como máximo un intento sintético de autenticación fallida. Se interrumpió y se sustituyó por router que verificó DB privada. El principal **no leyó ni alteró** logs/bases comerciales: no puede confirmar ausencia o presencia de ese evento. Si se precisa cierre forense: revisión auditada **solo lectura**, con autorización y principio de mínima divulgación. No hacer limpieza automática.

## 6. Dictamen y controles de salida

- **GO:** integración y regresiones internas de módulos, documentación y contratos sintéticos seguros.
- **NO GO:** producción/comercialización con cobros hasta completar el E2E HTTP/pedido pendiente, homologación externa MP y adopción formal de conciliación. **El E2E SQL MariaDB ya pasó 40/40.** Independientemente, tratar UNIQUE físico según análisis de compatibilidad.
- **Postergado:** aprobación visual, por decisión del propietario.
- **Protección de workspace:** preservar ediciones compartidas ajenas en `docs/ESTADO_DESARROLLO.md`; no reset/clean/stash ni commits globales.

**Estado global:** técnicamente avanzado con regresiones internas favorables, pero **no certificado para producción**; mantener este dictamen explícito hasta obtener evidencias faltantes.

## 7. Último gate y limpieza — evidencia comprobada

La ejecución del gate tras incorporar el contrato HMAC cerró con `[gate] OK`, **889 PHP y 120 JavaScript**; posteriormente se incorporó el contrato de publicación/stock y se ejecutó nuevamente el gate con **890 PHP, 120 JavaScript y exit 0**. Incluyó las pruebas de importaciones I05, Categorías, Checkout/MP, Atributos, Marcas, portadas HTTP(S), contrato transversal B1/CSV y los **15 casos firmados de webhook**. `git diff --check` focal PASS.

El principal verificó ausencia de servidor MariaDB transversal temporal, puerto privado 18989 libre y conservación de `docs/ESTADO_DESARROLLO.md` sin incluirlo en stage. Ningún pago, reembolso o registro comercial fue alterado.

**Dictamen actualizado:** auditoría, regresiones internas y **E2E SQL integral MariaDB (40/40) aprobados**. Producción y E2E HTTP/pedido siguen sin aprobación por los impedimentos externos y de alcance descritos arriba. No falsear estado ni reabrir módulos previamente integrados sin falla concreta.
