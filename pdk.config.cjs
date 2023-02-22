const {version} = require('./package.json');

/**
 * @type {import('@myparcel-pdk/app-builder/src').PdkBuilderConfig}
 */
module.exports = {
  name: 'woocommerce',
  version: version,
  source: [
    '!**/node_modules/**',
    'vendor/**/*',
    'views/**/lib/**/*',
    'config/**/*',
    'src/**/*',
    'CONTRIBUTING.md',
    'LICENSE.txt',
    'README.md',
    'composer.json',
    'readme.txt',
    'wpm-config.json',
    'woocommerce-myparcel.php',
  ],
  platforms: ['myparcelnl', 'myparcelbe'],
};
