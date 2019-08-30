var preCommit = [
  'npm i @myparcel/checkout@latest',
  'cp node_modules/@myparcel/checkout/dist/myparcel.js assets/js',
  'git add assets/js/myparcel.js',
  'git add package-lock.json',
].join(' && ');

module.exports = {
  hooks: {
    'pre-commit': preCommit
  },
};
