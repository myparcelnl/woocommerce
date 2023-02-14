const customTsConfig = require('vite-plugin-custom-tsconfig');
const {mergeConfig} = require('vite');

/** @param env {import('vite').Env} */
const createDefaultConfig = (env) => {
  const isDev = env.mode === 'development';

  return {
    plugins: [customTsConfig()],
    build: {
      minify: !isDev,
      outDir: 'lib',
      sourcemap: isDev,
    },
  };
};

/** @type createViteConfig {import('@myparcel-woocommerce/vite-config').createViteConfig} */
const createViteConfig = (config) => async (env) => {
  let resolvedConfig = config ?? {};

  if (typeof config === 'function') {
    resolvedConfig = await config(env);
  }

  return mergeConfig(createDefaultConfig(env), resolvedConfig);
};

module.exports = {createViteConfig};
