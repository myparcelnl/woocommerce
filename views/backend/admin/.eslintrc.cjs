/** @type {import('eslint').Linter.Config} */
module.exports = {
  extends: '@myparcel-eslint/eslint-config-tailwindcss',
  settings: {
    tailwindcss: {
      config: 'tailwind.config.cjs',
    },
  },
};
