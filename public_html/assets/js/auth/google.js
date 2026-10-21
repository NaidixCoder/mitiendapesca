// assets/js/auth/google.js (v3 - base meta fixed + debug)
export function init() {
  // Toggle mostrar/ocultar clave en login clásico
  document.querySelectorAll('[data-auth-toggle-password]').forEach((btn) => {
    if (btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';

    btn.addEventListener('click', () => {
      const selector = btn.getAttribute('data-target') || '';
      const input = selector ? document.querySelector(selector) : null;
      if (!(input instanceof HTMLInputElement)) return;

      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.classList.toggle('is-visible', !showing);
      btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
      btn.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });
  });

  const el = document.getElementById('google-btn-container');
  if (!el) return;

  console.log('[GIS] google.js v4 loaded');

  const metaId = document.querySelector('meta[name="google-client-id"]')?.content?.trim();
  const dataId = el.getAttribute('data-google-client-id')?.trim();
  const CLIENT_ID = metaId || dataId || window.GOOGLE_CLIENT_ID || '';
  if (!CLIENT_ID) {
    console.warn('[GIS] GOOGLE_CLIENT_ID vacío');
    return;
  }

  const metaBase = document.querySelector('meta[name="app-base-url"]')?.content?.trim();
  const baseHref = metaBase || new URL('.', document.baseURI).href;

  const oauthUri = new URL('oauth/google', baseHref).href;

  let busy = false;
  let rendered = false;

  function loadGoogleSdk() {
    if (window.google?.accounts?.id) {
      return Promise.resolve(window.google);
    }

    if (window.__googleSdkPromise) {
      return window.__googleSdkPromise;
    }

    window.__googleSdkPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-google-gsi]');

      const bindScript = (scriptEl) => {
        scriptEl.addEventListener('load', () => resolve(window.google), { once: true });
        scriptEl.addEventListener('error', () => reject(new Error('No se pudo cargar Google Identity Services')), { once: true });
      };

      if (existing) {
        if (window.google?.accounts?.id) {
          resolve(window.google);
          return;
        }
        bindScript(existing);
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.defer = true;
      script.dataset.googleGsi = '1';
      bindScript(script);
      document.head.appendChild(script);
    });

    return window.__googleSdkPromise;
  }

  async function postCredential(credential) {
    const params = new URLSearchParams();
    params.set('id_token', credential);

    // Volver al lugar tras login (si viene de un redirect por sesión expirada)
    try {
      const next = new URLSearchParams(window.location.search).get('next');
      if (next) params.set('next', next);
    } catch {}

    const res = await fetch(oauthUri, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: params.toString()
    });

    const ct = (res.headers.get('content-type') || '').toLowerCase();

    if (!ct.includes('application/json')) {
      const txt = await res.text();
      throw new Error(`Respuesta no JSON (${res.status}). ${txt.slice(0, 200)}`);
    }

    const data = await res.json();

    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Error al validar Google');
    }

    return data;
  }

  function render() {
    if (rendered) return;
    if (!window.google?.accounts?.id) return;
    rendered = true;

    el.style.minHeight = '48px';

    /* global google */
    google.accounts.id.initialize({
      client_id: CLIENT_ID,
      callback: async ({ credential }) => {
        if (busy) return;
        busy = true;

        try {
          el.classList.add('site-opacity-70', 'site-pointer-events-none');

          const data = await postCredential(credential);

          // Tu backend devuelve { redirect }
          const redirectTo = data.redirect || new URL('cuenta', baseHref).href;
          window.location.assign(redirectTo);
        } catch (err) {
          console.error('[GIS] Error:', err);
          alert(err.message || 'Error al validar Google');

          busy = false;
          el.classList.remove('site-opacity-70', 'site-pointer-events-none');
        }
      },
      auto_select: false,
      cancel_on_tap_outside: true,
      use_fedcm_for_prompt: true
    });

    el.innerHTML = '';
    google.accounts.id.renderButton(el, {
      theme: 'outline',
      size: 'large',
      type: 'standard',
      shape: 'rectangular',
      text: 'continue_with'
    });

    try { google.accounts.id.prompt(); } catch {}
  }

  async function boot() {
    try {
      await loadGoogleSdk();
      render();
    } catch (err) {
      console.error('[GIS] SDK de Google no disponible', err);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => boot(), { once: true });
  } else {
    boot();
  }
}
