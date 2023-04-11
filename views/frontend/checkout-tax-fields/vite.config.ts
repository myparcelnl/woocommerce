import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceTaxFields',
      fileName: 'tax-fields',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
