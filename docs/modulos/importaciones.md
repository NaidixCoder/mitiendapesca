# MiTiendaPesca — Importaciones Admin V3 · informe vivo del módulo

> Inicio de coordinación: 2026-10-21 · Responsable de las implementaciones: **nuevo chat especializado de Importaciones**.
> **Estado:** VENTANA 1 (HTML/CSS/UX) AUTORIZADA, **SIN IMPLEMENTAR TODAVÍA**. El propietario no ha aprobado ninguna nueva interfaz ni cambios de motor.

## Documento rector

Leer primero [Auditoría y plan](../PLAN_IMPORTACIONES_ADMIN_V3.md), además de `docs/COORDINACION.md` y `docs/GUIA_VISUAL_ADMIN_V3.md`. La auditoría inicial corresponde al chat principal; el especialista **no debe volver a escribirla completa**. Su primera tarea es ejecutar la Ventana 1 autorizada y mantener este documento actualizado.

## Situación inicial verificada

- Pantallas: `/admin/imports` (carga/historial), `POST /admin/imports/preview` (dry-run), `POST /admin/imports/apply`, `/admin/imports/show?id=...` (detalle/auditoría), `POST /admin/imports/rollback`, `/admin/imports/template` y `/admin/imports/export`; rutas antiguas redirigen o delegan.
- Solo CSV implementado en endpoint (máximo 10MB), aunque la vista inicial dice «CSV o Excel». El CSV oficial usa separador `;` y template/export actuales de 32 encabezados. No alterar.
- Arquitectura con `ImportsController.php` 787 líneas, `ImportService.php` 586, importadores especializados y repositorios; ViewModels y presenter; JS `preview.js`. Sin CSS de página `imports.css`.
- Problemas de UI y coherencia: 67 caracteres literales U+FFFD en cuatro parciales index, clases heredadas y 3 superficies descoordinadas; previews, tablas y badges sin patrón Admin V3.
- Riesgos funcionales catalogados en plan rector I05–I12; **ninguno implementado ni probado E2E en esta auditoría**.
- `php -l` exitoso en 7 archivos PHP focales. GET sin sesión devolvió HTTP 401; no hubo navegador autenticado, ejecución de importación, modificación DB ni gate global.
- Baseline visual exigido: Dashboard y Pedidos, además de `docs/GUIA_VISUAL_ADMIN_V3.md`. Atributos y Marcas tienen antecedentes de resultados técnicos correctos pero visuales no aprobados; **no copiar sus pantallas como patrón por defecto**.

## Alcance operativo de VENTANA 1

El nuevo chat implementará composición real mobile-first, tres estados (carga / preview / detalle), accesibilidad, normalización de warnings, corrección de textos, CSS único `av3-imports__*` y contrato específico sin tocar backend. Ver [sección 5](../PLAN_IMPORTACIONES_ADMIN_V3.md).

**Reservas:** vistas y parciales Importaciones, JS `admin/imports/preview.js`, CSS nuevo exclusivo y pruebas del módulo. Presenter/ViewModels propios solo para ajustes estrictamente presentacionales con justificación. No Controller/motor/importadores/repositorios, ni infraestructura/globales sin segunda ventana.

## Bitácora de implementación — a completar por nuevo chat

| Etapa | Estado | Evidencia exigida |
|---|---|---|
| Ventana 1: HTML/CSS/UX | **Autorizada, pendiente** | Antes/después, 363/390/768/1024/1600, PHP/JS/contrato, cambios de interfaz y hooks conservados |
| Ventana 2: refactor no semántico | **No autorizada** | Contrato export/import, compatibilidad y rendimiento |
| Ventana 3: integridad y rollback | **No autorizada** | MySQL descartable, pruebas de fallos y decisión comercial previa |
| Ventana 4: QA final | **No autorizada** | HTTP autenticado seguro, diseño aceptado por propietario |

## Entrega requerida al nuevo chat

Al cerrar cada ventana: enumerar archivos propios modificados; explicar reglas y contratos preservados, pruebas ejecutadas **y** no ejecutadas, capturas real/sintético, errores, bloqueos, resultados de gate, cumplimiento del protocolo de navegadores y pendientes. **No hacer commits/push** ni declarar aceptación visual por pasar tests.
