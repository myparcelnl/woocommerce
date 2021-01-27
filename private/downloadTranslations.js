const {importTranslations} = require('./importTranslations');
const {downloadFile} = require('./downloadFile');

/**
 * Downloads translations from Google Sheets and run importTranslations with the received data.
 *
 * @see https://docs.google.com/spreadsheets/d/1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I/edit#gid=0
 */
function downloadTranslations() {
  const documentId = '1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I';
  const sheetId = '0';

  downloadFile(
    `https://docs.google.com/spreadsheets/d/${documentId}/gviz/tq?tqx=out:csv&gid=${sheetId}`,
    importTranslations,
  );
}

module.exports = {downloadTranslations};
