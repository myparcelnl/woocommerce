import customTsConfig from 'vite-plugin-custom-tsconfig';
import {mergeConfig} from 'vite';

const dirname = new URL('.', import.meta.url).pathname;

/**
 * @type createDefaultConfig {import('vitest/config').UserConfigExport}
 * @returns {import('vitest/config').UserConfig}
 */
const createDefaultConfig = (env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [customTsConfig()],
    build: {
      // Vite 6 names the library CSS after the bundle, but PHP enqueues dist/style.css.
      lib: {cssFileName: 'style'},
      // Since Vite 7 the default target is "baseline-widely-available" (Safari 16+); keep the Vite 5 targets.
      target: ['es2020', 'edge88', 'firefox78', 'chrome87', 'safari14'],
      minify: !isDev,
      sourcemap: isDev,
      rollupOptions: {
        external: ['vue', 'vitest', 'vite', /@vitest\/.*/, /@vite\/.*/, '@myparcel-dev/delivery-options', 'leaflet'],
        output: {
          globals: {
            '@myparcel-dev/delivery-options': 'MyParcelDeliveryOptions',
            vue: 'Vue',
          },
        },
      },
    },

    test: {
      reporters: ['default', ['junit', {outputFile: './junit.xml'}]],
      passWithNoTests: true,
      setupFiles: [`${dirname}/test-setup.ts`],
      coverage: {
        // Absolute, because vitest matches coverage globs anywhere in the path.
        include: [`${process.cwd()}/src/**/*.{ts,vue}`],
        enabled: false,
        reporter: ['text', 'clover'],
      },
    },
  };
};

/**
 *  @param config {import('vitest/config').UserConfigExport}
 *  @returns {import('vitest/config').UserConfigFn}
 */
export const createViteConfig = (config) => async(env) => {
  let resolvedConfig = config ?? {};

  if (typeof config === 'function') {
    resolvedConfig = await config(env);
  }

  return mergeConfig(createDefaultConfig(env), resolvedConfig);
};
