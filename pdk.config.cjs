/**
 * @type {import('@myparcel-pdk/app-builder').PdkBuilderConfig}
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
    {path: 'woocommerce-myparcel.php', regex: /Version:\s*(.+)/},
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
  ],

  composerCommand: 'docker compose run --rm -T php composer',

  translations: {
    // eslint-disable-next-line no-magic-numbers
    additionalSheet: 535277615,
  },
};
