/** @type {import('eslint').Linter.Config} */
module.exports = {
  root: true,
  globals: {
    jQuery: false,
    JQuery: false,
  },
  overrides: [
    {
      files: ['./**/*.vue'],
      extends: ['@myparcel-eslint/eslint-config-prettier-typescript-vue', '@myparcel-eslint/eslint-config-import'],
      rules: {
        '@typescript-eslint/no-misused-promises': 'off',
        'import/first': 'off',
        'vue/no-empty-component-block': 'off',
        'vue/no-undef-components': [
          'error',
          {
            ignorePatterns: ['^Pdk(?:\\w)+$'],
          },
        ],
      },
    },
    {
      files: ['./**/*.ts', './**/*.tsx'],
      extends: ['@myparcel-eslint/eslint-config-prettier-typescript', '@myparcel-eslint/eslint-config-import'],
      rules: {
        'class-methods-use-this': 'off',
        '@typescript-eslint/explicit-function-return-type': 'off',
        '@typescript-eslint/no-misused-promises': 'off',
      },
    },
    {
      files: ['./**/index.ts'],
      plugins: ['sort-exports'],
      rules: {
        'sort-exports/sort-exports': ['warn', {sortDir: 'asc', sortExportKindFirst: 'type'}],
      },
    },
    {
      files: ['./**/*.js', './**/*.cjs', './**/*.mjs'],
      extends: [
        '@myparcel-eslint/eslint-config-esnext',
        '@myparcel-eslint/eslint-config-node',
        'plugin:@typescript-eslint/eslint-recommended',
        'plugin:@typescript-eslint/recommended',
        '@myparcel-eslint/eslint-config-import',
      ],
    },
    {
      files: ['./**/*.js', './**/*.mjs'],
      parserOptions: {
        sourceType: 'module',
      },
    },
    {
      files: ['./**/*.spec.*', './**/*.test.*', './**/__tests__/**'],
      rules: {
        '@typescript-eslint/no-magic-numbers': 'off',
        'max-len': 'off',
        'max-lines-per-function': 'off',
      },
    },

    /**
     * WooCommerce blocks
     */
    {
      files: ['./views/blocks/**/src/**/*.ts', './views/blocks/**/src/**/*.tsx'],
      extends: ['plugin:react-hooks/recommended'],
    },
  ],
};
