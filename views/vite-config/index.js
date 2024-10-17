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
      minify: !isDev,
      sourcemap: isDev ? 'inline' : false,
    },

    test: {
      reporters: ['default', ['junit', {outputFile: './junit.xml'}]],
      passWithNoTests: true,
      setupFiles: [`${dirname}/test-setup.ts`],
      coverage: {
        all: true,
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
