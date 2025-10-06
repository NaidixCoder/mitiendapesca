# Fix pack — bootstrap + helpers
Este pack reemplaza archivos truncados con `...` y restablece los helpers críticos.

## Archivos incluidos
- `config/boot/04-router.php` — normaliza URI y expone helpers `request_uri`, `request_method`, `uri_segment`, `is_api`.
- `config/boot/06-url_helpers.php` — `base_url`, `asset`, `public_url`, `e`, `wants_html`.
- `app/Views/helpers.php` — `render_page`, `section`, `flash`, `redirect`, `json`.

> No modifica tu `config/bootstrap.php`. Asegurate de que cargue **todos** los `config/boot/*.php` y **luego** `app/Views/helpers.php`.

## Cómo aplicar
1. Backup de tu proyecto.
2. Copiar estos archivos sobre las rutas correspondientes (sobrescribir).
3. Verificar que `config/bootstrap.php` contenga algo como:
   ```php
   if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
   foreach (glob(BASE_PATH.'/config/boot/*.php') as $f) require_once $f;
   require_once BASE_PATH.'/app/Views/helpers.php';
   ```
4. Probar:
   - `php -S 127.0.0.1:8000 -t public_html`
   - Abrir `http://127.0.0.1:8000/`

## Notas
- Si algún archivo adicional quedó con `...`, repetí el proceso para ese archivo.
