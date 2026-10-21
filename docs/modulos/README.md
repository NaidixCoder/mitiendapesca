# Informes vivos por módulo

Esta carpeta es la fuente de entrega del trabajo de cada chat especializado. Cada uno actualiza solamente su archivo: categorias.md, marcas.md, atributos.md, marketing.md o importaciones.md, según corresponda. El chat principal consulta los archivos mediante Desktop Commander, sin necesidad de copiar mensajes entre chats.

## Procedimiento obligatorio
1. Antes de trabajar, leer docs/README.md, docs/COORDINACION.md, docs/PROTOCOLO_GIT_MULTICHAT.md, docs/GUIA_VISUAL_ADMIN_V3.md y contratos aplicables.
2. Mantener actualizado el .md del propio módulo después de cada etapa relevante, corrección y prueba. No esperar a que el propietario lo solicite de nuevo.
3. Documentar hallazgos, evidencia, correcciones reales, archivos modificados, pruebas ejecutadas y pendientes. Diferenciar código inspeccionado, pruebas automatizadas y prueba autenticada real.
   **Si se usa un navegador automatizado**, leer `docs/pruebas/PROTOCOLO_NAVEGADORES.md`, lanzarlo en modo headless por defecto y cerrar sus pestañas, contextos, navegador y procesos propios al terminar, incluso ante fallos; registrar el resultado del cierre.
4. **Obligatorio para todo Admin:** aplicar `docs/GUIA_VISUAL_ADMIN_V3.md` v2.0, reutilizar clases reales `av3-ui-*` y variables existentes, mostrar mobile-first desde la base, y verificar 363, 390, 768, 1024 y 1600 px. Indicar explícitamente duplicaciones o excepciones justificadas.
5. Si un cambio afecta archivos compartidos, registrarlo y solicitar coordinación antes de modificar.
6. No editar simultáneamente docs/ESTADO_DESARROLLO.md, MANIFEST.md ni otros documentos globales. El chat principal actualiza el estado general y realiza Git.
7. Marcar el estado como En curso / Bloqueado / Listo para revisión. No declarar cerrado algo que tenga pruebas funcionales pendientes.

## Formato mínimo
Estado y fecha; alcance; hallazgos con prioridad y estado; cambios concretos y archivos; CSS y responsive; pruebas y evidencia; riesgos y pendientes; entrega al chat principal.
