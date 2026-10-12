import {createRequire} from 'node:module';
import customTsConfig from 'vite-plugin-custom-tsconfig';
import {mergeConfig} from 'vite';

const dirname = new URL('.', import.meta.url).pathname;

const VUE_DEMI_EXPORT_ALL = "export * from 'vue'";

/**
 * Make vue-demi re-export each Vue export by name instead of with `export * from 'vue'`.
 *
 * @TODO: Remove when Rolldown uses output.globals for `export * from` an external module in iife output.
 *   Rolldown turns vue-demi's `export * from 'vue'` into `require("vue")`.
 *   https://github.com/rolldown/rolldown/issues/11173
 *
 * @returns {import('vite').Plugin}
 */
const vueDemi = () => ({
  name: 'myparcel-woocommerce:vue-demi',
  apply: 'build',
  transform(code, id) {
    if (!id.endsWith('/vue-demi/lib/index.mjs')) {
      return null;
    }

    if (!code.includes(VUE_DEMI_EXPORT_ALL)) {
      throw new Error(`${id} no longer contains "${VUE_DEMI_EXPORT_ALL}", check if this plugin is still needed.`);
    }

    // Packages can have their own copy of vue-demi and vue, so read the vue next to this vue-demi.
    const vueExports = Object.keys(createRequire(id)('vue')).filter((name) => name !== 'default');

    return code.replace(VUE_DEMI_EXPORT_ALL, `export {${vueExports.join(', ')}} from 'vue'`);
  },
});

/**
 * @type createDefaultConfig {import('vitest/config').UserConfigExport}
 * @returns {import('vitest/config').UserConfig}
 */
const createDefaultConfig = (env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [customTsConfig(), vueDemi()],
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

    // Library mode leaves process.env.NODE_ENV in dependencies such as pinia, and the browser has no process.
    // Vitest sets NODE_ENV itself.
    define:
      env.command === 'build' ? {'process.env.NODE_ENV': JSON.stringify(isDev ? 'development' : 'production')} : {},

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
