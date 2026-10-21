# Documentación — punto único de entrada

> **Fecha de ordenamiento:** 2026-10-20. Este índice es la primera lectura de cualquier chat. Los archivos se agrupan por utilidad; NO se inventan estados de implementación.

## Inicio de cada chat (lectura obligatoria)

1. [Coordinación y módulos reservados](COORDINACION.md) — comprobar qué área se puede tocar.
2. [Estado de desarrollo](ESTADO_DESARROLLO.md) — avances documentados, pendientes y limitaciones de prueba.
3. [Protocolo Git multichat](PROTOCOLO_GIT_MULTICHAT.md) — estrategia actual: una carpeta, rama `main` y commits coordinados; worktrees no necesarios.
4. [Plan de trabajo y prioridades](../PLAN_DE_TRABAJO.md) — conserva prioridades acordadas; parte del progreso es histórico.
5. **Si se trabaja en el administrador:** [Contrato visual](../CONTRATO_VISUAL_ADMIN_2026.md), [Arquitectura Admin V3](ARQUITECTURA_ADMIN_V3.md) y [Plan de unificación CSS](../PLAN_CSS_UNIFICADO.md).
6. **Antes de editar código:** revisar contratos y pruebas específicos; usar [MANIFEST.md](../MANIFEST.md) como historial de cambios, no como lista de tareas.

## Directorios por propósito

| Carpeta / archivo | Qué contiene | Cómo utilizarlo |
|---|---|---|
| [contratos/](contratos/README.md) | Regla de IVA, contratos de Checkout/Carrito y flujo Mercado Pago | Reglas técnicas/dominio; confirmar código y vigencia |
| [referencia/catalogo/](referencia/catalogo/README.md) | Diseño, decisiones, atributos, filtros, tags y etapas de Catálogo | Referencia de decisiones históricas; NO es tablero actual |
| [referencia/variantes/](referencia/variantes/) | Mapa técnico de refactor de productos/variantes | Inventario de referencia |
| [planes/](planes/README.md) | Webhook, remediaciones, reseñas, home, refactor y pendientes generales | Propuestas y seguimiento sujetos a verificación |
| [checklists/](checklists/README.md) | SEO prepublicación y seguridad preproducción | Reevaluar contra código vigente antes de publicar |
| [seguridad/](seguridad/) | Notas y riesgos de carrito/checkout | Pendientes sensibles; nunca declarar cerrados sin evidencia |
| [pruebas/](pruebas/) | Planes de validación manual | Ejecutar según entorno y necesidad |
| [editorial_guidelines/](editorial_guidelines/) | Criterios editoriales y slugs | Guía de contenido |
| [archivo/](archivo/README.md) | Planes V2, auditorías antiguas y etapas ya cerradas | Histórico solamente; no son instrucciones actuales |

## Documentos que permanecen en posiciones compatibles

- [Auditoría CSS](AUDITORIA_CSS_2026.md) y [auditoría de encabezados](AUDITORIA_ADMIN_ENCABEZADOS.md): scripts existentes las consultan/generan por esas rutas.
- [Design System Tabler](ADMIN_DESIGN_SYSTEM_TABLER.md): fotografía de octubre 2026, con pendientes que pueden estar superados; priman contrato visual y arquitectura actuales.
- [MANIFEST.md](../MANIFEST.md), [PLAN_CSS_UNIFICADO.md](../PLAN_CSS_UNIFICADO.md), [CONTRATO_VISUAL_ADMIN_2026.md](../CONTRATO_VISUAL_ADMIN_2026.md) y [PLAN_DE_TRABAJO.md](../PLAN_DE_TRABAJO.md): rutas conocidas por otros chats y scripts; no moverlas durante el trabajo activo de Marketing.

## Reglas de mantenimiento documental

- Documento nuevo: clasificar por propósito; **no** dejar planes sueltos en la raíz salvo necesidad expresa.
- Una decisión actual se escribe en el contrato vigente y se registra su implementación en MANIFEST; no crear varios planes que compitan por autoridad.
- Un documento obsoleto se mueve a `archivo/` si aporta historia verificable; se elimina solo si no aporta información única y después de respaldo.
- No cambiar nombres/rutas de contratos usados por scripts sin actualizar referencias y probar consumidores.
- Archivos de un módulo reservado solo los modifica su chat responsable. Los históricos no son autorización para borrar código o migraciones.
- El índice se actualiza cuando nace una familia nueva de documentación o cambia la ubicación de un documento rector.

## Inicio rápido de módulo

**Primero** coordinación, estado y Git. **Después** contrato técnico del área y evidencia reciente. **Luego** alcance y archivos asignados. **Finalmente** implementación, pruebas y documentación. No modificar código de otro chat.
