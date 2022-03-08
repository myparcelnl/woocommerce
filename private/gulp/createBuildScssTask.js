function createBuildScssTask(gulp, plugins) {
  const sass = require('gulp-sass')(require('sass'));

  return () => gulp.src('src/scss/**/*.scss')
    .pipe(sass())
    .pipe(plugins.postcss())
    .pipe(gulp.dest('assets/css'));
}

module.exports = { createBuildScssTask };
