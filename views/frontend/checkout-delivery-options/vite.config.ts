import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  build: {
    lib: {
      name: 'MyParcelWooCommerceDeliveryOptions',
      fileName: 'delivery-options',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
    rollupOptions: {
      external: ['@myparcel/delivery-options'],
      output: {
        globals: {
          '@myparcel/delivery-options': 'MyParcelDeliveryOptions',
        },
      },
    },
  },
});
