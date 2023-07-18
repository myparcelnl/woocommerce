const customTsConfig = require('vite-plugin-custom-tsconfig');
const {mergeConfig} = require('vite');

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
      outDir: 'lib',
      sourcemap: isDev,
    },

    test: {
      passWithNoTests: true,
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
const createViteConfig = (config) => async (env) => {
  let resolvedConfig = config ?? {};

  if (typeof config === 'function') {
    resolvedConfig = await config(env);
  }

  return mergeConfig(createDefaultConfig(env), resolvedConfig);
};

module.exports = {createViteConfig};
