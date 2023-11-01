/* eslint-disable no-template-curly-in-string */
const mainConfig = require('@myparcel/semantic-release-config');
const {addExecPlugin, addGitHubPlugin, addGitPlugin} = require('@myparcel/semantic-release-config/src/plugins');
const {gitPluginDefaults} = require('@myparcel/semantic-release-config/src/plugins/addGitPlugin');
const {
  addCommitAnalyzerPlugin,
  addGitHubActionsOutputPlugin,
  addReleaseNotesGeneratorPlugin,
  addChangelogPlugin,
} = require('@myparcel/semantic-release-config/src/plugins/index.js');
const {spawnSync} = require('child_process');
const path = require('path');

const branch = spawnSync('git', ['rev-parse', '--abbrev-ref', 'HEAD']).stdout.toString().trim();

module.exports = {
  ...mainConfig,
  extends: '@myparcel/semantic-release-config',
  branches: [
    {name: 'main'},
    {name: 'develop', prerelease: 'rc', channel: 'rc'},
    {name: 'alpha', prerelease: 'alpha', channel: 'alpha'},
    {name: 'beta', prerelease: 'beta', channel: 'beta'},
  ],
  plugins: [
    addCommitAnalyzerPlugin(),
    addGitHubActionsOutputPlugin(),
    addReleaseNotesGeneratorPlugin({header: path.resolve(__dirname, `private/semantic-release/header-${branch}.md`)}),
    addChangelogPlugin(),
    // TODO: Uncomment when this version is stable.
    // '@myparcel/semantic-release-wordpress-readme-generator',
    addExecPlugin({
      prepareCmd: `yarn pdk-builder release --root-command "${process.env.PDK_ROOT_COMMAND}" --version $\{nextRelease.version} -v`,
    }),
    addGitHubPlugin({
      assets: [
        {
          path: './dist/myparcelnl-*.zip',
          label: 'Download MyParcelNL WooCommerce v${nextRelease.version} (for myparcel.nl customers)',
        },
        {
          path: './dist/myparcelbe-*.zip',
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
