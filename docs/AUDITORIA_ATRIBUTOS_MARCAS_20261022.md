# MiTiendaPesca — Auditoría central de Atributos y Marcas (2026-10-22)

**Ámbito:** validación técnica para integración selectiva en Git `main`. **No producción.** Aprobación estética expresamente postergada por el propietario. El árbol compartido contiene cambios simultáneos de ambos módulos, sin reset/stash/rebase.

## Atributos — veredicto

- Se revisó el cierre `docs/modulos/atributos.md` §13 y código de `VariantAttributeValueDeleteService`, `VariantValueSetsService`, `VariantAttributesRepository` y controller `AttributesValueSetActions`. La baja de valor hace `SELECT FOR UPDATE`, comprueba asociaciones de productos/variantes/plantillas antes de borrar y no ejecuta eliminación de sus selecciones para forzar borrado. Si falla SQL, no trata el fallo como cero relaciones.
- La edición de plantillas valida pares atributo/valor, ejes, categoría, marca, duplicados y usa transacciones o SAVEPOINT; la vía HTTP preserva método y CSRF. **Integración técnica favorable**, con reserva de prueba E2E cruzada ImportService↔Atributos y política de unicidad física.
- El especialista acredita **24 escenarios MariaDB real + 2 carreras con dos procesos + 25 comprobaciones HTTP autenticadas en DB privada + 18 SQLite**, además de gate. El principal **no repitió SQL/HTTP destructivo**: verificó código, contratos focales y ejecutó `scripts/dev/attributes_integrity_isolated.php` **18/18 PASS**. Contratos `attributes_admin`, `imports_i05_atomic` (21), `imports_attribute_relations` (30), `imports_variant_parent_snapshot` (25) PASS.
- **Incidente inicial de aislamiento HTTP reportado por el especialista:** el primer proceso podía leer `.env` del proyecto, y un intento sintético de autenticación pudo alcanzar su base configurada. Fue detenido y descartado; las 25 pruebas aprobadas posteriores utilizaron router con comprobación DB/puerto 18991. **No afirmar que no existió un evento de login fallido en DB habitual.** Principal no inspeccionó ni limpió logs comerciales. Si se requiere cierre forense, solo verificación de logs **read-only, con autorización**, antes de afirmar efectos cero; no modifica decisión de integrar código revisado.
- **UNIQUE físico en plantillas:** **NO implementar en silencio**. La unicidad lógica está serializada por bloqueos de categoría en escritores del servicio; entradas externas por SQL siguen pudiendo crear duplicados, especialmente con marca histórica NULL. Evaluar migración y compatibilidad de datos en otra decisión técnica/comercial, sin ALTER comercial.

## Marcas — veredicto

- Revisó cierre `docs/modulos/marcas.md` V2, `BrandSaveService`, `BrandsController`, helper `BrandCoverUrl`, y el catálogo público lector de imágenes. El POST guarda marca, SEO settings, historial/redirects/bloques en una única transacción; compensa nuevo upload si falla SQL y elimina portada anterior únicamente tras COMMIT cuando no esté referenciada. Se protegen imágenes compartidas y las URL externas.
- El especialista acredita E2E HTTP/SQL sintético privado (alta, edición, slug, SEO, upload, rollback forzado, referencias a productos, permisos y CSRF), más pruebas de archivo compartido y fallas de lectura. Principal reejecutó `brands_admin_contract.php` PASS y revisó código sin nuevas escrituras SQL.
- **Dependencia pública separada:** `app/Views/pages/catalogo/marcas.php` construye `public_url(cover_image)` incluso con HTTP(S) absoluta, por lo que las imágenes remotas guardadas correctamente en Admin pueden tener URL incorrecta en Catálogo público. **No está resuelta en este commit del Admin.** La corrección corresponde al principal/Catálogo (fuera de los 15 archivos de Marcas), con regresión propia y sin tocar servicios SEO globales.
- **Límite FS:** una caída irreversible entre mover imagen y compensar podría dejar archivo huérfano, sin pérdida de SQL; se registra para mantenimiento, no como garantía de atomicidad filesystem/DB.

## Estado transversal y alcance

Se reejecutaron `attributes_admin_contract.php`, `brands_admin_contract.php`, los contratos focales Importaciones I05/relaciones/padre, Categorías B1 y Checkout/MP, más **18/18 SQLite** de Atributos, todos PASS. **El gate global será acreditado solo si finaliza con exit 0 tras incorporar ambos contratos al gate.** El cierre total de QA transversal no sustituye homologación Mercado Pago, protocolo humano de conciliación, prueba real ImportService↔Atributos en DB privada ni revisión estética posterior.

**Criterio:** Atributos y Marcas son aptos para **integración selectiva del código**, no liberación para producción. Conservar sin stage cualquier archivo ajeno/no inventariado (por ejemplo documentación de progreso compartida), y publicar commits separados por responsable.

## Actualización de integración y dependencia pública

- Atributos integrado localmente en commit **`c893a12`** (21 archivos de Atributos), tras contrato Admin PASS, 18 escenarios SQLite PASS, regressions Imports/B1/Checkout PASS y corrección de whitespace en documentación.
- Marcas integrado localmente en commit **`00a176b`** (15 archivos exactos de Marcas), con contrato Admin PASS y eliminación de whitespace aislado en contrato.
- **Dependencia HTTPS del catálogo CORREGIDA** exclusivamente por el principal en `app/Views/pages/catalogo/marcas.php`: conservar URLs `http://` y `https://` absolutas; usar `public_url()` solo para paths locales. Contrato ejecutable `scripts/build/brands_public_cover_contract.php` **8/8 PASS** con la vista pública real y marcas sintéticas (sin DB). Añadido al gate general.
- Permanecen fuera de esta integración visual final, adopción de UNIQUE físico para plantillas, inspección de posible login fallido histórico sin permiso a la BD comercial, integración E2E ImportService↔Atributos y homologación MP; **no producción**.
