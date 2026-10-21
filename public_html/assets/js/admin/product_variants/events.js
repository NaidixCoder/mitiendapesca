export function bind(el, eventName, handler, options) {
  if (!el) return;
  el.addEventListener(eventName, handler, options);
}
