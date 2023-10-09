import fs from 'fs';
import glob from 'fast-glob';
import {getPlatformDistPath, executePromises} from '@myparcel-pdk/app-builder';
import path from 'path';

/**
 * @type {import('@myparcel-pdk/app-builder').PdkBuilderConfig}
 */
export default {
  name: 'woocommerce',
  platforms: ['myparcelnl', 'myparcelbe'],
  source: [
    '!**/node_modules/**',
    'build/vendor/**/*',
    'views/**/lib/**/*',
    'build/config/**/*',
    'build/src/**/*',
    'CONTRIBUTING.md',
    'LICENSE.txt',
    'README.md',
    'build/composer.json',
    'readme.txt',
    'wpm-config.json',
    'build/woocommerce-myparcel.php',
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

  hooks: {
    async afterCopy(args) {
      const {config, env, debug} = args.context;

      debug('Copying scoped build files to root');

      await executePromises(
        args,
        config.platforms.map(async(platform) => {
          const platformDistPath = getPlatformDistPath({config, env, platform});

          const files = glob.sync('build/**/*', {cwd: platformDistPath});

          await Promise.all(
            files.map(async(file) => {
              const oldPath = `${platformDistPath}/${file}`;
              const newPath = oldPath.replace('build/', '');

              if (!args.dryRun) {
                await fs.promises.mkdir(path.dirname(newPath), {recursive: true});
                await fs.promises.rename(oldPath, newPath);
              }
            }),
          );

          await fs.promises.rm(`${platformDistPath}/build`, {recursive: true});
        }));

      debug('Copied scoped build files to root.');
    },
  },
};
