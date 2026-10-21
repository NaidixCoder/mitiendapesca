# Tests de product_images (skeleton)

Instalá Vitest + JSDOM:

```bash
npm i -D vitest jsdom
```

Sugerencia de `vitest.config.js`:

```js
import { defineConfig } from 'vitest/config';
export default defineConfig({
  test: {
    environment: 'jsdom',
  },
});
```

Ejecutar:
```bash
npm test
```
