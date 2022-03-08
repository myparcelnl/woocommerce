function createBuildTask(gulp) {
  return gulp.series(
    'clean',
    gulp.parallel(
      'build:js',
      'build:scss',
      'copy',
      'translations:import',
      'update:composer',
      'copy:delivery-options',
    ),
  );
}

module.exports = { createBuildTask };
