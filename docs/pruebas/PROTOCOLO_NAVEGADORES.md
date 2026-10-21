# Protocolo obligatorio — navegadores de pruebas (MiTiendaPesca)

> Vigente desde 2026-10-21. Aplica a **todos los chats y módulos** que ejecuten Edge, Chrome, Firefox, Playwright, Puppeteer, WebDriver o scripts con un navegador, en la PC compartida por Desktop Commander.

## Objetivo

Evitar ventanas «Mi Pesca & Aventura» acumuladas en Alt+Tab, procesos huérfanos, perfiles temporales abiertos y conflictos con el Firefox personal o con otros chats. **Cerrar recursos propios es responsabilidad del chat que los crea, aun si una prueba falla.**

## Reglas obligatorias

1. **Modo headless por defecto** para capturas, smoke, responsive y E2E automáticos. No abrir ventanas visibles en el escritorio del propietario, salvo autorización expresa y una prueba que realmente requiera interfaz.
2. **Aislamiento**: crear perfil/contexto independiente y directorio temporal propio de la prueba. No usar el perfil del Firefox habitual ni el perfil de otro chat.
3. **Control explícito de ciclo de vida**: guardar identificadores de navegador/proceso que lance el propio script; cerrar páginas, contextos y navegador en un bloque `finally`/teardown que se ejecute incluso si hay excepción, fallo o cancelación controlada.
4. Si se inicia navegador por `subprocess`/PowerShell, terminar **solo** el árbol de procesos que ese script haya creado, dentro de un timeout razonable, primero cierre normal y luego forzado exclusivamente sobre esos PID comprobados si fuese necesario.
5. No usar `taskkill /IM chrome.exe /F`, `taskkill /IM msedge.exe /F`, `taskkill /IM firefox.exe /F` ni cerrar procesos por nombre globalmente. Tampoco reiniciar Explorer, detener XAMPP, VS Code o Desktop Commander como método de limpieza.
6. Al finalizar, verificar que **no quedan ventanas ni procesos propios de la prueba**; comprobar que se liberaron perfiles temporales y el puerto del servidor de prueba (si hubo uno). El servidor/BD de pruebas aislados deben seguir su protocolo de preservación o cierre documentado.
7. Ante una interrupción abrupta que impida ejecutar `finally`, al recuperar la sesión identificar PID/fecha/comando **propios** y limpiar únicamente esos recursos. Si no se puede atribuir un proceso con seguridad, **no terminarlo**: informar al chat principal.
8. Retirar HTML, perfiles y archivos temporales creados por esa ejecución cuando ya no se necesiten, conservando únicamente capturas/evidencias y respaldos necesarios **fuera** de la carpeta del proyecto.
9. Registrar en el informe del módulo o documento E2E: motor usado, headless o visible, tamaños probados, tipo de sesión (real/sintética), resultado del teardown y si quedó algún recurso o ventana pendiente.

## Patrón recomendado de Playwright (Python)

```python
from playwright.sync_api import sync_playwright

with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    context = None
    try:
        context = browser.new_context(viewport={"width": 390, "height": 844})
        page = context.new_page()
        # ... ejecutar capturas / aserciones ...
    finally:
        try:
            if context is not None:
                context.close()
        finally:
            browser.close()
```

Si hay herramientas de prueba distintas, usar su equivalente a `finally`/teardown. Evitar lanzar un navegador externo desde un script que no controla su cierre.

## Checklist obligatorio al cerrar una prueba

- [ ] Cerré todas las pestañas/páginas y contextos creados.
- [ ] Cerré la instancia de navegador de la prueba, aun con fallo.
- [ ] Confirmé que no quedaron procesos/ventanas creados por mi ejecución.
- [ ] Cerré los servidores/puertos temporales que inicié o documenté su preservación.
- [ ] Limpié los temporales propios y registré evidencias sin dejar artefactos en `public_html/`.
- [ ] Documenté el teardown en el informe del módulo.

**Importante:** un cierre correcto evita nuevos procesos huérfanos, pero no garantiza borrar entradas fantasma ya presentes en Alt+Tab. La reparación de Explorer es un procedimiento aparte que requiere consentimiento del propietario.
