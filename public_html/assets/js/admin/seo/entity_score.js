/**
 * Admin > Categorías / Marcas > SEO PRO
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
  const entityName = String(cfg.entityName || '');
  const slug = String(cfg.slug || '');
  const entityType = String(cfg.entityType || ''); // category|brand

  const $scoreValue = root.querySelector('[data-seo-score-value]');
  const $pill = root.querySelector('[data-seo-score-pill]');
  const $bar = root.querySelector('[data-seo-score-bar]');

  const $serpUrl = root.querySelector('[data-seo-serp-url]');
  const $serpTitle = root.querySelector('[data-seo-serp-title]');
  const $serpDesc = root.querySelector('[data-seo-serp-desc]');

  const $metaTitle = document.querySelector('input[name="meta_title"]');
  const $metaDesc  = document.querySelector('textarea[name="meta_description"]');
  const $seoText   = document.querySelector('textarea[name="seo_text"]');

  const $titleLen = document.querySelector('[data-seo-title-len]');
  const $descLen  = document.querySelector('[data-seo-desc-len]');
  const $textLen  = document.querySelector('[data-seo-text-len]');

  const DOT_OK = 'av3-bg-emerald-600';
  const DOT_WARN = 'av3-bg-amber-500';

  function textLen(s) {
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

  function buildUrl() {
    const prefix = baseUrl ? baseUrl.replace(/\/$/, '') : '';
    const path = (entityType === 'brand') ? '/marca' : '/categoria';
    if (!slug) return `${prefix}${path}`;
    return `${prefix}${path}?slug=${encodeURIComponent(slug)}`;
  }

  function compute(metaTitle, metaDesc, seoText) {
    const title = String(metaTitle || '').trim();
    const desc  = String(metaDesc || '').trim();
    const text  = String(seoText || '').trim();

    const titleL = textLen(title);
    const descL  = textLen(desc);
    const textL  = textLen(text);

    const titleOk = title !== '' && titleL >= 25 && titleL <= 60;
    const descOk  = desc !== '' && descL >= 70 && descL <= 160;

    // SEO text: recomendado >= 120 chars (modo landing)
    const textOk = textL >= 120;

    const titleDetail = title === '' ? 'Falta.' : (titleL > 60 ? 'Muy largo.' : (titleL < 25 ? 'Muy corto.' : 'OK'));
    const descDetail  = desc === '' ? 'Falta.' : (descL > 160 ? 'Muy larga.' : (descL < 70 ? 'Muy corta.' : 'OK'));
    const textDetail  = text === '' ? 'Opcional, pero recomendado.' : (textOk ? 'OK' : 'Muy corto (recomendado ≥ 120).');

    // Score
    let score = 0;
    score += entityName !== '' ? 20 : 0;
    score += slug !== '' ? 15 : 0;
    score += titleOk ? 20 : 10;
    score += descOk ? 20 : 10;

    if (text === '') score += 0;
    else if (textOk) score += 25;
    else score += 10;

    score = Math.min(100, score);

    const url = buildUrl();

    const serpTitle = title || (entityName ? `${entityName} | ${siteName || 'Mi Pesca & Aventura'}` : `${entityType === 'brand' ? 'Marca' : 'Categoría'} | ${siteName || 'Mi Pesca & Aventura'}`);
    const serpDesc = desc || (text ? text.slice(0, 160) : (entityName || (entityType === 'brand' ? 'Marca' : 'Categoría')));

    return { score, titleOk, descOk, textOk, titleDetail, descDetail, textDetail, titleL, descL, textL, url, serpTitle, serpDesc };
  }

  function render() {
    const r = compute($metaTitle?.value, $metaDesc?.value, $seoText?.value);

    if ($scoreValue) $scoreValue.textContent = String(r.score);
    setPill(r.score);
    setBar(r.score);

    if ($serpUrl) $serpUrl.textContent = r.url;
    if ($serpTitle) $serpTitle.textContent = r.serpTitle;
    if ($serpDesc) $serpDesc.textContent = r.serpDesc;

    setDot('meta_title', r.titleOk);
    setDetail('meta_title', r.titleDetail);
    setDot('meta_desc', r.descOk);
    setDetail('meta_desc', r.descDetail);
    setDot('seo_text', r.textOk);
    setDetail('seo_text', r.textDetail);

    if ($titleLen) $titleLen.textContent = String(r.titleL);
    if ($descLen) $descLen.textContent = String(r.descL);
    if ($textLen) $textLen.textContent = String(r.textL);
  }

  const onInput = (ev) => {
    if (!ev || !ev.target) return;
    if (ev.target === $metaTitle || ev.target === $metaDesc || ev.target === $seoText) render();
  };

  document.addEventListener('input', onInput);
  render();
}
