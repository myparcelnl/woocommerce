const Papa = require('papaparse');
const fs = require('fs');
const path = require('path');
const {convertCsvToPo} = require('./convertCsvToPo');

const languagesDir = path.join(__dirname, '../', 'languages');
const tempDir = path.join(__dirname, 'temp');

/**
 * Splits a given csv file with multiple languages to one csv file for each language. Puts this data in t.
 *
 * @param {Buffer} data
 */
function importTranslations(data) {
  if (!fs.existsSync(tempDir)) {
    fs.mkdirSync(tempDir);
  }

  const json = Papa.parse(data.toString());
  const [keys, ...strings] = json.data;
  const [, ...languageKeys] = keys;

  languageKeys.forEach((language, index) => {
    const outputFile = path.join(tempDir, `woocommerce-myparcel-${language}.csv`);
    const newTranslations = [
      ['location', 'source', 'target'],
    ];

    strings.forEach((translation) => {
      const [source] = translation;
      newTranslations.push(['', source, translation[index]]);
    });

    const string = Papa.unparse(newTranslations);

    // eslint-disable-next-line no-console
    console.log('Creating', outputFile);
    fs.writeFileSync(outputFile, string);
  });

  convertCsvToPo(tempDir, languagesDir);
  fs.rmSync(tempDir, {recursive: true});
}

module.exports = {importTranslations};
