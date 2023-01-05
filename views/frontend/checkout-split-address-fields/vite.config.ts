import customTsConfig from 'vite-plugin-custom-tsconfig';
import {defineConfig} from 'vitest/config';

export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      fileName: 'index',
      formats: ['iife'],
      name: 'MyParcelWooCommerceSplitAddressFields',
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
    sourcemap: true,
  },

  plugins: [customTsConfig()],
}));
