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
    'myparcelnl-woocommerce.php',
  ],

  platformFolderName(platform) {
    switch (platform) {
      case 'myparcelnl':
        return 'woocommerce-myparcel';

      case 'myparcelbe':
        return 'wc-myparcel-belgium';
    }

    return '{{name}}';
  },

  versionSource: [
    {path: 'package.json'},
    {path: 'composer.json'},
    {path: 'myparcelnl-woocommerce.php', regex: /Version:\s*(.+)/},
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
  ],
};
