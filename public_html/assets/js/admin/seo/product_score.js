/**
 * Admin > Productos > Tab SEO
 * Score + Checklist + Preview SERP (live)
 *
 * Regla de oro: JS siempre en archivos separados.
 */

export function init() {
  const root = document.querySelector('[data-seo-score]');
  if (!root) return;

  const cfgRaw = root.getAttribute('data-seo-score-config') || '{}';
  let cfg = {};
  try { cfg = JSON.parse(cfgRaw); } catch { cfg = {}; }

  const siteName = String(cfg.siteName || '');
  const baseUrl = String(cfg.baseUrl || '');
  const productName = String(cfg.productName || '');
  const slug = String(cfg.slug || '');
  const hasCover = !!cfg.hasCover;
  const hasPrice = !!cfg.hasPrice;

  const $scoreValue = root.querySelector('[data-seo-score-value]');
  const $pill = root.querySelector('[data-seo-score-pill]');
  const $bar = root.querySelector('[data-seo-score-bar]');

  const $serpUrl = root.querySelector('[data-seo-serp-url]');
  const $serpTitle = root.querySelector('[data-seo-serp-title]');
  const $serpDesc = root.querySelector('[data-seo-serp-desc]');

  const $metaTitle = document.querySelector('input[name="meta_title"]');
  const $metaDesc = document.querySelector('textarea[name="meta_description"]');

  const $titleLen = document.querySelector('[data-seo-title-len]');
  const $descLen = document.querySelector('[data-seo-desc-len]');

  const DOT_OK = 'av3-bg-emerald-600';
  const DOT_WARN = 'av3-bg-amber-500';

  function textLen(s) {
    // Cuenta “caracteres” de forma razonable (unicode-safe)
    return Array.from(String(s || '')).length;
  }

  function setDot(key, ok) {
    const dot = root.querySelector(`[data-seo-check-dot="${CSS.escape(key)}"]`);
    if (!dot) return;
    dot.classList.remove(DOT_OK, DOT_WARN);
    dot.classList.add(ok ? DOT_OK : DOT_WARN);
  }

  function setDetail(key, txt) {
    const el = root.querySelector(`[data-seo-check-detail="${CSS.escape(key)}"]`);
    if (!el) return;
    el.textContent = String(txt || '');
  }

  function setPill(score) {
    if (!$pill) return;
    $pill.classList.remove('av3-bg-emerald-600', 'av3-bg-amber-500', 'av3-bg-rose-600');
    if (score >= 85) $pill.classList.add('av3-bg-emerald-600');
    else if (score >= 65) $pill.classList.add('av3-bg-amber-500');
    else $pill.classList.add('av3-bg-rose-600');
  }

  function setBar(score) {
    if (!$bar) return;
    const s = Math.max(0, Math.min(100, Number(score) || 0));
    $bar.style.width = `${s}%`;
    $bar.classList.remove('av3-bg-emerald-600', 'av3-bg-amber-500', 'av3-bg-rose-600');
    if (s >= 85) $bar.classList.add('av3-bg-emerald-600');
    else if (s >= 65) $bar.classList.add('av3-bg-amber-500');
    else $bar.classList.add('av3-bg-rose-600');
  }

  function compute(metaTitle, metaDesc) {
    const title = String(metaTitle || '').trim();
    const desc = String(metaDesc || '').trim();

    const titleL = textLen(title);
    const descL = textLen(desc);

    const titleOk = title !== '' && titleL >= 25 && titleL <= 60;
    const descOk = desc !== '' && descL >= 70 && descL <= 160;

    // Details
    const titleDetail = title === '' ? 'Falta.' : (titleL > 60 ? 'Muy largo.' : (titleL < 25 ? 'Muy corto.' : 'OK'));
    const descDetail  = desc === '' ? 'Falta.' : (descL > 160 ? 'Muy larga.' : (descL < 70 ? 'Muy corta.' : 'OK'));

    // Score (mismo espíritu que SeoAuditService)
    let score = 0;
    score += productName !== '' ? 15 : 0;
    score += slug !== '' ? 10 : 0;
    score += titleOk ? 20 : 10;
    score += descOk ? 20 : 10;
    score += hasCover ? 20 : 0;
    score += hasPrice ? 15 : 0;
    score = Math.min(100, score);

    // SERP
    const url = (() => {
      const prefix = baseUrl ? baseUrl.replace(/\/$/, '') : '';
      if (!slug) return `${prefix}/producto`;
      return `${prefix}/producto?slug=${encodeURIComponent(slug)}`;
    })();
    const serpTitle = title || (productName ? `${productName} | ${siteName || 'Mi Pesca & Aventura'}` : `Producto | ${siteName || 'Mi Pesca & Aventura'}`);
    const serpDesc = desc || (productName || 'Producto');

    return { score, titleOk, descOk, titleDetail, descDetail, titleL, descL, url, serpTitle, serpDesc };
  }

  function render() {
    const metaTitle = $metaTitle ? $metaTitle.value : '';
    const metaDesc = $metaDesc ? $metaDesc.value : '';

    const r = compute(metaTitle, metaDesc);

    if ($scoreValue) $scoreValue.textContent = String(r.score);
    setPill(r.score);
    setBar(r.score);

    if ($serpUrl) $serpUrl.textContent = r.url;
    if ($serpTitle) $serpTitle.textContent = r.serpTitle;
    if ($serpDesc) $serpDesc.textContent = r.serpDesc;

    // Checklist live
    setDot('meta_title', r.titleOk);
    setDetail('meta_title', r.titleDetail);
    setDot('meta_desc', r.descOk);
    setDetail('meta_desc', r.descDetail);

    // Counters
    if ($titleLen) $titleLen.textContent = String(r.titleL);
    if ($descLen) $descLen.textContent = String(r.descL);
  }

  // Hooks
  const onInput = (ev) => {
    if (!ev || !ev.target) return;
    if (ev.target === $metaTitle || ev.target === $metaDesc) render();
  };

  document.addEventListener('input', onInput);
  render();
}
