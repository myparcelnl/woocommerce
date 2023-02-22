import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceSplitAddressFields',
      fileName: 'split-address-fields',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
