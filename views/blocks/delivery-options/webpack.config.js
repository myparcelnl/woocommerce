const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

/**
 * @type {import('webpack').Configuration}
 */
const config = {
  ...defaultConfig,

  devtool: 'inline-source-map',

  output: {
    ...defaultConfig.output,
    path: path.resolve(process.cwd(), 'dist'),
  },

  entry: {
    'myparcelnl-delivery-options-block': path.resolve(process.cwd(), 'src', 'index.tsx'),
    'myparcelnl-delivery-options-block-frontend': path.resolve(process.cwd(), 'src', 'frontend.tsx'),
  },

  plugins: [
    ...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};

module.exports = config;
