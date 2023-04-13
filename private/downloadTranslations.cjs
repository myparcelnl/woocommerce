const {createGettextFiles} = require('./createGettextFiles.cjs');
const download = require('download');

/**
 * Downloads translations from Google Sheets and run createGettextFiles with the received data.
 *
 * @see https://docs.google.com/spreadsheets/d/1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I/edit#gid=0
 */
async function downloadTranslations() {
  const documentId = '1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I';
  const sheetId = '0';

  const data = await download(
    `https://docs.google.com/spreadsheets/d/${documentId}/gviz/tq?tqx=out:csv&gid=${sheetId}`,
  );

  createGettextFiles(data);
}

module.exports = {downloadTranslations};
