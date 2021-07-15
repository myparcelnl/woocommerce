const https = require('https');

/**
 * Download a file and execute a callback passing the complete data as parameter when it's finished.
 *
 * @param {String} url
 *
 * @returns {Promise<Buffer>}
 */
function downloadFile(url) {
  return new Promise((resolve) => {
    https.get(url, (res) => {
      let data = '';
      res.setEncoding('utf-8');

      res.on('data', (stream) => {
        data += stream;
      });

      res.on('end', () => {
        res.destroy();
        resolve(data);
      });
    });
  });
}

module.exports = {downloadFile};
