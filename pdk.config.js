import {spawnSync} from 'node:child_process';
import {
  PdkPlatformName,
  addPlatformToContext,
  defineConfig,
  exists,
  getPlatformDistPath,
  renameFile,
  reportFileDoesNotExist,
  resolvePath,
  resolveString,
} from '@myparcel-pdk/app-builder';

const ENTRY_FILE = 'woocommerce-myparcel.php';

export default defineConfig({
  name: 'woocommerce',
  platforms: [PdkPlatformName.MyParcelNl, PdkPlatformName.MyParcelBe],
  source: [
    '!**/node_modules/**',
    'views/**/dist/**/*',
    'views/blocks/*/block.json',
    'CONTRIBUTING.md',
    'LICENSE.txt',
    'README.md',
    'readme.txt',
    'wpm-config.json',
  ],

  platformFolderName(platform) {
    switch (platform) {
      case PdkPlatformName.MyParcelNl:
        return 'woocommerce-myparcel';

      case PdkPlatformName.MyParcelBe:
        return 'wc-myparcel-belgium';
    }

    return '{{name}}';
  },

  versionSource: [
    {path: 'package.json'},
    {path: 'composer.json'},
    {path: ENTRY_FILE, regex: /Version:\s*(.+)/},
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
  ],

  rootCommand: 'docker compose run --rm -T php',

  translations: {
    // eslint-disable-next-line no-magic-numbers
    additionalSheet: 535277615,
  },

  hooks: {
    /**
     * Run the build target in all workspaces.
     */
    beforeCopy() {
      const buffer = spawnSync('yarn', ['nx', 'run-many', '--target=build', '--output-style=stream'], {
        stdio: 'inherit',
      });

      if (buffer.status !== 0) {
        throw new Error('Build failed.');
      }
    },

    /**
     * Rename the entry file.
     */
    async afterCopy({context}) {
      const {config} = context;

      await Promise.all(
        config.platforms.map(async(platform) => {
          const platformContext = addPlatformToContext(context, platform);
          const platformDistPath = getPlatformDistPath(platformContext);
          const sourcePath = resolvePath([platformDistPath, ENTRY_FILE], context);

          if (!await exists(sourcePath)) {
            reportFileDoesNotExist(sourcePath, platformContext);
            return;
          }

          const newFilename = `${resolveString(config.platformFolderName, platformContext)}.php`;

          await renameFile(sourcePath, sourcePath.replace(ENTRY_FILE, newFilename), platformContext);
        }),
      );
    },
  },
});
