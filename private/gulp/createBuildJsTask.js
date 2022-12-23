function createBuildJsTask(gulp, plugins) {
  const babelify = require('babelify');
  const browserify = require('browserify');
  const buffer = require('vinyl-buffer');

  return () =>
    gulp
      .src('src/js/**/*.js', {read: false})
      .pipe(
        plugins.tap((file) => {
          file.contents = browserify(file.path).transform(babelify).bundle();
        }),
      )
      .pipe(buffer())
      .pipe(plugins.sourcemaps.init())
      .pipe(plugins.uglify())
      .pipe(plugins.sourcemaps.write('./source-maps'))
      .pipe(gulp.dest('assets/js'));
}

module.exports = {createBuildJsTask};
