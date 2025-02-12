import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceAddressWidget',
      fileName: 'address-widget',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
