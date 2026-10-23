# MiTiendaPesca — Evidencia aprobada E2E SQL transversal

**Responsable de auditoría:** chat principal. **Fecha de ejecución local:** 22/10/2026, 22:16 (Argentina). **Estado:** **APROBADO en su alcance SQL (40/40 PASS, exit 0)**. **No autorizado para producción ni homologación Mercado Pago.**

## Origen verificable

- El propietario ejecutó localmente `scripts/dev/run_transversal_e2e_private.ps1` en su Windows; no se ejecutó por administración remota ni se usó la BD de desarrollo/comercial.
- Fixture probado: `scripts/dev/transversal_import_category_checkout_isolated.php`, ejecutado contra MariaDB 10.4 privada en **127.0.0.1:18989**, base `mi_transversal_qa_20261022`, **65 tablas** cargadas exclusivamente desde `database/schema.sql`.
- Evidencia original **leída directamente por el chat principal**: `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log` (no incorporada a Git porque contiene salida runtime local).
- **SHA-256 del log comprobado:** `0120F3DDF2DA48197EAF6E07C3BE580B7B842FD7A9FBBAD1A9D16E64A2E5AA8C`.
- Validación independiente del archivo: **40 líneas `PASS (n)` numeradas 1 a 40 sin saltos**, marcador literal `[transversal-import-category-checkout] OK 40 MariaDB cross-module assertions`, `exit=0`, **ninguna línea FAIL ni Fatal**.
- Comprobación separada por Remote Desktop Commander **después** de la ejecución: carpeta `%TEMP%/mi_transversal_qa_20261022` ausente; ningún listener puerto 18989; ningún `mysqld.exe` con directorio temporal de esta suite. Solo quedó el log en `docs/`.

## Resultados efectivos (SQL con servicios reales)

| Área | Aserciones acreditadas |
| --- | --- |
| Preparación privada, publicación y productos | 1–9. Categorías activas/inactivas sintéticas, `ImportService::run` commit real, snapshot reversible, dos SKUs, categoría correcta, `is_active=1` y stock; la excepción a imágenes se habilita **solo en la BD privada** para evitar que la validación de medios despublique fixtures sin imagen. |
| Categorías B1 y guard Checkout | 10–17. B1 OFF/ON, productos de categorías activas/inactivas y `CheckoutProductEligibilityGuard::checkItems` en modo normal y con bloqueo `FOR UPDATE`. |
| Variantes + atributos + stock | 18–25. `ImportService` de variante con atributo Color/Azul, `product_variants`, `inventory_variants`, `product_variant_values`, `product_attributes`, `product_attribute_values`, guard de Checkout aprobando la variante propia y rechazando una variante asociada a otro producto. |
| Política de visibilidad | 26. `ProductPublicVisibilityService` coincide con B1 de Checkout. |
| Atomicidad I05 y rollback | 27–32. CSV mixto con filas válidas e inválidas, `apply_outcome=rolled_back`, sin snapshot ni producto/variante/atributo nuevos, inventarios y asociaciones sin cambios remanentes. |
| Revalidación tras error/cambios de categoría | 33–40. B1 normal y bloqueado tras importación fallida, categoría desactivada luego del alta y cambio controlado B1 OFF, comprobado con y sin bloqueo de lectura. |

**Causa histórica de FAIL 6 (resuelta sin modificar reglas de negocio):** el CSV no suministra imágenes; `InventoryRepository` desencadena `ProductPublicationSyncService`, que exige imágenes por `ProductWizardService` salvo que `catalog_allow_publish_without_images=1`. Por eso fallaron los dos intentos iniciales. El fixture final habilitó esa excepción **solo en la base desechable**. El contrato permanente `scripts/build/transversal_publication_stock_contract.php` prueba **16/16** que la regla comercial por defecto continúa vigente.

## Lo que esta prueba NO acredita

- No crea un pedido confirmado con `OrderFromIntentService`, no genera una orden HTTP real de checkout y no ejecuta la cadena web de sesiones/CSRF.
- No verifica la configuración externa o aceptación de pagos por Mercado Pago ni recepción de un webhook genuino del proveedor.
- No evalúa migración `UNIQUE` física de plantillas ni sustituye decisiones de conciliación financiera.

Esos controles tienen otras suites aisladas parciales, y su aceptación final debe tratarse explícitamente por separado.

## Decisión técnica

**Se cierra el pendiente histórico de E2E SQL Importaciones ↔ Atributos ↔ Categorías/B1 ↔ validación Checkout ↔ Stock ↔ I05**: **PASS 40/40**. No repetir más este runner sin nueva incidencia; preservar el fixture y contrato como defensa frente a regresiones.

**Mantener NO GO a producción** hasta completar el E2E HTTP/pedido del checkout, homologación real de Mercado Pago y responsable/protocolo operativo de conciliación. La aprobación estética sigue postergada.

## Gate final independiente

Tras leer la evidencia y actualizar el dictamen, el chat principal **reejecutó** `C:\xampp\php\php.exe scripts/build/gate.php` sobre el código integrado. **890 archivos PHP, 120 JavaScript, todos los contratos y regresiones, `[gate] OK`, exit=0**. No se modificó ninguna clase de negocio, esquema comercial ni configuración de producto entre la prueba SQL y ese gate.
