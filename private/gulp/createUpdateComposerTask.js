function createUpdateComposerTask() {
  const path = require('path');
  const { spawnSync } = require('child_process');
  const volumePath = path.resolve(__dirname, '../', '../');

  return (done) => {
    spawnSync('docker', [
        'run',
        '--rm',
        '--volume', `${volumePath}:/app`,
        'composer',
        'update',
      ],
      { stdio: 'inherit' });
    done();
  };
}

module.exports = { createUpdateComposerTask };
