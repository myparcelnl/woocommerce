const childProcess = require('child_process');

/**
 * Converts csv files to .po files.
 *
 * @param {String} input
 * @param {String} output
 */
function convertCsvToPo(input, output) {
  childProcess.execSync(`csv2po ${input} ${output}`, {stdio: 'inherit'});
}

module.exports = {convertCsvToPo};
