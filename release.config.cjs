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
  ],
  plugins: [
    addCommitAnalyzerPlugin(),
    addGitHubActionsOutputPlugin(),
    addReleaseNotesGeneratorPlugin({header: path.resolve(__dirname, `private/semantic-release/header-${branch}.md`)}),
    addChangelogPlugin(),
    '@myparcel/semantic-release-wordpress-readme-generator',
    addExecPlugin({
      prepareCmd: 'node ./private/updateVersion.cjs ${nextRelease.version} && yarn bundle',
    }),
    addGitHubPlugin({
      assets: [
        {path: './woocommerce-myparcel.zip', label: 'Download MyParcel plugin v${nextRelease.version}'},
      ],
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
