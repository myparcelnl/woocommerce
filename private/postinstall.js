const path = require('path');
const cpx = require('cpx');

const files = [
  ['@myparcel/delivery-options/dist/myparcel.js', 'assets/js'],
];

files.forEach(([origin, destination]) => {
  destination = path.resolve(__dirname, `../${destination}`);
  origin = path.resolve(__dirname, `../node_modules/${origin}`);

  cpx.copy(origin, destination, () => {
    console.log(`Copied ${origin} to ${destination}`);
  });
});
