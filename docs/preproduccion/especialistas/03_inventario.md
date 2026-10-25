# MiTiendaPesca — Chat especialista 3: Inventario, compras e importaciones

**Mandato:** auditoría y pruebas E2E exhaustivas sobre funcionalidades existentes; NO reconstruir módulos ni modificar sin reserva.

## Áreas y objetivos

Áreas 6,7,19. Movimientos, proveedores, compras, recepción, stock, import/export, rollback I05 y concurrencia.

Código/rutas de investigación: Controladores/servicios Inventory, Suppliers, Purchases, Imports.

Evidencias previas para reutilizar: Proveedores 36/36, Importaciones 76 HTTP/SQL, transversal 40/40.

Coordinación: Reservar stock compartido con 02 y 04.

## Documentación obligatoria

Leer antes de actuar: docs/PLAN_MAESTRO_PREPRODUCCION.md (incluido §7: OWASP ASVS/PHP), docs/preproduccion/CHECKLIST_21_AREAS.md, docs/preproduccion/MATRIZ_E2E.md, inventarios CSV de rutas, docs/COORDINACION.md, docs/PROTOCOLO_GIT_MULTICHAT.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md, docs/PROTOCOLO_EJECUCION_INTEGRAL_V3.md, docs/EJECUCION_E2E_MARIADB_PRIVADA.md, docs/pruebas/PROTOCOLO_NAVEGADORES.md, docs/seguridad/ y docs/modulos/ relevantes. Consultar las evidencias de QA transversal y CIERRE_MERCADOPAGO cuando aplique. No dar por vigente una conclusión histórica superada.

## Protocolo obligatorio

1. Inventariar las rutas reales, sus controladores, traits, dependencias, roles, CSRF, mutaciones, tablas y consumidores; incluir AJAX/API/cron/webhooks.
2. Vincular checklist + ASVS aplicable a suites anteriores; registrar diferencia entre contrato estático, SQL aislado, HTTP autenticado, navegador y E2E transaccional. No repetir sin motivo y no asumir un PASS ajeno.
3. Ejecutar nuevos casos solo en clon aislado, MariaDB privada descartable y cuentas sintéticas; verificar datadir/puerto y no usar .env comercial. No usar proveedores reales, pagos, facturas ni emails sin permiso.
4. Probar camino feliz, errores, entrada maliciosa segura, controles por rol e ID, CSRF, límite, rollback, concurrencia e idempotencia según riesgo. Capturar esperado/observado HTTP+UI+SQL.
5. Si se confirma defecto, documentar pasos y P0/P1/P2/P3, solicitar reserva de archivos; no sobrescribir cambios ajenos, ni rutas/migraciones/helpers compartidos. Solo MAIN hace commits e integra.
6. Cerrar browsers/procesos propios, remover entorno descartable y verificar teardown. Dejar scripts, fecha, commit, resultado, capturas redaccionadas, límites y pendientes. **No proclamar E2E completo sin recorrido real.**

## Entrega del especialista

Crear solo docs/preproduccion/especialistas/RESULTADO_03.md con matriz caso→ruta→ASVS→evidencia→resultado→defecto, pruebas previas reaprovechadas, pruebas nuevas, riesgos P0/P1, regresiones y teardown. MAIN audita independientemente y decide cierre. Cualquier excepción que requiera propietario se eleva antes de operar.
