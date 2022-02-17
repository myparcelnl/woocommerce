function createCleanTask(gulp, plugins) {
  return () => gulp.src('dist/**/*.*', { read: false })
    .pipe(plugins.clean({ force: true }));
}

module.exports = { createCleanTask };
