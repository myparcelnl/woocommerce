import customTsConfig from 'vite-plugin-custom-tsconfig';
import {defineConfig} from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig((env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [vue(), customTsConfig()],

    build: {
      lib: {
        name: 'MyParcelWooCommerceAdmin',
        fileName: 'admin',
        entry: 'src/main.ts',
        formats: ['iife'],
      },
      minify: !isDev,
      outDir: 'lib',
      rollupOptions: {
        external: ['vue', 'vue-demi'],
        output: {
          globals: {
            vue: 'Vue',
            'vue-demi': 'VueDemi',
          },
        },
      },
      sourcemap: isDev,
    },

    define: {
      'process.env': {},
    },
  };
});
