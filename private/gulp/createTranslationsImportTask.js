function createTranslationsImportTask() {
  const {downloadTranslations} = require('../downloadTranslations');

  return async (done) => {
    await downloadTranslations();
    return done;
  };
}

module.exports = {createTranslationsImportTask};
