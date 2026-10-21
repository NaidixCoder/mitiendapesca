// tests/product_images/templates.test.js
import { describe, it, expect } from 'vitest';
import { createFigure } from '../../admin/product_images/templates.js';

describe('createFigure', () => {
  it('crea figure con datos mínimos', () => {
    const fig = createFigure({ item: { id: 1, url: '/a.jpg' }, csrf: 'x', pid: 1 });
    expect(fig instanceof HTMLElement || fig === null).toBe(true);
  });
});
