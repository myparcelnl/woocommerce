import customTsConfig from 'vite-plugin-custom-tsconfig';
import {defineConfig} from 'vitest/config';

export default defineConfig((env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [customTsConfig()],

    build: {
      lib: {
        name: 'MyParcelWooCommerceDeliveryOptions',
        fileName: 'delivery-options',
        entry: 'src/main.ts',
        formats: ['iife'],
      },
      minify: !isDev,
      outDir: 'lib',
      rollupOptions: {
        external: ['@myparcel/delivery-options'],
        output: {
          globals: {
            '@myparcel/delivery-options': 'MyParcelDeliveryOptions',
          },
        },
      },
      sourcemap: isDev,
    },
  };
});
