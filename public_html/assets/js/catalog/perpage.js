// public_html/assets/js/catalog/perpage.js
(function () {
  const LS_PER = 'catalog:perPage';
  const LS_ORD = 'catalog:orden';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn, { once: true });
  }

  function getParam(name) {
    const p = new URLSearchParams(location.search);
    return p.get(name);
  }

  ready(function () {
    const perSel = document.querySelector('select.js-perpage-select');
    const ordSel = document.querySelector('select.js-order-select');

    // 1) Cargar preferencia previa si no viene en URL (opcional "amable")
    const urlHasPer = getParam('perPage') || getParam('per_page');
    if (perSel && !urlHasPer) {
      const last = localStorage.getItem(LS_PER);
      if (last && ['12','24','48'].includes(last)) {
        if (perSel.value !== last) {
          perSel.value = last;
          // enviar form una vez para aplicar
          const form = perSel.form;
          if (form && !form.hasAttribute('data-autosubmitted')) {
            form.setAttribute('data-autosubmitted', '1');
            form.submit();
            return;
          }
        }
      }
    }

    const urlHasOrd = getParam('orden');
    if (ordSel && !urlHasOrd) {
      const lastOrd = localStorage.getItem(LS_ORD);
      if (lastOrd && ordSel.querySelector(`option[value="${CSS.escape(lastOrd)}"]`)) {
        if (ordSel.value !== lastOrd) {
          ordSel.value = lastOrd;
          const form = ordSel.form;
          if (form && !form.hasAttribute('data-autosubmitted')) {
            form.setAttribute('data-autosubmitted', '1');
            form.submit();
            return;
          }
        }
      }
    }

    // 2) Guardar preferencia al cambiar + autosubmit
    if (perSel) {
      perSel.addEventListener('change', () => {
        localStorage.setItem(LS_PER, perSel.value);
        perSel.form && perSel.form.submit();
      });
    }
    if (ordSel) {
      ordSel.addEventListener('change', () => {
        localStorage.setItem(LS_ORD, ordSel.value);
        ordSel.form && ordSel.form.submit();
      });
    }
  });
})();
