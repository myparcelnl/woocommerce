const babelify = require('babelify');
const browserify = require('browserify');
const buffer = require('vinyl-buffer');
const clean = require('gulp-clean');
const gulp = require('gulp');
const postcss = require('gulp-postcss');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const tap = require('gulp-tap');
const uglify = require('gulp-uglify');
const zip = require('gulp-zip');

/**
 * Empty the dist folder.
 */
gulp.task('clean', () => gulp.src('dist/**/*.*', {read: false})
  .pipe(clean({force: true})));

/**
 * Run babel on the javascript files.
 */
gulp.task('build:js', () => gulp.src('src/js/**/*.js', {read: false})
  .pipe(tap((file) => {
    file.contents = browserify(file.path)
      .transform(babelify)
      .bundle();
  }))
  .pipe(buffer())
  .pipe(sourcemaps.init())
  .pipe(uglify())
  .pipe(sourcemaps.write('./source-maps'))
  .pipe(gulp.dest('assets/js')));

/**
 * Copy the delivery options js.
 */
gulp.task('copy:delivery-options', () => gulp.src('node_modules/@myparcel/delivery-options/dist/myparcel.js')
  .pipe(gulp.dest('assets/js')));

/**
 * Compile and run postcss.
 */
gulp.task('build:scss', () => gulp.src('src/scss/**/*.scss')
  .pipe(sass())
  .pipe(postcss())
  .pipe(gulp.dest('assets/css')));

/**
 * Copy the static css files and images.
 */
gulp.task('copy', () => gulp.src([
  'src/css/**/*.*',
  'src/img/**/*.*',
], {
  base: 'src',
})
  .pipe(gulp.dest('assets')));

/**
 * Collect all files and put it in a zip file.
 */
gulp.task('zip', () => gulp.src([
  '*.png',
  'LICENSE',
  'assets/**/*',
  'includes/**/*',
  'languages/**/*',
  'migration/**/*',
  'readme.txt',
  'templates/**/*',
  'woocommerce-myparcel.php',
], {base: '.'})
  .pipe(zip('woocommerce-myparcel.zip'))
  .pipe(gulp.dest('./')));

/**
 * The default task.
 */
const build = gulp.series(
  'clean',
  gulp.parallel(
    'build:js',
    'build:scss',
    'copy',
    'copy:delivery-options',
  ),
);

gulp.task('build', build);
gulp.task('build:zip', gulp.series('build', 'zip'));

gulp.task('watch', () => {
  gulp.watch(['src/css/**/*', 'src/images/**/*'], null, gulp.series('copy'));
  // Skip babel in watch mode
  gulp.watch(['src/js/**/*'], null, () => gulp.src('src/js/**/*.js').pipe(gulp.dest('assets/js')));
  gulp.watch(['node_modules/@myparcel/delivery-options/**/*'], null, gulp.series('copy:delivery-options'));
  gulp.watch(['src/scss/**/*'], null, gulp.series('build:scss'));
});

exports.default = build;
