// admin/dashboard/trend_draw.js - render SVG del grafico de tendencia
const NS = 'http://www.w3.org/2000/svg';

function fmtARS(n) {
  const v = Math.round(Number(n) || 0);
  return '$ ' + v.toLocaleString('es-AR');
}

function fmtInt(n) {
  const v = Math.round(Number(n) || 0);
  return v.toLocaleString('es-AR');
}

function formatValue(metric, n) {
  if (metric === 'orders') return fmtInt(n);
  return fmtARS(n);
}

function createSvgNode(tag, attrs = {}, text = '') {
  const node = document.createElementNS(NS, tag);
  Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, String(v)));
  if (text !== '') node.textContent = text;
  return node;
}

export function drawTrend(container, data, metric, tooltip) {
  if (!container) return;

  const message = container.querySelector('#trend-message');
  if (message) message.classList.add('av3-hidden');

  const svg = container.querySelector('#trend-svg');
  if (!svg) return;

  const gridGroup = svg.querySelector('#trend-grid');
  const dotsGroup = svg.querySelector('#trend-dots');
  const areaPath = svg.querySelector('#trend-area');
  const linePath = svg.querySelector('#trend-line');
  const xStartLabel = svg.querySelector('#trend-x-start');
  const xEndLabel = svg.querySelector('#trend-x-end');
  const frame = svg.querySelector('.tframe');

  if (!gridGroup || !dotsGroup || !areaPath || !linePath || !xStartLabel || !xEndLabel || !frame) {
    return;
  }

  const w = container.clientWidth ? Math.max(360, container.clientWidth) : 860;
  const h = 220;
  const pad = { l: 52, r: 16, t: 16, b: 28 };
  const innerW = w - pad.l - pad.r;
  const innerH = h - pad.t - pad.b;

  svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
  svg.setAttribute('height', String(h));

  frame.setAttribute('x', '1');
  frame.setAttribute('y', '1');
  frame.setAttribute('width', String(w - 2));
  frame.setAttribute('height', String(h - 2));

  const values = data.map((d) => Number(d.value) || 0);
  const isEmptyPeriod = values.length === 0 || values.every((v) => v === 0);
  svg.style.display = isEmptyPeriod ? 'none' : '';
  if (message) {
    message.textContent = 'No hay actividad registrada en el período seleccionado.';
    message.classList.toggle('av3-hidden', !isEmptyPeriod);
    message.classList.toggle('is-empty-period', isEmptyPeriod);
  }
  if (isEmptyPeriod) {
    if (tooltip) tooltip.classList.add('av3-hidden');
    return;
  }
  const max = Math.max(1, ...values);

  const denom = Math.max(1, data.length - 1);
  const x = (i) => pad.l + (i / denom) * innerW;
  const y = (v) => pad.t + innerH - (v / max) * innerH;

  gridGroup.replaceChildren();
  const yTicks = 4;
  for (let i = 0; i <= yTicks; i++) {
    const yy = pad.t + (i / yTicks) * innerH;
    const val = Math.round(max * (1 - i / yTicks));

    gridGroup.appendChild(createSvgNode('line', {
      x1: pad.l,
      y1: yy,
      x2: w - pad.r,
      y2: yy,
      class: 'tgrid',
    }));

    gridGroup.appendChild(createSvgNode('text', {
      x: pad.l - 10,
      y: yy + 4,
      'text-anchor': 'end',
      class: 'tlabel',
    }, formatValue(metric, val)));
  }

  let dpath = '';
  data.forEach((d, i) => {
    const xx = x(i);
    const yy = y(d.value);
    dpath += (i === 0 ? 'M' : 'L') + xx + ',' + yy + ' ';
  });

  if (data.length > 0) {
    const areaPathValue = `${dpath} L ${x(data.length - 1)} ${pad.t + innerH} L ${x(0)} ${pad.t + innerH} Z`;
    areaPath.setAttribute('d', areaPathValue);
    linePath.setAttribute('d', dpath);
  } else {
    areaPath.setAttribute('d', '');
    linePath.setAttribute('d', '');
  }

  dotsGroup.replaceChildren();
  data.forEach((d, i) => {
    const dot = createSvgNode('circle', {
      class: 'tdot',
      cx: x(i),
      cy: y(d.value),
      r: 4,
    });
    dot.dataset.day = d.day || '';
    dot.dataset.val = String(d.value ?? 0);
    dotsGroup.appendChild(dot);
  });

  const xStart = data.length ? (data[0].day || '') : '';
  const xEnd = data.length ? (data[data.length - 1].day || '') : '';

  xStartLabel.setAttribute('x', String(pad.l));
  xStartLabel.setAttribute('y', String(h - 8));
  xStartLabel.textContent = xStart;

  xEndLabel.setAttribute('x', String(w - pad.r));
  xEndLabel.setAttribute('y', String(h - 8));
  xEndLabel.textContent = xEnd;

  svg.onmousemove = (e) => {
    const t = e.target;
    if (!(t instanceof SVGElement)) return;

    if (t.classList.contains('tdot')) {
      const val = Number(t.dataset.val || 0);
      const day = String(t.dataset.day || '');
      tooltip.innerHTML = `<div><strong>${day}</strong></div><div>${formatValue(metric, val)}</div>`;
      tooltip.style.left = `${e.pageX + 12}px`;
      tooltip.style.top = `${e.pageY + 12}px`;
      tooltip.classList.remove('av3-hidden');
    } else {
      tooltip.classList.add('av3-hidden');
    }
  };

  svg.onmouseleave = () => tooltip.classList.add('av3-hidden');
}
