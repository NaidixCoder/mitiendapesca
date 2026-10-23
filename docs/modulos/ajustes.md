# Ajustes — intervención integral Admin V3 (23/10/2026)

**Estado: cierre técnico implementado, pendiente revisión de integración por MAIN CHAT y aprobación estética del propietario.** La continuación y evidencia final reemplazan los pendientes históricos del primer informe.

## Alcance comprobado
- `SettingsController` (1167 líneas antes de esta intervención), `AppSettingsRepository`, `HomeHeroSlideRepository`, `HomeSectionsConfigService`, `EmailAutomationRepository`, ViewModels fiscales, hero y email.
- Rutas: GET/POST `/admin/settings`; GET/POST `/admin/settings/design/home-hero`; POST upload/delete-image; GET/POST `/admin/settings/design/home-sections`. La prueba de Correo Argentino tiene controlador específico.
- Nueve vistas: index, general, pagos, mayoristas, envios, fiscal, email_marketing, home_hero, home_sections. Formularios compuestos por parciales bajo `app/Views/partials/admin/settings/`.
- Persistencia principal: tabla `app_settings` (claves string), `HomeHeroSlideRepository` (filas de slides) y repositorio propio de pasos de automatización. Los secretos fiscales externos y credenciales de correo opcionalmente provienen del entorno; no se inspeccionaron valores de producción.

## Agrupación implementada en la portada
| Área | Pantalla | Configuraciones existentes | Persistencia |
|---|---|---|---|
| Tienda y catálogo | general | `catalog_hide_inactive_category_products`, `catalog_allow_publish_without_images`, `catalog_show_empty_categories`, `catalog_show_empty_brands`, `contact_support_email`, `social_*_url` | app_settings |
| Ventas y precios | pagos | `pricing_installments_enabled`, `pricing_installments_count`, `pricing_interest_rate_percent`, `pricing_debit_discount_percent`, `payments_transfer_reservation_hours` | app_settings |
| Ventas y precios | mayoristas | `wh_enabled`, `wh1_discount_percent`, `wh2_discount_percent`, `wh_round_enabled`, `wh_round_to`, `wh_round_mode` | app_settings |
| Envíos y logística | envios | `correo_*` (integración y credenciales), `shipping_*` (origen, gratuidad, pickup, paquetes, remitente) | app_settings o entorno |
| Facturación | fiscal | `fiscal_seller_*`, `arca_env`, `arca_pto_vta`, `arca_auto_emit`, `arca_auto_emit_trigger`, `arca_ws_service`, `arca_*_statuses` | app_settings |
| Comunicaciones | email-marketing | `email_marketing_*` (consentimiento, límites, tracking, topes y runner), demoras de pasos | app_settings y EmailAutomationRepository |
| Diseño y apariencia | design/home-hero | `home_hero_title`, `home_hero_description`, slides, imágenes, destino y vigencia | app_settings y slides |
| Diseño y apariencia | design/home-sections | `home_sections_config` (JSON con secciones, productos y video) | app_settings y uploads |

Las claves comerciales conservan sus nombres y valores predeterminados vigentes del controlador. No hay evidencia para habilitar una categoría autónoma de Seguridad o una pantalla de Integraciones: se retiró el enlace inoperante y Correo queda en Envíos. No se leyó ni expuso el contenido secreto de ninguna instalación.

## Modificaciones aplicadas
1. Portada real como centro de configuración, seis grupos funcionales y ocho accesos; subnavegación ordenada con enlace a inicio de Ajustes y sin Integraciones vacía. CSS específico y responsive mobile-first.
2. GET /admin/settings presenta el centro (antes redirigía conceptualmente al panel Email por default); la ruta `?tab=email-marketing` conserva su comportamiento.
3. POST con pestaña desconocida ya no cae en guardado de email: se informa error y retorna sin ejecutar esa rama.
4. Fiscal dejó de escribir `shipping_*` cuando su formulario no envía esos campos; su edición se conserva exclusivamente desde Envíos.
5. Credenciales secretas de Correo Argentino no se rellenan ni se devuelven a los campos password. Un campo vacío conserva la contraseña existente, sin borrarla accidentalmente.
6. El token web de email y la URL con token dejan de mostrarse en HTML. Se quitó el generador alternativo inseguro ante error de CSPRNG.
7. Se rechazaron rutas con segmentos de traversal o barra invertida antes de borrar archivos gestionados, tanto para hero como para upload de secciones.

## QA ejecutado y límites
- `php -l` **3/3 correcto**: controlador, portada parcial y tarjeta de runner (23/10).
- Rutas revisadas estáticamente en `config/routes.php`.
- **Pendiente:** HTTP con sesión/CSRF originales, transacciones MariaDB aisladas, ensayo de guardado/rollback, pruebas en 363/390/768/1024/1600 y regresión pública. No se modificó ninguna base comercial.
- Riesgos pendientes: los formularios aún realizan escrituras secuenciales sin transacción global; cargas de imagen y eliminación de archivos requieren mayor manejo de fallas/consistencia; consumidor externo del enlace privado debe disponer del token por un canal operativo seguro. La visibilidad previa de token fue retirada deliberadamente; ajustar aprovisionamiento antes de habilitar ese runner.
- Las claves `shipping_*` no se migraron; la corrección evita que el POST fiscal las restablezca indebidamente.
- Las pantallas derivadas mantienen el HTML Admin V3 existente; **todavía no está verificado el rediseño visual integral**.

## Archivos de esta intervención
`app/Controllers/Admin/SettingsController.php`,
`app/Views/pages/admin/settings/index.php`,
`app/Views/partials/admin/settings/index/summary.php`,
`app/Views/partials/admin/settings/email_marketing/web_runner_card.php`,
`app/Views/partials/admin/settings_subnav.php`,
`public_html/assets/css/admin-v3/pages/settings.css`,
`docs/modulos/ajustes.md`.

Sin commit, push ni operaciones SQL comerciales. La integración queda a cargo de MAIN CHAT.

## Continuación integral — cierre técnico verificable (23/10/2026)

**Estado de implementación:** ocho pantallas intervenidas y QA autenticado completo en entorno descartable. Revisión estética final del propietario y aprobación de producción permanecen externas a este informe.

### Persistencia y corrección de fallas
- Se añadió `app/Services/Admin/Settings/SettingsAtomicWriter.php`: acumula pares clave/valor y confirma en una única transacción InnoDB, controlando los errores de `AppSettingsRepository::set()` y revirtiendo todo el lote ante un fallo.
- Los guardados de General, Pagos, Mayoristas, Envíos, Fiscal y Email Marketing pasaron al escritor transaccional. Los retrasos de pasos de email se modifican dentro de esa operación. En General, la sincronización de publicación se integra como callback en la transacción; fallas SQL revierten configuración y sincronización.
- `HomeHeroSlideRepository::replaceAll()` respeta una transacción externa, sin confirmar ni revertir la transacción de su llamador. El título, descripción y reemplazo de slides del hero se escriben atómicamente.
- En `home_sections_config`, el avatar reemplazado se borra **después** de persistir el JSON; si falla el guardado se elimina el archivo nuevo, conservando el anterior. Prueba real: alta, referencia JSON, existencia del archivo, baja y limpieza física.
- En hero, subida con comprobación MIME/tamaño y CSRF; el endpoint de eliminación comprueba referencias activas, devuelve HTTP 409 si la imagen está en uso y permite borrar sólo cuando deja de estar asociada. Rechaza traversal, normaliza URLs legacy con `\\/` y elimina únicamente dentro de `uploads/home-hero`.
- Ninguna clave comercial fue renombrada o migrada. Se preservaron defaults, nombres de campos, rutas y consumidores; Fiscal no vuelve a alterar opciones de Envíos.

### Secretos y operación segura
- Los password de Correo Argentino se renderizan vacíos y el formulario vacío conserva su valor existente. El token del runner no se envía al HTML ni se registra, la vista muestra solamente estado configurado y ruta sin token.
- Se añadió `scripts/dev/settings_provision_email_token.php` de uso **exclusivamente CLI**: genera token de 256 bits mediante `random_bytes`, almacena en `app_settings` y entrega la URL exclusivamente mediante un archivo nuevo con acceso restringido fuera del proyecto; no imprime el secreto, rechaza destinos del árbol público y rotación accidental. El botón de regeneración HTTP fue retirado, y activar el runner sin clave aprovisionada no está permitido.
- La rotación deliberada requiere CLI `--rotate`, canal privado, resguardo del nuevo enlace y coordinación operativa; no se ejecutó en ninguna base comercial.

### HTML/CSS Admin V3: ocho pantallas
- `general.php`: cards diferenciadas de catálogo y redes/contacto.
- `pagos.php`: presentación agrupada de cuotas, intereses, descuentos y reserva.
- `mayoristas.php`: formulario y grid mobile-first, sección dedicada de descuentos y redondeo, CSRF corregido (`<?= csrf_field() ?>`).
- `envios.php`: paneles de Correo, checkout/retiro, remitente y estados.
- `fiscal.php`: bloques de régimen, ARCA y conciliación.
- `email_marketing.php`: suscripciones, límites, frecuencia, runner seguro y automatizaciones.
- `home_hero.php`: contenido principal, diapositivas, modal y subida, preservando `data-*` JS.
- `home_sections.php`: productos, secciones y editor dinámico, preservando `data-*` JS.
- Cada pantalla dispone de navegación por secciones y enlaces internos; estilos únicos en `public_html/assets/css/admin-v3/pages/settings.css` para layout de formularios, tarjetas, grid fluido, scroll interno de navegación, foco, paneles móviles y dimensiones compactas. Sin alterar shell/sidebar/tokens/rutas compartidas ni agregar Tailwind. Se conserva el centro de configuración de la portada de la primera etapa.

### QA verificado — sesión original, SQL real y navegador
- Suite reproducible: `python scripts/dev/settings_http_mariadb_private.py`, resultado final **155/155 PASS**. La suite clonó el código a `%TEMP%\mi_settings_http_qa_20261023` **sin .env comercial** y levantó MariaDB aislada en puerto **18987**, base `mi_settings_private_qa`, más PHP HTTP privado en **18887**; validó explícitamente `@@datadir` y `@@port` antes de toda escritura.
- Se validaron login real de admin/cliente sintéticos, GET/POST de las ocho pantallas, CSRF original (419 ante ausente/falso), bloqueo de usuario no admin, persistencia en `app_settings`, error SQL forzado y rollback InnoDB, credenciales existentes no borradas ni reveladas, token CLI sin exposición, alta/baja de imágenes físicas y sus referencias, límites de valores, y lecturas de home/carrito/checkout/pedidos/facturación sin HTTP 500.
- **Navegador real Edge headless: 40 combinaciones, 8 pantallas × 363, 390, 768, 1024 y 1600 px, todas HTTP 200, CSS específico cargado y sin desbordamiento horizontal.** Evidencia: **40 imágenes PNG** guardadas fuera del proyecto en `%TEMP%\mi_settings_visual_evidence_20261023`.
- **Teardown comprobado:** procesos propios de MariaDB y PHP finalizados; navegador/contextos cerrados; clon, base y directorio temporal privado retirados exitosamente. Ninguna tabla o configuración comercial fue modificada.
- `php -l`: **47/47 archivos** PHP propios del módulo sin errores. `git diff --check` sin errores de espacios; posibles avisos LF/CRLF no alteran código. `scripts/build/gate.php` **gate final OK** (exit 0), incluyendo PHP 907 archivos, JS 120 archivos y regresiones globales.
- Alcance de regresión: peticiones GET aisladas de tienda pública, carrito, checkout, pedidos y control de facturación, sin 500. No se realizaron cobros, emisión ARCA, emails, envíos a Correo Argentino ni E2E externos con proveedores reales; estos requieren homologación/credenciales y autorización adicional. No se confunden pruebas de lectura con transacciones comerciales reales.
- **Aprobación estética por propietario y validación en producción no otorgadas por estas pruebas.** No es un defecto técnico detectado en la suite, sino aprobación externa.

### Archivos de continuación (más los ya enumerados)
`app/Repositories/Settings/HomeHeroSlideRepository.php`,
`app/Services/Admin/Settings/SettingsAtomicWriter.php`,
`app/Services/Admin/Settings/ViewModels/SettingsEmailMarketingViewModel.php`,
las ocho `app/Views/pages/admin/settings/{general,pagos,mayoristas,envios,fiscal,email_marketing,home_hero,home_sections}.php`,
parciales específicos de General, Pagos, Mayoristas, Envíos, Fiscal y Email Marketing,
`scripts/dev/settings_http_mariadb_private.py`,
`scripts/dev/settings_provision_email_token.php`,
`public_html/assets/css/admin-v3/pages/settings.css`.
Todo sin commit, push ni modificación de archivos compartidos reservados. MAIN CHAT integra y audita.
