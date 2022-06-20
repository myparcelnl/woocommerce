const mainConfig = require('@myparcel/semantic-release-config');
const { addExecPlugin, addGitHubPlugin, addGitPlugin } = require('@myparcel/semantic-release-config/src/plugins');
const { gitPluginDefaults } = require('@myparcel/semantic-release-config/src/plugins/addGitPlugin');

module.exports = {
  ...mainConfig,
  extends: '@myparcel/semantic-release-config',
  plugins: [
    ...mainConfig.plugins,
    addGitHubPlugin({
      assets: [
        { path: './woocommerce-myparcel.zip', label: 'Download Woocommerce MyParcel plugin v${nextRelease.version}' },
      ],
    }),
    addExecPlugin({
      prepareCmd: 'node ./private/updateVersion.js ${nextRelease.version}',
    }),
    addGitPlugin({
      ...gitPluginDefaults,
      assets: [
        ...gitPluginDefaults.assets,
        'woocommerce-myparcel.php',
        'readme.txt',
      ],
    }),
  ],
};