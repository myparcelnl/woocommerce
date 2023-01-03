import {defineConfig} from 'vitest/config';
import dts from 'vite-plugin-dts';

export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      fileName: 'index',
      formats: ['es', 'cjs'],
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
  },

  plugins: [dts()],
}));
