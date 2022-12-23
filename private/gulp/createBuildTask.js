function createBuildTask(gulp) {
  return gulp.series(
    'clean',
    gulp.parallel('build:js', 'build:scss', 'update:composer', 'copy:delivery-options'),
    'copy',
  );
}

module.exports = {createBuildTask};
