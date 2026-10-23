# MiTiendaPesca — Ejecución local del E2E privado de MariaDB

**Estado:** runner preparado, **no ejecutado**. No constituye aprobación de QA final ni producción.

## Por qué es necesaria una ejecución local

El chat principal no pudo crear la base sintética en el equipo remoto: el control de seguridad de la herramienta rechazó `CREATE DATABASE` incluso después de iniciar una instancia independiente. No se debe eludir esa restricción ni ejecutar el fixture contra la MariaDB comercial de XAMPP.

Para disponer de una ejecución voluntaria y auditada en **el propio Windows del propietario**, se preparó un script local independiente:

`scripts/dev/run_transversal_e2e_private.ps1`

### Única acción local

Abrir PowerShell en Windows y ejecutar:

```powershell
& 'C:\xampp\htdocs\mi-pesca\scripts\dev\run_transversal_e2e_private.ps1'
```

No modificar comandos, puerto, rutas o credenciales para forzar una prueba fallida. Si falla, revisar el archivo de evidencia y entregar el error al chat principal.

### Garantías implementadas

- El ejecutor falla si existe la carpeta `%TEMP%\mi_transversal_qa_20261022` o si el puerto 18989 ya está ocupado.
- Genera credenciales aleatorias exclusivas del ensayo y **no abre ni lee `.env`**.
- Inicializa MariaDB nueva en la carpeta temporal del usuario; obliga a `127.0.0.1:18989`.
- **Antes de escribir SQL**, verifica el puerto, directorio real de datos `@@datadir` y ausencia previa del esquema.
- Carga únicamente `database/schema.sql` dentro de la instancia privada y comprueba tablas/engine InnoDB.
- Ejecuta `scripts/dev/transversal_import_category_checkout_isolated.php`, el cual vuelve a verificar DSN, puerto, datadir, tablas vacías y directorio de snapshots autorizado.
- Detiene únicamente el proceso MariaDB que inició y **verifica su identidad** antes de terminarlo. Borra la carpeta temporal cuando el proceso termina; no toca el MariaDB habitual.
- Preserva evidencia de éxito o error fuera de la carpeta temporal en `docs/QA_TRANSVERSAL_SQL_RESULT_20261022.log`. Este archivo NO se commitea automáticamente y se debe revisar antes de publicar evidencias.
- Si el proceso no se puede detener con certeza, conserva sus archivos y señala que requiere revisión manual; no borra un datadir activo.

## Alcance del fixture

Ejecuta `ImportService` real sobre productos, variante, relaciones de atributos, inventario y snapshots, comprueba B1 OFF/ON mediante `CheckoutProductEligibilityGuard` (incluido `FOR UPDATE`), y asegura que un CSV válido + inválido dispara I05 sin cambios remanentes en productos/variantes/stock/atributos.

**Límite expreso:** no simula HTTP autenticado, formulario/CSRF, una compra con `OrderFromIntentService` ni un cargo real de Mercado Pago. Es un E2E SQL entre módulos, no el E2E completo de usuario o pasarela. Las concurrencias de `OrderFromIntentService` y webhook tienen evidencia privada anterior documentada separadamente.

## Criterio de aceptación

Únicamente marcar este punto como **E2E SQL aprobado** si el log termina con:

- `exit=0`;
- `[transversal-import-category-checkout] OK` y todas las aserciones;
- ausencia de errores en archivo de evidencia;
- proceso/datos de QA eliminados y puerto 18989 libre.

Ante cualquier error, permanecer **NO GO** y corregir el defecto antes de repetir. El usuario solo debe entregar el log para la auditoría del chat principal. No ejecutar SQL, envíos, cobros, devoluciones o ajustes de datos sobre la base habitual.

## Resultado oficial de ejecución local — 22/10/2026 22:16

**El propietario ejecutó el runner local y completó el E2E SQL con 40/40 PASS, exit=0**, MariaDB privada de 65 tablas; el principal revisó la evidencia original y verificó limpieza. **No es necesario volver a ejecutar el comando para este punto.** La ejecución inicial fallida por falta de imagen del fixture se resolvió exclusivamente habilitando `catalog_allow_publish_without_images=1` en la base temporal; la política comercial permaneció intacta.

El cierre y SHA del archivo de resultados se encuentran en `docs/EVIDENCIA_E2E_SQL_TRANSVERSAL_20261022.md`. E2E HTTP de pedido y MP real son alcances separados no acreditados por esta suite.
