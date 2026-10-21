export function normalizeMoney(value) {
  let s = String(value ?? '').trim();
  if (!s) return '';

  s = s.replace(/[^0-9.,-]/g, '');
  s = s.replace(/(?!^)-/g, '');
  if (!s) return '';

  const isNegative = s.startsWith('-');
  s = s.replace(/-/g, '');

  const hasComma = s.includes(',');
  const hasDot = s.includes('.');

  let intPart = '';
  let decPart = '';

  if (hasComma) {
    const lastComma = s.lastIndexOf(',');
    intPart = s.slice(0, lastComma).replace(/\./g, '').replace(/\D/g, '');
    decPart = s.slice(lastComma + 1).replace(/\D/g, '').slice(0, 2);
  } else if (hasDot) {
    const parts = s.split('.');
    if (parts.length === 2) {
      const left = parts[0].replace(/\D/g, '');
      const right = parts[1].replace(/\D/g, '');
      if (right.length >= 1 && right.length <= 2) {
        intPart = left;
        decPart = right.slice(0, 2);
      } else {
        intPart = (left + right).replace(/\D/g, '');
        decPart = '';
      }
    } else {
      const last = (parts.pop() ?? '').replace(/\D/g, '');
      const head = parts.join('').replace(/\D/g, '');
      if (last.length >= 1 && last.length <= 2) {
        intPart = head;
        decPart = last.slice(0, 2);
      } else {
        intPart = (head + last).replace(/\D/g, '');
        decPart = '';
      }
    }
  } else {
    intPart = s.replace(/\D/g, '');
    decPart = '';
  }

  if (!intPart) intPart = '0';
  intPart = intPart.replace(/^0+(\d)/, '$1');

  const out = (isNegative ? '-' : '') + intPart + (decPart ? '.' + decPart : '');
  if (!/^\-?\d+(?:\.\d{1,2})?$/.test(out)) return '';
  return out;
}

export function formatMoney(raw) {
  const n = Number(raw);
  if (!Number.isFinite(n)) return '';
  try {
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
      currencyDisplay: 'symbol',
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(n);
  } catch (_) {
    return '$ ' + n.toFixed(2);
  }
}

export function initMoneyInputs(root) {
  const inputs = Array.from(root.querySelectorAll('input.pv-money[data-field]'));
  for (const inp of inputs) {
    if (!(inp instanceof HTMLInputElement)) continue;
    const raw = normalizeMoney(inp.value);
    inp.dataset.raw = raw;
    if (inp.dataset.orig != null) inp.dataset.orig = normalizeMoney(inp.dataset.orig);
    if (document.activeElement !== inp) {
      inp.value = raw === '' ? '' : formatMoney(raw);
    }
  }
}

export function isMoneyField(field) {
  return field === 'price' || field === 'list_price' || field === 'cost';
}
