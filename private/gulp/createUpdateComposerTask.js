function createUpdateComposerTask() {
  const {spawnSync} = require('child_process');

  return (done) => {
    spawnSync('composer', ['update'], {stdio: 'inherit'});
    done();
  };
}

module.exports = {createUpdateComposerTask};
