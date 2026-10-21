# MiTiendaPesca — Git y coordinación de chats

> Protocolo vigente: 2026-10-20. Aplica a todas las tareas, independientemente del chat o del módulo.
> Estrategia aprobada: **una única carpeta de trabajo, rama main y coordinación por archivos**. Los worktrees son opcionales para el futuro; no se usan ahora.

## 1. Entorno y repositorio

- Carpeta de desarrollo y XAMPP: `C:\xampp\htdocs\mi-pesca`.
- Repositorio remoto: `https://github.com/NaidixCoder/mitiendapesca.git`.
- Rama de integración: `main`. La copia local se inicializó y conectó a `origin` el 20/10/2026, conservando el historial remoto.
- **Fuente de verdad para esta migración:** los archivos locales. La publicación inicial requiere revisar exclusiones de seguridad y confirmar el push; no asumir que ya está publicada si no hay verificación.
- Cada chat debe leer primero `docs/README.md`, `docs/COORDINACION.md` y `docs/ESTADO_DESARROLLO.md`.

## 2. Trabajo habitual: una carpeta, cambios inmediatos

- Los chats trabajan directamente en la misma carpeta mediante Desktop Commander, **solo en los módulos que tienen asignados**.
- No cambiar la rama activa de esa carpeta mientras otros chats trabajan. No usar `checkout`, `switch`, `pull`, `reset`, `clean`, `rebase` ni `stash` unilateralmente.
- XAMPP sirve el árbol local. Los cambios se ven en la URL existente al refrescar el navegador, con recarga forzada cuando haya caché.
- Git **no** resuelve automáticamente las colisiones entre chats que escriben sobre un mismo archivo. Coordinar archivos compartidos y mantener un único editor por archivo.
- No se crea una rama nueva por cada tarea en esta modalidad: el chat principal gestiona los commits ordenados en `main` después de las validaciones.

## 3. Responsabilidades

- **Chat principal:** coordina permisos, revisa `git status`, define límites de cada commit, verifica archivos staged, crea commits y publica en `main` cuando los cambios están terminados.
- **Chat especializado:** implementa el módulo asignado, ejecuta pruebas, registra los archivos tocados, documenta pendientes y avisa cuándo está listo para incluirse en un commit. No hace push/merge ni cambia de rama por su cuenta.
- **Propietario:** decide prioridades, cambios de alcance, publicación a producción y operaciones de riesgo.
- Si una tarea necesita `config/routes.php`, un layout global, CSS compartido, migraciones o documentos rectores, solicitar coordinación antes de modificar.

## 4. Publicación segura

1. Identificar qué cambios están terminados; **no** incluir por accidente archivos que otro chat todavía esté editando.
2. Comprobar `git status --short`, `git diff --check` y diferencias de archivos afectados.
3. Revisar exclusiones: nunca publicar `.env`, credenciales, backups, ZIP, bases reales, datos de `uploads/`, sesiones, datos privados ni material temporal.
4. Preparar archivos explícitos cuando haya tareas simultáneas: `git add -- <archivos>`. Revisar `git diff --cached` y `git diff --cached --stat` antes de hacer commit.
5. Ejecutar contratos y build gate que correspondan; distinguir pruebas automáticas/sintéticas de pruebas autenticadas.
6. Commit descriptivo (`tipo(modulo): resumen`). Push a `origin/main` **sin `--force`** y comprobar el commit remoto.
7. Registrar SHA, pruebas, pendientes y archivos en `docs/ESTADO_DESARROLLO.md` o `MANIFEST.md` en una ventana de edición coordinada.

## 5. Si aparecen cambios externos o conflictos

- Si `origin/main` avanzó, no ejecutar `pull` automático mientras haya modificaciones locales activas: coordinar una ventana de integración y respaldo.
- Nunca ejecutar `reset --hard`, `clean -fd`, `push --force` ni eliminar trabajo ajeno.
- Git conserva versiones de archivos versionados, **no** es reemplazo del backup de `.env`, MySQL, `storage/` o `uploads/`.
- La misma base de datos, sesiones, cron y correos se comparten aunque trabajemos en chats separados. No efectuar pruebas con escrituras o envíos reales sin autorización y entorno seguro.

## 6. ¿Cuándo usar ramas y worktrees?

**No hacen falta ahora.** Se considerarían solo si necesitamos trabajar con versiones de código incompatibles o desarrollar y probar funcionalidades separadas sin tocar la carpeta principal. En ese escenario, cada worktree tendría carpeta física distinta, URL de pruebas propia y aislamiento de datos/servicios.

La mera existencia de varios chats **no obliga a usar worktrees**. Para nuestro flujo actual, son suficientes un único árbol, responsables claros, y commits coordinados.

## 7. Entrega de una tarea

Indicar módulo, archivos editados, funcionalidades, resultado de pruebas, evidencia visual, cambios de base de datos, riesgos, estado de la tarea y si está lista para commit. El chat principal se ocupa de integrar y publicar.
