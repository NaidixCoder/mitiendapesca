# MiTiendaPesca — Protocolo de ejecución integral V2

**Vigente desde 2026-10-22 por decisión del propietario.** Sustituye el flujo de microventanas para Categorías, Importaciones y Atributos una vez que sus chats reciban el nuevo mandato. Las reservas estrechas y órdenes de «detenerse» de etapas anteriores en los planes históricos **no son el método operativo vigente**; mantienen valor como historial de decisiones y evidencias. Seguridad, privacidad, protección de datos, separación de dueños, decisiones comerciales y falta de autorización de producción **siguen vigentes**.

## Regla principal

**Encargo funcional completo → implementación/corrección continua → QA integral y entrega.** Corregir defectos del propio módulo no requiere nueva reserva cada vez. Tres hitos son criterios de organización, **no un límite artificial de tres mensajes**.

1. **Diagnóstico breve:** leer pruebas y decisiones existentes, identificar riesgos pendientes, verificar rutas y dependencias, definir matriz de aceptación. No repetir auditorías validadas.
2. **Ejecución integral:** implementar y refactorizar solo lo que mejora resultado/seguridad, probar focalmente, corregir hallazgos propios sin detenerse por cada archivo. Reutilizar entorno privado durante la intervención, reseteando fixtures. Priorizar P0/P1 de integridad y seguridad por encima de estética, sin abandonar UX.
3. **QA y cierre:** HTTP real autenticado donde corresponda, SQL, permisos/CSRF, escenarios negativos, concurrencia y rollback cuando importan, visual responsive real y regresiones. Gate global al cierre (repetir únicamente si una corrección posterior lo invalida). Una única bitácora concisa de alcance, pruebas, fallos no resueltos, limpieza y estado de aprobación.

**No ejecutar gate global, montar y destruir MariaDB ni pedir nueva aprobación en cada microcorrección.** Conservar pruebas focales. El especialista sigue trabajando tras un fallo de QA de su dominio.

## Coordinación: mismo working tree, propietarios exclusivos

Situación actual: `C:\xampp\htdocs\mi-pesca`, rama `main`, muchas modificaciones sin integrar de varios chats. **NO** usar `git reset/clean/stash/checkout/rebase`, no iniciar worktrees migrando cambios ajenos, ni realizar commits/push desde los especialistas. El chat principal hace **integración selectiva y auditada por dominio** cuando cada trabajo esté listo; si se adoptan worktrees será después de estabilizar el árbol, sin perder cambios.

| Dueño | Archivos propios con autonomía | Límites compartidos |
|---|---|---|
| **Importaciones** | `app/Services/Import/**`, `app/Repositories/Imports/**`, `app/Controllers/Admin/ImportsController.php`, vistas/ViewModels/assets de Importaciones, scripts `imports_*`, `docs/modulos/importaciones.md` | Repositorios `app/Repositories/Catalog/**` y Admin Atributos pertenecen a otras áreas. Preferir helper propio de Imports, no editar métodos de otros dueños sin coordinación. Nada de Checkout, Pagos o SEO. |
| **Categorías** | Admin y público propios de Categorías, jerarquía/B1, `PublicRedirectTargetResolver.php`, bloques de SEO/HEAD/UTM en `public_html/index.php`, bloque legacy de `ProductsController`, rutas HEAD públicas seguras si imprescindibles, vistas/assets/scripts de Categorías, `docs/modulos/categorias.md` | `index.php` y `routes.php` son globales: preservar boot, sesiones, GET/POST, CSRF, auth y rutas no relacionadas. No tocar Cart/Checkout/MP ni Atributos/Imports. |
| **Atributos** | Controller/Concerns, Services, Views y assets **Admin Atributos/Plantillas**, `VariantAttributeValueDeleteService`, `VariantValueSetsService`, `VariantAttributesRepository` para guard de integridad; contratos `attributes_*`, `docs/modulos/atributos.md` | No editar `RowNormalizer`/`AttributeImporter` ni otros archivos Imports; no modificar `ProductRepository` ni Cart/Checkout. |
| **Principal/Checkout** | `CheckoutIntentRepository` y correcciones de intents NULL, coordinación del caso Mercado Pago aprobado sin pedido, protocolos de conciliación, cambios transversales disputados, Git | Sin reembolsos automáticos, pagos reales ni mutaciones financieras no aprobadas. |

**Cruces:** si una corrección exige editar un archivo de otro dueño, describir el método, el riesgo y la prueba que lo exige; continuar autónomamente las demás tareas. El principal asigna **una sola intervención compartida por dependencia real**, no una cadena de micropermisos. Una vulnerabilidad crítica no se ignora: contener el flujo afectado y comunicar el riesgo; evitar bloquear lo independiente. No escribir la base comercial ni exponer credenciales; QA destructivo solo con SQL/HTTP sintético en entornos descartables.

## Tres encargos integrales

### Importaciones — terminar módulo, no seguir ventanas 1F-A2/1F-B/I05 separadas
- Completar HTTP autenticado de padre implícito (snapshot v3, auditoría, rollback), resolver CSV `attr_1/valor_1` y **trazabilidad íntegra** de relaciones `product_variant_values`, `product_attributes`, `product_attribute_values` antes de aceptar escritura de atributos.
- Auditar/resolver I05: errores por fila vs COMMIT confirmado y política segura explícita. **No cambiar silenciosamente una decisión comercial de aplicación parcial**; presentar una sola propuesta si la semántica exige aprobación, continuar en paralelo con el resto.
- Validar inventario, variantes, precios, padres, LIFO, `review_required`, fallos SQL/FS, snapshot/revert, dependencias FK, idempotencia, rendimiento y UI Admin responsive. Refactorizar lo necesario para calidad, sin reescribir funcionalidad estable por estética técnica. Informar límites de conciliación incierta y rendimiento LIFO.

### Categorías — terminar SEO, UX y QA completo
- Cerrar HEAD y limpieza UTM 6C-B2 sin 301→404, redirects abiertos o pérdida de atribución; validar GET/HEAD, B1 ON/OFF, categorías con jerarquía, sitemap/robots/canonical, legacy y 404.
- Realizar ahora QA integral de Categorías Admin/público, accesibilidad, responsive, rendimiento y regresiones. Corregir defectos propios sin esperar «Etapa 7» formal.
- CheckoutIntent NULL y pagos MP sin pedido **son responsabilidad Principal/Checkout**, no editar esos módulos a la vez que Categorías; incluir pruebas B1 de regresión y reportar dependencias.

### Atributos — cerrar integridad y visual de todas sus vistas
- Reparar P0 de integridad en eliminación de valores/relaciones de variantes y validación de plantillas, con prueba MySQL real sintética, CSRF, autorizaciones, enlaces afectados y reversibilidad.
- Completar diseño mobile-first de listado, formularios `/new`/`/edit` y editores/plantillas, coherente con Dashboard y Pedidos, usando `docs/GUIA_VISUAL_ADMIN_V3.md`, CSS único por módulo y pruebas de navegador reales en tamaños distintos.
- No declarar aprobación estética sin revisión visual del propietario. Evitar otro ciclo de mockups sin implementación.

## Definición de terminado y límites

Un módulo se entrega con funcionamiento completo de su objetivo, pruebas reales y reproducibles, UX revisable, datos íntegros y problemas conocidos de seguridad resueltos. Incidencias pendientes de otro dueño quedan asignadas con evidencia; no se maquillan como aprobación de producción. **Este protocolo no autoriza producción, alteraciones de DB real, pagos/reintegros automáticos, migraciones incompatibles ni Git por parte de especialistas.**

**Formato del mensaje del principal a cada chat:** «Leé `docs/PROTOCOLO_EJECUCION_INTEGRAL_V2.md`; reemplaza la estrategia de microventanas. Tenés autonomía integral en tu dominio. Avanzá hasta el cierre funcional y QA sin detenerte tras cada subetapa. Pedí decisión solo por archivos compartidos, riesgos financieros o reglas comerciales genuinas. Entregá un informe final, no una autorización por microcorrección».
