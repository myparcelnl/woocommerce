import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceSeparateAddressFields',
      fileName: 'separate-address-fields',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
