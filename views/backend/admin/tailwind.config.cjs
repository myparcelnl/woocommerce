const path = require('path');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [path.resolve(__dirname, 'src/**/*.{ts,vue}')],
  corePlugins: {
    preflight: false,
  },
  prefix: 'mypa-',
};
