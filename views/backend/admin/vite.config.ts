import customTsConfig from 'vite-plugin-custom-tsconfig';
import {defineConfig} from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      fileName: 'index',
      formats: ['iife'],
      name: 'MyParcelWooCommerceAdmin',
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
    sourcemap: env.mode === 'development',
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

  plugins: [vue(), customTsConfig()],
}));
