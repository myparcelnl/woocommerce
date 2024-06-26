import vue from '@vitejs/plugin-vue';
import {createViteConfig} from '@myparcel-woocommerce/vite-config';

export default createViteConfig({
  plugins: [vue()],

  build: {
    lib: {
      name: 'MyParcelWooCommerceAdmin',
      fileName: 'admin',
      entry: 'src/main.ts',
      formats: ['iife'],
    },
    rollupOptions: {
      external: ['vue', 'vitest'],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
  },

  define: {
    'process.env': {},
  },

  test: {
    environment: 'happy-dom',
  },
});
