var preCommit = [
  'npm i @myparcel/checkout@latest',
  'cp node_modules/@myparcel/checkout/dist/myparcel.js assets/js',
  'git add package-lock.json',
  'git add assets/js/myparcel.js',
  'git commit -m "Updated checkout"',
  'git push --no-verify',
].join(' && ');

module.exports = {
  hooks: {
    'pre-push': preCommit
  },
};
