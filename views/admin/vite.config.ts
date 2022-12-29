import {defineConfig} from 'vitest/config';
import vue from '@vitejs/plugin-vue';

/**
 * @see https://vitejs.dev/config/
 */
export default defineConfig((env) => ({
  build: {
    lib: {
      entry: 'src/index.ts',
      formats: ['iife'],
      name: 'MyParcelAdmin',
    },
    minify: env.mode !== 'development',
    outDir: 'lib',
    rollupOptions: {
      external: ['vue', 'vite', 'vitest'],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
    // sourcemap: env.mode === 'development',
  },

  define: {
    'process.env': {},
  },

  plugins: [vue({
    template: {
      compilerOptions: {
        isCustomElement: (tag) => ['WcMultiCheckBox', 'NotificationContainer'].includes(tag),
      }
    }
  })],
}));
