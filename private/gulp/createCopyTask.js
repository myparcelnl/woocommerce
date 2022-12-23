function createCopyTask(gulp) {
  return () =>
    gulp
      .src(['src/css/**/*.*', 'src/img/**/*.*'], {
        base: 'src',
      })
      .pipe(gulp.dest('assets'));
}

module.exports = {createCopyTask};
