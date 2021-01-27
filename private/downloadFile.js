const https = require('https');

/**
 * Download a file and execute a callback passing the complete data as parameter when it's finished.
 *
 * @param {String} url
 * @param {Function} callback
 */
function downloadFile(url, callback) {
  https.get(url, (res) => {
    let data = '';

    res.on('data', (stream) => {
      data += stream;
    });

    res.on('end', () => callback(data));
  });
}

module.exports = {downloadFile};
