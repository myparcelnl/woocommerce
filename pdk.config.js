import fs from 'fs';
import glob from 'fast-glob';
import {getPlatformDistPath, executePromises, executeCommand} from '@myparcel-pdk/app-builder';
import path from 'path';

/**
 * @type {import('@myparcel-pdk/app-builder').PdkBuilderConfig}
 */
export default {
  name: 'woocommerce',
  platforms: ['myparcelnl', 'myparcelbe'],
  source: [
    '!**/node_modules/**',
    '.cache/build/composer.json',
    '.cache/build/config/**/*',
    '.cache/build/src/**/*',
    '!cache/build/vendor/autoload.php',
    '.cache/build/vendor/**/*',
    '.cache/build/woocommerce-myparcel.php',
    'views/**/lib/**/*',
    'CONTRIBUTING.md',
    'LICENSE.txt',
    'README.md',
    'readme.txt',
    'wpm-config.json',
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
    { path: 'package.json' },
    { path: 'composer.json' },
    { path: 'woocommerce-myparcel.php', regex: /Version:\s*(.+)/ },
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
    { path: 'dist/*/composer.json' },
    { path: 'dist/*/package.json' },
    { path: 'dist/wc-myparcel-belgium/wc-myparcel-belgium.php', regex: /Version:\s*(.+)/ },
    { path: 'dist/woocommerce-myparcel/woocommerce-myparcel.php', regex: /Version:\s*(.+)/ },
  ],

  rootCommand: 'docker compose run --rm -T php',

  translations: {
    // eslint-disable-next-line no-magic-numbers
    additionalSheet: 535277615,
  },

  hooks: {
    /**
     * Prefix the vendor and source php files.
     */
    async beforeCopy(args) {
      const { debug } = args.context;

      debug('Prefixing build files...');

      if (fs.existsSync('.cache/build/composer.json')) {
        debug('Build files already exist, skipping prefixing.');
        return;
      }

      if (!args.dryRun) {
        await executeCommand(args.context,
          'php',
          [
            '-d memory_limit=-1',
            '.cache/php-scoper/vendor/bin/php-scoper',
            'add-prefix',
            '--output-dir=.cache/build',
            '--force',
            '--no-ansi',
            '--no-interaction',
          ],
          { stdio: 'inherit' },
        );
      }

      debug('Finished prefixing build files.');
    },

    async afterCopy(args) {
      const { config, env, debug } = args.context;

      debug('Copying scoped build files to root');

      await executePromises(
        args,
        config.platforms.map(async(platform) => {
          const platformDistPath = getPlatformDistPath({ config, env, platform });

          const files = glob.sync('.cache/build/**/*', { cwd: platformDistPath });

          await Promise.all(
            files.map(async(file) => {
              const oldPath = `${platformDistPath}/${file}`;
              const newPath = oldPath.replace('.cache/build/', '');

              if (!args.dryRun) {
                await fs.promises.mkdir(path.dirname(newPath), { recursive: true });
                await fs.promises.rename(oldPath, newPath);
              }
            }),
          );

          await fs.promises.rm(`${platformDistPath}/.cache`, { recursive: true });
        }));

      debug('Copied scoped build files to root.');
    },

    async afterTransform({ context }) {
      const { config, env, args } = context;

      await Promise.all(
        config.platforms.map(async(platform) => {

          const distPath = getPlatformDistPath({ config, env, platform });
          const platformDistPath = path.relative(env.cwd, distPath);

          await executeCommand(
            context,
            'composer',
            ['dump-autoload', `--working-dir=${platformDistPath}`, '--classmap-authoritative'],
            args.verbose >= 1 ? { stdio: 'inherit' } : {},
          );
        }),
      );
    },
  },
};
