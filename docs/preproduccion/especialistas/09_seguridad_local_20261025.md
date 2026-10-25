# Especialista 09 — Seguridad transversal — QA exclusivamente LOCAL

Leer PLAN_QA_LOCAL_05_10.md, CONTROL_VIVO.md, COORDINACION.md, docs/seguridad y actas 01–08. Objetivo: revisar SOLO riesgos que no están cubiertos por cierres de otros especialistas. Reutilizar pruebas y evidencias existentes; no ejecutar de nuevo suites de auth/checkout/pagos por rutina.

## Ejecutar
1. Auditar límites de confianza de rutas públicas/admin/API: autorización, CSRF, IDOR, redirect/SSRF y XSS, especialmente endpoints no acreditados, sin enumeración explosiva de rutas.
2. Ejecutar pruebas focales locales sencillas y aisladas para riesgos detectados: control de acceso, sanitización, validación, uploads y secretos; distinguir revisión estática de HTTP real. No atacar infraestructura ajena ni usar datos reales.
3. Revisar dependencias/configuración sensible y logs por exposición de secretos y bypass de roles. En caso de defecto reproducible P0/P1, detener la certificación, describir reproducción e informar reserva MAIN; no modificar archivos compartidos ni hacer commits.
4. Recuperar hallazgos QA08-URL-01 (doble slash), QA08-AUTH-01 (HTTP 429) y QA08-CART-01 solo si afectan un límite de seguridad; de lo contrario documentar para pruebas de experiencia del servidor, sin reinventar cobertura UI.
5. Documentar por separado pendientes de HTTPS/cookies/proveedores externos como NO EJECUTADO, nunca PASS.

Entrega docs/preproduccion/especialistas/RESULTADO_09.md con tabla ruta→riesgo→prueba real→resultado→evidencia, clasificación y teardown. No construir runners generales nuevos, no tocar sistema comercial, no modificar docs de MAIN. Cierre APTO LOCAL CONDICIONADO / PARCIAL / BLOQUEADO DEFECTO.
