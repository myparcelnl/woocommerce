const packageName = '@myparcel/checkout';

const preCommit = [
  `npm i ${packageName}@latest`,
  `cp node_modules/${packageName}/dist/myparcel.js assets/js`,
  'git add package-lock.json',
  'git add assets/js/myparcel.js',
  'git commit -m "Updated delivery options"',
  'git push --no-verify',
].join(' && ');

module.exports = {
  hooks: {
    'pre-commit': preCommit
  },
};
