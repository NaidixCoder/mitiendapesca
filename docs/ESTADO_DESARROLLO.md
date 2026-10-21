# MiTiendaPesca — Estado de desarrollo y auditoría de coordinación

> Corte documental inicial: 2026-10-20. Mantenido por el chat principal y los chats de módulo.
> Leyenda: **observado** = comprobado en archivos/comandos de esta auditoría; **documentado** = consta en documentos del proyecto, no reejecutado aquí; **pendiente** = falta confirmar.

## 1. Panorama operativo
| Área | Estado según documentación | Responsable asignado | Próximo control |
|---|---|---|---|
| Arquitectura admin V3 / shell / Page Header | Implementaciones documentadas; verificaciones visuales puntuales pendientes | Chat principal (coordinación, solo lectura del código) | Revisión integrada de escritorio/móvil en sesión real |
| Dashboard `/admin` | Rediseño de columnas independientes, KPIs y filas móviles documentado | Sin chat especializado activo | Revisar 363/768/1024/1600 px en navegador autenticado |
| Pedidos `/admin/orders` | Listado, tabs, filtros, pagos y entregas diferenciados documentados | Sin chat especializado activo | Validar operación y visual autenticada; no confundir con detalle |
| SEO administrativo | Auditoría y editores migrados a admin-v3 según documentos | Sin chat especializado activo | Confirmar interacciones reales en sesión autenticada |
| Marketing `/admin/marketing/*` | Campañas y Email V3 fases 1–5 documentadas; ciclo de vida reforzado | **Otro chat trabajando; reserva preventiva** | Persistencia/programación por HTTP en entorno controlado; ningún envío real hasta autorizarlo |
| CSS/legacy transversal | Unificación en curso; retirada total de dependencias no concluida | Sin editor asignado | Identificar consumidores antes de eliminar CSS/Tailwind compartido |
| Tienda pública / Checkout / Producción | Fuera del rediseño actual del admin | Sin editor asignado | Preservar experiencia pública; auditoría de seguridad antes de publicar |

## 2. Evidencia reciente comprobada en documentación
- `CONTRATO_VISUAL_ADMIN_2026.md`: identidad `#006B6B`, fondo `#F6F8FA`, superficie blanca, texto `#182B39`; tipografía operativa 14px, densidad equilibrada y responsive por componente.
- `docs/ARQUITECTURA_ADMIN_V3.md`: CSS único por módulo y `av3-` como prefijo; JS desacoplado por `data-*`; se documentan cambios de Dashboard, SEO, Campañas y Email.
- `PLAN_CSS_UNIFICADO.md`: estado «EN EJECUCIÓN»; Marketing y Email incorporados a `marketing-campaigns.css` y `marketing-email.css`; no retirar componentes legacy usados en otras vistas.
- `MANIFEST.md`: última entrada de 2026-10-20 reporta build gate **OK (812 PHP, 118 JS)**, smoke MySQL transaccional con `ROLLBACK` y UI sintética sin POST. **No es una ejecución hecha por este chat**.
- `MANIFEST.md`: el ciclo de vida de campañas protege edición, programación y cancelación por estado; la evidencia documentada no incluye guardado/programación autenticados sobre HTTP ni envío real.
- `scripts/build/gate.php`: observado: usa análisis sintáctico y contratos de admin, Marketing y Email; el encabezado declara que no conecta a DB. **No se corrió aquí** para evitar lecturas sobre archivos posiblemente en edición simultánea.
- Comprobados en disco: vistas `app/Views/pages/admin/marketing_campaigns/`, `marketing_email/`, hojas `public_html/assets/css/admin-v3/pages/marketing-*.css`, JS `public_html/assets/js/admin/marketing/` y contratos de build Marketing/Email.
- Al ejecutar `git -C C:\xampp\htdocs\mi-pesca status --short --branch` se obtuvo «not a git repository» (**observado**). Sin control de versiones local verificable en esta carpeta.
- `git ls-remote --symref https://github.com/NaidixCoder/mitiendapesca.git HEAD` confirmó el remoto y la rama por defecto `main`; HEAD al verificar: `d8f6df3c9b48f52958cef2f94913d53f68569945`. La carpeta actual **no** quedó conectada por esta comprobación.
- `.gitignore` local excluye `.env`, `vendor/`, `storage/*` y logs, pero **no excluye los ZIP de respaldo ni todos los artefactos del proyecto**; revisar antes de hacer `git add` o cualquier publicación.

## 3. Hallazgos de la auditoría documental
| Prioridad | Hallazgo | Impacto / tratamiento |
|---|---|---|
| Alta | No existe repositorio Git en la ruta auditada | Riesgo de sobrescritura entre chats; coordinar editores por archivo y estudiar Git/worktrees sin inicializarlos de oficio |
| Alta | Marketing se modifica desde otro chat, sin lock automático | Reserva preventiva y lectura únicamente desde el chat principal hasta liberar el módulo |
| Alta | Pruebas Marketing reales pendientes | No equiparar UI sintética, smoke con `ROLLBACK` y build gate con un envío o guardado autenticado real |
| Media | `PLAN_DE_TRABAJO.md` tiene fecha 2026-10-17 y tareas de Dashboard antiguas | Mantener prioridades vigentes, pero no usar sus checkboxes como fotografía del avance del 20/10 |
| Media | `docs/ADMIN_DESIGN_SYSTEM_TABLER.md` enumera Pedidos como pendiente de adaptación | Evidencia histórica desactualizada respecto del contrato/manifest de 20/10; actualizar tras coordinación |
| Media | `docs/ARQUITECTURA_ADMIN_V3.md` comienza con «Etapa 2: adopción progresiva pendiente» | El encabezado es genérico y debe leerse junto con entradas posteriores; no significa que Marketing o Pedidos no existan |
| Media | `MANIFEST.md` es un historial extenso con múltiples áreas y etapas | Usar este resumen para orientación, sin borrar ni reescribir la trazabilidad anterior |

## 4. Tablero de asignaciones y bloqueos
| Fecha | Trabajo | Responsable | Estado | Archivos / límites |
|---|---|---|---|---|
| 2026-10-20 | Arquitectura y coordinación entre chats | Chat principal | Activo | Solo `docs/COORDINACION.md` y este documento; resto en lectura |
| 2026-10-20 | Marketing: campañas y email | Otro chat mencionado por el usuario | En curso (declarado por el usuario) | Reserva de rutas Marketing y archivos funcionales relacionados; no abrir ediciones paralelas |
| 2026-10-20 | Dashboard, Pedidos, SEO y demás módulos | Pendiente de asignación | Sin inicio en este esquema | No se reservaron archivos adicionales |

## 4.1 Protocolo Git multichat (2026-10-20)
- Creado y verificado `docs/PROTOCOLO_GIT_MULTICHAT.md` para cualquier chat/tarea: ramas por tarea, worktrees por carpeta, ciclo de commits/revisión, integración, seguridad y vista previa con XAMPP.
- Es **documentación**, no una conexión Git: no se ejecutaron clone/init/fetch/commit/push, ni se cambiaron rutas servidas por Apache.
- El trabajo actual sigue con cambios instantáneos en la carpeta que sirve XAMPP; cambios en un futuro worktree separado requerirán URL propia o integración para verse en esa URL.

## 5. Próximas acciones recomendadas
1. Comunicar al chat de Marketing la existencia de `docs/COORDINACION.md` y pedirle que lo lea antes de su siguiente cambio, sin interrumpir una escritura en curso.
2. Al concluir Marketing: registrar qué cambió desde este corte, pruebas realizadas, archivos compartidos tocados y pendientes, y liberar su reserva.
3. Cuando no haya editores activos sobre archivos compartidos: correr build gate y revisar que la ejecución sea consistente con el Manifest.
4. Planificar baseline de control de versiones excluyendo secretos, datos reales y respaldos; no ejecutar `git init` ni un commit sin acuerdo.
5. Actualizar resúmenes históricos al cerrar etapas, evitando reescribir decisiones que siguen vigentes.

## 6. Plantilla para nuevas entradas
**Fecha / módulo / chat responsable / estado:** ...
**Archivos modificados y archivos compartidos:** ...
**Alcance funcional y contratos preservados:** ...
**Pruebas ejecutadas (salida real) / visual autenticada / pendientes:** ...
**Respaldo, riesgos, bloqueos, decisiones a escalar y siguiente paso:** ...

## 7. Límites del corte inicial
- Esta auditoría revisó estructura, documentación y presencia de archivos; **no** auditó línea por línea todo Marketing ni comprobó el comportamiento autenticado en navegador.
- No se alteraron código de Marketing, rutas, base de datos, workers, CSS, contratos existentes ni documentos históricos.
- Como el otro chat puede seguir implementando, el estado de Marketing reflejado aquí puede cambiar después de esta fecha; verificar la documentación fresca antes de actuar.

## Ordenamiento documental (2026-10-20)
- Se revisaron 55 archivos Markdown; 38 se reubicaron por categoría y 2 se retiraron del árbol activo tras respaldo verificado.
- Índice para cualquier chat: `docs/README.md` y protocolo `docs/COORDINACION.md`.
- La nota única sobre favoritos de Mi cuenta se preservó en `docs/planes/PENDIENTES_POR_REVISAR.md`.
- El respaldo completo, con huellas de archivos originales, quedó fuera del proyecto en `C:\xampp\mi-pesca-backups\md_reorg_20261020`.
- Contrato visual, plan CSS, arquitectura V3, Manifest y código Marketing no se modificaron durante el ordenamiento.
