# Mi Tienda Pesca — Estructura Base

**Fecha:** 2025-10-03

## Estructura
- `public_html/` → zona pública (index, assets, uploads)
- Core fuera de `public_html`: `app/`, `config/`, `database/`, `storage/`, `vendor/`, `.env`

## Primeros pasos
1. Copiá `.env.example` a `.env` y ajustá credenciales.
2. Asegurate que `BASE_PATH` en `public_html/index.php` apunta a **este** directorio.
3. (Opcional) Ejecutá `composer dump-autoload` si usás Composer.

## Seguridad
- Archivos sensibles fuera de `public_html`.
- `.htaccess` para hardening en `public_html` y bloqueo en `uploads/`.
- Cookies de sesión seguras, CSRF (placeholder), rate limit (placeholder).
