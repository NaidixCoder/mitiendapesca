# A1 — Autenticación y cuentas — ejecución MAIN 25/10/2026

## Objetivo
Circuito: alta de usuario, verificación de email, login, perfil, direcciones, recuperación y cambio de contraseña, sesiones, logout. Entorno XAMPP `mi_pesca_qa_local` con datos ficticios, más runner MariaDB privado aislado para pruebas sensibles de Auth.

## Resultados acreditados
- `qa_registration_full_main.py` sobre `mi_pesca_qa_local`: registro HTTP 200, emisión de email interceptado (función mail nativa deshabilitada), verificación URL por HTTP 302, cuenta 200, logout 302 y cuenta 401 tras logout. **PASS**.
- `qa_auth_http_main.py`: login con sesión real y cuenta 200. **PASS**.
- `qa_registration_negative_http_main.py`: GET registro 200, CSRF inválido 419 y campos inválidos 422. **PASS**.
- `usuarios_auth_security_private.py`: runner reutilizado (servidor PHP HTTP y MariaDB privados; datadir y puerto validados; cero .env comercial). **21/21 PASS**:
  - Login con cookie autenticada.
  - Perfil y direcciones GET 200.
  - Perfil POST sin CSRF 419, dirección alta sin CSRF 419.
  - IDOR dirección ajena GET bloqueado; borrado de dirección ajena no altera registros.
  - Formulario de reset con token válido HTTP 200; CSRF inválido 419 preserva token.
  - Reset HTTP correcto consume token una sola vez e incrementa `users.session_version`.
  - Cookie anterior revocada inmediatamente (401), token reutilizado no cambia versión.
  - Dos procesos PHP concurrentes sobre mismo token: exactamente un SUCCESS y un REJECTED, estado SQL consistente.
- Los enlaces de verificación y recuperación dejaron de registrarse con tokens en logs: corrección previamente integrada en commit `cf5adbb`.

## Sin acreditar en A1 para cierre integral
- Guardar y editar **perfil propio** vía HTTP con validación SQL.
- Crear, editar, establecer por defecto y eliminar **direcciones propias** vía HTTP, incluyendo persistencia.
- Verificar por HTTP la revocación voluntaria mediante `/cuenta/sessions/clear` con dos sesiones activas (el runner sí prueba revocación tras reset).
- Solicitud del email de recuperación por el formulario completo (el runner inyecta token sintético directamente en SQL, sin envío real; el cambio y consumo posteriores sí son HTTP).
Estos casos no se declaran PASS por inferencia. Se intentó incorporar pruebas HTTP adicionales, pero la herramienta remota bloqueó la escritura de los ejecutores; no se eludió el control.

## Dictamen
**A1 PARCIAL AVANZADO, NO CERRADO AL 100%.** Los 21/21 Auth sensibles y el registro/verificación/logout son PASS comprobados. No se debe etiquetar todo el circuito cerrado mientras estén pendientes las mutaciones funcionales del perfil y direcciones. No repetir las suites aprobadas indiscriminadamente.

## Seguridad
Sin conexiones a sistemas productivos, sin datos reales, no se tocaron bases comerciales. Servidor de QA HTTP local iniciado en puerto 18855 con bloqueo de mail nativo.
