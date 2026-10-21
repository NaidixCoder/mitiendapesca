# ETAPA 06 - Hardening y Gate

## Objetivo
Asegurar que el orden no se degrade otra vez.

## Alcance
- scripts/build_gate.*
- scripts/build_gate_smoke.php
- docs de convenciones

## Tareas
1. Agregar checks de estructura:
   - maximo de lineas por archivo clave
   - prohibicion de facades legacy
   - prohibicion de SQL fuera de repositories
2. Agregar smoke de clases/metodos por modulo.
3. Agregar check de ownership frontend (importes prohibidos entre modulos no relacionados).
4. Documentar reglas de arquitectura y ejemplos.
5. Definir protocolo de rollback por etapa.

## Criterio de cierre
- Gate falla ante regresiones de estructura.
- Equipo puede continuar con reglas claras.
- Roadmap actualizado.
