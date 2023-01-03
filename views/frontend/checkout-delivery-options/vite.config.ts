import {defineConfig} from 'vitest/config';

export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      fileName: 'index',
      formats: ['iife'],
      name: 'MyParcelWooCommerceDeliveryOptions',
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
    sourcemap: env.mode !== 'development',

    rollupOptions: {
      external: ['@myparcel/delivery-options'],
      output: {
        globals: {
          '@myparcel/delivery-options': 'MyParcelDeliveryOptions',
        },
      },
    },
  },
}));
