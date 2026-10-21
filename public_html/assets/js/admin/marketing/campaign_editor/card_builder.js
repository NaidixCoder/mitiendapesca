export function escHtml(s) {
  return String(s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function normText(s, fallback) {
  const t = String(s || '').trim();
  return t ? t : String(fallback || '').trim();
}

export function parseMoney(v) {
  const s = String(v || '').replace(/[^0-9]/g, '');
  const n = parseInt(s, 10);
  return Number.isFinite(n) ? n : 0;
}

export function fmtMoney(n) {
  try {
    return new Intl.NumberFormat('es-AR').format(n || 0);
  } catch {
    return String(n || 0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
}

export function computeDiscountPct(listPrice, price) {
  const lp = parseMoney(listPrice);
  const p = parseMoney(price);
  if (!lp || !p || lp <= p) return 0;
  return Math.round((1 - (p / lp)) * 100);
}

export function buildCardHtml(data, options = {}) {
  const size = (data && data.size) ? String(data.size) : String(options.defaultSize || 'normal');
  const safeUrl = typeof options.sanitizeUrl === 'function' ? options.sanitizeUrl : ((u) => String(u || '').trim());

  const sizes = {
    compact: { max: 520, pad: 12, radius: 14 },
    normal: { max: 560, pad: 14, radius: 16 },
    large: { max: 600, pad: 16, radius: 18 },
  };
  const cfg = sizes[size] || sizes.normal;

  const img = safeUrl(data.img) || 'https://via.placeholder.com/1200x720';
  const title = escHtml(normText(data.title, 'Nombre del producto'));
  const desc = escHtml(normText(data.desc, 'Descripcion corta del beneficio.'));

  const priceNum = parseMoney(data.price);
  const listNum = parseMoney(data.listPrice);
  const priceLabel = priceNum ? ('$ ' + fmtMoney(priceNum)) : escHtml(normText(data.price, '$ 10.000'));
  const listLabel = listNum ? ('$ ' + fmtMoney(listNum)) : '';
  const pct = computeDiscountPct(data.listPrice, data.price);

  const ctaText = escHtml(normText(data.ctaText, 'Ver'));
  const ctaUrl = safeUrl(data.ctaUrl) || 'https://tusitio.com/producto';

  const imgEsc = img.replace(/"/g, '&quot;');
  const urlEsc = ctaUrl.replace(/"/g, '&quot;');

  const priceRow = (listLabel && pct)
    ? (
      '<div style="margin:10px 0 0 0;">' +
        '<span data-email-price="current" style="font-size:14px;font-weight:700;color:#0f172a;">' + escHtml(priceLabel) + '</span>' +
        '<span data-email-price="list" style="font-size:12px;color:#64748b;text-decoration:line-through;margin-left:8px;">' + escHtml(listLabel) + '</span>' +
        '<span data-email-discount style="font-size:11px;font-weight:700;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:999px;margin-left:8px;">-' + pct + '%</span>' +
      '</div>'
    )
    : ('<div style="margin:10px 0 0 0;"><span data-email-price="current" style="font-size:14px;font-weight:700;color:#0f172a;">' + escHtml(priceLabel) + '</span></div>');

  return (
    '<table role="presentation" data-email-block="card" data-email-card-size="' + escHtml(size) + '" width="' + cfg.max + '" style="width:100%;max-width:' + cfg.max + 'px;margin:12px auto;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:' + cfg.radius + 'px;overflow:hidden;background:#ffffff;">' +
      '<tr><td style="padding:0;">' +
        '<img src="' + imgEsc + '" alt="' + title + '" width="' + cfg.max + '" style="width:100%;max-width:' + cfg.max + 'px;height:auto;display:block;" />' +
      '</td></tr>' +
      '<tr><td style="padding:' + cfg.pad + 'px;">' +
        '<h3 style="margin:0 0 8px 0;font-size:16px;line-height:1.25;color:#0f172a;">' + title + '</h3>' +
        '<p style="margin:0;font-size:13px;line-height:1.5;color:#475569;">' + desc + '</p>' +
        priceRow +
        '<div style="margin:12px 0 0 0;">' +
          '<a href="' + urlEsc + '" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:12px;font-size:13px;font-weight:700;">' + ctaText + '</a>' +
        '</div>' +
      '</td></tr>' +
    '</table>'
  );
}

export function buildGrid2Html(a, b, sizeOverride, options = {}) {
  const safeUrl = typeof options.sanitizeUrl === 'function' ? options.sanitizeUrl : ((u) => String(u || '').trim());
  const size = String(sizeOverride || a.size || b.size || options.defaultSize || 'normal');

  const sizes = {
    compact: { imgW: 220, pad: 12, radius: 14 },
    normal: { imgW: 260, pad: 14, radius: 16 },
    large: { imgW: 300, pad: 16, radius: 18 },
  };
  const cfg = sizes[size] || sizes.normal;

  function priceRow(d) {
    const priceNum = parseMoney(d.price);
    const listNum = parseMoney(d.listPrice);
    const priceLabel = priceNum ? ('$ ' + fmtMoney(priceNum)) : escHtml(normText(d.price, '$ 10.000'));
    const listLabel = listNum ? ('$ ' + fmtMoney(listNum)) : '';
    const pct = computeDiscountPct(d.listPrice, d.price);

    if (listLabel && pct) {
      return (
        '<div style="margin:10px 0 0 0;">' +
          '<span data-email-price="current" style="font-size:14px;font-weight:700;color:#0f172a;">' + escHtml(priceLabel) + '</span>' +
          '<span data-email-price="list" style="font-size:12px;color:#64748b;text-decoration:line-through;margin-left:8px;">' + escHtml(listLabel) + '</span>' +
          '<span data-email-discount style="font-size:11px;font-weight:700;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:999px;margin-left:8px;">-' + pct + '%</span>' +
        '</div>'
      );
    }
    return '<div style="margin:10px 0 0 0;"><span data-email-price="current" style="font-size:14px;font-weight:700;color:#0f172a;">' + escHtml(priceLabel) + '</span></div>';
  }

  function cardInner(d) {
    const img = safeUrl(d.img) || 'https://via.placeholder.com/520x520';
    const title = escHtml(normText(d.title, 'Producto'));
    const desc = escHtml(normText(d.desc, 'Descripcion corta.'));
    const ctaText = escHtml(normText(d.ctaText, 'Ver'));
    const ctaUrl = safeUrl(d.ctaUrl) || 'https://tusitio.com/producto';

    const imgEsc = img.replace(/"/g, '&quot;');
    const urlEsc = ctaUrl.replace(/"/g, '&quot;');

    return (
      '<table role="presentation" data-email-card="item" style="width:100%;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:' + cfg.radius + 'px;overflow:hidden;background:#ffffff;">' +
        '<tr><td style="padding:0;"><img src="' + imgEsc + '" alt="' + title + '" width="' + cfg.imgW + '" style="width:100%;max-width:' + cfg.imgW + 'px;height:auto;display:block;" /></td></tr>' +
        '<tr><td style="padding:' + cfg.pad + 'px;">' +
          '<p style="margin:0 0 6px 0;font-size:14px;font-weight:700;color:#0f172a;">' + title + '</p>' +
          '<p style="margin:0;font-size:12px;line-height:1.5;color:#475569;">' + desc + '</p>' +
          priceRow(d) +
          '<div style="margin:12px 0 0 0;">' +
            '<a href="' + urlEsc + '" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:12px;font-size:13px;font-weight:700;">' + ctaText + '</a>' +
          '</div>' +
        '</td></tr>' +
      '</table>'
    );
  }

  return (
    '<table role="presentation" data-email-block="grid2" data-email-card-size="' + escHtml(size) + '" style="width:100%;border-collapse:collapse;margin:12px 0;">' +
      '<tr>' +
        '<td style="width:50%;padding-right:8px;vertical-align:top;">' + cardInner(a) + '</td>' +
        '<td style="width:50%;padding-left:8px;vertical-align:top;">' + cardInner(b) + '</td>' +
      '</tr>' +
    '</table>'
  );
}
