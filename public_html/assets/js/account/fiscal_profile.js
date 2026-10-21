// Datos fiscales (Mi Cuenta)
// - Valida CUIT por padrón (best effort)
// - No guarda automático: el guardado es por submit del formulario

function digitsOnly(s) {
  return (s || '').toString().replace(/\D+/g, '');
}

function isValidCuitLocal(cuit) {
  const d = digitsOnly(cuit);
  if (d.length !== 11) return false;
  const mult = [5,4,3,2,7,6,5,4,3,2];
  let sum = 0;
  for (let i=0;i<10;i++) sum += parseInt(d.charAt(i),10) * mult[i];
  let mod = 11 - (sum % 11);
  if (mod === 11) mod = 0;
  if (mod === 10) mod = 9;
  return mod === parseInt(d.charAt(10),10);
}

function setPill(box, status) {
  const pill = box.querySelector('[data-fiscal-status-pill]');
  if (!pill) return;
  pill.classList.remove('pill--ok', 'pill--warn', 'pill--err', 'pill--muted');
  if (status === 'valid') {
    pill.classList.add('pill--ok');
    pill.textContent = 'Validado';
  } else if (status === 'pending') {
    pill.classList.add('pill--warn');
    pill.textContent = 'Pendiente';
  } else if (status === 'invalid') {
    pill.classList.add('pill--err');
    pill.textContent = 'No validado';
  } else {
    pill.classList.add('pill--muted');
    pill.textContent = 'Sin validar';
  }
}

async function postJson(url, payload, csrfToken) {
  const r = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken || ''
    },
    body: JSON.stringify(payload)
  });
  let data = null;
  try { data = await r.json(); } catch (_) {}
  return { ok: r.ok, status: r.status, data };
}

export function initAccountProfile() {
  const box = document.getElementById('fiscalProfile');
  if (!box) return;

  const validateUrl = box.getAttribute('data-validate-url');
  if (!validateUrl) return;

  const taxSel = box.querySelector('[data-fiscal-tax]');
  const cuitInp = box.querySelector('[data-fiscal-cuit]');
  const btnVal  = box.querySelector('[data-fiscal-validate]');
  const helper  = box.querySelector('[data-fiscal-helper]');
  const errP    = box.querySelector('[data-fiscal-error]');
  const csrfInp = box.querySelector('input[name="_csrf"]');

  if (!taxSel || !cuitInp || !btnVal || !csrfInp) return;

  const showError = (msg) => {
    if (!errP) return;
    errP.textContent = msg || '';
    errP.classList.toggle('ui-is-hidden', !msg);
  };

  const showHelper = (html) => {
    if (!helper) return;
    helper.innerHTML = html;
  };

  const syncState = () => {
    const tc = (taxSel.value || 'consumidor_final');
    const isCF = tc === 'consumidor_final';
    cuitInp.disabled = isCF;
    btnVal.disabled = isCF;
    if (isCF) {
      cuitInp.value = '';
      showError('');
      showHelper('No necesitás CUIT para consumidor final.');
      setPill(box, 'unverified');
    } else {
      if (!cuitInp.value) showHelper('Ingresá el CUIT (11 dígitos). Lo validamos con AFIP/ARCA.');
    }
  };

  taxSel.addEventListener('change', syncState);
  syncState();

  btnVal.addEventListener('click', async () => {
    const tc = (taxSel.value || 'consumidor_final');
    const cuit = digitsOnly(cuitInp.value);

    if (tc === 'consumidor_final') return;

    if (cuit.length !== 11) {
      showError('El CUIT debe tener 11 dígitos.');
      setPill(box, 'invalid');
      return;
    }
    if (!isValidCuitLocal(cuit)) {
      showError('CUIT inválido (dígito verificador).');
      setPill(box, 'invalid');
      return;
    }

    showError('');
    setPill(box, 'pending');
    showHelper('Validando con padrón...');

    const { ok, data } = await postJson(validateUrl, { tax_condition: tc, cuit }, csrfInp.value);
    const status = (data && data.status) ? data.status : (ok ? 'pending' : 'invalid');
    const legal = (data && data.legal_name) ? data.legal_name : '';
    const msg   = (data && data.message) ? data.message : '';

    setPill(box, status);

    if (status === 'valid') {
      showError('');
      showHelper(`Razón social: <strong>${legal}</strong>`);
    } else if (status === 'pending') {
      showError('');
      showHelper('No pudimos validar ahora. Podés guardar igual y reintentar luego.');
    } else {
      showError(msg || 'CUIT no válido para facturación.');
      showHelper('Revisá el CUIT o elegí “Consumidor final”.');
    }
  });
}
