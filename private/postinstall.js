const path = require('path');
const cpx = require('cpx');

const files = [
  ['@myparcel/checkout/dist/myparcel.js', 'assets/js'],
];

files.forEach(([origin, destination]) => {
  destination = path.resolve(__dirname, `../${destination}`);
  origin = path.resolve(__dirname, `../node_modules/${origin}`);

  cpx.copy(origin, destination, () => {
    console.log(`Copied ${origin} to ${destination}`);
  });
});
