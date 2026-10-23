# MiTiendaPesca — Protocolo operativo integral V3 (chat principal)

**Vigente:** 22/10/2026 por mandato del propietario. Complementa V2; no elimina límites de seguridad, de coordinación ni decisiones comerciales.

## Ejecución por objetivo completo
1. Definir un resultado verificable y revisar solamente sus dependencias. No repetir QA aprobado sin una regresión.
2. Implementar y corregir autónomamente dentro del dominio propio; no detenerse tras cada microcambio. Escalar solo riesgos comerciales, incompatibilidades o archivos de otro responsable.
3. Ejecutar ensayos reales y reproducibles, corregir y repetir focalmente. No llamar E2E real a stubs, contratos estáticos ni pruebas con autenticación reemplazada.
4. Ejecutar el gate una vez al concluir los cambios sustantivos y registrar una entrega consolidada: archivos, defectos, pruebas, límites y limpieza.
5. Dar novedades breves ante hitos o bloqueos, no informes fragmentados.

## Protecciones obligatorias
- Único working tree en C:\xampp\htdocs\mi-pesca; respetar reservas y cambios ajenos. Sin reset/clean/stash ni commits globales. Integración Git selectiva a cargo del principal.
- Nada de SQL comercial, credenciales reales, envíos, cobros, reembolsos o producción en QA sin autorización específica.
- Bases de ensayo privadas descartables: guardias de puerto, esquema, carpetas, aislamiento del .env comercial y limpieza verificada.
- No sortear bloqueos de ejecución; los scripts marcados para ejecución exclusivamente local se respetan.
- Diferenciar resultado sintético de autenticación/CSRF originales, SQL real, concurrencia de peticiones y homologación externa.

## Criterio de salida actual
E2E SQL transversal 40/40 ya aprobado. En la nueva intervención: regresión post-commit 3/3 y HTTP sintético 17/17 aprobados, con límite explícito de stubs. Pendiente el E2E HTTP real en MariaDB privado, con sesión/CSRF originales, confirmación de transferencia y MP simulado, stock, B1 y concurrencia. Mercado Pago externo y conciliación humana son bloqueos separados de producción.

Dictamen vigente: docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md
