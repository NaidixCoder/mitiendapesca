// tests/product_images/api.test.js
// Skeleton de test (ejecutar con Vitest o Jest + JSDOM)
import { describe, it, expect } from 'vitest';
import { requestUpload } from '../../admin/product_images/api.js';

describe('api parser', () => {
  it('normaliza JSON', async () => {
    // ejemplo ilustrativo; en un proyecto real se mockea fetch
    expect(typeof requestUpload).toBe('function');
  });
});
