/* eslint-disable no-template-curly-in-string */
const mainConfig = require('@myparcel/semantic-release-config');
const {addExecPlugin, addGitHubPlugin, addGitPlugin} = require('@myparcel/semantic-release-config/src/plugins');
const {gitPluginDefaults} = require('@myparcel/semantic-release-config/src/plugins/addGitPlugin');

module.exports = {
  ...mainConfig,
  extends: '@myparcel/semantic-release-config',
  plugins: [
    ...mainConfig.plugins,
    '@myparcel/semantic-release-wordpress-readme-generator',
    addExecPlugin({
      prepareCmd: 'node ./private/updateVersion.js ${nextRelease.version} && yarn build',
    }),
    addGitHubPlugin({
      assets: [
        {
          path: './myparcelnl-woocommerce-*.zip',
          label: 'Download MyParcelNL WooCommerce v${nextRelease.version} (for myparcel.nl customers)',
        },
        {
          path: './myparcelbe-woocommerce-*.zip',
          label: 'Download MyParcelBE WooCommerce v${nextRelease.version} (for sendmyparcel.be customers)',
        },
      ],
    }),
    addGitPlugin({
      ...gitPluginDefaults,
      assets: [...gitPluginDefaults.assets, 'woocommerce-myparcel.php', 'readme.txt'],
    }),
  ],
};
