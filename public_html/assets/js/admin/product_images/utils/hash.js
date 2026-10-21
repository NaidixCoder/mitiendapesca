// admin/product_images/utils/hash.js
export async function computeHash(file) {
  const buf = await file.arrayBuffer();
  const dig = await crypto.subtle.digest('SHA-256', buf);
  const bytes = new Uint8Array(dig);
  return Array.from(bytes).map(b => b.toString(16).padStart(2, '0')).join('');
}
