# ETAPA 07 - Views Clean (Admin)

Objetivo: mantener `app/Views/pages/admin` en modo render-only, sin logica de negocio ni transformaciones.

## Estado final
- 07.1 auditoria: completada
- 07.2 base viewmodels: completada
- 07.3 migracion por modulo: completada
- 07.4 enforcement gate: completada
- 07.5 smoke y cierre: completada

## Regla de oro
En vistas solo se permite:
- `echo`, `if` de render, `foreach` de render
- acceso a variables ya preparadas (`$vm`, `$rows`, etc.)

No se permite:
- closures / funciones
- parseos (`json_decode`, `explode`, etc.)
- normalizacion de filtros
- calculos de negocio / mapeos de estado

## Fuente activa
1. `etapa_07_03_migracion_por_modulo.md` (si aparece deuda nueva)
2. `etapa_07_05_smoke_cierre.md` (cierre tecnico + smoke final)
