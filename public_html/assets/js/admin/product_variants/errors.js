export function normalizeErrMessage(err, fallback) {
  const d = err?.data;
  if (!d) return fallback;
  if (typeof d === 'string') {
    const t = d.replace(/<[^>]*>/g, '').trim();
    return t ? t.slice(0, 220) : fallback;
  }
  if (typeof d === 'object') {
    if (d.message) return String(d.message);
    if (d.error) return String(d.error);
  }
  return fallback;
}

export function parseValuesList(raw) {
  const s = String(raw || '').replace(/\r/g, '\n');
  if (!s.trim()) return [];

  const out = [];
  s.split('\n').forEach((line) => {
    const parts = line.split(/[;,\t]/g);
    parts.forEach((p) => {
      const v = String(p || '').trim();
      if (!v) return;
      out.push(v);
    });
  });

  const uniq = [];
  const seen = new Set();
  for (const v of out) {
    const key = v.toLowerCase();
    if (seen.has(key)) continue;
    seen.add(key);
    uniq.push(v);
    if (uniq.length >= 300) break;
  }
  return uniq;
}

export function markRowError(row, err) {
  const data = err?.data;
  let fieldsMap = null;

  if (data && typeof data === 'object') {
    const fe = data.field_errors;
    const er = data.errors;

    if (fe && typeof fe === 'object' && !Array.isArray(fe)) {
      fieldsMap = fe;
    } else if (er && typeof er === 'object' && !Array.isArray(er)) {
      fieldsMap = er;
    } else if (Array.isArray(er)) {
      fieldsMap = {};
      er.forEach((it) => {
        if (!it || typeof it === 'string') return;
        const f = String(it.field || it.name || '').trim();
        const msg = String(it.message || it.error || '').trim();
        if (f && msg) fieldsMap[f] = msg;
      });
      if (Object.keys(fieldsMap).length === 0) fieldsMap = null;
    }
  }

  const mark = (inp, msg) => {
    inp.classList.add('input-error');
    inp.setAttribute('aria-invalid', 'true');
    if (msg) inp.title = msg;
  };

  if (fieldsMap && typeof fieldsMap === 'object') {
    Object.keys(fieldsMap).forEach((k) => {
      const msg = String(fieldsMap[k] ?? '').trim();
      const inp = row.querySelector(`input[data-field="${cssEscape(k)}"]`);
      if (inp) mark(inp, msg);
    });
    return;
  }

  row.querySelectorAll('input[data-field]').forEach((inp) => mark(inp, ''));
}

function cssEscape(s) {
  try { return CSS.escape(String(s)); } catch { return String(s).replace(/[^a-z0-9_-]/ig, ''); }
}
