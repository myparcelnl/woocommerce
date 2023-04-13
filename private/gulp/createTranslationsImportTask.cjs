function createTranslationsImportTask() {
  const {downloadTranslations} = require('../downloadTranslations.cjs');

  return async(done) => {
    await downloadTranslations();
    return done;
  };
}

module.exports = {createTranslationsImportTask};
