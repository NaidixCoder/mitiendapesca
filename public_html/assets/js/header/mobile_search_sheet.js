const RECENT_KEY = 'tp_recent_searches_v1';
const RECENT_MAX = 8;

function lockBodyScroll() {
  const body = document.body;
  const count = Number(body.dataset.phScrollLockCount || '0');
  if (count === 0) {
    const scrollbar = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
    body.dataset.phPrevPaddingRight = body.style.paddingRight || '';
    if (scrollbar > 0) {
      body.style.paddingRight = `${scrollbar}px`;
    }
    body.classList.add('ph-no-scroll');
  }
  body.dataset.phScrollLockCount = String(count + 1);
}

function unlockBodyScroll() {
  const body = document.body;
  const count = Number(body.dataset.phScrollLockCount || '0');
  if (count <= 1) {
    body.classList.remove('ph-no-scroll');
    body.style.paddingRight = body.dataset.phPrevPaddingRight || '';
    delete body.dataset.phPrevPaddingRight;
    delete body.dataset.phScrollLockCount;
    return;
  }
  body.dataset.phScrollLockCount = String(count - 1);
}

function safeLoadRecent() {
  try {
    const raw = localStorage.getItem(RECENT_KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr.filter((x) => typeof x === 'string') : [];
  } catch {
    return [];
  }
}

function safeSaveRecent(list) {
  try {
    localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_MAX)));
  } catch {
    // ignore
  }
}

function addRecent(term) {
  const t = String(term || '').trim().replace(/\s+/g, ' ');
  if (t.length < 2) return;
  const list = safeLoadRecent();
  const next = [t, ...list.filter((x) => x.toLowerCase() !== t.toLowerCase())];
  safeSaveRecent(next);
}

function clearRecent() {
  try { localStorage.removeItem(RECENT_KEY); } catch {
    // ignore
  }
}

function debounce(fn, ms) {
  let timer = null;
  return (...args) => {
    if (timer) window.clearTimeout(timer);
    timer = window.setTimeout(() => fn(...args), ms);
  };
}

export function init() {
  const root = document.querySelector('[data-ph-header-root]');
  if (!root || root.dataset.phSearchBound === '1') return;

  const overlay = root.querySelector('[data-ph-search-overlay]');
  const sheet = root.querySelector('[data-ph-search-sheet]');
  const openers = root.querySelectorAll('[data-ph-search-open]');
  const inputOpeners = root.querySelectorAll('[data-ph-search-open-input]');
  const closers = root.querySelectorAll('[data-ph-search-close]');
  const searchForm = root.querySelector('.ph-search-form-sheet');
  const searchInput = root.querySelector('.ph-search-form-sheet [data-search-input]');
  const chips = root.querySelectorAll('[data-ph-search-fill]');
  const recentBlock = root.querySelector('[data-ph-search-recent-block]');
  const recentList = root.querySelector('[data-ph-search-recent-list]');
  const recentClear = root.querySelector('[data-ph-search-recent-clear]');
  const resultsWrap = root.querySelector('[data-ph-search-results]');
  const emptyText = root.querySelector('[data-ph-search-empty]');

  if (!overlay || !sheet || !searchInput || !resultsWrap || !emptyText) return;
  root.dataset.phSearchBound = '1';
  let closeTimer = null;

  const productsUrl = new URL(
    (searchForm?.getAttribute('action') || '/productos'),
    window.location.href
  );
  const appBasePath = productsUrl.pathname.replace(/\/productos\/?$/, '');
  const goToSearch = (term) => {
    const q = String(term || '').trim().replace(/\s+/g, ' ');
    if (q.length < 2) return;
    addRecent(q);
    const target = new URL(window.location.origin + appBasePath + '/productos');
    target.searchParams.set('q', q);
    window.location.href = target.toString();
  };

  const renderRecent = () => {
    if (!recentBlock || !recentList) return;
    const list = safeLoadRecent();
    recentList.innerHTML = '';
    if (!list.length) {
      recentBlock.hidden = true;
      return;
    }
    recentBlock.hidden = false;

    list.slice(0, RECENT_MAX).forEach((q) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ph-search-chip';
      btn.textContent = q;
      btn.addEventListener('click', () => {
        goToSearch(q);
      });
      recentList.appendChild(btn);
    });
  };

  const renderResultsHtml = (html) => {
    const out = String(html || '').trim();
    resultsWrap.innerHTML = out;
    if (out === '') {
      emptyText.hidden = false;
      emptyText.textContent = 'Sin resultados para esta busqueda.';
      return;
    }
    emptyText.hidden = true;
  };

  const runLiveSearch = debounce(async (term) => {
    const q = String(term || '').trim();
    if (q.length < 2) {
      resultsWrap.innerHTML = '';
      emptyText.hidden = false;
      emptyText.textContent = 'Escribe al menos 2 caracteres para buscar.';
      return;
    }

    try {
      const u = new URL(window.location.origin + appBasePath + '/productos/suggest-fragment');
      u.searchParams.set('q', q);
      const res = await fetch(u.toString(), { headers: { Accept: 'text/html' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      renderResultsHtml(html);
    } catch {
      resultsWrap.innerHTML = '';
      emptyText.hidden = false;
      emptyText.textContent = 'No se pudieron cargar resultados.';
    }
  }, 220);

  const open = () => {
    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }
    if (!sheet.hidden) {
      window.setTimeout(() => searchInput.focus(), 30);
      return;
    }
    renderRecent();
    overlay.hidden = false;
    sheet.hidden = false;
    overlay.classList.remove('is-open');
    sheet.classList.remove('is-open');
    void sheet.offsetWidth;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        overlay.classList.add('is-open');
        sheet.classList.add('is-open');
      });
    });
    lockBodyScroll();
    window.setTimeout(() => searchInput.focus(), 30);
  };

  const close = () => {
    if (sheet.hidden) return;
    overlay.classList.remove('is-open');
    sheet.classList.remove('is-open');
    if (closeTimer) clearTimeout(closeTimer);
    unlockBodyScroll();
    closeTimer = window.setTimeout(() => {
      overlay.hidden = true;
      sheet.hidden = true;
      closeTimer = null;
    }, 220);
  };

  openers.forEach((el) => el.addEventListener('click', (ev) => {
    ev.preventDefault();
    open();
  }));
  inputOpeners.forEach((el) => {
    el.addEventListener('focus', () => {
      if (sheet.hidden) open();
    });
    el.addEventListener('click', () => {
      if (sheet.hidden) open();
    });
  });
  closers.forEach((el) => el.addEventListener('click', close));
  overlay.addEventListener('click', close);

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const value = chip.getAttribute('data-ph-search-fill') || '';
      searchInput.value = value;
      searchInput.focus();
      runLiveSearch(value);
    });
  });

  if (recentClear) {
    recentClear.addEventListener('click', () => {
      clearRecent();
      renderRecent();
    });
  }

  searchInput.addEventListener('input', (ev) => {
    runLiveSearch(ev.target.value || '');
  });

  if (searchForm) {
    searchForm.addEventListener('submit', () => {
      addRecent(searchInput.value || '');
    });
  }

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && !sheet.hidden) close();
  });
}
