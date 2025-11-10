/* eslint-disable no-template-curly-in-string */
const path = require('path');
const {spawnSync} = require('child_process');
const {
  addCommitAnalyzerPlugin,
  addGitHubActionsOutputPlugin,
  addReleaseNotesGeneratorPlugin,
  addChangelogPlugin,
} = require('@myparcel/semantic-release-config/src/plugins/index.js');
const {gitPluginDefaults} = require('@myparcel/semantic-release-config/src/plugins/addGitPlugin');
const {addExecPlugin, addGitHubPlugin, addGitPlugin} = require('@myparcel/semantic-release-config/src/plugins');
const mainConfig = require('@myparcel/semantic-release-config');

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
    // TODO: Uncomment when we're releasing to the WordPress svn repository.
    // '@myparcel/semantic-release-wordpress-readme-generator',
    addExecPlugin({
      prepareCmd: `yarn pdk-builder release --root-command "${process.env.PDK_ROOT_COMMAND}" --version $\{nextRelease.version} -v && zip -r ./dist/myparcel-woocommerce-$\{nextRelease.version}.zip -C dist .`,
    }),
    addGitHubPlugin({
      assets: [
        {
          path: './dist/myparcel-*.zip',
          label: 'Download MyParcel WooCommerce v${nextRelease.version}',
        },
      ],
    }),
    addGitPlugin({
      ...gitPluginDefaults,
      assets: [...gitPluginDefaults.assets, 'woocommerce-myparcel.php', 'readme.txt'],
    }),
  ],
};
