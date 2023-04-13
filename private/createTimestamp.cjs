/**
 * @param {Date} date
 *
 * @returns {string}
 */
function createTimestamp(date = new Date()) {
  const day = `0${date.getDate()}`.slice(-2);
  const month = `0${date.getMonth() + 1}`.slice(-2);
  const year = date.getFullYear();

  return `${year}-${month}-${day}`;
}

module.exports = {createTimestamp};
