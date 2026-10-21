import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../public_html/assets/js/admin/core/sidebar.js', import.meta.url), 'utf8');
const { initAdminSidebarV3 } = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const classes = new Set();
const listeners = [];
const attrs = {};
const button = {
  dataset: {},
  setAttribute: (name, value) => { attrs[name] = value; },
  addEventListener: (name, fn) => { if (name === 'click') listeners.push(fn); }
};
const body = {
  classList: {
    toggle: (name, active) => { active ? classes.add(name) : classes.delete(name); },
    contains: name => classes.has(name)
  }
};
const saved = new Map();
globalThis.document = { body, getElementById: id => id === 'av3-toggle' ? button : null };
globalThis.window = {
  matchMedia: () => ({ matches: true, addEventListener: () => {} }),
  localStorage: { getItem: key => saved.get(key) ?? null, setItem: (key, value) => saved.set(key, value) }
};

initAdminSidebarV3();
initAdminSidebarV3();
assert.equal(listeners.length, 1, 'un solo listener incluso si app.js llama dos veces');
assert.equal(classes.has('av3-expanded'), true);
listeners[0]();
assert.equal(classes.has('av3-collapsed'), true);
assert.equal(attrs['aria-label'], 'Expandir barra lateral');
listeners[0]();
assert.equal(classes.has('av3-expanded'), true);
assert.equal(attrs['aria-label'], 'Colapsar barra lateral');
console.log('[sidebar] OK: inicialización única y toggle en ambos sentidos');
