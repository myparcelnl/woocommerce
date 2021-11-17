const Papa = require('papaparse');
const fs = require('fs');
const gettextParser = require('gettext-parser');
const path = require('path');

const languagesDir = path.join(__dirname, '../', 'languages');

// The template object for gettext
const baseObject = {
  charset: 'utf-8',
  headers: {
    'Project-Id-Version': 'WooCommerce MyParcel',
    'MIME-Version': '1.0',
    'Content-Type': 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding': '8bit',
    'Language-Team': 'MyParcel <support@myparcel.nl>',
    'Report-Msgid-Bugs-To': 'https://github.com/myparcelnl/woocommerce/issues',
    'Plural-Forms': 'nplurals=1; plural=0;',
    'PO-Revision-Date': '',
    'Last-Translator': '',
  },
  translations: {'': {}},
};

/**
 * Splits a given csv file with multiple languages to one .po and .mo file for each language.
 *
 * @param {Buffer} data
 */
function createGettextFiles(data) {
  const translationObject = {...baseObject};

  const json = Papa.parse(data.toString());
  const [keys, ...strings] = json.data;
  const [, ...languageKeys] = keys;

  languageKeys.forEach((language, index) => {
    const poFilePath = path.join(languagesDir, `woocommerce-myparcel-${language}.po`);
    const moFilePath = path.join(languagesDir, `woocommerce-myparcel-${language}.mo`);

    translationObject.headers.Language = language;
    strings.forEach((translation) => {
      const [key] = translation;

      translationObject.translations[''][key] = {
        msgid: key,
        msgstr: [
          translation[index + 1],
        ],
      };
    });

    fs.writeFileSync(poFilePath, `${gettextParser.po.compile(translationObject)}\n`);
    fs.writeFileSync(moFilePath, gettextParser.mo.compile(translationObject));
  });
}

module.exports = {createGettextFiles};
