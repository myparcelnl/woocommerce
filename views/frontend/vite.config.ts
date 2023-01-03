import {defineConfig} from 'vitest/config';

/**
 * @see https://vitejs.dev/config/
 */
export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      formats: ['iife'],
      name: 'MyParcelFrontend',
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
  },

  define: {
    'process.env': {},
  },
}));
