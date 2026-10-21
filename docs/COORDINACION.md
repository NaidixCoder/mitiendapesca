# MiTiendaPesca — Coordinación entre chats

**Entrada obligatoria a la documentación:** [docs/README.md](README.md). Antes de leer planes antiguos, seguir su índice de reglas vigentes y reservas.

> Protocolo operativo v1.0 | Inicio: 2026-10-20 | Proyecto local: `C:\xampp\htdocs\mi-pesca`.
> Este documento coordina trabajo humano/asistido; **no implementa bloqueos reales ni sincroniza conversaciones automáticamente**.

## 1. Roles
- **Chat principal — Arquitectura y coordinación:** inventario, decisiones transversales, resolución de conflictos, asignaciones, auditoría e integración. Evita tocar el código de un módulo asignado.
- **Chat especializado:** implementa y prueba solo su módulo autorizado; registra cambios, contratos afectados, pruebas realizadas y pendientes.
- **Propietario del proyecto:** valida prioridades, decisiones de negocio, cambios de alcance y operaciones riesgosas.

## 2. Fuentes y jerarquía práctica
1. Decisiones expresas vigentes del propietario; ante duda o contradicción, detener el cambio conflictivo.
2. Contrato específico vigente: `CONTRATO_VISUAL_ADMIN_2026.md`, `docs/ARQUITECTURA_ADMIN_V3.md` y contratos `scripts/build/*` aplicables.
3. `PLAN_CSS_UNIFICADO.md` y `PLAN_DE_TRABAJO.md` (este último conserva las prioridades aunque su detalle de avance es anterior).
4. `MANIFEST.md` como historial y evidencia de cambios; `docs/ESTADO_DESARROLLO.md` como síntesis operativa, **no** reemplaza el historial.
5. Ante documentos desactualizados o incompatibles: dejar constancia de la discrepancia y acordar la resolución antes de implementar.

## 3. Asignaciones actuales
| Área | Responsable operativo | Situación | Regla |
|---|---|---|---|
| Arquitectura, documentación y coordinación | Chat principal (este chat) | Activo | Escribir únicamente documentación de coordinación hasta acordar un cambio técnico |
| `/admin/marketing/*` (Campañas y Email) | Otro chat de Marketing, informado por el usuario | **Trabajo en curso, reserva preventiva** | Este chat y cualquier tercero: SOLO LECTURA; no pisar cambios |
| Dashboard, Pedidos, SEO | Sin chat nuevo asignado | Implementaciones recientes documentadas; revisiones visuales pendientes | No iniciar escrituras sin nueva asignación |
| Catálogo, Clientes, Proveedores y demás módulos | Sin asignar | Por planificar | Definir alcance y archivos antes de abrir trabajo paralelo |

**Reserva preventiva:** la asignación Marketing procede de la indicación del usuario. No existe un lock técnico ni confirmación automática de que el otro chat haya leído este archivo.

## 4. Ámbitos y archivos sensibles
- Marketing: `app/Views/pages/admin/marketing_campaigns/`, `app/Views/pages/admin/marketing_email/`, `app/Views/partials/admin/marketing_*`, `app/Controllers/Admin/*Marketing*`, controladores de campañas/email, servicios y repositorios correspondientes, `public_html/assets/css/admin-v3/pages/marketing-*.css`, `public_html/assets/js/admin/marketing/` y contratos/smokes `scripts/*/marketing_*`.
- Componentes transversales: `app/Views/layouts/admin.php`, `app/Views/partials/admin/page_header.php`, `app/Views/partials/admin/sidebar_v2.php`, `app/Support/helpers.php`, `public_html/assets/css/admin-v3/{tokens,base,layout}.css`, `public_html/assets/css/admin-v3/components/`, `public_html/assets/js/admin/core/`.
- Contratos e infraestructura transversal: `config/routes.php`, `scripts/build/gate.php`, migraciones DB, modelos/servicios compartidos, `MANIFEST.md` y documentos rectores.
- **Regla:** tener una ruta asignada no autoriza cambios indirectos en archivos transversales; solicitar coordinación y reservar el archivo con precisión.

## 5. Protocolo de inicio de trabajo
1. Leer este archivo, `docs/ESTADO_DESARROLLO.md` y los cuatro documentos centrales de la sección 2.
2. Confirmar alcance funcional, rutas, archivos previstos, contratos JS/DOM, pruebas y si el módulo ya tiene responsable.
3. Antes de editar, actualizar **una sola fila de asignación** o pedir al chat principal que lo haga. No escribir si otra sesión figura activa sobre los mismos archivos.
4. No asumir que el contexto de otro chat está sincronizado: la documentación en disco es el mecanismo de traspaso.
5. Antes de cambios grandes, realizar respaldo privado **fuera** del proyecto; no crear ZIP incremental salvo orden expresa.

## 6. Durante y al cerrar cada tarea
- Trabajar en archivos acotados, conservar backend, rutas, nombres de campos, selectores JS y DB salvo autorización explícita; respetar `av3-`, CSS por ámbito y componentes comunes.
- Para un archivo compartido, detener la edición propia, registrar el conflicto y asignar un único responsable temporal. No hay ediciones simultáneas sobre el mismo archivo.
- No realizar pruebas con envíos reales de emails, trabajadores scheduler/queue, pagos, cambios de datos, migraciones ni acciones irreversibles sin entorno/alcance seguro y autorización específica.
- Al cerrar: detallar archivos tocados, funcionalidad, respaldo, resultado de `scripts/build/gate.php`, pruebas focales, validación visual real o pendiente y posibles conflictos.
- Registrar conclusión en `docs/ESTADO_DESARROLLO.md`; el chat ejecutor actualiza el `MANIFEST.md` en ventana coordinada si corresponde. No declarar aprobado algo verificado solo de forma sintética.
- Si falla una prueba, falta conexión o hay colisión de cambios, registrar bloqueo; nunca informar como concluido.

## 7. Integración y control de concurrencia
- La carpeta **no tenía `.git` al auditarse el 2026-10-20** (`git status` devolvió «not a git repository»); no existen ramas ni worktrees que aíslen cambios.
- El repositorio remoto **sí existe y es accesible en lectura**: `https://github.com/NaidixCoder/mitiendapesca.git`, rama por defecto `main` (comprobado con `git ls-remote --symref ... HEAD` el 2026-10-20). Eso **no** vincula automáticamente esta carpeta local ni demuestra permisos de escritura.
- **Actualización posterior (2026-10-20):** se inicializó Git en la carpeta XAMPP, con `origin` apuntando al remoto y rama local `main` basada en la remota. El usuario aprobó una sola carpeta y no utilizar worktrees por ahora; el chat principal administra los commits/push. Ver `docs/PROTOCOLO_GIT_MULTICHAT.md`.
- Sigue siendo obligatorio un único editor por archivo y seguimiento manual de reservas/estado; Git no bloquea archivos compartidos.
- Git, ramas o worktrees son una **mejora propuesta**, no implementada. No inicializar Git ni alterar la estructura del proyecto sin acordarlo.
- El chat principal revisa integración y documentación; NO puede dirigir ni notificar de forma autónoma a otro chat. El usuario debe transmitirle las instrucciones iniciales.

## 8. Protocolo Git y mensaje de arranque para chats especializados
- **Lectura obligatoria general:** `docs/PROTOCOLO_GIT_MULTICHAT.md` antes de cualquier operación Git, sin importar el módulo. Por ahora es un protocolo documentado, no una conexión Git activa.

«Trabajás en MiTiendaPesca, ruta `C:\xampp\htdocs\mi-pesca`. Antes de editar leé `docs/COORDINACION.md`, `docs/ESTADO_DESARROLLO.md`, `docs/PROTOCOLO_GIT_MULTICHAT.md`, `CONTRATO_VISUAL_ADMIN_2026.md`, `PLAN_CSS_UNIFICADO.md`, `MANIFEST.md` y `docs/ARQUITECTURA_ADMIN_V3.md`. Respetá la asignación de archivos, no toques áreas ajenas ni compartidas sin coordinar. NO inicialices Git ni cambies ramas hasta que el coordinador habilite el baseline. Al finalizar, registrá cambios, pruebas y pendientes; no crees ZIP incrementales».
