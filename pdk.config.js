import { spawnSync } from 'node:child_process';
import {
  defineConfig,
} from '@myparcel-pdk/app-builder';

const ENTRY_FILE = 'woocommerce-myparcel.php';

export default defineConfig({
  name: 'woocommerce-myparcel',
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

  versionSource: [
    { path: 'package.json' },
    { path: 'composer.json' },
    { path: ENTRY_FILE, regex: /Version:\s*(.+)/ },
    // TODO: Uncomment when this version is stable.
    // {path: 'readme.txt', regex: /Stable tag:\s*(.+)/},
  ],

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
  },
});
