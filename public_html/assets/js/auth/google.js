// Auth Google module
export function init() {
  if (!(document.body && document.body.dataset.page === 'auth-google')) return;
  (function(){
// --- original auth-google.js start ---
(function () {
    const container = document.getElementById('google-btn-container');
    const CLIENT_ID = window.GOOGLE_CLIENT_ID;
    const loginUri = (window.APP_BASE_URL || '') + '/oauth/google';

    if (!container) {
        console.warn('[GIS] container no encontrado');
        return;
    }
    if (!CLIENT_ID) {
        console.warn('[GIS] GOOGLE_CLIENT_ID vacío');
        return;
    }

    function render() {
        try {
            google.accounts.id.initialize({
                client_id: CLIENT_ID,
                callback: async ({
                    credential
                }) => {
                    try {
                        const fd = new FormData();
                        fd.append('id_token', credential);
                        const r = await fetch(loginUri, {
                            method: 'POST',
                            body: fd,
                            credentials: 'include'
                        });
                        const data = await r.json().catch(() => ({}));
                        if (r.ok && data.ok) location.href = data.redirect || '/cuenta';
                        else alert(data.error || 'No se pudo iniciar sesión con Google.');
                    } catch {
                        alert('Error de red con el login de Google.');
                    }
                },
                ux_mode: 'popup',
                auto_select: false
            });

            google.accounts.id.renderButton(container, {
                type: 'standard',
                theme: 'outline',
                size: 'large',
                text: 'continue_with',
                shape: 'pill'
            });
            // opcional: one-tap (comentado)
            // google.accounts.id.prompt();
        } catch (e) {
            console.error('[GIS] error al inicializar:', e);
        }
    }

    function waitGoogle(maxTries = 200) {
        if (window.google && google.accounts && google.accounts.id) return render();
        if (maxTries <= 0) return console.error('[GIS] SDK no disponible (timeout)');
        setTimeout(() => waitGoogle(maxTries - 1), 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitGoogle);
    } else {
        waitGoogle();
    }
})();
// --- original auth-google.js end ---
  })();
}
