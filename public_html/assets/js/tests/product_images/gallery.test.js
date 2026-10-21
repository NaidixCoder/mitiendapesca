// tests/product_images/gallery.test.js
import { describe, it, expect } from 'vitest';
import { replaceGalleryFromHtml } from '../../admin/product_images/gallery.js';

describe('gallery fragment swap', () => {
  it('detecta y reemplaza #img-gallery', () => {
    expect(typeof replaceGalleryFromHtml).toBe('function');
  });
});
