export function toInt(v) {
  const n = Number.parseInt(String(v ?? ''), 10);
  return Number.isFinite(n) ? n : 0;
}
