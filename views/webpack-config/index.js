// const path = require('path');
// const defaultConfig = require('@wordpress/scripts/config/webpack.config');
// const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

import path from 'path';
import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
import WooCommerceDependencyExtractionWebpackPlugin from '@woocommerce/dependency-extraction-webpack-plugin';

/**
 * @var {import('webpack').Configuration} defaultConfig
 */

/**
 * @param config {undefined|Partial<import('webpack').Configuration>}
 * @returns {import('webpack').Configuration}
 */
export const createWebpackConfig = (config) => {
  // noinspection UnnecessaryLocalVariableJS
  /**
   * @type {import('webpack').Configuration}
   */
  const configuration = {
    ...defaultConfig,
    ...config,

    devtool: 'inline-source-map',

    watchOptions: {
      ...defaultConfig?.watchOptions,
      ignored: ['**/dist', '**/node_modules'],
      ...config?.watchOptions,
    },

    output: {
      ...defaultConfig.output,
      path: path.resolve(process.cwd(), 'dist'),
      ...config?.output,
    },

    entry: {
      index: path.resolve(process.cwd(), 'src', 'index.tsx'),
      frontend: path.resolve(process.cwd(), 'src', 'frontend.tsx'),
      ...config?.entry,
    },

    plugins: [
      ...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'),
      new WooCommerceDependencyExtractionWebpackPlugin(),
      ...config?.plugins ?? [],
    ],
  };

  return configuration;
};

export default createWebpackConfig;
