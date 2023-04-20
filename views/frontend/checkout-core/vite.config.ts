import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceCheckoutCore',
      fileName: 'checkout-core',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
