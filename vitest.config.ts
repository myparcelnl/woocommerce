import {defineConfig} from 'vitest/config';

export default defineConfig({
  test: {
    projects: [
      './views/backend/admin/vite.config.ts',
      './views/frontend/checkout-core/vite.config.ts',
      './views/frontend/common/vite.config.ts',
      './views/frontend/checkout-tax-fields/vite.config.ts',
      './views/frontend/checkout-separate-address-fields/vite.config.ts',
      './views/frontend/checkout-delivery-options/vite.config.ts',
      './views/frontend/checkout-address-widget/vite.config.ts',
    ],
  },
});
