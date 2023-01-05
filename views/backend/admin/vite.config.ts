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
    sourcemap: true,
  },

  define: {
    'process.env': {},
  },

  plugins: [vue(), customTsConfig()],
}));
