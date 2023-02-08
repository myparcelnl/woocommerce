import customTsConfig from 'vite-plugin-custom-tsconfig';
import {defineConfig} from 'vitest/config';

export default defineConfig((env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [customTsConfig()],

    build: {
      lib: {
        name: 'MyParcelWooCommerceSplitAddressFields',
        fileName: 'split-address-fields',
        entry: 'src/main.ts',
        formats: ['iife'],
      },
      minify: !isDev,
      outDir: 'lib',
      sourcemap: isDev,
    },
  };
});
