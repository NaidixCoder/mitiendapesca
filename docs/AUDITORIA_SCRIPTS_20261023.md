# Auditoría del directorio scripts — 23/10/2026

## Inventario verificado
Antes de limpieza: 329 archivos versionados: scripts raíz 7, audit 5, build 49, cron 2, dev 265 y dev/__pycache__ 1. Muchas pruebas son herramientas de regresión manual y no tienen necesariamente referencias automáticas.

## Archivos retirados con baja justificada
Cinco utilidades temporales creadas para la homologación provisional de MP, sin referencias de ejecución en app/config/database/scripts, más un archivo binario generado por Python:
- scripts/dev/mp_staging_private.ps1 — staging y Quick Tunnel experimental ya descartado.
- scripts/dev/mp_test_connectivity_readonly.php — sondeo puntual de API.
- scripts/dev/mp_test_preference_live.php — prueba puntual de preferencia.
- scripts/dev/preparar_credenciales_mp_test.ps1 — plantilla redundante respecto del .env existente.
- scripts/dev/inspeccionar_mp_env_seguro.ps1 — inspección puntual de .env.
- scripts/dev/__pycache__/migrate_admin_headers_full.cpython-313.pyc — bytecode generado, no código fuente.

Los informes previos conservan referencias históricas a estos scripts, pero sus ejecutables fueron retirados. No se ha eliminado ninguna base de datos ni configuración comercial.

## Scripts retenidos intencionalmente
- scripts/build: contratos automatizados y gate.
- Runner privado de Checkout, EJECUTAR_QA_CHECKOUT.cmd, fixtures y scripts relacionados: regresión y evidencia; runner HTTP privado se mantiene de ejecución local por propietario.
- Pruebas de concurrencia, postcommit y orquestador integral: regresiones reutilizables.
- Resto de scripts/dev relativos a categorías, importaciones, UI, marketing y migraciones: conservar hasta verificar el ciclo de vida de cada módulo.

**Criterio:** no borrar por ausencia de referencias; borrar sólo cuando el propósito caduque y no existan consumidores operativos. Una limpieza mayor necesita revisar lotes por módulo, no una supresión indiscriminada.
