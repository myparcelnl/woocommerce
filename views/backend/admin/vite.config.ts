import {createViteConfig} from '@myparcel-woocommerce/vite-config';
import vue from '@vitejs/plugin-vue';

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
      external: ['vue', 'vue-demi'],
      output: {
        globals: {
          vue: 'Vue',
          'vue-demi': 'VueDemi',
        },
      },
    },
  },

  define: {
    'process.env': {},
  },
});
