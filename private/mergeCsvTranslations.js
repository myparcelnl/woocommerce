import { createTimestamp } from './createTimestamp';

const Papa = require('papaparse');
const fs = require('fs');
const glob = require('glob');
const path = require('path');

/**
 * Reads csv files in multiple languages and merges them into one file.
 */
function mergeCsvTranslations() {
  glob(path.join(__dirname, '../', 'languages', 'csv', '*.csv'), {}, (error, files) => {
    const newTranslations = [['source']];

    files.forEach((file) => {
      const [language] = (/[a-z]{2}_[A-Z]{2}/).exec(file);
      newTranslations[0].push(language);

      if (!language) {
        throw new Error(`Invalid filename: ${path.basename(file)}`);
      }

      const buffer = fs.readFileSync(file);
      const json = Papa.parse(buffer.toString(), {
        delimiter: ',',
        header: true,
        quoteChar: '"',
        escapeChar: '\\',
      });

      json.data.forEach(({
        source,
        target,
      }, index) => {
        index++;
        newTranslations[index] = newTranslations[index] ?? [source];
        newTranslations[index] = [...newTranslations[index] ?? [], target];
      });
    });

    const newCsv = Papa.unparse(newTranslations, {
      delimiter: ',',
      header: true,
      quoteChar: '"',
      escapeChar: '\\',
    });

    const outputPath = path.join(__dirname, `exported-translations-${createTimestamp()}.csv`);

    // eslint-disable-next-line no-console
    console.log('Creating', outputPath);
    fs.writeFileSync(outputPath, newCsv);
  });
}

module.exports = {mergeCsvTranslations};
