/**
 * @type {import('@myparcel-pdk/app-builder/src').PdkBuilderConfig}
 */
module.exports = {
  name: 'woocommerce',
  platforms: ['myparcelnl', 'myparcelbe'],
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
  versionSource: [
    {path: 'package.json'},
    {path: 'composer.json'},
    {path: 'woocommerce-myparcel.php', regex: /Version:\s*(.+)/},
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
  ],
};
