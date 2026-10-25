# Diagnóstico MAIN — viabilidad de QA local 05–08
Fecha: 2026-10-25. Base de revisión Git: c8ea623. Estado: infraestructura básica presente; no equivale a ejecución funcional 05–08.

## Comprobación ejecutada en equipo autorizado
- PHP CLI C:\xampp\php\php.exe 8.2.12; php actual no apto para publicar sin parche. MariaDB mysqld/mysql presentes; database/schema.sql y vendor/autoload.php presentes.
- Python 3.13/3.10/3.8 instalados; Node presente. netstat no reportó listeners en rango QA revisado 188xx/189xx (instantánea, revalidar antes de cada prueba). HTTP localhost XAMPP no respondió al especialista 08; NO es motivo para activar/alterar Apache compartido.
- Ejecutores existentes: scripts/dev/run_checkout_http_private.ps1 (puertos 18897/18896, DB 18997), scripts/dev/settings_http_mariadb_private.py (18887/18987), scripts/dev/auditoria_http_mariadb_private.py (18883/18983), scripts/dev/facturacion_domain_smoke_private.php (SQLite memoria), scripts/dev/facturacion_visual_cdp_private.mjs; scripts/dev/checkout_webhook_simulated_isolated.php exige DSN exacto y puede borrar tablas.
- Precaución: la existencia de un ejecutor NO autoriza ampliarlo ni reutilizar la DB que crea. Cada especialista debe respetar controles expresos y no ejecutar scripts destructivos sin verificar datadir y propiedad de la instancia. Ningún script de 05–08 fue ejecutado por MAIN durante este diagnóstico.

## Decisión de MAIN
05 pagos: priorizar análisis y simulaciones ya seguras; el runner webhook destructivo requiere DB privativa dedicada verificada y reserva del entorno. Sin instancia aprobada NO EJECUTAR; informar pruebas SQL como no ejecutadas, no como imposibles.
06 facturación: hay smoke en memoria y runner de capturas. No se halló runner fiscal HTTP/MariaDB completo ya aprobado. Priorizar pruebas SQLite/in-memory focales de numeración/NC/estados con doubles, y delimitar exactamente HTTP pendiente. No tocar ARCA real ni vistas fiscales con diff ajeno.
07 ajustes: el runner settings_http_mariadb_private.py existe; revisar si puede ejecutarse de forma segura y aislada con su entorno propio y sin API externa; de ser así ejecutar solo regresión focal, no 155/155 indiscriminadamente. Correo Argentino productivo totalmente prohibido, incluso GET.
08 tienda: no depender de Apache compartido. Examinar si el runner privado existente puede servir páginas públicas sin checkout ni BD comercial usando sus propias rutas y fixture; está prohibido ejecutarlo sobre la instancia comercial o activar Apache global. Cuando no pueda separarse sin un arnés nuevo, dejar casos UI como PENDIENTE ENTORNO con fundamento.

## Límite estricto para nueva intervención
Una prueba escrita en un clon privado solo si existe mecanismo seguro, reproducible, autorizado y controlado. No crear grandes ejecutores ad hoc ni eludir avisos de seguridad. Si hay bloqueo real, documentar nombre/comando/razón, trabajo alternativo y responsable MAIN. Reutilizar cobertura previa solo en su alcance. Los hallazgos de vulnerabilidad o P0/P1 nunca se disfrazan como diferidos.

## Orden
1) Especialistas 05–08 reciben el presente diagnóstico y su mandato individual; 2) identifican un máximo de 3–5 circuitos locales ausentes; 3) ejecutan los permitidos y devuelven resultados verificables; 4) MAIN integra/corrige; 5) cierre local 05–08 o pendientes justificados. No convocar 09–10 antes del cierre de esta tanda.