import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceDeliveryOptions',
      fileName: 'delivery-options',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
  },
});
